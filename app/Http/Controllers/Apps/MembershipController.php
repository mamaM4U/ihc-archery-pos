<?php

namespace App\Http\Controllers\Apps;

use App\Exceptions\MembershipException;
use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\CustomerMembership;
use App\Models\MembershipPlan;
use App\Models\SessionUsage;
use App\Services\MembershipService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class MembershipController extends Controller
{
    public function __construct(
        private MembershipService $membershipService
    ) {}

    /**
     * Display a listing of customer memberships with filters.
     */
    public function index(): Response
    {
        $memberships = CustomerMembership::with(['customer', 'membershipPlan'])
            ->when(request()->search, function ($query) {
                $query->whereHas('customer', function ($q) {
                    $q->where('name', 'like', '%'.request()->search.'%');
                });
            })
            ->when(request()->status, function ($query) {
                $query->where('status', request()->status);
            })
            ->when(request()->category, function ($query) {
                $query->whereHas('membershipPlan', function ($q) {
                    $q->where('category', request()->category);
                });
            })
            ->latest()
            ->paginate(10);

        $categories = MembershipPlan::distinct()->pluck('category');

        return Inertia::render('Dashboard/Memberships/Index', [
            'memberships' => $memberships,
            'categories' => $categories,
            'filters' => [
                'search' => request()->search,
                'status' => request()->status,
                'category' => request()->category,
            ],
        ]);
    }

    /**
     * Display the check-in page with customer search interface.
     */
    public function checkInPage(Request $request): Response
    {
        $customers = [];
        $selectedMembership = null;

        if ($request->filled('search')) {
            $customers = Customer::where('name', 'like', '%'.$request->search.'%')
                ->orWhere('no_telp', 'like', '%'.$request->search.'%')
                ->limit(10)
                ->get(['id', 'name', 'no_telp']);
        }

        if ($request->filled('customer_id')) {
            $selectedMembership = $this->membershipService->getActiveMembership(
                Customer::find($request->customer_id)
            );
        }

        return Inertia::render('Dashboard/Memberships/CheckIn', [
            'customers' => $customers,
            'selectedMembership' => $selectedMembership,
            'filters' => [
                'search' => $request->search,
                'customer_id' => $request->customer_id,
            ],
        ]);
    }

    /**
     * Record a session check-in for a customer.
     */
    public function checkIn(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'customer_membership_id' => ['required', 'exists:customer_memberships,id'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $membership = CustomerMembership::findOrFail($validated['customer_membership_id']);

        try {
            $this->membershipService->checkIn(
                membership: $membership,
                checkedInBy: $request->user()->id,
                notes: $validated['notes'] ?? null,
            );
        } catch (MembershipException $e) {
            return back()->withErrors(['check_in' => $e->getMessage()]);
        }

        return back()->with('success', 'Check-in berhasil! Sisa sesi: '.($membership->fresh()->remaining_sessions));
    }

    /**
     * Return membership statistics (active members, revenue, expiring soon, utilization).
     */
    public function stats(): JsonResponse
    {
        $activeMembers = CustomerMembership::active()->count();

        $totalRevenue = CustomerMembership::where('status', '!=', 'pending')
            ->whereNotNull('transaction_id')
            ->join('membership_plans', 'customer_memberships.membership_plan_id', '=', 'membership_plans.id')
            ->sum('membership_plans.price');

        $expiringSoon = CustomerMembership::active()
            ->where('end_date', '>=', Carbon::today())
            ->where('end_date', '<=', Carbon::today()->addDays(7))
            ->count();

        $activeMemberships = CustomerMembership::active();
        $totalSessionQuota = (clone $activeMemberships)->sum('session_quota');
        $totalSessionUsed = (clone $activeMemberships)->sum('session_used');
        $sessionUtilizationRate = $totalSessionQuota > 0
            ? round(($totalSessionUsed / $totalSessionQuota) * 100, 2)
            : 0;

        return response()->json([
            'active_members' => $activeMembers,
            'total_revenue' => (int) $totalRevenue,
            'expiring_soon' => $expiringSoon,
            'session_utilization_rate' => $sessionUtilizationRate,
        ]);
    }

    /**
     * Display the daily check-in log for a selected date.
     */
    public function dailyLog(Request $request): Response
    {
        $date = $request->filled('date')
            ? Carbon::parse($request->date)->toDateString()
            : Carbon::today()->toDateString();

        $sessionUsages = SessionUsage::with(['customer', 'customerMembership.membershipPlan'])
            ->whereDate('checked_in_at', $date)
            ->latest('checked_in_at')
            ->get();

        return Inertia::render('Dashboard/Memberships/DailyLog', [
            'sessionUsages' => $sessionUsages,
            'selectedDate' => $date,
            'totalCount' => $sessionUsages->count(),
        ]);
    }
}
