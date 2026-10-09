<?php

namespace App\Http\Controllers\Api;

use App\Models\Organization;
use App\Models\Payment;
use App\Services\MobilePaymentService;
use App\Services\SmsService;
use App\Services\SonicPesaService;
use App\Support\PlanLimits;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class PaymentController extends Controller
{
    protected SonicPesaService $sonicPesa;

    public function __construct(SonicPesaService $sonicPesa)
    {
        $this->sonicPesa = $sonicPesa;
    }

    public function initiate(Request $request): JsonResponse
    {
        $user = $request->user();
        $org = $user->organization;

        $validator = Validator::make($request->all(), [
            'plan' => ['required', Rule::exists('packages', 'code')->where('active', true)],
            'billing_cycle' => ['nullable', 'string', Rule::in(['monthly', 'annual'])],
            'mobile_provider' => ['required', 'string', Rule::in(['mpesa', 'tigopesa', 'airtelmoney', 'halopesa'])],
            'phone_number' => ['required', 'string', 'min:9', 'max:20'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Please provide valid payment details.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $validated = $validator->validated();
        $planCode = $validated['plan'];
        $cycle = strtolower($validated['billing_cycle'] ?? 'monthly');
        $provider = strtolower($validated['mobile_provider']);
        $rawPhone = $validated['phone_number'];
        $normalizedPhone = MobilePaymentService::normalizePhoneNumber($rawPhone);

        $calculation = PlanLimits::calculateAmount($planCode, $cycle, (int) $org->discount_percent);
        $reference = MobilePaymentService::generateReference();
        $ussd = MobilePaymentService::PROVIDER_USSD[$provider] ?? '*150*00#';

        $payment = Payment::create([
            'organization_id' => $org->id,
            'user_id' => $user->id,
            'plan' => $planCode,
            'billing_cycle' => $cycle,
            'amount' => $calculation['final_amount'],
            'original_amount' => $calculation['original_amount'],
            'discount_amount' => $calculation['discount_amount'],
            'currency' => $calculation['currency'],
            'gateway' => Payment::GATEWAY_SONICPESA,
            'mobile_provider' => $provider,
            'phone_number' => $normalizedPhone,
            'reference' => $reference,
            'ussd_code' => $ussd,
            'status' => Payment::STATUS_PENDING,
            'metadata' => [
                'initiated_by' => $user->name,
                'user_email' => $user->email,
                'discount_percent' => $calculation['discount_percent'],
                'package_name' => PlanLimits::package($planCode)?->name ?? ucfirst($planCode),
            ],
        ]);

        // Initiate remote SonicPesa order / USSD push
        $orderResult = $this->sonicPesa->createOrder([
            'order_id' => $reference,
            'amount' => $payment->amount,
            'currency' => $payment->currency,
            'buyer_name' => $user->name,
            'buyer_email' => $user->email,
            'buyer_phone' => $normalizedPhone,
            'metadata' => [
                'payment_id' => $payment->id,
                'org_id' => $org->id,
                'plan' => $planCode,
            ],
        ]);

        // Surface gateway failures instead of pretending the prompt was sent.
        if (empty($orderResult['success'])) {
            $payment->markAsFailed($orderResult['message'] ?? 'Payment gateway could not create the order.');

            return response()->json([
                'message' => $orderResult['message'] ?? 'Could not initiate mobile payment. Please try again.',
                'payment' => $this->formatPayment($payment->fresh()),
                'gateway' => Payment::GATEWAY_SONICPESA,
            ], 502);
        }

        $payment->forceFill([
            'sonicpesa_order_id' => $orderResult['order_id'] ?? $reference,
            'sonicpesa_checkout_url' => $orderResult['checkout_url'] ?? null,
            'sonicpesa_qr_code' => $orderResult['qr_code'] ?? null,
            'sonicpesa_response' => $orderResult['raw'] ?? null,
        ])->save();

        $instructions = MobilePaymentService::getInstructions(
            $provider,
            $payment->amount,
            $normalizedPhone,
            $reference
        );

        return response()->json([
            'message' => $orderResult['message'] ?? 'Mobile payment prompt sent. Please check your phone to approve.',
            'payment' => $this->formatPayment($payment->fresh()),
            'instructions' => $instructions,
            'checkout_url' => $orderResult['checkout_url'] ?? null,
            'qr_code' => $orderResult['qr_code'] ?? null,
            'gateway' => Payment::GATEWAY_SONICPESA,
        ], 201);
    }

    public function verify(Payment $payment, Request $request): JsonResponse
    {
        $org = $request->user()->organization;
        if ($payment->organization_id !== $org->id) {
            return response()->json(['message' => 'Payment record not found for this organization.'], 404);
        }

        if ($payment->isPending()) {
            $orderId = $payment->sonicpesa_order_id ?? $payment->reference;

            // Query live status from SonicPesa if configured
            if ($this->sonicPesa->isConfigured() && $orderId) {
                $statusCheck = $this->sonicPesa->getOrderStatus($orderId);

                if ($statusCheck['is_completed']) {
                    $payment->markAsCompleted($statusCheck['external_transaction_id'], [
                        'sonicpesa_checked_at' => now()->toIso8601String(),
                        'sonicpesa_status_response' => $statusCheck['raw'],
                    ]);
                    $org->activateSubscription($payment->plan, $payment->billing_cycle, $payment);
                    $this->notifyPaymentSuccess($org, $payment);
                } elseif ($statusCheck['is_failed']) {
                    $payment->markAsFailed($statusCheck['raw']['message'] ?? 'Payment failed on SonicPesa');
                }
            } else {
                // In local sandbox or unconfigured mode, mark as completed for seamless testing
                $payment->markAsCompleted();
                $org->activateSubscription($payment->plan, $payment->billing_cycle, $payment);
                $this->notifyPaymentSuccess($org, $payment);
            }
        }

        return response()->json([
            'message' => $payment->isCompleted()
                ? "Payment verified! Your {$payment->provider_name} payment was successful and your {$payment->plan} plan is active."
                : ($payment->status === Payment::STATUS_FAILED ? 'Payment failed or was cancelled.' : 'Payment is still pending approval on mobile phone.'),
            'payment' => $this->formatPayment($payment->fresh()),
            'subscription' => SubscriptionController::payload($org->fresh()),
        ]);
    }

    public function simulate(Payment $payment, Request $request): JsonResponse
    {
        $org = $request->user()->organization;
        if ($payment->organization_id !== $org->id) {
            return response()->json(['message' => 'Payment record not found for this organization.'], 404);
        }

        $payment->markAsCompleted('SIM-'.strtoupper(bin2hex(random_bytes(5))));
        $org->activateSubscription($payment->plan, $payment->billing_cycle, $payment);
        $this->notifyPaymentSuccess($org, $payment);

        return response()->json([
            'message' => "Payment successful! Your {$payment->plan} subscription is now active.",
            'payment' => $this->formatPayment($payment->fresh()),
            'subscription' => SubscriptionController::payload($org->fresh()),
        ]);
    }

    public function index(Request $request): JsonResponse
    {
        $org = $request->user()->organization;

        $payments = $org->payments()
            ->latest()
            ->paginate(15)
            ->through(fn (Payment $p) => $this->formatPayment($p));

        return response()->json([
            'payments' => $payments->items(),
            'meta' => [
                'current_page' => $payments->currentPage(),
                'per_page' => $payments->perPage(),
                'total' => $payments->total(),
                'last_page' => $payments->lastPage(),
            ],
            'total_spent' => (int) $org->payments()->where('status', Payment::STATUS_COMPLETED)->sum('amount'),
            'total_spent_formatted' => 'TZS '.number_format((int) $org->payments()->where('status', Payment::STATUS_COMPLETED)->sum('amount')),
        ]);
    }

    public function show(Payment $payment, Request $request): JsonResponse
    {
        $org = $request->user()->organization;
        if ($payment->organization_id !== $org->id) {
            return response()->json(['message' => 'Payment not found.'], 404);
        }

        return response()->json([
            'payment' => $this->formatPayment($payment),
        ]);
    }

    private function notifyPaymentSuccess(Organization $org, Payment $payment): void
    {
        try {
            $admins = $org->users()->where('role', 'admin')->where('active', true)->get();
            $amount = number_format((int) $payment->amount);
            SmsService::notifyOrgAdmins(
                $org,
                "SmartAttend: Payment of TZS {$amount} was successful. Your ".ucfirst((string) $payment->plan)." subscription for {$org->name} is now active. Thank you."
            );
        } catch (\Throwable $e) {
            Log::warning('Failed to SMS payment success: '.$e->getMessage());
        }
    }

    private function formatPayment(Payment $payment): array
    {
        return [
            'id' => $payment->id,
            'reference' => $payment->reference,
            'external_transaction_id' => $payment->external_transaction_id,
            'gateway' => $payment->gateway ?? Payment::GATEWAY_SONICPESA,
            'sonicpesa_order_id' => $payment->sonicpesa_order_id,
            'sonicpesa_checkout_url' => $payment->sonicpesa_checkout_url,
            'sonicpesa_qr_code' => $payment->sonicpesa_qr_code,
            'plan' => $payment->plan,
            'plan_name' => PlanLimits::package($payment->plan)?->name ?? ucfirst($payment->plan),
            'billing_cycle' => $payment->billing_cycle,
            'amount' => $payment->amount,
            'formatted_amount' => $payment->formatted_amount,
            'original_amount' => $payment->original_amount,
            'discount_amount' => $payment->discount_amount,
            'currency' => $payment->currency,
            'mobile_provider' => $payment->mobile_provider,
            'provider_name' => $payment->provider_name,
            'phone_number' => $payment->phone_number,
            'ussd_code' => $payment->ussd_code,
            'status' => $payment->status,
            'failure_reason' => $payment->failure_reason,
            'paid_at' => $payment->paid_at?->toIso8601String(),
            'created_at' => $payment->created_at->toIso8601String(),
        ];
    }
}
