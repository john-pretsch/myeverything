<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\NewsSource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;

class SsoController extends Controller
{
    public function redirect(): RedirectResponse
    {
        return Socialite::driver('jepflow_sso')->redirect();
    }

    public function callback(): RedirectResponse
    {
        $ssoUser = Socialite::driver('jepflow_sso')->user();

        $user = User::where('sso_id', $ssoUser->id)->first();

        if (! $user) {
            $user = User::where('email', $ssoUser->email)->first();

            if ($user) {
                $user->update(['sso_id' => $ssoUser->id]);
            }
        }

        if (! $user) {
            $user = User::create([
                'name' => $ssoUser->name,
                'email' => $ssoUser->email,
                'sso_id' => $ssoUser->id,
                'password' => null,
                'email_verified_at' => now(),
            ]);

            $defaultSourceIds = NewsSource::where('is_default', true)->orderBy('name')->pluck('id');
            $user->newsSources()->attach(
                $defaultSourceIds->mapWithKeys(fn ($id, $position) => [$id => ['position' => $position]]),
            );
        }

        Auth::login($user, remember: true);

        request()->session()->regenerate();

        return redirect(config('services.jepflow_sso.frontend_redirect'));
    }

    /**
     * End the local session, then hand the browser to the IdP so the
     * jepflow.io SSO session ends too (single logout).
     */
    public function logout(Request $request): JsonResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        $query = http_build_query([
            'client_id' => config('services.jepflow_sso.client_id'),
            'redirect_uri' => config('services.jepflow_sso.frontend_redirect'),
        ]);

        return response()->json([
            'redirect' => rtrim(config('services.jepflow_sso.base_url'), '/').'/logout/client?'.$query,
        ]);
    }
}
