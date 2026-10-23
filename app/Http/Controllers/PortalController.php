<?php

namespace App\Http\Controllers;

use App\Mail\SystemAdminLoginAlert;
use App\Models\User;
use App\Services\EmailService;
use App\Support\TotpService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class PortalController extends Controller
{
    private const SYSTEM_ROLES = ['superadmin', 'minor_admin', 'sysadmin'];

    public function showLogin(Request $request)
    {
        $user = $request->user();
        if ($user && in_array($user->role, self::SYSTEM_ROLES, true) && $user->active) {
            return redirect()->route('portal.dashboard');
        }

        return view('portal.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'employee_id' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $user = User::where('employee_id', trim($credentials['employee_id']))->first();

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            return back()->withErrors(['employee_id' => 'Invalid system admin credentials.']);
        }

        if (! in_array($user->role, self::SYSTEM_ROLES, true) || ! $user->active) {
            return back()->withErrors(['employee_id' => 'This account is not an active system admin.']);
        }

        $request->session()->regenerate();

        if ($user->mfa_enabled && $user->mfa_secret) {
            $request->session()->put('mfa_user_id', $user->id);
            $request->session()->put('mfa_remember', $request->boolean('remember'));

            return redirect()->route('portal.mfa');
        }

        Auth::login($user, $request->boolean('remember'));
        $request->session()->regenerate();

        $this->sendLoginAlert($user, $request);

        return redirect()->route('portal.dashboard');
    }

    public function showMfa(Request $request)
    {
        if ($request->user()) {
            return redirect()->route('portal.dashboard');
        }

        $user = $this->pendingMfaUser($request);

        if (! $user) {
            return redirect()->route('portal.login')->withErrors(['employee_id' => 'Start signing in again.']);
        }

        return view('portal.mfa', ['employeeId' => $user->employee_id]);
    }

    public function verifyMfa(Request $request)
    {
        $request->validate([
            'code' => ['required', 'digits:6'],
        ]);

        $user = $this->pendingMfaUser($request);

        if (! $user) {
            return redirect()->route('portal.login')->withErrors(['employee_id' => 'Start signing in again.']);
        }

        if (! TotpService::verify((string) $user->mfa_secret, (string) $request->input('code'))) {
            return back()->withErrors(['code' => 'That authentication code is invalid or has expired.']);
        }

        $remember = (bool) $request->session()->pull('mfa_remember', false);
        $request->session()->forget('mfa_user_id');

        Auth::login($user, $remember);
        $request->session()->regenerate();

        $this->sendLoginAlert($user, $request);

        return redirect()->route('portal.dashboard');
    }

    public function setupMfa(Request $request)
    {
        $user = $request->user();

        if ($user->mfa_enabled) {
            return response()->json(['message' => 'Two-factor authentication is already enabled.'], 422);
        }

        $secret = TotpService::generateSecret();
        $request->session()->put('mfa_setup_secret', $secret);

        return response()->json([
            'secret' => $secret,
            'otpauth_uri' => TotpService::provisioningUri($user, $secret),
        ]);
    }

    public function enableMfa(Request $request)
    {
        $request->validate(['code' => ['required', 'digits:6']]);

        $secret = (string) $request->session()->get('mfa_setup_secret', '');

        if ($secret === '' || ! TotpService::verify($secret, (string) $request->input('code'))) {
            return response()->json(['message' => 'That authentication code is invalid. Please try again.'], 422);
        }

        $request->user()->forceFill([
            'mfa_secret' => $secret,
            'mfa_enabled' => true,
            'mfa_confirmed_at' => now(),
        ])->save();

        $request->session()->forget('mfa_setup_secret');

        return response()->json(['message' => 'Two-factor authentication enabled.']);
    }

    public function disableMfa(Request $request)
    {
        $request->validate(['password' => ['required', 'string']]);

        if (! Hash::check((string) $request->input('password'), $request->user()->password)) {
            return response()->json(['message' => 'Your password is incorrect.'], 422);
        }

        $request->user()->forceFill([
            'mfa_secret' => null,
            'mfa_enabled' => false,
            'mfa_confirmed_at' => null,
        ])->save();

        return response()->json(['message' => 'Two-factor authentication disabled.']);
    }

    public function dashboard()
    {
        return view('portal.dashboard');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('portal.login');
    }

    private function pendingMfaUser(Request $request): ?User
    {
        $userId = $request->session()->get('mfa_user_id');

        if (! $userId) {
            return null;
        }

        $user = User::find($userId);

        if (! $user || ! $user->active || ! in_array($user->role, self::SYSTEM_ROLES, true) || ! $user->mfa_enabled) {
            $request->session()->forget('mfa_user_id');

            return null;
        }

        return $user;
    }

    private function sendLoginAlert(User $user, Request $request): void
    {
        EmailService::send(
            $user->email,
            new SystemAdminLoginAlert(
                $user,
                (string) $request->ip(),
                $request->userAgent(),
                now()->timezone('Africa/Dar_es_Salaam')->format('d M Y, H:i').' (EAT)',
            )
        );
    }
}
