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

        if (! $user->organization || $user->organization->status !== 'active') {
            return response()->json([
                'message' => 'Your organization is still pending review. You will be notified once it is approved.',
                'organization_status' => $user->organization?->status ?? 'none',
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
            'employee_id' => $user->employee_id,
            'role' => $user->role,
            'org' => $org ? ['id' => $org->id, 'name' => $org->name, 'status' => $org->status] : null,
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
}
