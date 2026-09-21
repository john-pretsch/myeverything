<?php

namespace App\Socialite;

use Illuminate\Support\Facades\Http;
use Laravel\Socialite\Two\AbstractProvider;
use Laravel\Socialite\Two\ProviderInterface;
use Laravel\Socialite\Two\User as SocialiteUser;

class JepflowSsoProvider extends AbstractProvider implements ProviderInterface
{
    protected $scopes = [];

    protected function getAuthUrl($state): string
    {
        return $this->buildAuthUrlFromBase(rtrim(config('services.jepflow_sso.base_url'), '/').'/oauth/authorize', $state);
    }

    protected function getTokenUrl(): string
    {
        return rtrim(config('services.jepflow_sso.base_url'), '/').'/oauth/token';
    }

    protected function getTokenFields($code): array
    {
        return array_merge(parent::getTokenFields($code), [
            'grant_type' => 'authorization_code',
        ]);
    }

    protected function getUserByToken($token): array
    {
        return Http::withToken($token)
            ->get(rtrim(config('services.jepflow_sso.base_url'), '/').'/api/user')
            ->throw()
            ->json();
    }

    protected function mapUserToObject(array $user): SocialiteUser
    {
        return (new SocialiteUser)->setRaw($user)->map([
            'id' => $user['id'],
            'name' => $user['name'],
            'email' => $user['email'],
        ]);
    }
}
