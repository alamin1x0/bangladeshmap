<?php

namespace App\Http\Controllers;

use App\Models\AppUser;
use App\Models\AppUserLog;
use App\Models\PushNotification;
use App\Models\PushNotificationRecipient;
use App\Services\FcmService;
use App\Support\NotificationImage;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use RuntimeException;

class NotificationController extends Controller
{
    private const PLATFORMS = ['Android', 'iOS', 'Web'];

    /** Users per batch when sending to a large audience. */
    private const BATCH = 500;

    public function index(): View
    {
        return view('notifications.index', [
            'notifications' => PushNotification::with('sender:id,username')->latest('id')->paginate(15),
            'stats' => [
                'sent' => PushNotification::count(),
                'delivered' => (int) PushNotification::sum('success_count'),
                'failed' => (int) PushNotification::sum('failure_count'),
                'reachable' => $this->reachable()->count(),
            ],
            'configured' => FcmService::isConfigured(),
        ]);
    }

    public function create(Request $request): View
    {
        $preselected = collect(explode(',', (string) $request->query('user')))
            ->map(fn ($id) => (int) $id)->filter()->unique()->values();

        $reachableUsers = $this->reachable()
            ->orderBy('name')
            ->limit(1000)
            ->get(['id', 'name', 'email', 'phone', 'platform', 'photo_path']);

        $platformCounts = $this->reachable()
            ->selectRaw('platform, COUNT(*) AS total')
            ->groupBy('platform')
            ->pluck('total', 'platform')
            ->reduce(function ($carry, $total, $platform) {
                $match = collect(self::PLATFORMS)->first(fn ($p) => strcasecmp((string) $platform, $p) === 0);
                if ($match) {
                    $carry[$match] = ($carry[$match] ?? 0) + (int) $total;
                }

                return $carry;
            }, []);

        // Preselected users who cannot receive notifications (no token / not active) are reported to the admin.
        $unreachablePreselected = $preselected->isEmpty() ? collect() : AppUser::whereIn('id', $preselected)
            ->whereNotIn('id', $reachableUsers->pluck('id'))
            ->get(['id', 'name', 'status', 'fcm_token']);

        return view('notifications.create', [
            'reachableUsers' => $reachableUsers,
            'platformCounts' => $platformCounts,
            'platforms' => self::PLATFORMS,
            'preselected' => $preselected->intersect($reachableUsers->pluck('id'))->values(),
            'unreachablePreselected' => $unreachablePreselected,
            'configured' => FcmService::isConfigured(),
            'imagesPublic' => NotificationImage::publiclyReachable(),
            'imageMaxMb' => (int) (NotificationImage::MAX_KB / 1024),
            'imageTypes' => \App\Support\ProfilePhoto::acceptedExtensions(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'audience' => ['required', Rule::in(['all', 'platform', 'users', 'token'])],
            'platform' => ['required_if:audience,platform', 'nullable', Rule::in(self::PLATFORMS)],
            'user_ids' => ['required_if:audience,users', 'nullable', 'array', 'max:1000'],
            'user_ids.*' => ['integer', 'distinct'],
            'test_token' => ['required_if:audience,token', 'nullable', 'string', 'min:20', 'max:4096', 'regex:/^[A-Za-z0-9_:\-\.]+$/'],
            'title' => ['required', 'string', 'max:120'],
            'body' => ['required', 'string', 'max:1000'],
            'image_url' => ['nullable', 'url:https', 'max:500'],
            'image' => NotificationImage::validationRules(),
            'link' => ['nullable', 'string', 'max:500'],
        ], [
            'user_ids.required_if' => 'Please choose at least one user.',
            'test_token.required_if' => 'Please paste a device FCM token.',
            'test_token.regex' => 'That does not look like an FCM token.',
            'image_url.url' => 'The image must be an https:// link.',
            'image.image' => 'This file is not a valid image. Please choose a JPG or PNG.',
            'image.max' => 'The image must be smaller than '.(NotificationImage::MAX_KB / 1024).' MB.',
        ]);

        try {
            $fcm = new FcmService();
        } catch (RuntimeException $e) {
            return back()->withInput()->withErrors(['audience' => $e->getMessage()]);
        }

        $recipientsQuery = match ($data['audience']) {
            'all' => $this->reachable(),
            'platform' => $this->reachable()->whereRaw('LOWER(platform) = ?', [strtolower($data['platform'])]),
            'users' => $this->reachable()->whereIn('id', $data['user_ids'] ?? []),
            'token' => null,
        };

        $targetCount = $recipientsQuery ? (clone $recipientsQuery)->count() : 1;
        if ($targetCount === 0) {
            return back()->withInput()->withErrors(['audience' => 'Nobody in this audience can receive notifications yet (no active user with a device token).']);
        }

        @set_time_limit(300);

        // An uploaded image wins over a pasted link. Stored only now, after the audience check passed.
        $imageName = $request->hasFile('image') ? NotificationImage::store($request->file('image')) : null;
        $imageUrl = $imageName ? NotificationImage::url($imageName) : ($data['image_url'] ?? null);

        $notification = PushNotification::create([
            'title' => $data['title'],
            'body' => $data['body'],
            'image_url' => $imageName ? null : ($data['image_url'] ?? null),
            'image_path' => $imageName,
            'link' => $data['link'] ?? null,
            'audience' => $data['audience'],
            'audience_detail' => match ($data['audience']) {
                'platform' => $data['platform'],
                'token' => '…'.substr($data['test_token'], -10),
                default => null,
            },
            'target_count' => $targetCount,
            'status' => 'sending',
            'admin_user_id' => $request->user()?->id,
        ]);

        $message = [
            'title' => $data['title'],
            'body' => $data['body'],
            'image' => $imageUrl,
            'link' => $data['link'] ?? null,
            'data' => ['notification_id' => (string) $notification->id],
        ];

        $success = 0;
        $failed = 0;

        try {
            if ($recipientsQuery === null) {
                $results = $fcm->sendToTokens(['test' => $data['test_token']], $message);
                [$s, $f] = $this->record($notification, $results, ['test' => null], ['test' => $data['test_token']]);
                $success += $s;
                $failed += $f;
            } else {
                $recipientsQuery->select(['id', 'fcm_token'])->chunkById(self::BATCH, function ($users) use ($fcm, $message, $notification, &$success, &$failed) {
                    $tokens = $users->mapWithKeys(fn ($u) => [$u->id => $u->fcm_token])->all();
                    $results = $fcm->sendToTokens($tokens, $message);
                    [$s, $f] = $this->record($notification, $results, array_combine(array_keys($tokens), array_keys($tokens)), $tokens);
                    $success += $s;
                    $failed += $f;
                });
            }
        } catch (RuntimeException $e) {
            report($e);
            $notification->update(['status' => 'failed', 'success_count' => $success, 'failure_count' => max($failed, $targetCount - $success)]);

            return redirect()->route('notifications.show', $notification)->withErrors(['send' => $e->getMessage()]);
        }

        $notification->update([
            'success_count' => $success,
            'failure_count' => $failed,
            'status' => $failed === 0 ? 'sent' : ($success === 0 ? 'failed' : 'partial'),
        ]);

        $msg = $failed === 0
            ? "Notification sent to {$success} ".str('device')->plural($success).'.'
            : "Sent to {$success}, failed for {$failed}. See details below.";

        return redirect()->route('notifications.show', $notification)->with($failed === 0 ? 'success' : 'warning', $msg);
    }

    public function show(PushNotification $notification): View
    {
        return view('notifications.show', [
            'notification' => $notification->load('sender:id,username'),
            'recipients' => $notification->recipients()
                ->with('appUser:id,name,email,photo_path,platform')
                ->orderByRaw("CASE WHEN status = 'failed' THEN 0 ELSE 1 END")
                ->orderBy('id')
                ->paginate(50),
        ]);
    }

    /**
     * Public (no login) image for a notification: phones download it when the push arrives.
     * The route only matches 40 random characters + ".jpg", so nothing else on disk can be reached.
     */
    public function image(string $file): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $path = NotificationImage::DIRECTORY.'/'.$file;
        abort_unless(Storage::disk(NotificationImage::DISK)->exists($path), 404);

        return Storage::disk(NotificationImage::DISK)->response($path, null, [
            'Content-Type' => 'image/jpeg',
            'Cache-Control' => 'public, max-age=2592000, immutable',
            'X-Content-Type-Options' => 'nosniff',
            'Cross-Origin-Resource-Policy' => 'cross-origin',
            'Content-Disposition' => 'inline',
        ]);
    }

