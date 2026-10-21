<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ErrorLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class ErrorLogController extends Controller
{
    /**
     * Ingest client-side errors reported from the mobile / web application.
     */
    public function report(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'message' => 'required|string|max:5000',
            'exception_class' => 'nullable|string|max:255',
            'file' => 'nullable|string|max:500',
            'line' => 'nullable|integer',
            'trace' => 'nullable|string|max:20000',
            'level' => 'nullable|string|in:critical,error,warning,info',
            'device' => 'nullable|array',
            'breadcrumbs' => 'nullable|array',
            'route' => 'nullable|string|max:255',
            'extra' => 'nullable|array',
        ]);

        $user = auth('sanctum')->user();

        $context = [
            'device' => $validated['device'] ?? null,
            'breadcrumbs' => $validated['breadcrumbs'] ?? null,
            'route' => $validated['route'] ?? null,
            'extra' => $validated['extra'] ?? null,
        ];

        $log = ErrorLog::create([
            'source' => 'client',
            'level' => $validated['level'] ?? 'error',
            'message' => $validated['message'],
            'exception_class' => $validated['exception_class'] ?? 'ClientException',
            'file' => $validated['file'] ?? null,
            'line' => $validated['line'] ?? null,
            'trace' => $validated['trace'] ?? null,
            'context' => $context,
            'user_id' => $user?->id ?? $request->input('user_id'),
            'org_id' => $user?->org_id ?? $request->input('org_id'),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'status' => 'unresolved',
        ]);

        return response()->json([
            'success' => true,
            'id' => $log->id,
            'message' => 'Error report recorded successfully.',
        ], 201);
    }

    /**
     * List error logs with filters and pagination for system admins.
     */
    public function index(Request $request): JsonResponse
    {
        $query = ErrorLog::query()
            ->with([
                'user:id,name,email,role,employee_id,org_id',
                'organization:id,name',
            ])
            ->latest('id');

        if ($request->filled('source') && $request->source !== 'all') {
            $query->where('source', $request->source);
        }

        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        if ($request->filled('level') && $request->level !== 'all') {
            $query->where('level', $request->level);
        }

        if ($request->filled('search')) {
            $query->search($request->search);
        }

        $perPage = min(max((int) $request->input('per_page', 25), 1), 100);
        $paginator = $query->paginate($perPage);

        return response()->json([
            'errors' => $paginator->items(),
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
            'total' => $paginator->total(),
            'per_page' => $paginator->perPage(),
        ]);
    }

    /**
     * Summary statistics for system admin dashboard.
     */
    public function stats(): JsonResponse
    {
        $todayStart = Carbon::today();

        $total = ErrorLog::count();
        $unresolved = ErrorLog::where('status', 'unresolved')->count();
        $today = ErrorLog::where('created_at', '>=', $todayStart)->count();
        $clientCount = ErrorLog::where('source', 'client')->count();
        $serverCount = ErrorLog::where('source', 'server')->count();

        return response()->json([
            'stats' => [
                'total' => $total,
                'unresolved' => $unresolved,
                'today' => $today,
                'client_count' => $clientCount,
                'server_count' => $serverCount,
            ],
        ]);
    }

    /**
     * Mark an error log as resolved.
     */
    public function resolve(ErrorLog $errorLog): JsonResponse
    {
        $errorLog->update([
            'status' => 'resolved',
            'resolved_at' => now(),
        ]);

        return response()->json([
            'message' => 'Error marked as resolved.',
            'error' => $errorLog,
        ]);
    }

    /**
     * Delete an individual error log.
     */
    public function destroy(ErrorLog $errorLog): JsonResponse
    {
        $errorLog->delete();

        return response()->json([
            'message' => 'Error log deleted successfully.',
        ]);
    }

    /**
     * Clear error logs in bulk (either all or resolved only).
     */
    public function clearAll(Request $request): JsonResponse
    {
        $onlyResolved = filter_var($request->input('only_resolved', false), FILTER_VALIDATE_BOOLEAN);

        if ($onlyResolved) {
            $count = ErrorLog::where('status', 'resolved')->delete();
            $message = "Cleared {$count} resolved error logs.";
        } else {
            $count = ErrorLog::count();
            ErrorLog::truncate();
            $message = "Cleared all {$count} error logs.";
        }

        return response()->json([
            'message' => $message,
            'cleared_count' => $count,
        ]);
    }
}
