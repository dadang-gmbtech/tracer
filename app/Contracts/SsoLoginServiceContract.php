<?php

namespace App\Contracts;

use App\Models\User;

/**
 * Abstraction over "Login Dengan Akun UNSOED (SSO)". Swap the bound implementation
 * (see AppServiceProvider) for a real SAML/OAuth client without touching
 * LoginController.
 */
interface SsoLoginServiceContract
{
    /**
     * Resolve (and if needed provision) the locally authenticated user for the
     * current SSO session/assertion.
     */
    public function login(): User;
}
