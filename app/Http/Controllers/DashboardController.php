<?php

namespace App\Http\Controllers;

use App\Models\AccessLog;
use App\Models\AdminUser;
use App\Models\AppUser;
use App\Models\AppUserLog;
use App\Models\Division;
use App\Models\LocationSet;
use App\Models\Upazila;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /** Bangladesh local time, used only for greeting / display. */
    private const DISPLAY_TZ = 'Asia/Dhaka';

    /** Aggregates are cached briefly so a busy dashboard does not hammer the database. */
    private const CACHE_SECONDS = 60;

    /** A user counts as "online" if the app reported activity within this window. */
    private const ONLINE_WINDOW_MINUTES = 15;

    private const MONTHS_OF_HISTORY = 12;

    private const MAX_PLATFORM_ROWS = 5;

    public function index(): View
    {
        $stats = Cache::remember('dashboard:stats:v3', self::CACHE_SECONDS, fn (): array => $this->buildStats());

        return view('dashboard', $stats + [
            'greeting' => $this->greeting(),
            'recentLogs' => AccessLog::query()
                ->with('adminUser:id,username')
                ->latest('id')
                ->limit(5)
                ->get(['id', 'admin_user_id', 'ip_address', 'method', 'path', 'browser', 'platform', 'device', 'created_at']),
        ]);
    }

    private function buildStats(): array
    {
        $now = now();
        $thisMonthStart = $now->copy()->startOfMonth();
        $prevMonthStart = $thisMonthStart->copy()->subMonthNoOverflow();
        $prevSamePointInTime = $now->copy()->subMonthNoOverflow();

        // ---- App users -------------------------------------------------------------
        $statusCounts = AppUser::query()
            ->selectRaw('status, COUNT(*) AS total')
            ->groupBy('status')
            ->pluck('total', 'status')
            ->map(fn ($v): int => (int) $v);

        $totalAppUsers = $statusCounts->sum();

        $newThisMonth = AppUser::where('created_at', '>=', $thisMonthStart)->count();
        $newPrevMonth = AppUser::where('created_at', '>=', $prevMonthStart)->where('created_at', '<', $thisMonthStart)->count();
        $newPrevSamePeriod = AppUser::where('created_at', '>=', $prevMonthStart)->where('created_at', '<=', $prevSamePointInTime)->count();

        $activeThisMonth = $this->distinctActiveUsers($thisMonthStart, $now);
        $activePrevMonth = $this->distinctActiveUsers($prevMonthStart, $thisMonthStart, exclusiveEnd: true);
        $onlineNow = $this->distinctActiveUsers($now->copy()->subMinutes(self::ONLINE_WINDOW_MINUTES), $now);

        // ---- Locations -------------------------------------------------------------
        $divisions = Division::query()
            ->withCount(['districts', 'upazilas'])
            ->orderByDesc('upazilas_count')
            ->orderBy('name')
            ->get(['id', 'name', 'name_bn']);

        $locationSetCounts = LocationSet::query()
            ->selectRaw('status, COUNT(*) AS total')
            ->groupBy('status')
            ->pluck('total', 'status')
            ->map(fn ($v): int => (int) $v);

        // ---- Admin portal activity -------------------------------------------------
        $todayStart = $now->copy()->startOfDay();

        return [
            'generatedAt' => $now->copy()->timezone(self::DISPLAY_TZ)->format('d M Y, h:i A'),
            'onlineWindowMinutes' => self::ONLINE_WINDOW_MINUTES,
            'appUsers' => [
                'total' => $totalAppUsers,
                'active' => $statusCounts->get('active', 0),
                'inactive' => $statusCounts->get('inactive', 0),
                'blocked' => $statusCounts->get('blocked', 0),
                'newThisMonth' => $newThisMonth,
                'newPrevMonth' => $newPrevMonth,
                'newPrevSamePeriod' => $newPrevSamePeriod,
                'growthPercent' => $this->percentChange($newThisMonth, $newPrevSamePeriod),
                'activeThisMonth' => $activeThisMonth,
                'activePrevMonth' => $activePrevMonth,
                'activeGrowthPercent' => $this->percentChange($activeThisMonth, $activePrevMonth),
                'onlineNow' => $onlineNow,
                'thisMonthLabel' => $thisMonthStart->format('F Y'),
                'prevMonthLabel' => $prevMonthStart->format('F Y'),
            ],
            'monthlySignups' => $this->monthlySignups($now),
            'platforms' => $this->platformBreakdown(),
            'recentAppUsers' => AppUser::query()
                ->latest('id')
                ->limit(5)
                ->get(['id', 'name', 'email', 'status', 'platform', 'photo_path', 'created_at'])
                ->map(fn (AppUser $u): array => [
                    'id' => $u->id,
                    'name' => $u->name,
                    'email' => $u->email,
                    'status' => $u->status,
                    'platform' => $u->platform,
                    'photo' => $u->photo_url,
                    'joined' => $u->created_at?->diffForHumans(),
                ])->all(),
            'locations' => [
                'divisions' => $divisions->count(),
                'districts' => (int) $divisions->sum('districts_count'),
                'upazilas' => Upazila::count(),
                'sets' => $locationSetCounts->sum(),
                'setsActive' => $locationSetCounts->get('active', 0),
                'setsInactive' => $locationSetCounts->get('inactive', 0),
                'byDivision' => $divisions->map(fn (Division $d): array => [
                    'name' => $d->name,
                    'name_bn' => $d->name_bn,
                    'districts' => (int) $d->districts_count,
                    'upazilas' => (int) $d->upazilas_count,
                ])->all(),
            ],
            'admin' => [
                'activeAdmins' => AdminUser::where('status', 'active')->count(),
                'eventsToday' => AccessLog::where('created_at', '>=', $todayStart)->count(),
                'uniqueIpsToday' => AccessLog::where('created_at', '>=', $todayStart)->distinct()->count('ip_address'),
            ],
        ];
    }

    private function distinctActiveUsers(Carbon $from, Carbon $to, bool $exclusiveEnd = false): int
    {
        return AppUserLog::query()
            ->whereNotNull('app_user_id')
            ->where('created_at', '>=', $from)
            ->where('created_at', $exclusiveEnd ? '<' : '<=', $to)
            ->distinct()
            ->count('app_user_id');
    }

    /** New registrations per calendar month, oldest first, always MONTHS_OF_HISTORY entries. */
    private function monthlySignups(Carbon $now): array
    {
        $start = $now->copy()->startOfMonth()->subMonthsNoOverflow(self::MONTHS_OF_HISTORY - 1);
        $bucket = $this->dateBucket('created_at', 'month');

        $counts = AppUser::query()
            ->selectRaw("{$bucket} AS bucket, COUNT(*) AS total")
            ->where('created_at', '>=', $start)
            ->groupByRaw($bucket)
            ->pluck('total', 'bucket');

        $months = [];
        for ($i = 0; $i < self::MONTHS_OF_HISTORY; $i++) {
            $month = $start->copy()->addMonthsNoOverflow($i);
            $key = $month->format('Y-m');
            $months[] = [
                'label' => $month->format('M'),
                'full' => $month->format('F Y'),
                'value' => (int) ($counts[$key] ?? 0),
                'current' => $i === self::MONTHS_OF_HISTORY - 1,
            ];
        }

        return $months;
    }

    /** App users grouped by platform (case-insensitive), small tail folded into "Other". */
    private function platformBreakdown(): array
    {
        // Group by the raw column (strict SQL mode friendly), then merge "android" / "Android " etc. in PHP.
        $rows = AppUser::query()
            ->selectRaw('platform, COUNT(*) AS total')
            ->groupBy('platform')
            ->pluck('total', 'platform')
            ->reduce(function ($carry, $total, $platform) {
                $key = strtolower(trim((string) $platform)) ?: 'unknown';
                $carry[$key] = ($carry[$key] ?? 0) + (int) $total;

                return $carry;
            }, collect())
            ->sortDesc();

        $label = fn (string $name): string => match ($name) {
            'ios' => 'iOS',
            'android' => 'Android',
            'web' => 'Web',
            'unknown' => 'Not reported',
            default => ucfirst($name),
        };

        $result = [];
        $other = 0;
        foreach ($rows as $name => $total) {
            if (count($result) < self::MAX_PLATFORM_ROWS - 1 || $rows->count() <= self::MAX_PLATFORM_ROWS) {
                $result[] = ['name' => $label((string) $name), 'value' => (int) $total];
            } else {
                $other += (int) $total;
            }
        }

        if ($other > 0) {
            $result[] = ['name' => 'Other', 'value' => $other];
        }

        return $result;
    }

    /** Database-portable "YYYY-MM" / "YYYY-MM-DD" bucket expression for a trusted column name. */
    private function dateBucket(string $column, string $unit): string
    {
        $driver = DB::connection()->getDriverName();

        return match ($driver) {
            'sqlite' => $unit === 'month' ? "strftime('%Y-%m', {$column})" : "strftime('%Y-%m-%d', {$column})",
            'pgsql' => $unit === 'month' ? "to_char({$column}, 'YYYY-MM')" : "to_char({$column}, 'YYYY-MM-DD')",
            default => $unit === 'month' ? "DATE_FORMAT({$column}, '%Y-%m')" : "DATE_FORMAT({$column}, '%Y-%m-%d')",
        };
    }

    private function percentChange(int $current, int $previous): ?float
    {
        if ($previous === 0) {
            return $current === 0 ? 0.0 : null; // null = "new" (no baseline to compare against)
        }

        return round((($current - $previous) / $previous) * 100, 1);
    }

    private function greeting(): string
    {
        $hour = (int) now(self::DISPLAY_TZ)->format('G');

        return match (true) {
            $hour < 5 => 'Good evening',
            $hour < 12 => 'Good morning',
            $hour < 17 => 'Good afternoon',
            default => 'Good evening',
        };
    }
}
