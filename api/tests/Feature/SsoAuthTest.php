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

    public function test_new_sso_user_is_created_with_default_news_sources(): void
    {
        $default = NewsSource::forceCreate(['name' => 'Default', 'site_url' => 'https://a.test', 'feed_url' => 'https://a.test/rss', 'is_default' => true]);
        NewsSource::forceCreate(['name' => 'Other', 'site_url' => 'https://b.test', 'feed_url' => 'https://b.test/rss', 'is_default' => false]);

        $this->fakeSsoCallback('sso-1', 'jane@example.com', 'Jane');

        $this->get('/api/auth/sso/callback')->assertRedirect(config('services.jepflow_sso.frontend_redirect'));

        $user = User::where('sso_id', 'sso-1')->firstOrFail();
        $this->assertAuthenticatedAs($user);
        $this->assertSame([$default->id], $user->newsSources()->pluck('news_sources.id')->all());
    }

    public function test_existing_local_user_is_linked_without_resetting_sources(): void
    {
        $user = User::factory()->create(['email' => 'jane@example.com']);
        NewsSource::forceCreate(['name' => 'Default', 'site_url' => 'https://a.test', 'feed_url' => 'https://a.test/rss', 'is_default' => true]);

        $this->fakeSsoCallback('sso-1', 'jane@example.com', 'Jane');

        $this->get('/api/auth/sso/callback');

        $this->assertAuthenticatedAs($user->fresh());
        $this->assertSame('sso-1', $user->fresh()->sso_id);
        $this->assertSame(0, $user->newsSources()->count());
    }

    public function test_callback_gets_a_session_even_when_referred_by_the_idp(): void
    {
        $this->fakeSsoCallback('sso-1', 'jane@example.com', 'Jane');

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

        $this->actingAs(User::factory()->create())
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
