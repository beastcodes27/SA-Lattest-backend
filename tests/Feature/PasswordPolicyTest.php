<?php

namespace Tests\Feature;

use App\Support\PasswordPolicy;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class PasswordPolicyTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'auth.password_policy.min_length' => 12,
            'auth.password_policy.check_breaches' => true,
        ]);
    }

    public function test_passwords_shorter_than_twelve_characters_are_rejected(): void
    {
        $validator = Validator::make(['password' => 'Short@123'], [
            'password' => [PasswordPolicy::rule()],
        ]);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('password', $validator->errors()->toArray());
    }

    public function test_breached_passwords_are_rejected(): void
    {
        $password = 'Password@1234';
        $hash = strtoupper(sha1($password));
        $prefix = substr($hash, 0, 5);
        $suffix = substr($hash, 5);

        Http::fake([
            'api.pwnedpasswords.com/*' => Http::response($suffix.':42', 200),
        ]);

        $validator = Validator::make(['password' => $password], [
            'password' => [PasswordPolicy::rule()],
        ]);

        $this->assertTrue($validator->fails());
        $this->assertSame(
            'The given password has appeared in a data leak. Please choose a different password.',
            $validator->errors()->first('password')
        );
    }

    public function test_strong_unbreached_passwords_are_accepted(): void
    {
        Http::fake([
            'api.pwnedpasswords.com/*' => Http::response('', 200),
        ]);

        $validator = Validator::make(['password' => 'FreshStrongPass@2026'], [
            'password' => [PasswordPolicy::rule()],
        ]);

        $this->assertFalse($validator->fails());
    }
}
