<?php

namespace App\Http\Controllers;

use App\Models\AppSetting;
use App\Models\District;
use App\Models\Division;
use App\Models\LandingOrder;
use App\Support\LandingContent;
use App\Support\LandingVisits;
use App\Support\WebSettings;
use Illuminate\Http\Response;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class LandingController extends Controller
{
    public const DEFAULT_PRICE = 299;

    public const CACHE_KEY = 'landing:data:v1';

    /** Public landing page. All DB work is cached so the page stays fast under traffic. */
    public function show(Request $request): View|Response
    {
        $web = WebSettings::all();

        // Maintenance / offline: visitors get a 503 status page; logged-in admins still see the site.
        if (WebSettings::isPublicBlocked()) {
            return response()->view('site-status', ['web' => $web, 'phone' => WebSettings::phone()], 503)
                ->header('Retry-After', '3600')->header('X-Robots-Tag', 'noindex');
        }

        // Count the visit after the response is sent, so it never slows the page down.
        if ($web['track_visitors']) {
            app()->terminating(fn () => LandingVisits::record($request));
        }

        $data = Cache::remember(self::CACHE_KEY, 600, function (): array {
            $settings = AppSetting::current();
            $divisions = Division::withCount(['districts', 'upazilas'])
                ->with(['districts' => fn ($q) => $q->select('id', 'division_id', 'name', 'name_bn')->orderBy('name')])
                ->orderBy('name')->get(['id', 'name', 'name_bn']);

            return [
                'price' => (int) ($settings->landing_price ?: self::DEFAULT_PRICE),
                'phone' => $settings->landing_phone ?: $settings->support_phone,
                'bkash' => $settings->bkash_merchant_number,
                'ordersOpen' => (bool) ($settings->landing_orders_open ?? true),
                'divisions' => $divisions->map(fn ($d) => [
                    'name' => $d->name_bn ?: $d->name,
                    'name_en' => ucfirst(strtolower($d->name)),
                    'districts' => $d->districts_count,
                    'upazilas' => $d->upazilas_count,
                    'district_names' => $d->districts->map(fn ($x) => $x->name_bn ?: $x->name)->all(),
                ])->all(),
                'totals' => [
                    'divisions' => $divisions->count(),
                    'districts' => (int) $divisions->sum('districts_count'),
                    'upazilas' => (int) $divisions->sum('upazilas_count'),
                ],
                'districtOptions' => District::orderBy('name')->get(['name', 'name_bn'])
                    ->map(fn ($d) => $d->name_bn ? "{$d->name_bn} ({$d->name})" : $d->name)->all(),
            ];
        });

        $data['phone'] = WebSettings::phone();

        return view('landing', $data + ['text' => LandingContent::all(), 'web' => $web, 'logoUrl' => WebSettings::logoUrl(), 'appLinks' => WebSettings::appLinks()]);
    }

    public function order(Request $request): RedirectResponse
    {
        // Honeypot: real visitors never see or fill this field.
        if (filled($request->input('website'))) {
            return redirect()->to(route('home').'#order')->with('order_success', 0);
        }

        if (WebSettings::isPublicBlocked()) {
            return redirect()->route('home');
        }

        $settings = AppSetting::current();
        if (! ($settings->landing_orders_open ?? true)) {
            throw ValidationException::withMessages(['name' => 'এই মুহূর্তে অর্ডার নেওয়া বন্ধ আছে। পরে আবার চেষ্টা করুন।'])->errorBag('order')->redirectTo(route('home').'#order');
        }

        $request->merge(['phone' => $this->normalizePhone((string) $request->input('phone'))]);
        $data = $request->validateWithBag('order', [
            'name' => ['required', 'string', 'min:2', 'max:120'],
            'phone' => ['required', 'regex:/^01[3-9]\d{8}$/'],
            'district' => ['nullable', 'string', 'max:120'],
            'address' => ['nullable', 'string', 'max:1000'],
        ], [
            'name.required' => 'আপনার নাম লিখুন।',
            'name.min' => 'আপনার পুরো নাম লিখুন।',
            'phone.required' => 'মোবাইল নম্বর লিখুন।',
            'phone.regex' => 'সঠিক মোবাইল নম্বর দিন (যেমন 017XXXXXXXX)।',
        ]);

        // Same phone within 10 minutes = double submit; reuse the existing order instead of creating a duplicate.
        $order = LandingOrder::where('phone', $data['phone'])->where('created_at', '>=', now()->subMinutes(10))->latest()->first()
            ?? LandingOrder::create([
                'name' => trim(strip_tags($data['name'])),
                'phone' => $data['phone'],
                'district' => isset($data['district']) ? trim(strip_tags($data['district'])) : null,
                'address' => isset($data['address']) ? trim(strip_tags($data['address'])) : null,
                'amount' => (int) ($settings->landing_price ?: self::DEFAULT_PRICE),
                'status' => 'new',
                'ip_address' => $request->ip(),
            ]);

        return redirect()->to(route('home').'#order')->with('order_success', $order->id);
    }

    private function normalizePhone(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', strtr($phone, array_combine(
            ['০', '১', '২', '৩', '৪', '৫', '৬', '৭', '৮', '৯'], range(0, 9)
        )));

        return str_starts_with($digits, '880') ? substr($digits, 2) : $digits;
    }
}
