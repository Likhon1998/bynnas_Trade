<?php

namespace App\Http\Controllers\Concerns;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

trait RedirectsWrongPortal
{
    protected function wrongPortal(User $user, string $here): RedirectResponse
    {
        Auth::logout();
        $home = $user->portalLogin();

        return back()
            ->withInput(['email' => $user->email])
            ->withErrors(['email' => "This is a {$home['account']} account, so it can't sign in to the {$here}. Please use the {$home['label']}."])
            ->with('portal_hint', [
                'label' => $home['label'],
                'url' => route($home['route'], ['email' => $user->email]),
            ]);
    }
}
