<?php

namespace App\Http\Controllers\Api;

use App\Models\Payment;
use App\Services\SmsService;
use App\Services\SonicPesaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SonicPesaWebhookController extends Controller
{
    protected SonicPesaService $sonicPesa;

    public function __construct(SonicPesaService $sonicPesa)
    {
        $this->sonicPesa = $sonicPesa;
    }

    /**
     * Handle incoming webhook notification from SonicPesa.
     */
    public function handle(Request $request): JsonResponse
    {
        $rawPayload = $request->getContent();
        $signature = $request->header('X-SonicPesa-Signature')
            ?? $request->header('X-Signature')
            ?? $request->header('Signature')
            ?? $request->input('signature');

        Log::info('SonicPesa Webhook incoming notification', [
            'has_signature' => ! empty($signature),
            'content_length' => strlen($rawPayload),
        ]);

        // Verify webhook signature if configured
        if (! $this->sonicPesa->verifyWebhookSignature($rawPayload, $signature)) {
            Log::warning('SonicPesa Webhook rejected: invalid HMAC signature', [
                'provided_signature' => $signature,
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Invalid webhook signature',
            ], 401);
        }

        $payload = $request->all();
        $data = $this->sonicPesa->extractWebhookData($payload);

        $orderId = $data['order_id'];
        if (empty($orderId)) {
            Log::warning('SonicPesa Webhook payload missing order_id / reference', ['payload' => $payload]);

            return response()->json([
                'status' => 'error',
                'message' => 'Missing order_id or reference in webhook payload',
            ], 422);
        }

        /** @var Payment|null $payment */
        $payment = Payment::where('sonicpesa_order_id', $orderId)
            ->orWhere('reference', $orderId)
            ->first();

        if (! $payment) {
            Log::warning("SonicPesa Webhook: Payment record not found for order {$orderId}", ['data' => $data]);

            return response()->json([
                'status' => 'not_found',
                'message' => "Payment record with order_id {$orderId} not found",
            ], 404);
        }

        // Save latest webhook response
        $payment->sonicpesa_response = $payload;

        // Process status idempotently
        if ($data['is_completed']) {
            if ($payment->isCompleted()) {
                Log::info("SonicPesa Webhook: Payment {$payment->id} already completed, skipping activation");

                return response()->json([
                    'status' => 'success',
                    'message' => 'Payment already completed previously',
                    'order_id' => $orderId,
                ]);
            }

            DB::transaction(function () use ($payment, $data, $payload) {
                $payment->forceFill([
                    'status' => Payment::STATUS_COMPLETED,
                    'external_transaction_id' => $data['transaction_id'] ?? $payment->external_transaction_id ?? ('SP-'.strtoupper(bin2hex(random_bytes(5)))),
                    'paid_at' => now(),
                    'sonicpesa_response' => $payload,
                ])->save();

                $org = $payment->organization;
                if ($org) {
                    $org->activateSubscription($payment->plan, $payment->billing_cycle, $payment);
                }
            });

            Log::info("SonicPesa Webhook: Successfully processed payment {$payment->id} and activated subscription for Org {$payment->organization_id}");

            $org = $payment->organization;
            if ($org) {
                try {
                    $admins = $org->users()->where('role', 'admin')->where('active', true)->get();
                    $amount = number_format((int) $payment->amount);
                    SmsService::notifyOrgAdmins(
                        $org,
                        "SmartAttend: Payment of TZS {$amount} was successful. Your ".ucfirst((string) $payment->plan)." subscription for {$org->name} is now active. Thank you."
                    );
                } catch (\Throwable $e) {
                    Log::warning('Failed to SMS payment success (webhook): '.$e->getMessage());
                }
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Payment verified and subscription activated successfully',
                'order_id' => $orderId,
                'payment_id' => $payment->id,
            ]);
        }

        if ($data['is_failed']) {
            $payment->markAsFailed($payload['message'] ?? $payload['error'] ?? 'SonicPesa transaction failed or was cancelled by user');
            Log::info("SonicPesa Webhook: Payment {$payment->id} marked as failed");

            return response()->json([
                'status' => 'failed',
                'message' => 'Payment marked as failed',
                'order_id' => $orderId,
            ]);
        }

        // For pending or other intermediate states
        $payment->save();

        return response()->json([
            'status' => 'pending',
            'message' => 'Payment status updated',
            'order_id' => $orderId,
        ]);
    }
}
