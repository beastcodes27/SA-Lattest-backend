<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $forwardedProto = strtolower((string) $request->header('X-Forwarded-Proto', ''));
        $isSecure = $request->secure() || $forwardedProto === 'https';

        if (app()->environment('production') && ! $isSecure && ! $request->is('up')) {
            return redirect()->away(
                'https://'.$request->getHttpHost().$request->getRequestUri(),
                301
            );
        }

        $nonce = base64_encode(random_bytes(18));
        View::share('cspNonce', $nonce);

        $response = $next($request);

        $response->headers->remove('X-Powered-By');
        if (function_exists('header_remove')) {
            header_remove('X-Powered-By');
        }

        if ($request->is('portal', 'portal/*', 'api', 'api/*')) {
            $response->headers->set('X-Robots-Tag', 'noindex, nofollow');
        }

        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set(
            'Permissions-Policy',
            'camera=(), microphone=(), geolocation=(), payment=(), usb=()'
        );

        if ($isSecure) {
            $response->headers->set(
                'Strict-Transport-Security',
                'max-age=31536000; includeSubDomains; preload'
            );
        }

        $csp = implode('; ', [
            "default-src 'self'",
            "base-uri 'self'",
            "object-src 'none'",
            "frame-ancestors 'none'",
            "form-action 'self'",
            "img-src 'self' data: https:",
            "font-src 'self' data: https://fonts.gstatic.com",
            "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com",
            "script-src 'self' 'nonce-{$nonce}'",
            "script-src-elem 'self' 'nonce-{$nonce}'",
            "script-src-attr 'unsafe-inline'",
            "connect-src 'self'",
            'upgrade-insecure-requests',
        ]);

        $response->headers->set('Content-Security-Policy', $csp);

        return $response;
    }
}
