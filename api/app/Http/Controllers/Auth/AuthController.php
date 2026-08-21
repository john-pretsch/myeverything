<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\NewsSource;
use App\Models\User;
use App\Services\Auth\TwoFactorAuthenticationProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;

class AuthController extends Controller
{
    private const TWO_FACTOR_LOGIN_CACHE_PREFIX = 'two-factor-login:';

    public function register(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
        ]);

        $defaultSourceIds = NewsSource::where('is_default', true)->orderBy('name')->pluck('id');
        $user->newsSources()->attach(
            $defaultSourceIds->mapWithKeys(fn ($id, $position) => [$id => ['position' => $position]]),
        );

        Auth::login($user);

        return response()->json($user);
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::validate($credentials)) {
            return response()->json([
                'message' => 'The provided credentials are incorrect.',
            ], 422);
        }

        $user = User::where('email', $credentials['email'])->first();

        // Feature-flagged off by default (see config/features.php) — until
        // FEATURE_2FA is enabled, no user can have a confirmed 2FA secret
        // (enrollment is gated behind the same flag), so this branch is
        // unreachable in production today. It's wired up so the login
        // challenge can be tested end to end ahead of activating it.
        if (config('features.two_factor_auth') && $user->hasEnabledTwoFactorAuthentication()) {
            $loginToken = Str::random(40);
            Cache::put(self::TWO_FACTOR_LOGIN_CACHE_PREFIX.$loginToken, $user->id, now()->addMinutes(5));

            return response()->json([
                'two_factor_required' => true,
                'login_token' => $loginToken,
            ]);
        }

        Auth::login($user);
        $request->session()->regenerate();

        return response()->json($request->user());
    }

    public function twoFactorChallenge(Request $request, TwoFactorAuthenticationProvider $provider)
    {
        $validated = $request->validate([
            'login_token' => ['required', 'string'],
            'code' => ['nullable', 'string'],
            'recovery_code' => ['nullable', 'string'],
        ]);

        if (empty($validated['code']) && empty($validated['recovery_code'])) {
            return response()->json([
                'message' => 'An authentication code or recovery code is required.',
            ], 422);
        }

        $cacheKey = self::TWO_FACTOR_LOGIN_CACHE_PREFIX.$validated['login_token'];
        $userId = Cache::get($cacheKey);
        $user = $userId ? User::find($userId) : null;

        if (! $user || ! $user->hasEnabledTwoFactorAuthentication()) {
            Cache::forget($cacheKey);

            return response()->json([
                'message' => 'This login attempt has expired. Please log in again.',
            ], 422);
        }

        $verified = false;

        if (! empty($validated['code'])) {
            $verified = $provider->verify($user->two_factor_secret, $validated['code']);
        } elseif (! empty($validated['recovery_code'])) {
            $recoveryCodes = $user->two_factor_recovery_codes ?? [];

            if (in_array($validated['recovery_code'], $recoveryCodes, true)) {
                $verified = true;

                $user->forceFill([
                    'two_factor_recovery_codes' => array_values(array_diff($recoveryCodes, [$validated['recovery_code']])),
                ])->save();
            }
        }

        if (! $verified) {
            return response()->json([
                'message' => 'The provided code was invalid.',
            ], 422);
        }

        Cache::forget($cacheKey);

        Auth::login($user);
        $request->session()->regenerate();

        return response()->json($request->user());
    }

    public function logout(Request $request)
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json(null, 204);
    }

    public function me(Request $request)
    {
        return response()->json($request->user());
    }
}
