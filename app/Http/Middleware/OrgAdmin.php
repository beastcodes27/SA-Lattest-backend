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
            return response()->json(['message' => 'Your free trial has ended. Contact your provider to renew access.'], 403);
        }

        return $next($request);
    }
}
