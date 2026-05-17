<?php

namespace App\Http\Controllers\Apps;

use App\Http\Controllers\Controller;
use App\Models\MembershipPlan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class MembershipPlanController extends Controller
{
    /**
     * Display a listing of membership plans.
     *
     * @return Response
     */
    public function index()
    {
        $membershipPlans = MembershipPlan::when(request()->search, function ($query) {
            $query->where('name', 'like', '%'.request()->search.'%');
        })->latest()->paginate(10);

        return Inertia::render('Dashboard/MembershipPlans/Index', [
            'membershipPlans' => $membershipPlans,
        ]);
    }

    /**
     * Show the form for creating a new membership plan.
     *
     * @return Response
     */
    public function create()
    {
        return Inertia::render('Dashboard/MembershipPlans/Create');
    }

    /**
     * Store a newly created membership plan in storage.
     *
     * @return RedirectResponse
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'category' => 'required|in:registration,trial,monthly_no_equipment,monthly_with_equipment,family',
            'price' => 'required|integer|min:0',
            'duration_days' => 'required|integer|min:0',
            'session_quota' => 'required|integer|min:0',
            'description' => 'nullable|string',
            'equipment_provided' => 'required|boolean',
            'family_members' => 'required_if:category,family|integer|min:1',
            'is_active' => 'required|boolean',
        ]);

        MembershipPlan::create($validated);

        return to_route('membership-plans.index');
    }

    /**
     * Show the form for editing the specified membership plan.
     *
     * @return Response
     */
    public function edit(MembershipPlan $membershipPlan)
    {
        return Inertia::render('Dashboard/MembershipPlans/Edit', [
            'membershipPlan' => $membershipPlan,
        ]);
    }

    /**
     * Update the specified membership plan in storage.
     *
     * @return RedirectResponse
     */
    public function update(Request $request, MembershipPlan $membershipPlan)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'category' => 'required|in:registration,trial,monthly_no_equipment,monthly_with_equipment,family',
            'price' => 'required|integer|min:0',
            'duration_days' => 'required|integer|min:0',
            'session_quota' => 'required|integer|min:0',
            'description' => 'nullable|string',
            'equipment_provided' => 'required|boolean',
            'family_members' => 'required_if:category,family|integer|min:1',
            'is_active' => 'required|boolean',
        ]);

        $membershipPlan->update($validated);

        return to_route('membership-plans.index');
    }

    /**
     * Remove the specified membership plan from storage.
     *
     * @return RedirectResponse
     */
    public function destroy(MembershipPlan $membershipPlan)
    {
        if ($membershipPlan->customerMemberships()->exists()) {
            return back()->withErrors([
                'membershipPlan' => 'Tidak dapat menghapus plan yang sudah memiliki member.',
            ]);
        }

        $membershipPlan->delete();

        return to_route('membership-plans.index');
    }
}
