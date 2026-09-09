<?php

namespace App\Http\Controllers\Api;

use App\Models\Attendance;
use App\Models\Branch;
use App\Models\Organization;
use App\Models\User;
use App\Services\GeolocationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Carbon\Carbon;

class AuthController extends Controller
{
    public function registerOrganization(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'organization.name' => ['required', 'string', 'max:255'],
            'organization.address' => ['required', 'string', 'max:255'],
            'organization.phone' => ['required', 'string', 'max:40'],
            'organization.website' => ['nullable', 'string', 'max:255'],
            'organization.tin' => ['required', 'string', 'max:120'],
            'organization.employee_id_prefix' => ['nullable', 'string', 'max:20'],
            'organization.plan' => ['required', Rule::in(['starter', 'business', 'enterprise'])],
            'admin.name' => ['required', 'string', 'max:255'],
            'admin.email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'admin.employee_id' => ['required', 'string', 'max:60', 'unique:users,employee_id'],
            'admin.password' => ['required', 'string', 'min:6'],
            'admin.phone' => ['nullable', 'string', 'max:40'],
            'branches' => ['required', 'array', 'min:1'],
            'branches.*.name' => ['required', 'string', 'max:255'],
            'branches.*.lat' => ['required', 'numeric', 'between:-90,90'],
            'branches.*.lng' => ['required', 'numeric', 'between:-180,180'],
            'branches.*.radius_meters' => ['required', 'integer', 'between:10,5000'],
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'The given data was invalid.', 'errors' => $validator->errors()], 422);
        }

        $data = $validator->validated();

        $organization = Organization::create([
            'name' => $data['organization']['name'],
            'contact_email' => $data['admin']['email'],
            'contact_phone' => $data['organization']['phone'],
            'address' => $data['organization']['address'],
            'website' => $data['organization']['website'] ?? null,
            'tin' => $data['organization']['tin'],
            'employee_id_prefix' => $this->normalizePrefix($data['organization']['employee_id_prefix'] ?? $data['organization']['name']),
            'plan' => $data['organization']['plan'],
            'status' => 'pending',
        ]);

        $branches = collect($data['branches'])->map(fn (array $b) => new Branch([
            'name' => $b['name'],
            'lat' => $b['lat'],
            'lng' => $b['lng'],
            'radius_meters' => $b['radius_meters'],
        ]));
        $organization->branches()->saveMany($branches);

        $firstBranch = $organization->branches()->orderBy('id')->first();

        $admin = new User([
            'name' => $data['admin']['name'],
            'email' => $data['admin']['email'],
            'employee_id' => $data['admin']['employee_id'],
            'phone' => $data['admin']['phone'] ?? null,
            'role' => 'admin',
            'org_id' => $organization->id,
            'branch_id' => $firstBranch?->id,
            'active' => true,
            'password' => $data['admin']['password'],
        ]);
        $admin->save();

        return response()->json([
            'message' => 'Your registration request has been received. You will be notified once it is approved.',
            'organization' => [
                'id' => $organization->id,
                'name' => $organization->name,
                'status' => $organization->status,
            ],
        ], 201);
    }

    public function login(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'employee_id' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Enter your employee ID and password.'], 422);
        }

        $user = User::where('employee_id', $request->employee_id)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            return response()->json(['message' => 'Invalid employee ID or password.'], 401);
        }

        if (! $user->active) {
            return response()->json(['message' => 'Your account has been deactivated.'], 403);
        }

        if ($user->role === 'superadmin') {
            $token = $user->createToken('mobile')->plainTextToken;

            return response()->json(['token' => $token, 'user' => $this->userPayload($user)]);
        }

        if (! $user->organization) {
            return response()->json([
                'message' => 'Your organization is still pending review. You will be notified once it is approved.',
                'organization_status' => 'none',
            ], 403);
        }

        $org = $user->organization;

        if ($org->status !== 'active') {
            $message = $org->status === 'suspended'
                ? 'Your organization is suspended. Contact support for help.'
                : 'Your organization is still pending review. You will be notified once it is approved.';

            return response()->json([
                'message' => $message,
                'organization_status' => $org->status,
            ], 403);
        }

        if (! $org->isAccessible()) {
            return response()->json([
                'message' => 'Your free trial has ended. Contact your provider to renew access.',
                'organization_status' => 'trial_expired',
            ], 403);
        }

        $token = $user->createToken('mobile')->plainTextToken;

        return response()->json(['token' => $token, 'user' => $this->userPayload($user)]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Signed out.']);
    }

    public static function userPayload(User $user): array
    {
        $org = $user->organization;
        $branch = $user->branch ?? $org?->activeBranches()->orderBy('id')->first();

        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'avatar_path' => $user->avatar_path,
            'employee_id' => $user->employee_id,
            'role' => $user->role,
            'must_change_password' => $user->must_change_password,
            'face_enrolled' => $user->face_enrolled,
            'org' => $org ? [
                'id' => $org->id,
                'name' => $org->name,
                'status' => $org->status,
                'employee_id_prefix' => $org->employee_id_prefix,
                'plan' => $org->plan,
                'on_trial' => $org->onTrial(),
                'trial_days_left' => $org->trialDaysLeft(),
                'trial_ends_at' => $org->trial_ends_at?->toIso8601String(),
                'subscription_status' => $org->subscription_status,
            ] : null,
            'branch' => $branch ? [
                'id' => $branch->id,
                'name' => $branch->name,
                'lat' => (float) $branch->lat,
                'lng' => (float) $branch->lng,
                'radius_meters' => (int) $branch->radius_meters,
            ] : null,
        ];
    }

    public static function attendancePayload(Attendance $attendance): array
    {
        return [
            'id' => $attendance->id,
            'type' => $attendance->type,
            'occurred_at' => $attendance->occurred_at?->toIso8601String(),
        ];
    }

    public static function todayRange(): array
    {
        $tz = 'Africa/Dar_es_Salaam';
        $start = Carbon::now($tz)->startOfDay()->utc();
        $end = Carbon::now($tz)->endOfDay()->utc();

        return [$start, $end];
    }

    public static function normalizePrefix(string $value): string
    {
        $words = preg_split('/[\s\-_]+/', trim($value));
        $letters = '';

        if (count($words) > 1) {
            foreach ($words as $word) {
                if ($word !== '') {
                    $letters .= strtoupper(mb_substr($word, 0, 1));
                }
            }
        }

        $letters = strtoupper((string) preg_replace('/[^A-Z0-9]/', '', $letters));

        if ($letters === '') {
            $letters = strtoupper((string) preg_replace('/[^A-Z0-9]/', '', $value));
        }

        return mb_substr($letters, 0, 4) ?: 'EMP';
    }
}
