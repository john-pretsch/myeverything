<?php

namespace Tests\Feature;

use App\Models\NewsSource;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;
use Mockery;
use Tests\TestCase;

class SsoAuthTest extends TestCase
{
    use RefreshDatabase;

    private function fakeSsoCallback(string $ssoId, string $email, string $name): void
    {
        $ssoUser = (new SocialiteUser)->map(['id' => $ssoId, 'email' => $email, 'name' => $name]);

        $driver = Mockery::mock();
        $driver->shouldReceive('user')->andReturn($ssoUser);
        Socialite::shouldReceive('driver')->with('jepflow_sso')->andReturn($driver);
    }

    public function test_first_login_attaches_default_news_sources_once(): void
    {
        $default = NewsSource::forceCreate(['name' => 'Default', 'site_url' => 'https://a.test', 'feed_url' => 'https://a.test/rss', 'is_default' => true]);
        NewsSource::forceCreate(['name' => 'Other', 'site_url' => 'https://b.test', 'feed_url' => 'https://b.test/rss', 'is_default' => false]);

        $this->fakeSsoCallback('7', 'jane@example.com', 'Jane');

        $this->get('/api/auth/sso/callback')->assertRedirect(config('services.jepflow_sso.frontend_redirect'));

        $this->assertSame(7, auth()->id());
        $this->assertSame([$default->id], User::fromSso(['id' => 7])->newsSources()->pluck('news_sources.id')->all());

        User::fromSso(['id' => 7])->newsSources()->detach();
        $this->get('/api/auth/sso/callback');

        $this->assertSame(0, User::fromSso(['id' => 7])->newsSources()->count());
    }

    public function test_session_user_comes_from_the_sso_profile(): void
    {
        $this->fakeSsoCallback('7', 'jane@example.com', 'Jane');
        $this->get('/api/auth/sso/callback');

        $this->getJson('/api/user')
            ->assertOk()
            ->assertExactJson(['id' => 7, 'name' => 'Jane', 'email' => 'jane@example.com', 'role' => 'user']);
    }

    public function test_admin_role_comes_from_configured_sso_ids(): void
    {
        config(['auth.admin_sso_ids' => [7]]);

        $this->assertTrue(User::fromSso(['id' => 7])->isAdmin());
        $this->assertFalse(User::fromSso(['id' => 8])->isAdmin());
    }

    public function test_market_preferences_are_saved_per_sso_user(): void
    {
        $this->actingAs(User::fromSso(['id' => 7]))
            ->putJson('/api/market/preferences', ['movers_count' => 25, 'movers_region' => 'ca'])
            ->assertOk();

        $this->actingAs(User::fromSso(['id' => 7]))
            ->getJson('/api/market/preferences')
            ->assertJsonPath('data.movers_count', 25)
            ->assertJsonPath('data.movers_region', 'ca');
    }

    public function test_callback_gets_a_session_even_when_referred_by_the_idp(): void
    {
        $this->fakeSsoCallback('7', 'jane@example.com', 'Jane');

        $this->withHeader('Referer', 'https://sso.jepflow.io/login')
            ->get('/api/auth/sso/callback')
            ->assertRedirect();

        $this->assertAuthenticated();
    }

    public function test_local_auth_endpoints_are_gone(): void
    {
        foreach (['/api/login', '/api/register', '/api/forgot-password', '/api/reset-password', '/api/two-factor-challenge'] as $path) {
            $this->postJson($path, ['email' => 'jane@example.com', 'password' => 'secret'])->assertNotFound();
        }
    }

    public function test_logout_ends_session_and_points_to_sso_logout(): void
    {
        config([
            'services.jepflow_sso.base_url' => 'https://sso.jepflow.io',
            'services.jepflow_sso.client_id' => 'me-client',
            'services.jepflow_sso.frontend_redirect' => 'https://myeverything.jepflow.io',
        ]);

        $this->actingAs(User::fromSso(['id' => 7]))
            ->withHeader('Referer', 'http://localhost:3000/')
            ->postJson('/api/logout')
            ->assertOk()
            ->assertJsonPath('redirect', 'https://sso.jepflow.io/logout/client?'.http_build_query([
                'client_id' => 'me-client',
                'redirect_uri' => 'https://myeverything.jepflow.io',
            ]));

        $this->assertGuest('web');
    }
}
