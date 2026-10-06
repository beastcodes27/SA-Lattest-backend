<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class OrgAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || $user->role !== 'admin') {
            return response()->json(['message' => 'Admin access required.'], 403);
        }

        if (! $user->organization || $user->organization->status !== 'active') {
            return response()->json(['message' => 'Your organization is not active yet.'], 403);
        }

        if (! $user->organization->isAccessible()) {
            if ($request->is('api/admin/subscription*', 'admin/subscription*', 'api/admin/promo*', 'admin/promo*')) {
                return $next($request);
            }

            return response()->json([
                'message' => 'Your subscription or free trial is inactive. Please subscribe to a package to access organization management features.',
                'code' => 'SUBSCRIPTION_REQUIRED',
                'accessible' => false,
            ], 403);
        }

        return $next($request);
    }
}
