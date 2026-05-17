<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\CustomerMembership;
use App\Models\MembershipPlan;
use App\Models\SessionUsage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MembershipControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->user = User::factory()->create();
    }

    // ─── Index Page ─────────────────────────────────────────────────────

    public function test_index_page_loads_with_memberships(): void
    {
        CustomerMembership::factory()->count(3)->create();

        $response = $this
            ->actingAs($this->user)
            ->get(route('memberships.index'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Dashboard/Memberships/Index')
            ->has('memberships.data', 3)
            ->has('categories')
            ->has('filters')
        );
    }

    public function test_index_page_filters_by_active_status(): void
    {
        CustomerMembership::factory()->count(2)->create(['status' => 'active']);
        CustomerMembership::factory()->expired()->create();
        CustomerMembership::factory()->pending()->create();

        $response = $this
            ->actingAs($this->user)
            ->get(route('memberships.index', ['status' => 'active']));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Dashboard/Memberships/Index')
            ->has('memberships.data', 2)
        );
    }

    public function test_index_page_filters_by_expired_status(): void
    {
        CustomerMembership::factory()->create(['status' => 'active']);
        CustomerMembership::factory()->expired()->count(2)->create();

        $response = $this
            ->actingAs($this->user)
            ->get(route('memberships.index', ['status' => 'expired']));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Dashboard/Memberships/Index')
            ->has('memberships.data', 2)
        );
    }

    public function test_index_page_filters_by_pending_status(): void
    {
        CustomerMembership::factory()->create(['status' => 'active']);
        CustomerMembership::factory()->pending()->count(2)->create();

        $response = $this
            ->actingAs($this->user)
            ->get(route('memberships.index', ['status' => 'pending']));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Dashboard/Memberships/Index')
            ->has('memberships.data', 2)
        );
    }

    public function test_index_page_filters_by_category(): void
    {
        $monthlyPlan = MembershipPlan::factory()->monthlyNoEquipment()->create();
        $familyPlan = MembershipPlan::factory()->family()->create();

        CustomerMembership::factory()->count(2)->create(['membership_plan_id' => $monthlyPlan->id]);
        CustomerMembership::factory()->create(['membership_plan_id' => $familyPlan->id]);

        $response = $this
            ->actingAs($this->user)
            ->get(route('memberships.index', ['category' => 'monthly_no_equipment']));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Dashboard/Memberships/Index')
            ->has('memberships.data', 2)
        );
    }

    public function test_index_page_searches_by_customer_name(): void
    {
        $customer = Customer::factory()->create(['name' => 'Budi Archer']);
        $otherCustomer = Customer::factory()->create(['name' => 'Siti Panahan']);

        CustomerMembership::factory()->create(['customer_id' => $customer->id]);
        CustomerMembership::factory()->create(['customer_id' => $otherCustomer->id]);

        $response = $this
            ->actingAs($this->user)
            ->get(route('memberships.index', ['search' => 'Budi']));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Dashboard/Memberships/Index')
            ->has('memberships.data', 1)
        );
    }

    // ─── Daily Log Page ─────────────────────────────────────────────────

    public function test_daily_log_page_loads_with_today_date_by_default(): void
    {
        $response = $this
            ->actingAs($this->user)
            ->get(route('memberships.daily-log'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Dashboard/Memberships/DailyLog')
            ->has('sessionUsages')
            ->has('selectedDate')
            ->has('totalCount')
            ->where('selectedDate', now()->toDateString())
        );
    }

    public function test_daily_log_page_accepts_date_parameter(): void
    {
        $customer = Customer::factory()->create();
        $membership = CustomerMembership::factory()->create(['customer_id' => $customer->id]);

        $targetDate = '2024-06-15';

        SessionUsage::create([
            'customer_membership_id' => $membership->id,
            'customer_id' => $customer->id,
            'checked_in_by' => $this->user->id,
            'checked_in_at' => $targetDate.' 09:00:00',
            'notes' => 'Morning session',
        ]);

        // Create another usage on a different date (should not appear)
        SessionUsage::create([
            'customer_membership_id' => $membership->id,
            'customer_id' => $customer->id,
            'checked_in_by' => $this->user->id,
            'checked_in_at' => '2024-06-16 10:00:00',
            'notes' => 'Next day session',
        ]);

        $response = $this
            ->actingAs($this->user)
            ->get(route('memberships.daily-log', ['date' => $targetDate]));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Dashboard/Memberships/DailyLog')
            ->where('selectedDate', $targetDate)
            ->where('totalCount', 1)
            ->has('sessionUsages', 1)
        );
    }

    public function test_daily_log_page_shows_empty_state_when_no_check_ins(): void
    {
        $response = $this
            ->actingAs($this->user)
            ->get(route('memberships.daily-log', ['date' => '2024-01-01']));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Dashboard/Memberships/DailyLog')
            ->where('totalCount', 0)
            ->has('sessionUsages', 0)
        );
    }
}
