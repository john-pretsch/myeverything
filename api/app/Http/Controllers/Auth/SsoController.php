<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
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
        }

        Auth::login($user, remember: true);

        request()->session()->regenerate();

        return redirect(config('services.jepflow_sso.frontend_redirect'));
    }
}
