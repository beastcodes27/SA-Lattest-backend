<?php

namespace App\Http\Controllers\Api;

use App\Models\Organization;
use App\Models\Package;
use App\Services\SmsService;
use App\Support\PlanLimits;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class SubscriptionController extends Controller
{
    public function details(Request $request): JsonResponse
    {
        return response()->json(['subscription' => $this->payload($request->user()->organization)]);
    }

    public function cancelTrial(Request $request): JsonResponse
    {
        $org = $request->user()->organization;

        if (! $org->onTrial()) {
            return response()->json([
                'message' => 'Organization is not currently on an active free trial.',
                'subscription' => $this->payload($org),
            ], 422);
        }

        $org->cancelTrial();

        try {
            $admins = $org->users()->where('role', 'admin')->where('active', true)->get();
            SmsService::notifyOrgAdmins($org, "SmartAttend: The free trial for {$org->name} has been canceled. Choose a package to keep managing your organization. Thank you.");
        } catch (\Throwable $e) {
            Log::warning('Failed to SMS trial cancellation: '.$e->getMessage());
        }

        return response()->json([
            'message' => 'Free trial canceled. You can now select your desired plan and pay with Mobile Money.',
            'subscription' => $this->payload($org->fresh()),
        ]);
    }

    public function upgrade(Request $request): JsonResponse
    {
        $org = $request->user()->organization;

        $validator = Validator::make($request->all(), [
            'plan' => ['required', Rule::exists('packages', 'code')->where('active', true)],
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Please choose a valid package.'], 422);
        }

        $plan = $validator->validated()['plan'];
        $package = PlanLimits::package($plan);

        $org->forceFill([
            'plan' => $plan,
            'subscription_status' => 'active',
            'trial_ends_at' => null,
            'canceled_at' => null,
        ])->save();

        return response()->json([
            'message' => 'You are now on the '.($package?->name ?? ucfirst($plan)).' plan.',
            'subscription' => $this->payload($org->fresh()),
        ]);
    }

    public function cancel(Request $request): JsonResponse
    {
        $org = $request->user()->organization;

        if ($org->subscription_status === 'canceled') {
            return response()->json(['message' => 'Your subscription is already canceled.', 'subscription' => $this->payload($org)], 422);
        }

        $org->forceFill([
            'subscription_status' => 'canceled',
            'canceled_at' => now(),
            'trial_ends_at' => $org->onTrial() ? now() : $org->trial_ends_at,
        ])->save();

        try {
            $admins = $org->users()->where('role', 'admin')->where('active', true)->get();
            SmsService::notifyOrgAdmins($org, "SmartAttend: The subscription for {$org->name} has been canceled. You can subscribe again anytime to restore access. Thank you.");
        } catch (\Throwable $e) {
            Log::warning('Failed to SMS subscription cancellation: '.$e->getMessage());
        }

        return response()->json([
            'message' => 'Your subscription has been canceled. You can subscribe again anytime to restore access.',
            'subscription' => $this->payload($org->fresh()),
        ]);
    }

    public static function payload(Organization $org): array
    {
        $onTrial = $org->onTrial();
        $status = $org->subscription_status === 'canceled'
            ? 'canceled'
            : ($org->subscriptionActive() ? 'active' : ($onTrial ? 'trial' : 'expired'));

        $latestPayment = $org->payments()->latest()->first();

        $packages = Package::query()
            ->where('active', true)
            ->orderBy('position')
            ->get()
            ->map(function (Package $p) use ($org) {
                $monthly = $p->calculateAmount('monthly', (int) $org->discount_percent);
                $annual = $p->calculateAmount('annual', (int) $org->discount_percent);

                return [
                    'code' => $p->code,
                    'name' => $p->name,
                    'tagline' => $p->tagline,
                    'price_label' => $p->price_label ?: $p->formatted_monthly_price,
                    'monthly_price' => $monthly['final_amount'],
                    'original_monthly_price' => $monthly['original_amount'],
                    'annual_price' => $annual['final_amount'],
                    'original_annual_price' => $annual['original_amount'],
                    'currency' => $p->currency ?: 'TZS',
                    'features' => $p->features ?? [],
                    'employee_limit' => $p->employee_limit,
                    'branch_limit' => $p->branch_limit,
                ];
            });

        $isTrialExpired = (bool) ($org->trial_ends_at && $org->trial_ends_at->isPast() && ! $org->subscriptionActive());
        $requiresPayment = ! $org->isAccessible();

        return [
            'plan' => $org->plan,
            'plan_label' => PlanLimits::package($org->plan)?->name ?? ucfirst($org->plan),
            'price_label' => PlanLimits::package($org->plan)?->price_label ?: 'TZS '.number_format(PlanLimits::monthlyPrice($org->plan)).' / mo',
            'status' => $status,
            'on_trial' => $onTrial,
            'trial_expired' => $isTrialExpired,
            'requires_payment' => $requiresPayment,
            'payment_prompt' => $isTrialExpired
                ? 'Your free trial has ended. Select a package and pay with Mobile Money (M-Pesa, Tigo Pesa, Airtel Money, HaloPesa) to resume organization management and attendance services.'
                : ($requiresPayment ? 'Subscription inactive. Please subscribe to a package with Mobile Money to continue.' : null),
            'can_cancel_trial' => $onTrial,
            'trial_days_left' => $org->trialDaysLeft(),
            'trial_ends_at' => $org->trial_ends_at?->toIso8601String(),
            'canceled_at' => $org->canceled_at?->toIso8601String(),
            'discount_percent' => (int) $org->discount_percent,
            'accessible' => $org->isAccessible(),
            'branches_used' => $org->activeBranches()->count(),
            'branches_total' => $org->branches()->count(),
            'branches_limit' => PlanLimits::branchLimit($org->plan),
            'employees_used' => $org->users()->where('role', 'employee')->count(),
            'employees_limit' => PlanLimits::employeeLimit($org->plan),
            'packages' => $packages,
            'supported_payment_methods' => ['mobile_money'],
            'mobile_payment_providers' => [
                ['code' => 'mpesa', 'name' => 'M-Pesa', 'carrier' => 'Vodacom Tanzania', 'ussd' => '*150*00#'],
                ['code' => 'tigopesa', 'name' => 'Tigo Pesa', 'carrier' => 'Yas Tanzania', 'ussd' => '*150*01#'],
                ['code' => 'airtelmoney', 'name' => 'Airtel Money', 'carrier' => 'Airtel Tanzania', 'ussd' => '*150*60#'],
                ['code' => 'halopesa', 'name' => 'HaloPesa', 'carrier' => 'Halotel Tanzania', 'ussd' => '*150*88#'],
            ],
            'latest_payment' => $latestPayment ? [
                'id' => $latestPayment->id,
                'reference' => $latestPayment->reference,
                'plan' => $latestPayment->plan,
                'amount' => $latestPayment->amount,
                'formatted_amount' => $latestPayment->formatted_amount,
                'mobile_provider' => $latestPayment->mobile_provider,
                'provider_name' => $latestPayment->provider_name,
                'phone_number' => $latestPayment->phone_number,
                'status' => $latestPayment->status,
                'paid_at' => $latestPayment->paid_at?->toIso8601String(),
                'created_at' => $latestPayment->created_at->toIso8601String(),
            ] : null,
        ];
    }
}
