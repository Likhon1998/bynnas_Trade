<?php

namespace Tests;

use Illuminate\Contracts\Auth\Authenticatable as UserContract;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * Switching users mid-test should behave like a fresh login, so AuthenticateSession
     * doesn't treat the previous user's password hash as a changed password.
     */
    public function actingAs(UserContract $user, $guard = null)
    {
        if ($this->app->bound('session.store')) {
            $this->app['session.store']->forget('password_hash_'.($guard ?? config('auth.defaults.guard')));
        }

        return parent::actingAs($user, $guard);
    }
}
