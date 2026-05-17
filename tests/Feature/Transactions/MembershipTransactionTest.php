<?php

namespace Tests\Feature\Transactions;

use App\Models\Cart;
use App\Models\CashierShift;
use App\Models\Category;
use App\Models\Customer;
use App\Models\MembershipPlan;
use App\Models\Product;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class MembershipTransactionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Permission::firstOrCreate([
            'name' => 'transactions-access',
            'guard_name' => 'web',
        ]);
        Permission::firstOrCreate([
            'name' => 'cashier-shifts-access',
            'guard_name' => 'web',
        ]);
        Permission::firstOrCreate([
            'name' => 'cashier-shifts-open',
            'guard_name' => 'web',
        ]);
        Permission::firstOrCreate([
            'name' => 'cashier-shifts-close',
            'guard_name' => 'web',
        ]);
    }

    public function test_transaction_index_includes_active_membership_plans(): void
    {
        $cashier = $this->createCashier();
        $this->openShiftFor($cashier);

        $activePlan = MembershipPlan::factory()->create(['is_active' => true]);
        MembershipPlan::factory()->create(['is_active' => false]);

        $response = $this
            ->actingAs($cashier)
            ->get(route('transactions.index'));

        $response
            ->assertOk()
            ->assertInertia(function (Assert $page) use ($activePlan) {
                $page->component('Dashboard/Transactions/Index')
                    ->has('membershipPlans', 1)
                    ->where('membershipPlans.0.id', $activePlan->id)
                    ->where('membershipPlans.0.name', $activePlan->name);
            });
    }

    public function test_cashier_can_create_transaction_with_membership_plan(): void
    {
        $cashier = $this->createCashier();
        $this->openShiftFor($cashier);
        $customer = Customer::create([
            'name' => 'Member Baru',
            'no_telp' => '62812345678',
            'address' => 'Jl. Membership No. 1',
        ]);
        $product = $this->createProduct();
        $plan = MembershipPlan::factory()->create([
            'is_active' => true,
            'price' => 200000,
        ]);

        Cart::create([
            'cashier_id' => $cashier->id,
            'product_id' => $product->id,
            'qty' => 1,
            'price' => $product->sell_price,
        ]);

        $grandTotal = $product->sell_price + $plan->price;

        $response = $this
            ->actingAs($cashier)
            ->post(route('transactions.store'), [
                'customer_id' => $customer->id,
                'discount' => 0,
                'grand_total' => $grandTotal,
                'cash' => $grandTotal,
                'change' => 0,
                'membership_plan_id' => $plan->id,
            ]);

        $transaction = Transaction::latest('id')->first();

        $this->assertNotNull($transaction);
        $response->assertRedirect(route('transactions.print', $transaction->invoice));
        $this->assertSame($plan->id, $transaction->membership_plan_id);
        $this->assertTrue($transaction->hasMembershipItem());
        $this->assertSame($plan->id, $transaction->membershipPlan->id);
    }

    public function test_cashier_can_create_transaction_without_membership_plan(): void
    {
        $cashier = $this->createCashier();
        $this->openShiftFor($cashier);
        $customer = Customer::create([
            'name' => 'Regular Customer',
            'no_telp' => '62899887766',
            'address' => 'Jl. Biasa No. 2',
        ]);
        $product = $this->createProduct();

        Cart::create([
            'cashier_id' => $cashier->id,
            'product_id' => $product->id,
            'qty' => 1,
            'price' => $product->sell_price,
        ]);

        $response = $this
            ->actingAs($cashier)
            ->post(route('transactions.store'), [
                'customer_id' => $customer->id,
                'discount' => 0,
                'grand_total' => $product->sell_price,
                'cash' => $product->sell_price,
                'change' => 0,
            ]);

        $transaction = Transaction::latest('id')->first();

        $this->assertNotNull($transaction);
        $this->assertNull($transaction->membership_plan_id);
        $this->assertFalse($transaction->hasMembershipItem());
    }

    public function test_transaction_rejects_inactive_membership_plan(): void
    {
        $cashier = $this->createCashier();
        $this->openShiftFor($cashier);
        $customer = Customer::create([
            'name' => 'Inactive Plan Customer',
            'no_telp' => '62877665544',
            'address' => 'Jl. Inactive No. 3',
        ]);
        $product = $this->createProduct();
        $inactivePlan = MembershipPlan::factory()->create(['is_active' => false]);

        Cart::create([
            'cashier_id' => $cashier->id,
            'product_id' => $product->id,
            'qty' => 1,
            'price' => $product->sell_price,
        ]);

        $response = $this
            ->from(route('transactions.index'))
            ->actingAs($cashier)
            ->post(route('transactions.store'), [
                'customer_id' => $customer->id,
                'discount' => 0,
                'grand_total' => $product->sell_price + $inactivePlan->price,
                'cash' => $product->sell_price + $inactivePlan->price,
                'change' => 0,
                'membership_plan_id' => $inactivePlan->id,
            ]);

        $response->assertRedirect(route('transactions.index'));
        $response->assertSessionHas('error', 'Paket membership tidak ditemukan atau tidak aktif.');
        $this->assertDatabaseCount('transactions', 0);
    }

    public function test_transaction_rejects_nonexistent_membership_plan(): void
    {
        $cashier = $this->createCashier();
        $this->openShiftFor($cashier);
        $customer = Customer::create([
            'name' => 'Bad Plan Customer',
            'no_telp' => '62866554433',
            'address' => 'Jl. NotFound No. 4',
        ]);
        $product = $this->createProduct();

        Cart::create([
            'cashier_id' => $cashier->id,
            'product_id' => $product->id,
            'qty' => 1,
            'price' => $product->sell_price,
        ]);

        $response = $this
            ->from(route('transactions.index'))
            ->actingAs($cashier)
            ->post(route('transactions.store'), [
                'customer_id' => $customer->id,
                'discount' => 0,
                'grand_total' => $product->sell_price,
                'cash' => $product->sell_price,
                'change' => 0,
                'membership_plan_id' => 99999,
            ]);

        $response->assertRedirect(route('transactions.index'));
        $response->assertSessionHasErrors('membership_plan_id');
        $this->assertDatabaseCount('transactions', 0);
    }

    public function test_transaction_model_has_membership_item_helper(): void
    {
        $plan = MembershipPlan::factory()->create();
        $transactionWithPlan = Transaction::factory()->withMembershipPlan($plan)->create();
        $transactionWithoutPlan = Transaction::factory()->create();

        $this->assertTrue($transactionWithPlan->hasMembershipItem());
        $this->assertFalse($transactionWithoutPlan->hasMembershipItem());
    }

    public function test_transaction_model_membership_plan_relationship(): void
    {
        $plan = MembershipPlan::factory()->create();
        $transaction = Transaction::factory()->withMembershipPlan($plan)->create();

        $this->assertNotNull($transaction->membershipPlan);
        $this->assertSame($plan->id, $transaction->membershipPlan->id);
        $this->assertSame($plan->name, $transaction->membershipPlan->name);
    }

    public function test_print_page_includes_membership_plan_data(): void
    {
        $cashier = $this->createCashier();
        $this->openShiftFor($cashier);
        $plan = MembershipPlan::factory()->create(['name' => 'Paket Bulanan']);
        $transaction = Transaction::factory()->withMembershipPlan($plan)->create([
            'cashier_id' => $cashier->id,
        ]);

        $response = $this
            ->actingAs($cashier)
            ->get(route('transactions.print', $transaction->invoice));

        $response
            ->assertOk()
            ->assertInertia(function (Assert $page) use ($plan) {
                $page->component('Dashboard/Transactions/Print')
                    ->where('transaction.membership_plan.id', $plan->id)
                    ->where('transaction.membership_plan.name', 'Paket Bulanan');
            });
    }

    protected function createCashier(): User
    {
        $user = User::factory()->create();
        $user->givePermissionTo([
            'transactions-access',
            'cashier-shifts-access',
            'cashier-shifts-open',
            'cashier-shifts-close',
        ]);

        return $user;
    }

    protected function openShiftFor(User $cashier): CashierShift
    {
        return CashierShift::create([
            'user_id' => $cashier->id,
            'opened_by' => $cashier->id,
            'opened_at' => now(),
            'opening_cash' => 100000,
            'expected_cash' => 100000,
            'status' => 'open',
        ]);
    }

    protected function createProduct(): Product
    {
        $category = Category::create([
            'name' => 'Alat Panahan',
            'description' => 'Kategori alat panahan',
            'image' => 'category.png',
        ]);

        return Product::create([
            'category_id' => $category->id,
            'image' => 'product.png',
            'barcode' => 'BRCD-'.Str::upper(Str::random(10)),
            'title' => 'Busur Recurve',
            'description' => 'Busur recurve untuk latihan.',
            'buy_price' => 150000,
            'sell_price' => 200000,
            'stock' => 10,
        ]);
    }
}
