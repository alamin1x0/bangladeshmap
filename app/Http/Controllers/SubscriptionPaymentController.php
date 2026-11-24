<?php

namespace App\Http\Controllers;

use App\Models\SubscriptionPaymentRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Illuminate\View\View;

class SubscriptionPaymentController extends Controller
{
    public function index(Request $request): View
    {
        $status = in_array($request->query('status'), ['pending', 'approved', 'rejected'], true)
            ? $request->query('status') : null;
        $search = trim($request->string('q')->toString());
        $summary = SubscriptionPaymentRequest::query()
            ->selectRaw('COUNT(*) as total')
            ->selectRaw("SUM(status = 'pending') as pending")
            ->selectRaw("SUM(status = 'approved') as approved")
            ->selectRaw("SUM(status = 'rejected') as rejected")
            ->selectRaw("COALESCE(SUM(CASE WHEN status = 'pending' THEN amount ELSE 0 END), 0) as pending_amount")
            ->selectRaw("COALESCE(SUM(CASE WHEN status = 'approved' THEN amount ELSE 0 END), 0) as approved_amount")
            ->first();

        // Search by TrxID, bKash number, user name / email / ID (LIKE wildcards escaped).
        $like = '%'.addcslashes($search, '%_\\').'%';
        $userId = preg_match('/^#?(\d{1,18})$/', $search, $m) ? (int) $m[1] : null;

        return view('subscription-payments.index', [
            'payments' => SubscriptionPaymentRequest::with(['user:id,name,email,phone,photo_path,account_type', 'reviewer:id,username'])
                ->when($status, fn ($q) => $q->where('status', $status))
                ->when($search !== '', fn ($q) => $q->where(fn ($q) => $q
                    ->where('transaction_id', 'like', $like)
                    ->orWhere('customer_bkash_number', 'like', $like)
                    ->orWhereHas('user', fn ($u) => $u->where('name', 'like', $like)->orWhere('email', 'like', $like))
                    ->when($userId, fn ($q) => $q->orWhere('app_user_id', $userId))))
                // Pending first so the review queue is always on top, then newest.
                ->orderByRaw("CASE WHEN status = 'pending' THEN 0 ELSE 1 END")
                ->latest()
                ->paginate(25)
                ->withQueryString(),
            'status' => $status,
            'search' => $search,
            'summary' => $summary,
        ]);
    }

    public function approve(Request $request, SubscriptionPaymentRequest $payment): RedirectResponse
    {
        if ($payment->status !== 'pending') {
            return back()->with('error', 'This payment request has already been reviewed.');
        }

        $data = $request->validate(['duration_days' => ['nullable', 'integer', 'min:1', 'max:3650']]);
        $start = Carbon::today();
        $days = (int) ($data['duration_days'] ?? $payment->duration_days);
        $user = $payment->user;
        $user->forceFill([
            'account_type' => 'paid',
            'subscription_id' => 'BKASH-'.$payment->transaction_id,
            'subscription_package_name' => $payment->package_name,
            'subscription_package_id' => $payment->package_id,
            'subscription_package_type' => 'paid',
            'package_start_date' => $start,
            'package_end_date' => $start->copy()->addDays($days - 1),
            'package_status' => 'active',
            'package_auto_renew' => false,
        ])->save();
        $payment->update([
            'status' => 'approved',
            'duration_days' => $days,
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
        ]);

        return back()->with('success', 'Payment approved and PAID package activated.');
    }

    public function reject(Request $request, SubscriptionPaymentRequest $payment): RedirectResponse
    {
        if ($payment->status !== 'pending') {
            return back()->with('error', 'This payment request has already been reviewed.');
        }
        $data = $request->validate(['rejection_reason' => ['required', 'string', 'max:1000']]);
        $payment->update([
            'status' => 'rejected',
            'rejection_reason' => $data['rejection_reason'],
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
        ]);

        return back()->with('success', 'Payment request rejected.');
    }
}
