<?php

namespace App\Http\Controllers\Api;

use App\Models\Attendance;
use App\Models\Organization;
use App\Support\PlanLimits;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class SystemController extends Controller
{
    private const TIMEZONE = 'Africa/Dar_es_Salaam';

    public function stats(): JsonResponse
    {
        $today = Carbon::now(self::TIMEZONE);
        [$start] = AuthController::todayRange();

        $byStatus = Organization::query()
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return response()->json([
            'stats' => [
                'pending' => (int) ($byStatus['pending'] ?? 0),
                'active' => (int) ($byStatus['active'] ?? 0),
                'suspended' => (int) ($byStatus['suspended'] ?? 0),
                'organizations_total' => Organization::count(),
                'employees' => \App\Models\User::where('role', 'employee')->count(),
                'branches' => \App\Models\Branch::count(),
                'today_checkins' => Attendance::whereBetween('occurred_at', AuthController::todayRange())->count(),
            ],
        ]);
    }

    public function organizations(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'status' => ['nullable', Rule::in(['pending', 'active', 'suspended'])],
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Invalid status filter.'], 422);
        }

        $query = Organization::query()->withCount([
            'users as employees_count' => fn ($q) => $q->where('role', 'employee'),
            'branches as branches_count',
        ]);

        if ($request->status) {
            $query->where('status', $request->status);
        }

        $orgs = $query->latest()->get()->map(fn (Organization $org) => $this->payload($org))->values();

        return response()->json(['organizations' => $orgs]);
    }

    public function approve(Request $request, Organization $organization): JsonResponse
    {
        if ($organization->status !== 'pending') {
            return response()->json(['message' => 'Only pending organizations can be approved.'], 422);
        }

        $validator = Validator::make($request->all(), [
            'days' => ['nullable', 'integer', 'between:1,365'],
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Invalid trial days.'], 422);
        }

        $organization->forceFill(['status' => 'active'])->save();

        if ($organization->trial_started_at === null) {
            $organization->startTrial((int) ($request->days ?? 30));
        }

        return response()->json([
            'message' => $organization->name.' approved. Free trial started.',
            'organization' => $this->payload($organization->fresh()),
        ]);
    }

    public function setStatus(Request $request, Organization $organization): JsonResponse
    {
        if ($organization->status === 'pending') {
            return response()->json(['message' => 'Pending organizations must be approved or rejected first.'], 422);
        }

        $validator = Validator::make($request->all(), [
            'status' => ['required', Rule::in(['active', 'suspended'])],
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Invalid status.'], 422);
        }

        $status = $request->status;

        $organization->forceFill(['status' => $status])->save();

        if ($status === 'active' && $organization->trial_started_at === null) {
            $organization->startTrial(30);
        }

        return response()->json([
            'message' => $status === 'active'
                ? $organization->name.' reactivated.'
                : $organization->name.' suspended. Users can no longer sign in.',
            'organization' => $this->payload($organization->fresh()),
        ]);
    }

    private function payload(Organization $org): array
    {
        $admin = $org->users()->where('role', 'admin')->orderBy('id')->first();

        return [
            'id' => $org->id,
            'name' => $org->name,
            'contact_email' => $org->contact_email,
            'contact_phone' => $org->contact_phone,
            'address' => $org->address,
            'website' => $org->website,
            'tin' => $org->tin,
            'admin' => $admin ? [
                'name' => $admin->name,
                'email' => $admin->email,
                'phone' => $admin->phone,
                'employee_id' => $admin->employee_id,
            ] : null,
            'employee_id_prefix' => $org->employee_id_prefix,
            'plan' => $org->plan,
            'status' => $org->status,
            'employees_count' => (int) ($org->employees_count ?? $org->users()->where('role', 'employee')->count()),
            'branches_count' => (int) ($org->branches_count ?? $org->branches()->count()),
            'branches_limit' => PlanLimits::branchLimit($org->plan),
            'employees_limit' => PlanLimits::employeeLimit($org->plan),
            'on_trial' => $org->onTrial(),
            'trial_days_left' => $org->trialDaysLeft(),
            'trial_ends_at' => $org->trial_ends_at?->toIso8601String(),
            'subscription_status' => $org->subscription_status,
            'created_at' => $org->created_at?->toIso8601String(),
        ];
    }
}
