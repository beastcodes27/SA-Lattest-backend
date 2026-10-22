<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PortalController extends Controller
{
    public function showLogin(Request $request)
    {
        $user = $request->user();
        if ($user && in_array($user->role, ['superadmin', 'minor_admin', 'sysadmin'], true) && $user->active) {
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

        if (Auth::attempt(['employee_id' => $credentials['employee_id'], 'password' => $credentials['password']], $request->boolean('remember'))) {
            $user = Auth::user();

            if (! in_array($user->role, ['superadmin', 'minor_admin', 'sysadmin'], true) || ! $user->active) {
                Auth::logout();
                $request->session()->invalidate();

                return back()->withErrors(['employee_id' => 'This account is not an active system admin.']);
            }

            $request->session()->regenerate();

            return redirect()->route('portal.dashboard');
        }

        return back()->withErrors(['employee_id' => 'Invalid system admin credentials.']);
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
}
