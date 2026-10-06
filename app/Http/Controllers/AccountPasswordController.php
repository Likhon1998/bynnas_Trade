<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class AccountPasswordController extends Controller
{
    private const PORTALS = [
        'admin' => ['layout' => 'layouts.app', 'route' => 'account.password'],
        'portal' => ['layout' => 'portal.layouts.app', 'route' => 'portal.account.password'],
        'field' => ['layout' => 'field.layouts.app', 'route' => 'field.account.password'],
    ];

    public function edit(Request $request)
    {
        $portal = $this->portal($request);

        return view('account.password', [
            'layout' => self::PORTALS[$portal]['layout'],
            'action' => route(self::PORTALS[$portal]['route'].'.update'),
            'portal' => $portal,
        ]);
    }

    public function update(Request $request)
    {
        $portal = $this->portal($request);

        $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', 'different:current_password', Password::min(8)],
        ], [
            'current_password.current_password' => 'Your current password is not correct.',
            'password.different' => 'Choose a password different from your current one.',
        ]);

        $user = $request->user();
        $user->forceFill(['password' => Hash::make($request->input('password'))])->save();

        Auth::logoutOtherDevices($request->input('password'));
        $request->session()->regenerate();

        return redirect()
            ->route(self::PORTALS[$portal]['route'])
            ->with('success', 'Password changed. Other devices were signed out.');
    }

    private function portal(Request $request): string
    {
        $name = (string) $request->route()?->getName();

        return match (true) {
            str_starts_with($name, 'portal.') => 'portal',
            str_starts_with($name, 'field.') => 'field',
            default => 'admin',
        };
    }
}
