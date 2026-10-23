<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SecurityHeadersTest extends TestCase
{
    use RefreshDatabase;

    public function test_portal_responses_include_security_headers(): void
    {
        $response = $this->get('https://localhost/portal');

        $response->assertOk();
        $response->assertHeader('X-Frame-Options', 'DENY');
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->assertHeader('X-Robots-Tag', 'noindex, nofollow');
        $response->assertHeaderMissing('X-Powered-By');

        $this->assertStringContainsString('camera=()', (string) $response->headers->get('Permissions-Policy'));
        $this->assertStringContainsString('max-age=31536000', (string) $response->headers->get('Strict-Transport-Security'));
        $this->assertStringContainsString('includeSubDomains', (string) $response->headers->get('Strict-Transport-Security'));
        $this->assertStringContainsString('preload', (string) $response->headers->get('Strict-Transport-Security'));

        $csp = (string) $response->headers->get('Content-Security-Policy');
        $this->assertStringContainsString("default-src 'self'", $csp);
        $this->assertStringContainsString("frame-ancestors 'none'", $csp);
        $this->assertStringContainsString("script-src 'self' 'nonce-", $csp);
        $this->assertStringContainsString("object-src 'none'", $csp);
    }

    public function test_http_requests_are_redirected_to_https_in_production(): void
    {
        $this->app->detectEnvironment(fn () => 'production');

        $this->get('http://localhost/portal')
            ->assertStatus(301)
            ->assertRedirect('https://localhost/portal');
    }
}
