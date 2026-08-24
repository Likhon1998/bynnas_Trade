<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function __construct(private AuditLogger $auditLogger) {}

    public function showLogin()
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }

        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = User::query()->where('email', $credentials['email'])->first();

        if (! $user || ! Auth::attempt([
            'email' => $credentials['email'],
            'password' => $credentials['password'],
        ], $request->boolean('remember'))) {
            throw ValidationException::withMessages([
                'email' => 'These credentials do not match our records.',
            ]);
        }

        if (! $user->is_active) {
            Auth::logout();

            throw ValidationException::withMessages([
                'email' => 'This account has been deactivated. Contact Super Admin.',
            ]);
        }

        if ($user->portal !== User::PORTAL_ADMIN && ! $user->isSuperAdmin()) {
            Auth::logout();

            throw ValidationException::withMessages([
                'email' => 'This account is not authorized for the admin portal.',
            ]);
        }

        $request->session()->regenerate();

        $user->forceFill(['last_login_at' => now()])->save();

        $this->auditLogger->log(
            'auth',
            'login',
            "User {$user->email} signed in",
            $user,
            null,
            null,
            $user,
        );

        return redirect()->intended(route('dashboard'));
    }

    public function logout(Request $request)
    {
        $user = Auth::user();

        if ($user) {
            $this->auditLogger->log(
                'auth',
                'logout',
                "User {$user->email} signed out",
                $user,
                null,
                null,
                $user,
            );
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
