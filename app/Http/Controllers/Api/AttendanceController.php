<?php

namespace App\Http\Controllers\Api;

use App\Models\Attendance;
use App\Models\Branch;
use App\Services\GeolocationService;
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
            return response()->json(['message' => 'Your free trial has ended. Contact your provider to renew access.'], 403);
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

        $lastToday = Attendance::where('user_id', $user->id)
            ->whereBetween('occurred_at', [$start, $end])
            ->orderByDesc('occurred_at')
            ->first();

        $type = $request->filled('type') ? $request->type : ($lastToday && $lastToday->type === 'in' ? 'out' : 'in');
        $occurredAt = $request->filled('occurred_at') ? \Carbon\Carbon::parse($request->occurred_at) : now();

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
            return response()->json(['message' => 'Organization is not accessible.'], 403);
        }

        $branch = $user->branch ?? $org->activeBranches()->orderBy('id')->first();
        if (! $branch) {
            return response()->json(['message' => 'No active branch found.'], 422);
        }

        $synced = [];
        foreach ($request->records as $r) {
            $occurredAt = !empty($r['occurred_at']) ? \Carbon\Carbon::parse($r['occurred_at']) : now();
            $type = !empty($r['type']) ? $r['type'] : 'in';

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
            ->whereBetween('occurred_at', [$start, $end])
            ->orderBy('occurred_at')
            ->get()
            ->map(fn (Attendance $a) => AuthController::attendancePayload($a))
            ->values()
            ->all();
    }
}
