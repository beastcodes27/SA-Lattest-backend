<?php

namespace App\Http\Controllers\Api;

use App\Models\Attendance;
use App\Models\Branch;
use App\Models\Organization;
use App\Models\User;
use App\Support\PlanLimits;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class AdminController extends Controller
{
    private const TIMEZONE = 'Africa/Dar_es_Salaam';
    private const START_MINUTES = 9 * 60;

    public function nextEmployeeId(Request $request): JsonResponse
    {
        $org = $request->user()->organization;

        return response()->json([
            'employee_id' => $this->nextEmployeeIdFor($org),
        ]);
    }

    public function updateOrganization(Request $request): JsonResponse
    {
        $org = $request->user()->organization;

        $validator = Validator::make($request->all(), [
            'employee_id_prefix' => ['required', 'string', 'max:20', 'regex:/^[A-Za-z0-9]+$/'],
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Prefix must be letters or numbers only (no spaces).'], 422);
        }

        $prefix = strtoupper($validator->validated()['employee_id_prefix']);
        $org->forceFill(['employee_id_prefix' => $prefix])->save();

        return response()->json([
            'message' => "Employee IDs will use the $prefix prefix.",
            'org' => [
                'id' => $org->id,
                'name' => $org->name,
                'status' => $org->status,
                'employee_id_prefix' => $org->employee_id_prefix,
            ],
        ]);
    }

    public function stats(Request $request): JsonResponse
    {
        $org = $request->user()->organization;

        $employees = $org->users()->where('role', 'employee')->get();
        $active = $employees->filter(fn (User $u) => $u->active);
        [$start, $end] = AuthController::todayRange();

        $records = Attendance::whereIn('user_id', $active->pluck('id'))
            ->whereBetween('occurred_at', [$start, $end])
            ->orderBy('occurred_at')
            ->get()
            ->groupBy('user_id');

        $checkedIn = 0;
        $late = 0;
        foreach ($records as $userId => $userRecords) {
            $last = $userRecords->last();
            $firstIn = $userRecords->firstWhere('type', 'in');
            if ($last->type === 'in') {
                $checkedIn++;
            }
            if ($firstIn && $this->minutesInDay($firstIn) > self::START_MINUTES) {
                $late++;
            }
        }

        return response()->json([
            'stats' => [
                'employees_total' => $employees->count(),
                'active_employees' => $active->count(),
                'checked_in_today' => $checkedIn,
                'late_today' => $late,
                'absent_today' => $active->count() - $records->keys()->count(),
                'branches_total' => $org->branches()->count(),
                'org' => [
                    'name' => $org->name,
                    'plan' => $org->plan,
                    'status' => $org->status,
                ],
            ],
        ]);
    }

    public function employees(Request $request): JsonResponse
    {
        $employees = $request->user()->organization->users()
            ->where('role', 'employee')
            ->with('branch:id,name')
            ->orderBy('name')
            ->get()
            ->map(fn (User $u) => $this->employeePayload($u))
            ->values();

        return response()->json(['employees' => $employees]);
    }

    public function storeEmployee(Request $request): JsonResponse
    {
        $org = $request->user()->organization;

        $employeeLimit = PlanLimits::employeeLimit($org->plan);

        if ($employeeLimit !== null && $org->users()->where('role', 'employee')->count() >= $employeeLimit) {
            return response()->json([
                'message' => "Your {$org->plan} plan covers up to {$employeeLimit} employees. Upgrade your package to add more.",
            ], 422);
        }

        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:255'],
            'employee_id' => ['nullable', 'string', 'max:60', 'unique:users,employee_id'],
            'password' => ['required', 'string', 'min:6'],
            'phone' => ['nullable', 'string', 'max:40'],
            'email' => ['nullable', 'email', 'max:255', 'unique:users,email'],
            'branch_id' => [
                'nullable',
                'integer',
                Rule::exists('branches', 'id')->where('org_id', $org->id),
            ],
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'The given data was invalid.', 'errors' => $validator->errors()], 422);
        }

        $data = $validator->validated();

        $employee = new User([
            'name' => $data['name'],
            'employee_id' => $data['employee_id'] ?? $this->nextEmployeeIdFor($org),
            'password' => $data['password'],
            'phone' => $data['phone'] ?? null,
            'email' => $data['email'] ?? null,
            'branch_id' => $data['branch_id'] ?? null,
            'role' => 'employee',
            'org_id' => $org->id,
            'active' => true,
            'must_change_password' => true,
        ]);
        $employee->save();

        return response()->json([
            'message' => 'Employee added.',
            'employee' => $this->employeePayload($employee->load('branch:id,name')->refresh()),
        ], 201);
    }

    public function toggleEmployee(Request $request, User $employee): JsonResponse
    {
        $org = $request->user()->organization;

        if ($employee->org_id !== $org->id || $employee->role !== 'employee') {
            return response()->json(['message' => 'Employee not found.'], 404);
        }

        $validator = Validator::make($request->all(), [
            'active' => ['required', 'boolean'],
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'The given data was invalid.', 'errors' => $validator->errors()], 422);
        }

        $employee->forceFill(['active' => (bool) $request->active])->save();

        return response()->json([
            'message' => $employee->active ? 'Employee activated.' : 'Employee deactivated.',
            'employee' => $this->employeePayload($employee->load('branch:id,name')->refresh()),
        ]);
    }

    public function resetEmployeePassword(Request $request, User $employee): JsonResponse
    {
        $org = $request->user()->organization;

        if ($employee->org_id !== $org->id || $employee->role !== 'employee') {
            return response()->json(['message' => 'Employee not found.'], 404);
        }

        $validator = Validator::make($request->all(), [
            'password' => ['required', 'string', 'min:6'],
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Password must be at least 6 characters.', 'errors' => $validator->errors()], 422);
        }

        $employee->forceFill([
            'password' => $request->password,
            'must_change_password' => true,
        ])->save();

        return response()->json([
            'message' => "Password reset successfully for {$employee->name}.",
            'employee' => $this->employeePayload($employee->load('branch:id,name')->refresh()),
        ]);
    }

    public function reports(Request $request): JsonResponse
    {
        $org = $request->user()->organization;

        $validator = Validator::make($request->all(), [
            'date' => ['nullable', 'date_format:Y-m-d'],
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'The given data was invalid.'], 422);
        }

        $date = $request->date
            ? Carbon::createFromFormat('Y-m-d', $request->date, self::TIMEZONE)
            : Carbon::now(self::TIMEZONE);

        $start = $date->copy()->startOfDay()->utc();
        $end = $date->copy()->endOfDay()->utc();

        $employees = $org->users()
            ->where('role', 'employee')
            ->with('branch:id,name')
            ->orderBy('name')
            ->get();

        $records = Attendance::whereIn('user_id', $employees->pluck('id'))
            ->whereBetween('occurred_at', [$start, $end])
            ->orderBy('occurred_at')
            ->get()
            ->groupBy('user_id');

        $rows = $employees->map(function (User $employee) use ($records) {
            $userRecords = $records->get($employee->id, collect());
            $firstIn = $userRecords->firstWhere('type', 'in');
            $last = $userRecords->last();

            $status = 'absent';
            $workedMinutes = 0;

            if ($userRecords->isNotEmpty()) {
                $closed = 0;
                $open = null;
                foreach ($userRecords as $r) {
                    if ($r->type === 'in') {
                        $open = $r->occurred_at;
                    } elseif ($open) {
                        $closed += $open->diffInMinutes($r->occurred_at);
                        $open = null;
                    }
                }
                $workedMinutes = $closed + ($open ? $open->diffInMinutes(now()) : 0);

                if ($last->type === 'in') {
                    $status = 'present';
                } else {
                    $status = $firstIn && $this->minutesInDay($firstIn) > self::START_MINUTES ? 'late' : 'present';
                }
            }

            return [
                'id' => $employee->id,
                'name' => $employee->name,
                'employee_id' => $employee->employee_id,
                'active' => $employee->active,
                'branch' => $employee->branch?->name ?? 'Unassigned',
                'status' => $employee->active ? $status : 'inactive',
                'first_in' => $firstIn?->occurred_at?->toIso8601String(),
                'last_out' => ($userRecords->last() && $userRecords->last()->type === 'out')
                    ? $userRecords->last()->occurred_at->toIso8601String()
                    : null,
                'worked_minutes' => max(0, $workedMinutes),
            ];
        })->values();

        return response()->json([
            'date' => $date->toDateString(),
            'rows' => $rows,
        ]);
    }

    public function branches(Request $request): JsonResponse
    {
        $branches = $request->user()->organization->branches()
            ->orderBy('name')
            ->withCount(['users as employee_count' => fn ($q) => $q->where('role', 'employee')])
            ->get()
            ->map(fn (Branch $b) => [
                'id' => $b->id,
                'name' => $b->name,
                'lat' => (float) $b->lat,
                'lng' => (float) $b->lng,
                'radius_meters' => (int) $b->radius_meters,
                'active' => $b->active,
                'employee_count' => (int) $b->employee_count,
            ])
            ->values();

        return response()->json(['branches' => $branches]);
    }

    public function storeBranch(Request $request): JsonResponse
    {
        $org = $request->user()->organization;

        $limit = PlanLimits::branchLimit($org->plan);

        if ($limit !== null && $org->branches()->count() >= $limit) {
            return response()->json([
                'message' => "Your {$org->plan} plan allows up to {$limit} branch".($limit === 1 ? '' : 'es').'. Upgrade your package to add more.',
            ], 422);
        }

        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:255'],
            'lat' => ['required', 'numeric', 'between:-90,90'],
            'lng' => ['required', 'numeric', 'between:-180,180'],
            'radius_meters' => ['required', 'integer', 'between:10,5000'],
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'The given data was invalid.', 'errors' => $validator->errors()], 422);
        }

        $data = $validator->validated();

        $branch = $org->branches()->create([
            'name' => $data['name'],
            'lat' => $data['lat'],
            'lng' => $data['lng'],
            'radius_meters' => $data['radius_meters'],
            'active' => true,
        ]);

        return response()->json([
            'message' => 'Branch added.',
            'branch' => [
                'id' => $branch->id,
                'name' => $branch->name,
                'lat' => (float) $branch->lat,
                'lng' => (float) $branch->lng,
                'radius_meters' => (int) $branch->radius_meters,
                'active' => $branch->active,
                'employee_count' => 0,
            ],
        ], 201);
    }

    private function minutesInDay(Attendance $attendance): int
    {
        return (int) $attendance->occurred_at->copy()->setTimezone(self::TIMEZONE)->format('H') * 60
            + (int) $attendance->occurred_at->copy()->setTimezone(self::TIMEZONE)->format('i');
    }

    private function nextEmployeeIdFor(Organization $org): string
    {
        $prefix = strtoupper(trim((string) $org->employee_id_prefix));

        if ($prefix === '') {
            $prefix = AuthController::normalizePrefix($org->name);
        }

        $max = 0;
        $existing = User::where('employee_id', 'like', $prefix.'-%')->pluck('employee_id');
        foreach ($existing as $id) {
            $suffix = substr($id, strlen($prefix) + 1);
            if ($suffix !== false && ctype_digit($suffix)) {
                $max = max($max, (int) $suffix);
            }
        }

        $next = $max + 1;
        while (User::where('employee_id', $this->formatEmployeeId($prefix, $next))->exists()) {
            $next++;
        }

        return $this->formatEmployeeId($prefix, $next);
    }

    private function formatEmployeeId(string $prefix, int $number): string
    {
        return sprintf('%s-%04d', $prefix, $number);
    }

    private function employeePayload(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'employee_id' => $user->employee_id,
            'email' => $user->email,
            'phone' => $user->phone,
            'active' => $user->active,
            'branch' => $user->branch?->name ?? 'Unassigned',
            'branch_id' => $user->branch_id,
            'created_at' => $user->created_at?->toIso8601String(),
        ];
    }
}
