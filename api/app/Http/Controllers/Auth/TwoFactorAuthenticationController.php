<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\Auth\TwoFactorAuthenticationProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class TwoFactorAuthenticationController extends Controller
{
    public function __construct(private TwoFactorAuthenticationProvider $provider) {}

    public function store(Request $request)
    {
        $user = $request->user();

        if ($user->hasEnabledTwoFactorAuthentication()) {
            return response()->json([
                'message' => 'Two-factor authentication is already enabled.',
            ], 422);
        }

        $secret = $this->provider->generateSecretKey();
        $recoveryCodes = $this->provider->generateRecoveryCodes();

        $user->forceFill([
            'two_factor_secret' => $secret,
            'two_factor_recovery_codes' => $recoveryCodes,
            'two_factor_confirmed_at' => null,
        ])->save();

        return response()->json([
            'secret' => $secret,
            'qr_code_url' => $this->provider->qrCodeUrl($user->email, $secret),
            'recovery_codes' => $recoveryCodes,
        ]);
    }

    public function confirm(Request $request)
    {
        $validated = $request->validate([
            'code' => ['required', 'string'],
        ]);

        $user = $request->user();

        if (! $user->two_factor_secret || $user->hasEnabledTwoFactorAuthentication()) {
            return response()->json([
                'message' => 'There is no pending two-factor setup to confirm.',
            ], 422);
        }

        if (! $this->provider->verify($user->two_factor_secret, $validated['code'])) {
            return response()->json([
                'message' => 'The provided code was invalid.',
            ], 422);
        }

        $user->forceFill(['two_factor_confirmed_at' => now()])->save();

        return response()->json([
            'message' => 'Two-factor authentication has been enabled.',
            'recovery_codes' => $user->two_factor_recovery_codes,
        ]);
    }

    public function destroy(Request $request)
    {
        $validated = $request->validate([
            'current_password' => ['required', 'string'],
        ]);

        $user = $request->user();

        if (! Hash::check($validated['current_password'], $user->password)) {
            return response()->json([
                'message' => 'The provided password is incorrect.',
            ], 422);
        }

        $user->forceFill([
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
        ])->save();

        return response()->json(null, 204);
    }

    public function recoveryCodes(Request $request)
    {
        $user = $request->user();

        if (! $user->hasEnabledTwoFactorAuthentication()) {
            return response()->json([
                'message' => 'Two-factor authentication is not enabled.',
            ], 422);
        }

        return response()->json([
            'recovery_codes' => $user->two_factor_recovery_codes,
        ]);
    }

    public function regenerateRecoveryCodes(Request $request)
    {
        $user = $request->user();

        if (! $user->hasEnabledTwoFactorAuthentication()) {
            return response()->json([
                'message' => 'Two-factor authentication is not enabled.',
            ], 422);
        }

        $recoveryCodes = $this->provider->generateRecoveryCodes();

        $user->forceFill(['two_factor_recovery_codes' => $recoveryCodes])->save();

        return response()->json(['recovery_codes' => $recoveryCodes]);
    }
}
