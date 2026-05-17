<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\CustomerMembership;
use App\Models\MembershipPlan;
use App\Models\PaymentSetting;
use App\Models\Transaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MembershipWebhookTest extends TestCase
{
    use RefreshDatabase;

    private PaymentSetting $paymentSetting;

    protected function setUp(): void
    {
        parent::setUp();

        $this->paymentSetting = PaymentSetting::factory()->create([
            'midtrans_enabled' => true,
            'midtrans_server_key' => 'test-server-key',
            'midtrans_client_key' => 'test-client-key',
            'xendit_enabled' => true,
            'xendit_secret_key' => 'test-xendit-secret',
            'xendit_public_key' => 'test-xendit-public',
            'xendit_callback_token' => 'test-callback-token',
        ]);
    }

    public function test_midtrans_webhook_activates_membership_on_paid_status(): void
    {
        $customer = Customer::factory()->create();
        $plan = MembershipPlan::factory()->registration()->create();

        $transaction = Transaction::factory()->withMembershipPlan($plan)->create([
            'customer_id' => $customer->id,
            'payment_status' => 'pending',
            'payment_method' => 'midtrans',
        ]);

        $orderId = $transaction->invoice;
        $statusCode = '200';
        $grossAmount = (string) $transaction->grand_total;
        $signatureKey = hash('sha512', $orderId.$statusCode.$grossAmount.'test-server-key');

        $response = $this->postJson('/api/webhooks/midtrans', [
            'order_id' => $orderId,
            'status_code' => $statusCode,
            'gross_amount' => $grossAmount,
            'signature_key' => $signatureKey,
            'transaction_status' => 'settlement',
            'fraud_status' => 'accept',
            'transaction_id' => 'midtrans-txn-123',
        ]);

        $response->assertOk()->assertJson(['status' => 'success']);

        $transaction->refresh();
        $this->assertSame('paid', $transaction->payment_status);

        $this->assertDatabaseHas('customer_memberships', [
            'customer_id' => $customer->id,
            'membership_plan_id' => $plan->id,
            'transaction_id' => $transaction->id,
            'status' => 'active',
        ]);
    }

    public function test_xendit_webhook_activates_membership_on_paid_status(): void
    {
        $customer = Customer::factory()->create();
        $plan = MembershipPlan::factory()->registration()->create();

        $transaction = Transaction::factory()->withMembershipPlan($plan)->create([
            'customer_id' => $customer->id,
            'payment_status' => 'pending',
            'payment_method' => 'xendit',
        ]);

        $response = $this->postJson('/api/webhooks/xendit', [
            'external_id' => $transaction->invoice,
            'status' => 'PAID',
            'id' => 'xendit-payment-123',
        ], [
            'X-CALLBACK-TOKEN' => 'test-callback-token',
        ]);

        $response->assertOk()->assertJson(['status' => 'success']);

        $transaction->refresh();
        $this->assertSame('paid', $transaction->payment_status);

        $this->assertDatabaseHas('customer_memberships', [
            'customer_id' => $customer->id,
            'membership_plan_id' => $plan->id,
            'transaction_id' => $transaction->id,
            'status' => 'active',
        ]);
    }

    public function test_midtrans_webhook_does_not_activate_membership_on_pending_status(): void
    {
        $customer = Customer::factory()->create();
        $plan = MembershipPlan::factory()->registration()->create();

        $transaction = Transaction::factory()->withMembershipPlan($plan)->create([
            'customer_id' => $customer->id,
            'payment_status' => 'pending',
            'payment_method' => 'midtrans',
        ]);

        $orderId = $transaction->invoice;
        $statusCode = '201';
        $grossAmount = (string) $transaction->grand_total;
        $signatureKey = hash('sha512', $orderId.$statusCode.$grossAmount.'test-server-key');

        $response = $this->postJson('/api/webhooks/midtrans', [
            'order_id' => $orderId,
            'status_code' => $statusCode,
            'gross_amount' => $grossAmount,
            'signature_key' => $signatureKey,
            'transaction_status' => 'pending',
            'fraud_status' => null,
            'transaction_id' => 'midtrans-txn-456',
        ]);

        $response->assertOk()->assertJson(['status' => 'success']);

        $this->assertDatabaseCount('customer_memberships', 0);
    }

    public function test_midtrans_webhook_does_not_activate_membership_on_failed_status(): void
    {
        $customer = Customer::factory()->create();
        $plan = MembershipPlan::factory()->registration()->create();

        $transaction = Transaction::factory()->withMembershipPlan($plan)->create([
            'customer_id' => $customer->id,
            'payment_status' => 'pending',
            'payment_method' => 'midtrans',
        ]);

        $orderId = $transaction->invoice;
        $statusCode = '202';
        $grossAmount = (string) $transaction->grand_total;
        $signatureKey = hash('sha512', $orderId.$statusCode.$grossAmount.'test-server-key');

        $response = $this->postJson('/api/webhooks/midtrans', [
            'order_id' => $orderId,
            'status_code' => $statusCode,
            'gross_amount' => $grossAmount,
            'signature_key' => $signatureKey,
            'transaction_status' => 'expire',
            'fraud_status' => null,
            'transaction_id' => 'midtrans-txn-789',
        ]);

        $response->assertOk()->assertJson(['status' => 'success']);

        $this->assertDatabaseCount('customer_memberships', 0);
    }

    public function test_webhook_does_not_create_membership_for_transaction_without_membership_plan(): void
    {
        $customer = Customer::factory()->create();

        $transaction = Transaction::factory()->create([
            'customer_id' => $customer->id,
            'payment_status' => 'pending',
            'payment_method' => 'midtrans',
            'membership_plan_id' => null,
        ]);

        $orderId = $transaction->invoice;
        $statusCode = '200';
        $grossAmount = (string) $transaction->grand_total;
        $signatureKey = hash('sha512', $orderId.$statusCode.$grossAmount.'test-server-key');

        $response = $this->postJson('/api/webhooks/midtrans', [
            'order_id' => $orderId,
            'status_code' => $statusCode,
            'gross_amount' => $grossAmount,
            'signature_key' => $signatureKey,
            'transaction_status' => 'settlement',
            'fraud_status' => 'accept',
            'transaction_id' => 'midtrans-txn-no-plan',
        ]);

        $response->assertOk()->assertJson(['status' => 'success']);

        $this->assertDatabaseCount('customer_memberships', 0);
    }

    public function test_midtrans_webhook_rejects_invalid_signature(): void
    {
        $transaction = Transaction::factory()->create([
            'payment_status' => 'pending',
            'payment_method' => 'midtrans',
        ]);

        $response = $this->postJson('/api/webhooks/midtrans', [
            'order_id' => $transaction->invoice,
            'status_code' => '200',
            'gross_amount' => (string) $transaction->grand_total,
            'signature_key' => 'invalid-signature',
            'transaction_status' => 'settlement',
            'fraud_status' => 'accept',
        ]);

        $response->assertStatus(403);
    }

    public function test_xendit_webhook_rejects_invalid_callback_token(): void
    {
        $transaction = Transaction::factory()->create([
            'payment_status' => 'pending',
            'payment_method' => 'xendit',
        ]);

        $response = $this->postJson('/api/webhooks/xendit', [
            'external_id' => $transaction->invoice,
            'status' => 'PAID',
            'id' => 'xendit-payment-bad',
        ], [
            'X-CALLBACK-TOKEN' => 'wrong-token',
        ]);

        $response->assertStatus(403);
    }

    public function test_full_flow_mixed_transaction_with_membership_and_products_via_midtrans_webhook(): void
    {
        $customer = Customer::factory()->create();
        $registrationPlan = MembershipPlan::factory()->registration()->create([
            'price' => 100000,
        ]);

        // Create a transaction that includes both products and a membership plan
        $transaction = Transaction::factory()->withMembershipPlan($registrationPlan)->create([
            'customer_id' => $customer->id,
            'grand_total' => 350000, // products + membership
            'payment_status' => 'pending',
            'payment_method' => 'midtrans',
        ]);

        // Verify no membership exists yet
        $this->assertDatabaseCount('customer_memberships', 0);

        // Simulate Midtrans webhook with settlement status
        $orderId = $transaction->invoice;
        $statusCode = '200';
        $grossAmount = (string) $transaction->grand_total;
        $signatureKey = hash('sha512', $orderId.$statusCode.$grossAmount.'test-server-key');

        $response = $this->postJson('/api/webhooks/midtrans', [
            'order_id' => $orderId,
            'status_code' => $statusCode,
            'gross_amount' => $grossAmount,
            'signature_key' => $signatureKey,
            'transaction_status' => 'settlement',
            'fraud_status' => 'accept',
            'transaction_id' => 'midtrans-full-flow',
        ]);

        $response->assertOk()->assertJson(['status' => 'success']);

        // Verify transaction is now paid
        $transaction->refresh();
        $this->assertSame('paid', $transaction->payment_status);

        // Verify membership was activated
        $membership = CustomerMembership::where('customer_id', $customer->id)
            ->where('membership_plan_id', $registrationPlan->id)
            ->first();

        $this->assertNotNull($membership);
        $this->assertSame('active', $membership->status);
        $this->assertSame($transaction->id, $membership->transaction_id);
        $this->assertSame($registrationPlan->session_quota, $membership->session_quota);
        $this->assertSame(0, $membership->session_used);
        $this->assertEquals(now()->toDateString(), $membership->start_date->toDateString());
    }

    public function test_full_flow_mixed_transaction_with_membership_via_xendit_webhook(): void
    {
        $customer = Customer::factory()->create();
        $plan = MembershipPlan::factory()->trial()->create([
            'price' => 50000,
            'session_quota' => 1,
            'duration_days' => 7,
        ]);

        // Create a transaction with membership plan
        $transaction = Transaction::factory()->withMembershipPlan($plan)->create([
            'customer_id' => $customer->id,
            'grand_total' => 250000, // products + membership
            'payment_status' => 'pending',
            'payment_method' => 'xendit',
        ]);

        // Verify no membership exists yet
        $this->assertDatabaseCount('customer_memberships', 0);

        // Simulate Xendit webhook with PAID status
        $response = $this->postJson('/api/webhooks/xendit', [
            'external_id' => $transaction->invoice,
            'status' => 'PAID',
            'id' => 'xendit-full-flow-123',
        ], [
            'X-CALLBACK-TOKEN' => 'test-callback-token',
        ]);

        $response->assertOk()->assertJson(['status' => 'success']);

        // Verify transaction is now paid
        $transaction->refresh();
        $this->assertSame('paid', $transaction->payment_status);

        // Verify membership was activated with correct data
        $membership = CustomerMembership::where('customer_id', $customer->id)
            ->where('membership_plan_id', $plan->id)
            ->first();

        $this->assertNotNull($membership);
        $this->assertSame('active', $membership->status);
        $this->assertSame($transaction->id, $membership->transaction_id);
        $this->assertSame(1, $membership->session_quota);
        $this->assertSame(0, $membership->session_used);
        $this->assertEquals(now()->toDateString(), $membership->start_date->toDateString());
        $this->assertEquals(now()->addDays(7)->toDateString(), $membership->end_date->toDateString());
    }

    public function test_webhook_gracefully_handles_transaction_without_customer(): void
    {
        $plan = MembershipPlan::factory()->registration()->create();

        $transaction = Transaction::factory()->withMembershipPlan($plan)->create([
            'customer_id' => null,
            'payment_status' => 'pending',
            'payment_method' => 'midtrans',
        ]);

        $orderId = $transaction->invoice;
        $statusCode = '200';
        $grossAmount = (string) $transaction->grand_total;
        $signatureKey = hash('sha512', $orderId.$statusCode.$grossAmount.'test-server-key');

        $response = $this->postJson('/api/webhooks/midtrans', [
            'order_id' => $orderId,
            'status_code' => $statusCode,
            'gross_amount' => $grossAmount,
            'signature_key' => $signatureKey,
            'transaction_status' => 'settlement',
            'fraud_status' => 'accept',
            'transaction_id' => 'midtrans-no-customer',
        ]);

        // Webhook should still succeed (not crash), just skip membership activation
        $response->assertOk()->assertJson(['status' => 'success']);

        $this->assertDatabaseCount('customer_memberships', 0);
    }

    public function test_xendit_webhook_does_not_activate_membership_on_expired_status(): void
    {
        $customer = Customer::factory()->create();
        $plan = MembershipPlan::factory()->registration()->create();

        $transaction = Transaction::factory()->withMembershipPlan($plan)->create([
            'customer_id' => $customer->id,
            'payment_status' => 'pending',
            'payment_method' => 'xendit',
        ]);

        $response = $this->postJson('/api/webhooks/xendit', [
            'external_id' => $transaction->invoice,
            'status' => 'EXPIRED',
            'id' => 'xendit-expired-123',
        ], [
            'X-CALLBACK-TOKEN' => 'test-callback-token',
        ]);

        $response->assertOk()->assertJson(['status' => 'success']);

        $transaction->refresh();
        $this->assertSame('failed', $transaction->payment_status);

        $this->assertDatabaseCount('customer_memberships', 0);
    }

    public function test_midtrans_webhook_returns_404_for_nonexistent_transaction(): void
    {
        $orderId = 'TRX-NONEXISTENT';
        $statusCode = '200';
        $grossAmount = '100000';
        $signatureKey = hash('sha512', $orderId.$statusCode.$grossAmount.'test-server-key');

        $response = $this->postJson('/api/webhooks/midtrans', [
            'order_id' => $orderId,
            'status_code' => $statusCode,
            'gross_amount' => $grossAmount,
            'signature_key' => $signatureKey,
            'transaction_status' => 'settlement',
            'fraud_status' => 'accept',
        ]);

        $response->assertStatus(404);
    }

    public function test_xendit_webhook_returns_404_for_nonexistent_transaction(): void
    {
        $response = $this->postJson('/api/webhooks/xendit', [
            'external_id' => 'TRX-NONEXISTENT',
            'status' => 'PAID',
            'id' => 'xendit-notfound',
        ], [
            'X-CALLBACK-TOKEN' => 'test-callback-token',
        ]);

        $response->assertStatus(404);
    }
}
