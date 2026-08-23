<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class AuthController extends Controller
{
    private const ADMIN_EMAIL = 'admin@bynnastrade.com';

    private const ADMIN_PASSWORD = '12345678';

    public function showLogin()
    {
        if (session('admin_authenticated')) {
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

        if (
            strcasecmp($credentials['email'], self::ADMIN_EMAIL) === 0
            && $credentials['password'] === self::ADMIN_PASSWORD
        ) {
            $request->session()->regenerate();
            $request->session()->put('admin_authenticated', true);
            $request->session()->put('admin_name', 'Super Admin');
            $request->session()->put('admin_email', self::ADMIN_EMAIL);

            return redirect()->intended(route('dashboard'));
        }

        return back()
            ->withInput($request->only('email'))
            ->withErrors(['email' => 'These credentials do not match our records.']);
    }

    public function logout(Request $request)
    {
        $request->session()->forget(['admin_authenticated', 'admin_name', 'admin_email']);
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
