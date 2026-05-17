<?php

namespace App\Http\Controllers\Customer;

use App\Exceptions\PaymentGatewayException;
use App\Http\Controllers\Controller;
use App\Models\CustomerMembership;
use App\Models\MembershipPlan;
use App\Models\PaymentSetting;
use App\Models\Transaction;
use App\Services\MembershipService;
use App\Services\Payments\PaymentGatewayManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class MembershipController extends Controller
{
    public function __construct(
        public MembershipService $membershipService
    ) {}

    /**
     * Display the customer's current membership status.
     */
    public function index(Request $request): Response
    {
        $customer = Auth::guard('customer')->user();

        $membership = $this->membershipService->getActiveMembership($customer);

        $sessionUsages = [];
        $isExpiringSoon = false;

        if ($membership) {
            $membership->append(['remaining_days', 'is_expiring_soon']);

            $sessionUsages = $membership->sessionUsages()
                ->with('checkedInBy:id,name')
                ->orderByDesc('checked_in_at')
                ->get();

            $isExpiringSoon = $membership->is_expiring_soon;
        }

        return Inertia::render('Customer/Membership/Index', [
            'membership' => $membership,
            'sessionUsages' => $sessionUsages,
            'isExpiringSoon' => $isExpiringSoon,
        ]);
    }

    /**
     * Display available membership plans grouped by category.
     */
    public function plans(Request $request): Response
    {
        $customer = Auth::guard('customer')->user();

        $plans = MembershipPlan::active()
            ->orderBy('category')
            ->orderBy('price')
            ->get()
            ->groupBy('category');

        $membership = $this->membershipService->getActiveMembership($customer);
        $hasRegistration = $this->membershipService->hasRegistration($customer);

        $currentMembership = null;
        if ($membership) {
            $membership->append(['remaining_days', 'is_expiring_soon']);
            $currentMembership = $membership;
        }

        return Inertia::render('Customer/Membership/Plans', [
            'plans' => $plans,
            'currentMembership' => $currentMembership,
            'hasRegistration' => $hasRegistration,
        ]);
    }

    /**
     * Purchase a membership plan.
     *
     * Creates a transaction with the selected membership plan and redirects
     * to the payment gateway for online payment.
     */
    public function purchase(Request $request, PaymentGatewayManager $paymentGatewayManager): JsonResponse
    {
        $request->validate([
            'membership_plan_id' => ['required', 'integer', 'exists:membership_plans,id'],
        ]);

        $customer = Auth::guard('customer')->user();

        // Validate plan exists and is active
        $plan = MembershipPlan::where('id', $request->membership_plan_id)
            ->where('is_active', true)
            ->first();

        if (! $plan) {
            return response()->json([
                'message' => 'Paket membership tidak ditemukan atau tidak aktif.',
            ], 422);
        }

        // Validate registration prerequisite for monthly/family plans
        $requiresRegistration = in_array($plan->category, [
            'monthly_no_equipment',
            'monthly_with_equipment',
            'family',
        ]);

        if ($requiresRegistration && ! $this->membershipService->hasRegistration($customer)) {
            return response()->json([
                'message' => 'Anda harus memiliki membership registrasi terlebih dahulu sebelum membeli paket bulanan.',
            ], 422);
        }

        // Get payment settings and determine gateway
        $paymentSetting = PaymentSetting::first();

        if (! $paymentSetting) {
            return response()->json([
                'message' => 'Pembayaran online belum dikonfigurasi. Silakan hubungi admin.',
            ], 422);
        }

        $gateway = $paymentSetting->default_gateway;

        // Ensure the gateway is ready (skip bank_transfer for customer portal)
        if (! $gateway || $gateway === PaymentSetting::GATEWAY_BANK_TRANSFER || ! $paymentSetting->isGatewayReady($gateway)) {
            // Try to find any available online gateway
            $gateway = null;
            if ($paymentSetting->isGatewayReady(PaymentSetting::GATEWAY_MIDTRANS)) {
                $gateway = PaymentSetting::GATEWAY_MIDTRANS;
            } elseif ($paymentSetting->isGatewayReady(PaymentSetting::GATEWAY_XENDIT)) {
                $gateway = PaymentSetting::GATEWAY_XENDIT;
            }

            if (! $gateway) {
                return response()->json([
                    'message' => 'Tidak ada gateway pembayaran online yang tersedia. Silakan hubungi admin.',
                ], 422);
            }
        }

        // Generate invoice number
        $length = 10;
        $random = '';
        for ($i = 0; $i < $length; $i++) {
            $random .= rand(0, 1) ? rand(0, 9) : chr(rand(ord('a'), ord('z')));
        }
        $invoice = 'TRX-'.Str::upper($random);

        // Create transaction
        $transaction = DB::transaction(function () use ($customer, $plan, $invoice) {
            return Transaction::create([
                'cashier_id' => null,
                'cashier_shift_id' => null,
                'customer_id' => $customer->id,
                'invoice' => $invoice,
                'cash' => 0,
                'change' => 0,
                'discount' => 0,
                'shipping_cost' => 0,
                'grand_total' => $plan->price,
                'payment_method' => 'online',
                'payment_status' => 'pending',
                'membership_plan_id' => $plan->id,
            ]);
        });

        // Create payment via gateway
        try {
            $paymentResponse = $paymentGatewayManager->createPayment($transaction, $gateway, $paymentSetting);

            $transaction->update([
                'payment_reference' => $paymentResponse['reference'] ?? null,
                'payment_url' => $paymentResponse['payment_url'] ?? null,
            ]);

            return response()->json([
                'message' => 'Transaksi berhasil dibuat.',
                'invoice' => $transaction->invoice,
                'payment_url' => $paymentResponse['payment_url'] ?? null,
                'payment_reference' => $paymentResponse['reference'] ?? null,
            ]);
        } catch (PaymentGatewayException $e) {
            Log::error('Customer membership purchase payment gateway error', [
                'customer_id' => $customer->id,
                'membership_plan_id' => $plan->id,
                'invoice' => $invoice,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Gagal membuat pembayaran: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the customer's membership history (past and current memberships).
     */
    public function history(Request $request): Response
    {
        $customer = Auth::guard('customer')->user();

        $memberships = CustomerMembership::where('customer_id', $customer->id)
            ->with('membershipPlan')
            ->orderByDesc('created_at')
            ->get();

        return Inertia::render('Customer/Membership/History', [
            'memberships' => $memberships,
        ]);
    }
}
