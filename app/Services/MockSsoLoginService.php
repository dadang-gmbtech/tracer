<?php

namespace App\Services;

use App\Contracts\SsoLoginServiceContract;
use App\Models\User;

/**
 * Development stand-in for UNSOED's SSO. Replace the binding in
 * AppServiceProvider with a real SAML/OAuth implementation for production.
 */
class MockSsoLoginService implements SsoLoginServiceContract
{
    public function login(): User
    {
        return User::firstOrCreate(
            ['email' => 'sso.demo@unsoed.ac.id'],
            ['name' => 'Demo SSO User', 'password' => bcrypt(str()->random(32)), 'status' => 'active']
        );
    }
}
