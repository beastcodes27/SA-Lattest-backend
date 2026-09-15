<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SystemAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ! in_array($user->role, ['superadmin', 'minor_admin', 'sysadmin']) || ! $user->active) {
            return response()->json(['message' => 'System admin access required.'], 403);
        }

        return $next($request);
    }
}
