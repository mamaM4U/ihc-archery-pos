<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\CustomerMembership;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MembershipCheckInTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    public function test_check_in_page_loads(): void
    {
        $response = $this
            ->actingAs($this->user)
            ->get(route('memberships.check-in'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Dashboard/Memberships/CheckIn')
            ->has('customers')
            ->has('filters')
        );
    }

    public function test_check_in_page_searches_customers(): void
    {
        $customer = Customer::factory()->create(['name' => 'John Archer']);
        Customer::factory()->create(['name' => 'Jane Smith']);

        $response = $this
            ->actingAs($this->user)
            ->get(route('memberships.check-in', ['search' => 'John']));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Dashboard/Memberships/CheckIn')
            ->has('customers', 1)
        );
    }

    public function test_check_in_page_shows_selected_customer_membership(): void
    {
        $customer = Customer::factory()->create();
        $membership = CustomerMembership::factory()->create([
            'customer_id' => $customer->id,
            'status' => 'active',
        ]);

        $response = $this
            ->actingAs($this->user)
            ->get(route('memberships.check-in', ['customer_id' => $customer->id]));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Dashboard/Memberships/CheckIn')
            ->has('selectedMembership')
            ->where('selectedMembership.id', $membership->id)
        );
    }

    public function test_check_in_succeeds_with_active_membership_and_remaining_quota(): void
    {
        $customer = Customer::factory()->create();
        $membership = CustomerMembership::factory()->create([
            'customer_id' => $customer->id,
            'session_quota' => 8,
            'session_used' => 2,
            'status' => 'active',
        ]);

        $response = $this
            ->actingAs($this->user)
            ->post(route('memberships.check-in.store'), [
                'customer_membership_id' => $membership->id,
                'notes' => 'Morning session',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $membership->refresh();
        $this->assertEquals(3, $membership->session_used);

        $this->assertDatabaseHas('session_usages', [
            'customer_membership_id' => $membership->id,
            'customer_id' => $customer->id,
            'checked_in_by' => $this->user->id,
            'notes' => 'Morning session',
        ]);
    }

    public function test_check_in_fails_when_quota_exhausted(): void
    {
        $customer = Customer::factory()->create();
        $membership = CustomerMembership::factory()->create([
            'customer_id' => $customer->id,
            'session_quota' => 4,
            'session_used' => 4,
            'status' => 'active',
        ]);

        $response = $this
            ->actingAs($this->user)
            ->post(route('memberships.check-in.store'), [
                'customer_membership_id' => $membership->id,
            ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors('check_in');

        $membership->refresh();
        $this->assertEquals(4, $membership->session_used);
    }

    public function test_check_in_fails_when_membership_not_active(): void
    {
        $customer = Customer::factory()->create();
        $membership = CustomerMembership::factory()->expired()->create([
            'customer_id' => $customer->id,
        ]);

        $response = $this
            ->actingAs($this->user)
            ->post(route('memberships.check-in.store'), [
                'customer_membership_id' => $membership->id,
            ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors('check_in');
    }

    public function test_check_in_validates_customer_membership_id_required(): void
    {
        $response = $this
            ->actingAs($this->user)
            ->post(route('memberships.check-in.store'), []);

        $response->assertSessionHasErrors('customer_membership_id');
    }

    public function test_check_in_validates_customer_membership_id_exists(): void
    {
        $response = $this
            ->actingAs($this->user)
            ->post(route('memberships.check-in.store'), [
                'customer_membership_id' => 99999,
            ]);

        $response->assertSessionHasErrors('customer_membership_id');
    }

    public function test_check_in_without_notes_succeeds(): void
    {
        $customer = Customer::factory()->create();
        $membership = CustomerMembership::factory()->create([
            'customer_id' => $customer->id,
            'session_quota' => 8,
            'session_used' => 0,
            'status' => 'active',
        ]);

        $response = $this
            ->actingAs($this->user)
            ->post(route('memberships.check-in.store'), [
                'customer_membership_id' => $membership->id,
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $membership->refresh();
        $this->assertEquals(1, $membership->session_used);
    }
}
