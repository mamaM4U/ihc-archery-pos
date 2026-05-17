<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Services\MembershipService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __construct(
        public MembershipService $membershipService
    ) {}

    public function index(Request $request): Response
    {
        $customer = Auth::guard('customer')->user();

        $recentTransactions = $customer->transactions()
            ->with('details.product:id,title')
            ->withCount('details')
            ->latest()
            ->limit(5)
            ->get();

        $stats = [
            'total_transactions' => $customer->transactions()->count(),
            'total_spent' => (int) $customer->transactions()->sum('grand_total'),
        ];

        $membership = $this->membershipService->getActiveMembership($customer);
        $membershipSummary = null;

        if ($membership) {
            $membership->append(['remaining_days', 'is_expiring_soon']);
            $membershipSummary = [
                'plan_name' => $membership->membershipPlan->name,
                'remaining_sessions' => $membership->remaining_sessions,
                'session_quota' => $membership->session_quota,
                'remaining_days' => $membership->remaining_days,
                'is_expiring_soon' => $membership->is_expiring_soon,
            ];
        }

        return Inertia::render('Customer/Dashboard', [
            'recentTransactions' => $recentTransactions,
            'stats' => $stats,
            'membershipSummary' => $membershipSummary,
        ]);
    }
}
