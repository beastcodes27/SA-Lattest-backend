<?php

namespace App\Http\Controllers\Api;

use App\Models\PermissionRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PermissionController extends Controller
{
    /**
     * List current user's permission requests.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $query = PermissionRequest::where('user_id', $user->id)
            ->with(['actionedBy'])
            ->orderByDesc('created_at');

        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        $requests = $query->get()->map(fn (PermissionRequest $p) => $p->toPayload());

        $categories = collect(PermissionRequest::REASON_CATEGORIES)->map(function ($label, $key) {
            return ['key' => $key, 'label' => $label];
        })->values();

        return response()->json([
            'categories' => $categories,
            'requests' => $requests,
        ]);
    }

    /**
     * Submit a new permission request.
     */
    public function store(Request $request): JsonResponse
    {
        $validCategories = implode(',', array_keys(PermissionRequest::REASON_CATEGORIES));

        $validator = Validator::make($request->all(), [
            'reason_category' => ['required', 'string', 'in:'.$validCategories],
            'reason' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = $request->user();
        $org = $user->organization;

        if (! $org || $org->status !== 'active') {
            return response()->json(['message' => 'Your organization is not active yet.'], 403);
        }

        if (! $org->isAccessible()) {
            return response()->json(['message' => 'Your organization subscription is not active.'], 403);
        }

        $permission = PermissionRequest::create([
            'user_id' => $user->id,
            'organization_id' => $org->id,
            'reason_category' => $request->reason_category,
            'reason' => $request->reason,
            'description' => $request->description,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'status' => PermissionRequest::STATUS_PENDING,
        ]);

        return response()->json([
            'message' => 'Permission request submitted successfully.',
            'request' => $permission->refresh()->toPayload(),
        ], 201);
    }

    /**
     * Cancel a pending permission request.
     */
    public function cancel(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $permission = PermissionRequest::where('id', $id)
            ->where('user_id', $user->id)
            ->first();

        if (! $permission) {
            return response()->json(['message' => 'Permission request not found.'], 404);
        }

        if ($permission->status !== PermissionRequest::STATUS_PENDING) {
            return response()->json(['message' => 'Only pending requests can be cancelled.'], 422);
        }

        $permission->status = PermissionRequest::STATUS_CANCELLED;
        $permission->save();

        return response()->json([
            'message' => 'Permission request cancelled.',
            'request' => $permission->toPayload(),
        ]);
    }

    /**
     * Admin: List all permission requests in organization.
     */
    public function adminIndex(Request $request): JsonResponse
    {
        $admin = $request->user();
        $orgId = $admin->org_id;

        $baseQuery = PermissionRequest::where('organization_id', $orgId);

        $pendingCount = (clone $baseQuery)->where('status', PermissionRequest::STATUS_PENDING)->count();
        $approvedCount = (clone $baseQuery)->where('status', PermissionRequest::STATUS_APPROVED)->count();
        $rejectedCount = (clone $baseQuery)->where('status', PermissionRequest::STATUS_REJECTED)->count();
        $totalCount = (clone $baseQuery)->count();

        $query = PermissionRequest::where('organization_id', $orgId)
            ->with(['user', 'actionedBy'])
            ->orderByDesc('created_at');

        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $s = '%'.$request->search.'%';
            $query->whereHas('user', function ($uq) use ($s) {
                $uq->where('name', 'like', $s)
                    ->orWhere('employee_id', 'like', $s)
                    ->orWhere('email', 'like', $s);
            });
        }

        $requests = $query->get()->map(fn (PermissionRequest $p) => $p->toPayload());

        $categories = collect(PermissionRequest::REASON_CATEGORIES)->map(function ($label, $key) {
            return ['key' => $key, 'label' => $label];
        })->values();

        return response()->json([
            'counts' => [
                'pending' => $pendingCount,
                'approved' => $approvedCount,
                'rejected' => $rejectedCount,
                'total' => $totalCount,
            ],
            'categories' => $categories,
            'requests' => $requests,
        ]);
    }

    /**
     * Admin: Approve a permission request.
     */
    public function adminApprove(Request $request, int $id): JsonResponse
    {
        $admin = $request->user();
        $permission = PermissionRequest::where('id', $id)
            ->where('organization_id', $admin->org_id)
            ->first();

        if (! $permission) {
            return response()->json(['message' => 'Permission request not found.'], 404);
        }

        $permission->status = PermissionRequest::STATUS_APPROVED;
        $permission->actioned_by_id = $admin->id;
        $permission->actioned_at = now();
        $permission->admin_remarks = $request->input('remarks');
        $permission->save();

        return response()->json([
            'message' => 'Permission request approved successfully.',
            'request' => $permission->refresh()->toPayload(),
        ]);
    }

    /**
     * Admin: Reject a permission request.
     */
    public function adminReject(Request $request, int $id): JsonResponse
    {
        $admin = $request->user();
        $permission = PermissionRequest::where('id', $id)
            ->where('organization_id', $admin->org_id)
            ->first();

        if (! $permission) {
            return response()->json(['message' => 'Permission request not found.'], 404);
        }

        $permission->status = PermissionRequest::STATUS_REJECTED;
        $permission->actioned_by_id = $admin->id;
        $permission->actioned_at = now();
        $permission->admin_remarks = $request->input('remarks');
        $permission->save();

        return response()->json([
            'message' => 'Permission request rejected.',
            'request' => $permission->refresh()->toPayload(),
        ]);
    }
}
