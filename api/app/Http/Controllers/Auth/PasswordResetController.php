<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Notifications\ResetPasswordNotification;
use App\Support\FrontendUrl;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Throwable;

class PasswordResetController extends Controller
{
    public function forgotPassword(Request $request)
    {
        $validated = $request->validate([
            'email' => ['required', 'string', 'email'],
        ]);

        $frontendBaseUrl = FrontendUrl::resolve($request);

        // The notification sends mail synchronously — a mail provider outage
        // or bad credentials would otherwise throw here and 500 the request.
        // Swallow and log instead so a delivery failure degrades quietly,
        // same as the generic response below (also keeps this endpoint from
        // being usable to enumerate accounts either way).
        try {
            Password::sendResetLink($validated, function (User $user, string $token) use ($frontendBaseUrl) {
                $resetUrl = sprintf(
                    '%s/reset-password?token=%s&email=%s',
                    $frontendBaseUrl,
                    $token,
                    urlencode($user->email),
                );

                $user->notify(new ResetPasswordNotification($token, $resetUrl));
            });
        } catch (Throwable $e) {
            Log::error('Failed to send password reset email: '.$e->getMessage());
        }

        // Always respond the same way whether or not the email is on file,
        // so this endpoint can't be used to enumerate registered accounts.
        return response()->json([
            'message' => 'If an account exists for that email, a password reset link has been sent.',
        ]);
    }

    public function reset(Request $request)
    {
        $validated = $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'confirmed', PasswordRule::defaults()],
        ]);

        $status = Password::reset(
            $validated,
            function (User $user, string $password) {
                $user->forceFill([
                    'password' => Hash::make($password),
                    'remember_token' => Str::random(60),
                ])->save();

                event(new PasswordReset($user));
            },
        );

        if ($status !== Password::PASSWORD_RESET) {
            return response()->json([
                'message' => 'This password reset link is invalid or has expired.',
            ], 422);
        }

        return response()->json([
            'message' => 'Your password has been reset.',
        ]);
    }
}
