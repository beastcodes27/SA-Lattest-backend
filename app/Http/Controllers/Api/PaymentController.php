<?php

namespace App\Http\Controllers\Api;

use App\Models\Payment;
use App\Models\Package;
use App\Services\MobilePaymentService;
use App\Support\PlanLimits;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class PaymentController extends Controller
{
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

        $instructions = MobilePaymentService::getInstructions(
            $provider,
            $payment->amount,
            $normalizedPhone,
            $reference
        );

        return response()->json([
            'message' => 'Mobile payment prompt sent. Please check your phone to approve.',
            'payment' => [
                'id' => $payment->id,
                'reference' => $payment->reference,
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
                'status' => $payment->status,
                'created_at' => $payment->created_at->toIso8601String(),
            ],
            'instructions' => $instructions,
        ], 201);
    }
}
