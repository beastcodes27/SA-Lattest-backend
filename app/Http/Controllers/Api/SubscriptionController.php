<?php

namespace App\Http\Controllers\Api;

use App\Models\Organization;
use App\Support\PlanLimits;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class SubscriptionController extends Controller
{
    private const PLANS = [
        'starter' => ['name' => 'Starter', 'seats' => 'Up to 50 employees'],
        'business' => ['name' => 'Business', 'seats' => 'Up to 500 employees'],
        'enterprise' => ['name' => 'Enterprise', 'seats' => 'Unlimited employees'],
    ];

    public function details(Request $request): JsonResponse
    {
        return response()->json(['subscription' => $this->payload($request->user()->organization)]);
    }

    public function upgrade(Request $request): JsonResponse
    {
        $org = $request->user()->organization;

        $validator = Validator::make($request->all(), [
            'plan' => ['required', Rule::in(array_keys(self::PLANS))],
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Please choose a valid package.'], 422);
        }

        $plan = $validator->validated()['plan'];

        $org->forceFill([
            'plan' => $plan,
            'subscription_status' => 'active',
            'canceled_at' => null,
        ])->save();

        return response()->json([
            'message' => "You are now on the ".self::PLANS[$plan]['name']." plan.",
            'subscription' => $this->payload($org->fresh()),
        ]);
    }

    public function cancel(Request $request): JsonResponse
    {
        $org = $request->user()->organization;

        if ($org->subscription_status === 'canceled') {
            return response()->json(['message' => 'Your subscription is already canceled.', 'subscription' => $this->payload($org)], 422);
        }

        if (! $org->trial_ends_at) {
            return response()->json(['message' => 'Cannot cancel an active paid subscription here. Contact support.'], 422);
        }

        $org->forceFill([
            'subscription_status' => 'canceled',
            'canceled_at' => now(),
        ])->save();

        return response()->json([
            'message' => 'Your subscription is canceled. You keep access until '.$org->fresh()->trial_ends_at?->toDateString().'.',
            'subscription' => $this->payload($org->fresh()),
        ]);
    }

    public static function payload(Organization $org): array
    {
        $onTrial = $org->onTrial();
        $status = $org->subscription_status === 'canceled'
            ? 'canceled'
            : ($org->subscriptionActive() ? 'active' : ($onTrial ? 'trial' : 'expired'));

        return [
            'plan' => $org->plan,
            'plan_label' => self::PLANS[$org->plan]['name'] ?? ucfirst($org->plan),
            'status' => $status,
            'on_trial' => $onTrial,
            'trial_days_left' => $org->trialDaysLeft(),
            'trial_ends_at' => $org->trial_ends_at?->toIso8601String(),
            'canceled_at' => $org->canceled_at?->toIso8601String(),
            'accessible' => $org->isAccessible(),
            'branches_used' => $org->branches()->count(),
            'branches_limit' => PlanLimits::branchLimit($org->plan),
            'employees_used' => $org->users()->where('role', 'employee')->count(),
            'employees_limit' => PlanLimits::employeeLimit($org->plan),
        ];
    }
}
