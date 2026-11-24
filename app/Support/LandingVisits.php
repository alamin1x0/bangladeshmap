<?php

namespace App\Support;

use App\Models\LandingOrder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/** Privacy-friendly visit counter for the landing page (no raw IPs, no cookies). */
class LandingVisits
{
    /** Bangladesh time (UTC+6, no daylight saving) for "today" and daily buckets; timestamps are stored in UTC. */
    public const TZ = 'Asia/Dhaka';

    private const DAY_SQL = 'DATE(DATE_ADD(created_at, INTERVAL 6 HOUR))';

    private const BOT_PATTERN = '/bot|crawl|spider|slurp|facebookexternalhit|preview|headless|lighthouse|curl|wget|python|httpclient|java\/|go-http|monitor|pingdom/i';

    public static function record(Request $request): void
    {
        $agent = (string) $request->userAgent();
        // Skip bots, link previews, admins checking their own page, and prefetches.
        if ($agent === '' || preg_match(self::BOT_PATTERN, $agent) || auth()->check()
            || $request->header('Purpose') === 'prefetch' || $request->header('Sec-Purpose')) {
            return;
        }

        try {
            DB::table('landing_visits')->insert([
                'visitor_hash' => hash_hmac('sha256', $request->ip().'|'.$agent.'|'.now()->toDateString(), (string) config('app.key')),
                'device' => preg_match('/iPad|Tablet/i', $agent) ? 'tablet' : (preg_match('/Mobi|Android|iPhone/i', $agent) ? 'mobile' : 'desktop'),
                'source' => self::source($request),
                'created_at' => now(),
            ]);
        } catch (\Throwable $e) {
            report($e);
        }
    }

    private static function source(Request $request): string
    {
        $utm = trim((string) $request->query('utm_source', $request->query('ref', '')));
        if ($utm !== '') {
            return Str::limit(Str::lower(preg_replace('/[^\pL\pN._ -]/u', '', $utm)), 60, '');
        }
        $host = Str::lower((string) parse_url((string) $request->headers->get('referer'), PHP_URL_HOST));
        if ($host === '' || $host === $request->getHost()) {
            return 'direct';
        }
        foreach (['facebook' => ['facebook.', 'fb.', 'messenger.'], 'google' => ['google.'], 'youtube' => ['youtube.', 'youtu.be'], 'whatsapp' => ['whatsapp.'], 'instagram' => ['instagram.'], 'linkedin' => ['linkedin.', 'lnkd.in'], 'bing' => ['bing.']] as $name => $needles) {
            if (Str::contains($host, $needles)) {
                return $name;
            }
        }

        return Str::limit(preg_replace('/^www\./', '', $host), 60, '');
    }

    public static function summary(): array
    {
        $localToday = Carbon::today(self::TZ);
        $today = $localToday->copy()->utc();
        $range = fn (Carbon $from, ?Carbon $to = null) => DB::table('landing_visits')->where('created_at', '>=', $from)
            ->when($to, fn ($q) => $q->where('created_at', '<', $to))
            ->selectRaw('COUNT(*) as views, COUNT(DISTINCT visitor_hash) as visitors')->first();

        $todayRow = $range($today);
        $yesterday = $range($today->copy()->subDay(), $today);
        $week = $range($today->copy()->subDays(6));
        $month = $range($today->copy()->subDays(29));
        $total = DB::table('landing_visits')->count();

        // Daily views/visitors for the last 14 days.
        $from = $today->copy()->subDays(13);
        $daily = DB::table('landing_visits')->where('created_at', '>=', $from)
            ->selectRaw(self::DAY_SQL.' as d, COUNT(*) as views, COUNT(DISTINCT visitor_hash) as visitors')
            ->groupByRaw(self::DAY_SQL)->get()->keyBy('d');
        $orders = LandingOrder::where('created_at', '>=', $from)->selectRaw(self::DAY_SQL.' as d, COUNT(*) as c')
            ->groupByRaw(self::DAY_SQL)->pluck('c', 'd');
        $days = [];
        for ($d = $localToday->copy()->subDays(13); $d->lte($localToday); $d->addDay()) {
            $k = $d->toDateString();
            $days[] = ['date' => $d->copy(), 'views' => (int) ($daily[$k]->views ?? 0), 'visitors' => (int) ($daily[$k]->visitors ?? 0), 'orders' => (int) ($orders[$k] ?? 0)];
        }

        $monthFrom = $today->copy()->subDays(29);
        $sources = DB::table('landing_visits')->where('created_at', '>=', $monthFrom)
            ->selectRaw('COALESCE(source, \'direct\') as s, COUNT(DISTINCT visitor_hash) as c')->groupByRaw('COALESCE(source, \'direct\')')
            ->orderByDesc('c')->limit(6)->pluck('c', 's');
        $devices = DB::table('landing_visits')->where('created_at', '>=', $monthFrom)
            ->selectRaw('device, COUNT(DISTINCT visitor_hash) as c')->groupBy('device')->pluck('c', 'device');
        $monthOrders = LandingOrder::where('created_at', '>=', $monthFrom)->count();

        return [
            'today' => $todayRow, 'yesterday' => $yesterday, 'week' => $week, 'month' => $month, 'total' => $total,
            'days' => $days, 'sources' => $sources, 'devices' => $devices,
            'monthOrders' => $monthOrders,
            'conversion' => $month->visitors > 0 ? round($monthOrders / $month->visitors * 100, 1) : null,
            'online' => DB::table('landing_visits')->where('created_at', '>=', now()->subMinutes(5))->distinct()->count('visitor_hash'),
        ];
    }
}
