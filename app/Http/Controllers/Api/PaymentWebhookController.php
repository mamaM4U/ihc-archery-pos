<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PaymentSetting;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PaymentWebhookController extends Controller
{
    /**
     * Handle Midtrans notification webhook
     * URL: POST /api/webhooks/midtrans
     */
    public function midtrans(Request $request)
    {
        Log::info('Midtrans Webhook Received', $request->all());

        try {
            $paymentSetting = PaymentSetting::first();

            if (! $paymentSetting || ! $paymentSetting->midtrans_enabled) {
                return response()->json(['status' => 'error', 'message' => 'Midtrans not configured'], 400);
            }

            // Get notification data
            $orderId     = $request->input('order_id');
            $statusCode  = $request->input('status_code');
            $grossAmount = $request->input('gross_amount');
            $serverKey   = $paymentSetting->midtrans_server_key;

            // Verify signature
            $signatureKey      = $request->input('signature_key');
            $expectedSignature = hash('sha512', $orderId . $statusCode . $grossAmount . $serverKey);

            if ($signatureKey !== $expectedSignature) {
                Log::warning('Midtrans Webhook: Invalid signature', [
                    'order_id' => $orderId,
                    'received' => $signatureKey,
                    'expected' => $expectedSignature,
                ]);
                return response()->json(['status' => 'error', 'message' => 'Invalid signature'], 403);
            }

            // Find transaction by invoice (order_id)
            $transaction = Transaction::where('invoice', $orderId)->first();

            if (! $transaction) {
                Log::warning('Midtrans Webhook: Transaction not found', ['order_id' => $orderId]);
                return response()->json(['status' => 'error', 'message' => 'Transaction not found'], 404);
            }

            // Map Midtrans status to our status
            $transactionStatus = $request->input('transaction_status');
            $fraudStatus       = $request->input('fraud_status');

            $newStatus = $this->mapMidtransStatus($transactionStatus, $fraudStatus);

            $transaction->update([
                'payment_status'    => $newStatus,
                'payment_reference' => $request->input('transaction_id') ?: $transaction->payment_reference,
            ]);

            Log::info('Midtrans Webhook: Transaction updated', [
                'order_id' => $orderId,
                'status'   => $newStatus,
            ]);

            return response()->json(['status' => 'success']);

        } catch (\Exception $e) {
            Log::error('Midtrans Webhook Error', [
                'message' => $e->getMessage(),
                'trace'   => $e->getTraceAsString(),
            ]);
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Handle Xendit notification webhook
     * URL: POST /api/webhooks/xendit
     */
    public function xendit(Request $request)
    {
        Log::info('Xendit Webhook Received', $request->all());

        try {
            $paymentSetting = PaymentSetting::first();

            if (! $paymentSetting || ! $paymentSetting->xendit_enabled) {
                return response()->json(['status' => 'error', 'message' => 'Xendit not configured'], 400);
            }

            $callbackToken = $request->header('X-CALLBACK-TOKEN');
            $expectedToken = $paymentSetting->xendit_callback_token ?: config('services.xendit.callback_token');

            if (blank($expectedToken)) {
                Log::warning('Xendit Webhook: Callback token is not configured.');
                return response()->json(['status' => 'error', 'message' => 'Xendit callback token is not configured'], 400);
            }

            if (! is_string($callbackToken) || ! hash_equals($expectedToken, $callbackToken)) {
                Log::warning('Xendit Webhook: Invalid callback token', [
                    'external_id' => $request->input('external_id'),
                ]);
                return response()->json(['status' => 'error', 'message' => 'Invalid callback token'], 403);
            }

            $externalId = $request->input('external_id'); // This is our invoice number
            $status     = $request->input('status');
            $paymentId  = $request->input('id');

            if (blank($externalId) || blank($status) || blank($paymentId)) {
                return response()->json(['status' => 'error', 'message' => 'Invalid payload'], 422);
            }

            // Find transaction by invoice
            $transaction = Transaction::where('invoice', $externalId)->first();

            if (! $transaction) {
                Log::warning('Xendit Webhook: Transaction not found', ['external_id' => $externalId]);
                return response()->json(['status' => 'error', 'message' => 'Transaction not found'], 404);
            }

            // Map Xendit status to our status
            $newStatus = $this->mapXenditStatus($status);

            $transaction->update([
                'payment_status'    => $newStatus,
                'payment_reference' => $paymentId ?: $transaction->payment_reference,
            ]);

            Log::info('Xendit Webhook: Transaction updated', [
                'external_id' => $externalId,
                'status'      => $newStatus,
            ]);

            return response()->json(['status' => 'success']);

        } catch (\Exception $e) {
            Log::error('Xendit Webhook Error', [
                'message' => $e->getMessage(),
                'trace'   => $e->getTraceAsString(),
            ]);
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Map Midtrans transaction status to our payment status
     */
    private function mapMidtransStatus(string $transactionStatus, ?string $fraudStatus = null): string
    {
        // Handle fraud status first
        if ($fraudStatus === 'challenge' || $fraudStatus === 'deny') {
            return 'failed';
        }

        return match ($transactionStatus) {
            'capture', 'settlement'    => 'paid',
            'pending' => 'pending',
            'deny', 'cancel', 'expire' => 'failed',
            default   => 'pending',
        };
    }

    /**
     * Map Xendit invoice status to our payment status
     */
    private function mapXenditStatus(string $status): string
    {
        return match (strtoupper($status)) {
            'PAID', 'SETTLED'   => 'paid',
            'PENDING' => 'pending',
            'EXPIRED', 'FAILED' => 'failed',
            default   => 'pending',
        };
    }
}
