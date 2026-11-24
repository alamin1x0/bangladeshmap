<?php

namespace App\Http\Controllers;

use App\Models\AppSetting;
use App\Models\LandingOrder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class LandingOrderController extends Controller
{
    public function index(Request $request): View
    {
        $status = array_key_exists((string) $request->query('status'), LandingOrder::STATUSES) ? $request->query('status') : null;
        $search = trim($request->string('q')->toString());
        $like = '%'.addcslashes($search, '%_\\').'%';
        $id = preg_match('/^#?(\d{1,18})$/', $search, $m) ? (int) $m[1] : null;

        $counts = LandingOrder::query()->selectRaw('status, COUNT(*) as c')->groupBy('status')->pluck('c', 'status');
        $revenue = (int) LandingOrder::whereIn('status', ['paid', 'delivered'])->sum('amount');
        $today = LandingOrder::where('created_at', '>=', \Illuminate\Support\Carbon::today('Asia/Dhaka')->utc())->count();

        return view('landing-orders.index', [
            'orders' => LandingOrder::query()
                ->when($status, fn ($q) => $q->where('status', $status))
                ->when($search !== '', fn ($q) => $q->where(fn ($q) => $q
                    ->where('name', 'like', $like)->orWhere('phone', 'like', $like)->orWhere('district', 'like', $like)
                    ->when($id, fn ($q) => $q->orWhere('id', $id))))
                ->orderByRaw("CASE WHEN status = 'new' THEN 0 ELSE 1 END")
                ->latest()->paginate(25)->withQueryString(),
            'counts' => $counts,
            'total' => (int) $counts->sum(),
            'revenue' => $revenue,
            'today' => $today,
            'status' => $status,
            'search' => $search,
            'settings' => AppSetting::current(),
        ]);
    }

    public function update(Request $request, LandingOrder $order): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(array_keys(LandingOrder::STATUSES))],
            'admin_note' => ['nullable', 'string', 'max:1000'],
        ]);
        $order->update($data);

        return back()->with('success', "Order #{$order->id} marked as ".LandingOrder::STATUSES[$data['status']].'.');
    }

    public function destroy(LandingOrder $order): RedirectResponse
    {
        \App\Models\DeletionLog::record('landing_order', $order, 'Order #'.$order->id.' - '.($order->name ?? ''));
        $order->delete();

        return back()->with('success', "Order #{$order->id} deleted.");
    }

    public function settings(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'landing_price' => ['required', 'integer', 'min:1', 'max:1000000'],
            'landing_phone' => ['nullable', 'string', 'max:30', 'regex:/^[0-9+\-\s()]{6,30}$/'],
        ]);
        $settings = AppSetting::current();
        $settings->landing_price = $data['landing_price'];
        if ($request->has('landing_phone')) {
            $settings->landing_phone = $data['landing_phone'] ?? null;
        }
        $settings->landing_orders_open = $request->boolean('landing_orders_open');
        $settings->save();
        Cache::forget(LandingController::CACHE_KEY);

        return back()->with('success', 'Landing page settings saved.');
    }
}
