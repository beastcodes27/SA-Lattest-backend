<?php

namespace App\Http\Controllers\Api;

use App\Models\Attendance;
use App\Services\GeolocationService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AttendanceController extends Controller
{
    public function __construct(private readonly GeolocationService $geo) {}

    public function toggle(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'lat' => ['required', 'numeric', 'between:-90,90'],
            'lng' => ['required', 'numeric', 'between:-180,180'],
            'type' => ['nullable', 'in:in,out'],
            'occurred_at' => ['nullable', 'date'],
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Location is required to check in.'], 422);
        }

        $user = $request->user();
        $org = $user->organization;

        if ($user->must_change_password) {
            return response()->json(['message' => 'Set a new password before checking in.'], 403);
        }

        if (! $org || $org->status !== 'active') {
            return response()->json(['message' => 'Your organization is not active yet.'], 403);
        }

        if (! $org->isAccessible()) {
            return response()->json([
                'message' => 'Attendance service is temporarily paused because your organization does not have an active package. Please contact your administrator or HR to renew the subscription.',
                'code' => 'SUBSCRIPTION_REQUIRED',
                'accessible' => false,
            ], 403);
        }

        $branch = $user->branch ??
            $org->activeBranches()->orderBy('id')->first();

        if (! $branch) {
            return response()->json(['message' => 'No active branch is set for your account.'], 422);
        }

        $distance = $this->geo->distanceMeters(
            (float) $request->lat,
            (float) $request->lng,
            (float) $branch->lat,
            (float) $branch->lng,
        );

        if ($distance > (float) $branch->radius_meters) {
            return response()->json([
                'message' => 'out_of_range',
                'distance_m' => round($distance, 1),
                'radius_m' => (int) $branch->radius_meters,
            ], 422);
        }

        [$start, $end] = AuthController::todayRange();

        $todayQuery = Attendance::where('user_id', $user->id)
            ->whereBetween('occurred_at', [$start, $end]);

        $hasCheckedIn = (clone $todayQuery)->where('type', 'in')->exists();
        $hasCheckedOut = (clone $todayQuery)->where('type', 'out')->exists();

        // Only one check-in and one check-out are allowed per day.
        if ($hasCheckedIn && $hasCheckedOut) {
            return response()->json([
                'message' => 'You have already checked in and checked out today. Only one check-in and one check-out are allowed per day.',
                'code' => 'daily_limit_reached',
            ], 422);
        }

        $type = $request->filled('type') ? $request->type : ($hasCheckedIn ? 'out' : 'in');

        if ($type === 'in' && $hasCheckedIn) {
            return response()->json([
                'message' => 'You have already checked in today.',
                'code' => 'already_checked_in',
            ], 422);
        }

        if ($type === 'out' && ! $hasCheckedIn) {
            return response()->json([
                'message' => 'You need to check in before you can check out.',
                'code' => 'needs_check_in',
            ], 422);
        }

        if ($type === 'out' && $hasCheckedOut) {
            return response()->json([
                'message' => 'You have already checked out today.',
                'code' => 'already_checked_out',
            ], 422);
        }
        $occurredAt = $request->filled('occurred_at') ? Carbon::parse($request->occurred_at) : now();

        $attendance = new Attendance([
            'user_id' => $user->id,
            'branch_id' => $branch->id,
            'type' => $type,
            'lat' => $request->lat,
            'lng' => $request->lng,
            'occurred_at' => $occurredAt,
        ]);
        $attendance->save();

        $todayRecords = $this->todayRecordsFor($user);

        return response()->json([
            'record' => AuthController::attendancePayload($attendance->refresh()),
            'is_checked_in' => $type === 'in',
            'branch' => [
                'id' => $branch->id,
                'name' => $branch->name,
                'radius_meters' => (int) $branch->radius_meters,
            ],
            'today' => $todayRecords,
        ]);
    }

    public function sync(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'records' => ['required', 'array', 'min:1'],
            'records.*.lat' => ['required', 'numeric', 'between:-90,90'],
            'records.*.lng' => ['required', 'numeric', 'between:-180,180'],
            'records.*.type' => ['nullable', 'in:in,out'],
            'records.*.occurred_at' => ['nullable', 'date'],
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Invalid sync data.', 'errors' => $validator->errors()], 422);
        }

        $user = $request->user();
        $org = $user->organization;

        if (! $org || $org->status !== 'active' || ! $org->isAccessible()) {
            return response()->json([
                'message' => 'Attendance syncing is paused because your organization does not have an active package. Please contact your administrator to renew.',
                'code' => 'SUBSCRIPTION_REQUIRED',
                'accessible' => false,
            ], 403);
        }

        $branch = $user->branch ?? $org->activeBranches()->orderBy('id')->first();
        if (! $branch) {
            return response()->json(['message' => 'No active branch found.'], 422);
        }

        $synced = [];
        foreach ($request->records as $r) {
            $occurredAt = ! empty($r['occurred_at']) ? Carbon::parse($r['occurred_at']) : now();
            $type = ! empty($r['type']) ? $r['type'] : 'in';

            $attendance = new Attendance([
                'user_id' => $user->id,
                'branch_id' => $branch->id,
                'type' => $type,
                'lat' => $r['lat'],
                'lng' => $r['lng'],
                'occurred_at' => $occurredAt,
            ]);
            $attendance->save();
            $synced[] = AuthController::attendancePayload($attendance->refresh());
        }

        return response()->json([
            'message' => count($synced).' offline record(s) synced successfully.',
            'synced' => $synced,
            'today' => $this->todayRecordsFor($user),
        ]);
    }

    public function today(Request $request): JsonResponse
    {
        return response()->json(['records' => $this->todayRecordsFor($request->user())]);
    }

    public function index(Request $request): JsonResponse
    {
        $records = Attendance::where('user_id', $request->user()->id)
            ->with('branch')
            ->orderByDesc('occurred_at')
            ->limit(500)
            ->get()
            ->map(fn (Attendance $a) => AuthController::attendancePayload($a))
            ->values();

        return response()->json(['records' => $records]);
    }

    private function todayRecordsFor($user): array
    {
        [$start, $end] = AuthController::todayRange();

        return Attendance::where('user_id', $user->id)
            ->with('branch')
            ->whereBetween('occurred_at', [$start, $end])
            ->orderBy('occurred_at')
            ->get()
            ->map(fn (Attendance $a) => AuthController::attendancePayload($a))
            ->values()
            ->all();
    }
}
