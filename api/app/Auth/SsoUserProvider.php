<?php

namespace App\Auth;

use App\Models\User;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\UserProvider;
use Illuminate\Contracts\Session\Session;

/**
 * Resolves the signed-in user from the SSO profile stored in the session at
 * login (see SsoController::callback). There is no local user store, so
 * credentials and remember-me tokens do not exist.
 */
class SsoUserProvider implements UserProvider
{
    public const SESSION_KEY = 'sso_profile';

    public function __construct(private readonly Session $session) {}

    public function retrieveById($identifier): ?Authenticatable
    {
        $profile = $this->session->get(self::SESSION_KEY);

        if (! is_array($profile) || (string) ($profile['id'] ?? '') !== (string) $identifier) {
            return null;
        }

        return User::fromSso($profile);
    }

    public function retrieveByToken($identifier, #[\SensitiveParameter] $token): ?Authenticatable
    {
        return null;
    }

    public function updateRememberToken(Authenticatable $user, #[\SensitiveParameter] $token): void {}

    public function retrieveByCredentials(#[\SensitiveParameter] array $credentials): ?Authenticatable
    {
        return null;
    }

    public function validateCredentials(Authenticatable $user, #[\SensitiveParameter] array $credentials): bool
    {
        return false;
    }

    public function rehashPasswordIfRequired(Authenticatable $user, #[\SensitiveParameter] array $credentials, bool $force = false): void {}
}