    /** Active app users who have a device token. */
    private function reachable(): Builder
    {
        return AppUser::query()
            ->where('status', 'active')
            ->whereNotNull('fcm_token')
            ->where('fcm_token', '<>', '');
    }

    /**
     * Store per-recipient results, write user activity logs and clear tokens Firebase reports as dead.
     *
     * @return array{0:int,1:int} [success, failed]
     */
    private function record(PushNotification $notification, array $results, array $userIds, array $tokens): array
    {
        $now = now();
        $rows = [];
        $logs = [];
        $dead = [];
        $success = 0;
        $failed = 0;

        foreach ($results as $key => $result) {
            $userId = $userIds[$key] ?? null;
            $result['ok'] ? $success++ : $failed++;

            $rows[] = [
                'push_notification_id' => $notification->id,
                'app_user_id' => $userId,
                'token_hint' => '…'.substr((string) ($tokens[$key] ?? ''), -10),
                'status' => $result['ok'] ? 'sent' : 'failed',
                'error' => $result['error'],
                'message_id' => $result['message_id'] ? mb_substr($result['message_id'], 0, 190) : null,
                'created_at' => $now,
                'updated_at' => $now,
            ];

            if ($userId && $result['ok']) {
                $logs[] = [
                    'app_user_id' => $userId,
                    'event' => 'notification',
                    'metadata' => json_encode(['notification_id' => $notification->id, 'title' => $notification->title]),
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            if ($userId && $result['unregistered']) {
                $dead[] = $userId;
            }
        }

        DB::transaction(function () use ($rows, $logs, $dead) {
            foreach (array_chunk($rows, 500) as $chunk) {
                PushNotificationRecipient::insert($chunk);
            }
            foreach (array_chunk($logs, 500) as $chunk) {
                AppUserLog::insert($chunk);
            }
            if ($dead) {
                AppUser::whereIn('id', $dead)->update(['fcm_token' => null]);
            }
        });

        return [$success, $failed];
    }
}
