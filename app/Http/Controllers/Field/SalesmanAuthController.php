<?php

namespace App\Http\Controllers\Field;

use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class SalesmanAuthController
{
    public function __construct(private AuditLogger $auditLogger) {}

    public function showLogin()
    {
        if (Auth::check() && Auth::user()->portal === User::PORTAL_SALESMAN) {
            return redirect()->route('field.dashboard');
        }

        return view('field.auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = User::query()->where('email', $credentials['email'])->first();

        if (! $user || ! Auth::attempt($credentials, $request->boolean('remember'))) {
            throw ValidationException::withMessages([
                'email' => 'These credentials do not match our records.',
            ]);
        }

        if (! $user->is_active || $user->portal !== User::PORTAL_SALESMAN) {
            Auth::logout();

            throw ValidationException::withMessages([
                'email' => 'This account is not authorized for the field app.',
            ]);
        }

        if (! $user->salesmanProfile?->is_active) {
            Auth::logout();

            throw ValidationException::withMessages([
                'email' => 'Your salesman profile is inactive.',
            ]);
        }

        $request->session()->regenerate();
        $user->forceFill(['last_login_at' => now()])->save();

        $this->auditLogger->log('auth', 'login', "Salesman {$user->email} signed in", $user, null, null, $user);

        return redirect()->intended(route('field.dashboard'));
    }

    public function logout(Request $request)
    {
        $user = Auth::user();

        if ($user) {
            $this->auditLogger->log('auth', 'logout', "Salesman {$user->email} signed out", $user, null, null, $user);
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('field.login');
    }
}
