<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AppNotification;
use App\Models\User;
use App\Services\ExpoPushService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    /**
     * Update user device push token.
     */
    public function updatePushToken(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'expo_push_token' => 'nullable|string',
            'push_token' => 'nullable|string',
            'device_type' => 'nullable|string|in:android,ios,web',
        ]);

        $token = $validated['expo_push_token'] ?? $validated['push_token'] ?? null;
        $deviceType = $validated['device_type'] ?? 'android';

        $user = $request->user();
        $user->update([
            'expo_push_token' => $token,
            'device_type' => $deviceType,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Push token updated successfully',
            'expo_push_token' => $user->expo_push_token,
            'device_type' => $user->device_type,
        ]);
    }

    /**
     * Get in-app notifications inbox for authenticated user.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $unreadCount = AppNotification::where('user_id', $user->id)
            ->where('is_read', false)
            ->count();

        $notifications = AppNotification::where('user_id', $user->id)
            ->with(['sender:id,name,avatar_path,role'])
            ->latest()
            ->paginate($request->input('per_page', 30));

        return response()->json([
            'unread_count' => $unreadCount,
            'notifications' => $notifications->items(),
            'current_page' => $notifications->currentPage(),
            'last_page' => $notifications->lastPage(),
            'total' => $notifications->total(),
        ]);
    }

    /**
     * Mark a single notification as read.
     */
    public function markAsRead(Request $request, $id): JsonResponse
    {
        $notification = AppNotification::where('user_id', $request->user()->id)
            ->where('id', $id)
            ->firstOrFail();

        $notification->update([
            'is_read' => true,
            'read_at' => now(),
        ]);

        $unreadCount = AppNotification::where('user_id', $request->user()->id)
            ->where('is_read', false)
            ->count();

        return response()->json([
            'success' => true,
            'notification' => $notification,
            'unread_count' => $unreadCount,
        ]);
    }

    /**
     * Mark all notifications as read.
     */
    public function markAllAsRead(Request $request): JsonResponse
    {
        AppNotification::where('user_id', $request->user()->id)
            ->where('is_read', false)
            ->update([
                'is_read' => true,
                'read_at' => now(),
            ]);

        return response()->json([
            'success' => true,
            'message' => 'All notifications marked as read',
            'unread_count' => 0,
        ]);
    }

    /**
     * Delete a notification from user inbox.
     */
    public function destroy(Request $request, $id): JsonResponse
    {
        $notification = AppNotification::where('user_id', $request->user()->id)
            ->where('id', $id)
            ->firstOrFail();

        $notification->delete();

        return response()->json([
            'success' => true,
            'message' => 'Notification deleted',
        ]);
    }

    /**
     * Org admin broadcast to organization employees.
     */
    public function orgBroadcast(Request $request): JsonResponse
    {
        $admin = $request->user();
        if (!$admin->org_id) {
            return response()->json(['message' => 'Admin does not belong to an organization.'], 403);
        }

        $validated = $request->validate([
            'title' => 'required|string|max:150',
            'body' => 'required|string|max:1000',
            'branch_id' => 'nullable|integer|exists:branches,id',
            'role' => 'nullable|string|in:employee,org_admin,all',
        ]);

        $query = User::where('org_id', $admin->org_id)
            ->where('active', true);

        if (!empty($validated['branch_id'])) {
            $query->where('branch_id', $validated['branch_id']);
        }

        if (!empty($validated['role']) && $validated['role'] !== 'all') {
            $query->where('role', $validated['role']);
        }

        $recipients = $query->get();

        $notifiedCount = ExpoPushService::notifyUsers(
            $recipients,
            $validated['title'],
            $validated['body'],
            'org_broadcast',
            [
                'org_id' => $admin->org_id,
                'branch_id' => $validated['branch_id'] ?? null,
                'sender_name' => $admin->name,
            ],
            $admin
        );

        return response()->json([
            'success' => true,
            'message' => "Notification broadcast sent to {$notifiedCount} user(s).",
            'recipients_count' => $notifiedCount,
        ]);
    }

    /**
     * System admin broadcast to all users, specific org, or specific role.
     */
    public function systemBroadcast(Request $request): JsonResponse
    {
        $sysAdmin = $request->user();

        $validated = $request->validate([
            'title' => 'required|string|max:150',
            'body' => 'required|string|max:1000',
            'org_id' => 'nullable|integer|exists:organizations,id',
            'role' => 'nullable|string|in:employee,org_admin,system_admin,all',
        ]);

        $query = User::where('active', true);

        if (!empty($validated['org_id'])) {
            $query->where('org_id', $validated['org_id']);
        }

        if (!empty($validated['role']) && $validated['role'] !== 'all') {
            $query->where('role', $validated['role']);
        }

        $recipients = $query->get();

        $notifiedCount = ExpoPushService::notifyUsers(
            $recipients,
            $validated['title'],
            $validated['body'],
            'system_broadcast',
            [
                'is_system' => true,
                'org_id' => $validated['org_id'] ?? null,
                'sender_name' => $sysAdmin->name,
            ],
            $sysAdmin
        );

        return response()->json([
            'success' => true,
            'message' => "System broadcast sent to {$notifiedCount} user(s).",
            'recipients_count' => $notifiedCount,
        ]);
    }
}

