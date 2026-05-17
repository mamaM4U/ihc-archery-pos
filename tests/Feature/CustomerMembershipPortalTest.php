<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\CustomerMembership;
use App\Models\MembershipPlan;
use App\Models\PaymentSetting;
use App\Models\SessionUsage;
use App\Models\Transaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CustomerMembershipPortalTest extends TestCase
{
    use RefreshDatabase;

    private Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->customer = Customer::factory()->create();
    }

    public function test_membership_index_shows_active_membership(): void
    {
        $membership = CustomerMembership::factory()->create([
            'customer_id' => $this->customer->id,
            'status' => 'active',
            'session_quota' => 8,
            'session_used' => 3,
            'start_date' => now()->subDays(10)->toDateString(),
            'end_date' => now()->addDays(20)->toDateString(),
        ]);

        $response = $this
            ->actingAs($this->customer, 'customer')
            ->get(route('customer.membership'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Customer/Membership/Index')
            ->has('membership')
            ->where('membership.id', $membership->id)
            ->where('membership.session_quota', 8)
            ->where('membership.session_used', 3)
            ->where('membership.remaining_sessions', 5)
            ->where('isExpiringSoon', false)
        );
    }

    public function test_membership_index_shows_no_membership_when_none_active(): void
    {
        $response = $this
            ->actingAs($this->customer, 'customer')
            ->get(route('customer.membership'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Customer/Membership/Index')
            ->where('membership', null)
            ->where('sessionUsages', [])
            ->where('isExpiringSoon', false)
        );
    }

    public function test_membership_index_shows_session_usage_history(): void
    {
        $membership = CustomerMembership::factory()->create([
            'customer_id' => $this->customer->id,
            'status' => 'active',
            'session_used' => 2,
        ]);

        SessionUsage::factory()->count(2)->create([
            'customer_membership_id' => $membership->id,
            'customer_id' => $this->customer->id,
        ]);

        $response = $this
            ->actingAs($this->customer, 'customer')
            ->get(route('customer.membership'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Customer/Membership/Index')
            ->has('sessionUsages', 2)
        );
    }

    public function test_membership_index_shows_expiring_soon_warning(): void
    {
        CustomerMembership::factory()->create([
            'customer_id' => $this->customer->id,
            'status' => 'active',
            'start_date' => now()->subDays(25)->toDateString(),
            'end_date' => now()->addDays(5)->toDateString(),
        ]);

        $response = $this
            ->actingAs($this->customer, 'customer')
            ->get(route('customer.membership'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Customer/Membership/Index')
            ->where('isExpiringSoon', true)
        );
    }

    public function test_membership_index_requires_customer_authentication(): void
    {
        $response = $this->get(route('customer.membership'));

        $response->assertRedirect(route('customer.login'));
    }

    public function test_plans_shows_active_plans_grouped_by_category(): void
    {
        MembershipPlan::factory()->create([
            'category' => 'registration',
            'is_active' => true,
            'price' => 100000,
        ]);
        MembershipPlan::factory()->create([
            'category' => 'monthly_no_equipment',
            'is_active' => true,
            'price' => 250000,
        ]);
        MembershipPlan::factory()->create([
            'category' => 'monthly_with_equipment',
            'is_active' => true,
            'price' => 350000,
        ]);
        MembershipPlan::factory()->create([
            'category' => 'monthly_no_equipment',
            'is_active' => false,
            'price' => 200000,
        ]);

        $response = $this
            ->actingAs($this->customer, 'customer')
            ->get(route('customer.membership.plans'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Customer/Membership/Plans')
            ->has('plans.registration', 1)
            ->has('plans.monthly_no_equipment', 1)
            ->has('plans.monthly_with_equipment', 1)
            ->missing('plans.monthly_no_equipment.1')
            ->has('hasRegistration')
        );
    }

    public function test_plans_excludes_inactive_plans(): void
    {
        MembershipPlan::factory()->create([
            'category' => 'trial',
            'is_active' => false,
        ]);

        $response = $this
            ->actingAs($this->customer, 'customer')
            ->get(route('customer.membership.plans'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Customer/Membership/Plans')
            ->where('plans', [])
        );
    }

    public function test_plans_includes_current_membership_info(): void
    {
        MembershipPlan::factory()->create([
            'category' => 'registration',
            'is_active' => true,
        ]);

        $membership = CustomerMembership::factory()->create([
            'customer_id' => $this->customer->id,
            'status' => 'active',
            'start_date' => now()->subDays(25)->toDateString(),
            'end_date' => now()->addDays(5)->toDateString(),
        ]);

        $response = $this
            ->actingAs($this->customer, 'customer')
            ->get(route('customer.membership.plans'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Customer/Membership/Plans')
            ->has('currentMembership')
            ->where('currentMembership.id', $membership->id)
            ->where('currentMembership.is_expiring_soon', true)
        );
    }

    public function test_plans_shows_has_registration_status(): void
    {
        $registrationPlan = MembershipPlan::factory()->create([
            'category' => 'registration',
            'is_active' => true,
        ]);

        CustomerMembership::factory()->create([
            'customer_id' => $this->customer->id,
            'membership_plan_id' => $registrationPlan->id,
            'status' => 'active',
        ]);

        $response = $this
            ->actingAs($this->customer, 'customer')
            ->get(route('customer.membership.plans'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Customer/Membership/Plans')
            ->where('hasRegistration', true)
        );
    }

    public function test_plans_requires_customer_authentication(): void
    {
        $response = $this->get(route('customer.membership.plans'));

        $response->assertRedirect(route('customer.login'));
    }

    public function test_purchase_creates_transaction_and_returns_payment_url(): void
    {
        $plan = MembershipPlan::factory()->create([
            'category' => 'registration',
            'is_active' => true,
            'price' => 100000,
        ]);

        PaymentSetting::create([
            'default_gateway' => 'midtrans',
            'midtrans_enabled' => true,
            'midtrans_server_key' => 'server-key',
            'midtrans_client_key' => 'client-key',
            'midtrans_production' => false,
        ]);

        Http::fake([
            'https://app.sandbox.midtrans.com/*' => Http::response([
                'order_id' => 'TRX-TEST123',
                'redirect_url' => 'https://pay.midtrans.test/snap',
                'token' => 'snap-token-123',
            ], 200),
        ]);

        $response = $this
            ->actingAs($this->customer, 'customer')
            ->postJson(route('customer.membership.purchase'), [
                'membership_plan_id' => $plan->id,
            ]);

        $response->assertOk();
        $response->assertJsonStructure(['message', 'invoice', 'payment_url', 'payment_reference']);
        $response->assertJsonFragment(['payment_url' => 'https://pay.midtrans.test/snap']);

        $transaction = Transaction::latest('id')->first();
        $this->assertNotNull($transaction);
        $this->assertSame($this->customer->id, $transaction->customer_id);
        $this->assertSame($plan->id, $transaction->membership_plan_id);
        $this->assertSame(100000, $transaction->grand_total);
        $this->assertSame('pending', $transaction->payment_status);
        $this->assertSame('online', $transaction->payment_method);
        $this->assertNull($transaction->cashier_id);
    }

    public function test_purchase_rejects_inactive_plan(): void
    {
        $plan = MembershipPlan::factory()->create([
            'category' => 'registration',
            'is_active' => false,
            'price' => 100000,
        ]);

        PaymentSetting::create([
            'default_gateway' => 'midtrans',
            'midtrans_enabled' => true,
            'midtrans_server_key' => 'server-key',
            'midtrans_client_key' => 'client-key',
        ]);

        $response = $this
            ->actingAs($this->customer, 'customer')
            ->postJson(route('customer.membership.purchase'), [
                'membership_plan_id' => $plan->id,
            ]);

        $response->assertStatus(422);
        $response->assertJsonFragment(['message' => 'Paket membership tidak ditemukan atau tidak aktif.']);
        $this->assertDatabaseCount('transactions', 0);
    }

    public function test_purchase_rejects_monthly_plan_without_registration(): void
    {
        $plan = MembershipPlan::factory()->create([
            'category' => 'monthly_no_equipment',
            'is_active' => true,
            'price' => 250000,
        ]);

        PaymentSetting::create([
            'default_gateway' => 'midtrans',
            'midtrans_enabled' => true,
            'midtrans_server_key' => 'server-key',
            'midtrans_client_key' => 'client-key',
        ]);

        $response = $this
            ->actingAs($this->customer, 'customer')
            ->postJson(route('customer.membership.purchase'), [
                'membership_plan_id' => $plan->id,
            ]);

        $response->assertStatus(422);
        $response->assertJsonFragment(['message' => 'Anda harus memiliki membership registrasi terlebih dahulu sebelum membeli paket bulanan.']);
        $this->assertDatabaseCount('transactions', 0);
    }

    public function test_purchase_allows_monthly_plan_with_registration(): void
    {
        $registrationPlan = MembershipPlan::factory()->create([
            'category' => 'registration',
            'is_active' => true,
        ]);

        CustomerMembership::factory()->create([
            'customer_id' => $this->customer->id,
            'membership_plan_id' => $registrationPlan->id,
            'status' => 'active',
        ]);

        $monthlyPlan = MembershipPlan::factory()->create([
            'category' => 'monthly_no_equipment',
            'is_active' => true,
            'price' => 250000,
        ]);

        PaymentSetting::create([
            'default_gateway' => 'midtrans',
            'midtrans_enabled' => true,
            'midtrans_server_key' => 'server-key',
            'midtrans_client_key' => 'client-key',
            'midtrans_production' => false,
        ]);

        Http::fake([
            'https://app.sandbox.midtrans.com/*' => Http::response([
                'order_id' => 'TRX-MONTHLY',
                'redirect_url' => 'https://pay.midtrans.test/monthly',
                'token' => 'snap-token-monthly',
            ], 200),
        ]);

        $response = $this
            ->actingAs($this->customer, 'customer')
            ->postJson(route('customer.membership.purchase'), [
                'membership_plan_id' => $monthlyPlan->id,
            ]);

        $response->assertOk();
        $response->assertJsonFragment(['payment_url' => 'https://pay.midtrans.test/monthly']);

        $transaction = Transaction::latest('id')->first();
        $this->assertSame($monthlyPlan->id, $transaction->membership_plan_id);
        $this->assertSame(250000, $transaction->grand_total);
    }

    public function test_purchase_fails_without_payment_settings(): void
    {
        $plan = MembershipPlan::factory()->create([
            'category' => 'registration',
            'is_active' => true,
            'price' => 100000,
        ]);

        $response = $this
            ->actingAs($this->customer, 'customer')
            ->postJson(route('customer.membership.purchase'), [
                'membership_plan_id' => $plan->id,
            ]);

        $response->assertStatus(422);
        $response->assertJsonFragment(['message' => 'Pembayaran online belum dikonfigurasi. Silakan hubungi admin.']);
    }

    public function test_purchase_fails_without_online_gateway_available(): void
    {
        $plan = MembershipPlan::factory()->create([
            'category' => 'registration',
            'is_active' => true,
            'price' => 100000,
        ]);

        PaymentSetting::create([
            'default_gateway' => 'bank_transfer',
            'bank_transfer_enabled' => true,
            'midtrans_enabled' => false,
            'xendit_enabled' => false,
        ]);

        $response = $this
            ->actingAs($this->customer, 'customer')
            ->postJson(route('customer.membership.purchase'), [
                'membership_plan_id' => $plan->id,
            ]);

        $response->assertStatus(422);
        $response->assertJsonFragment(['message' => 'Tidak ada gateway pembayaran online yang tersedia. Silakan hubungi admin.']);
    }

    public function test_purchase_requires_membership_plan_id(): void
    {
        $response = $this
            ->actingAs($this->customer, 'customer')
            ->postJson(route('customer.membership.purchase'), []);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('membership_plan_id');
    }

    public function test_purchase_requires_customer_authentication(): void
    {
        $response = $this->postJson(route('customer.membership.purchase'), [
            'membership_plan_id' => 1,
        ]);

        $response->assertUnauthorized();
    }

    public function test_history_shows_all_memberships_ordered_by_most_recent(): void
    {
        $olderMembership = CustomerMembership::factory()->create([
            'customer_id' => $this->customer->id,
            'status' => 'expired',
            'created_at' => now()->subMonths(2),
        ]);

        $newerMembership = CustomerMembership::factory()->create([
            'customer_id' => $this->customer->id,
            'status' => 'active',
            'created_at' => now(),
        ]);

        $response = $this
            ->actingAs($this->customer, 'customer')
            ->get(route('customer.membership.history'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Customer/Membership/History')
            ->has('memberships', 2)
            ->where('memberships.0.id', $newerMembership->id)
            ->where('memberships.1.id', $olderMembership->id)
        );
    }

    public function test_history_includes_membership_plan_data(): void
    {
        $plan = MembershipPlan::factory()->create([
            'name' => 'Paket Bulanan',
            'category' => 'monthly_no_equipment',
        ]);

        CustomerMembership::factory()->create([
            'customer_id' => $this->customer->id,
            'membership_plan_id' => $plan->id,
            'status' => 'active',
        ]);

        $response = $this
            ->actingAs($this->customer, 'customer')
            ->get(route('customer.membership.history'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Customer/Membership/History')
            ->has('memberships', 1)
            ->has('memberships.0.membership_plan')
            ->where('memberships.0.membership_plan.name', 'Paket Bulanan')
        );
    }

    public function test_history_shows_empty_when_no_memberships(): void
    {
        $response = $this
            ->actingAs($this->customer, 'customer')
            ->get(route('customer.membership.history'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Customer/Membership/History')
            ->has('memberships', 0)
        );
    }

    public function test_history_only_shows_authenticated_customers_memberships(): void
    {
        $otherCustomer = Customer::factory()->create();

        CustomerMembership::factory()->create([
            'customer_id' => $otherCustomer->id,
            'status' => 'active',
        ]);

        CustomerMembership::factory()->create([
            'customer_id' => $this->customer->id,
            'status' => 'active',
        ]);

        $response = $this
            ->actingAs($this->customer, 'customer')
            ->get(route('customer.membership.history'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Customer/Membership/History')
            ->has('memberships', 1)
        );
    }

    public function test_history_requires_customer_authentication(): void
    {
        $response = $this->get(route('customer.membership.history'));

        $response->assertRedirect(route('customer.login'));
    }
}
