<?php

namespace App\Http\Controllers;

use App\Mail\PasswordResetLink;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\Validation\ValidationException;
use Throwable;

class PasswordResetController extends Controller
{
    private const MAX_LINK_REQUESTS = 3;

    public function requestLink(Request $request): JsonResponse
    {
        $attributes = $request->validate([
            'email' => ['required', 'email', 'max:255'],
        ]);

        $email = Str::lower($attributes['email']);
        $throttleKey = 'password-reset:'.sha1($email);

        if (! RateLimiter::tooManyAttempts($throttleKey, self::MAX_LINK_REQUESTS)) {
            RateLimiter::hit($throttleKey, 15 * 60);

            $user = User::query()
                ->where('email', $email)
                ->first();

            if ($user && ! $user->is_blocked) {
                try {
                    $token = Password::broker('users')->createToken($user);

                    Mail::to($user->email)->send(new PasswordResetLink($user, $token));
                } catch (Throwable $exception) {
                    report($exception);
                }
            }
        }

        return response()->json([
            'message' => 'If an account exists for that email, a password reset link has been sent.',
        ]);
    }

    public function reset(Request $request): JsonResponse
    {
        $attributes = $request->validate([
            'email' => ['required', 'email', 'max:255'],
            'token' => ['required', 'string'],
            'password' => [
                'required',
                'confirmed',
                PasswordRule::min(12)->mixedCase()->numbers()->symbols(),
            ],
        ]);

        $user = User::query()
            ->where('email', Str::lower($attributes['email']))
            ->first();

        if (! $user || $user->is_blocked) {
            throw ValidationException::withMessages([
                'token' => ['This password reset link is invalid or has expired.'],
            ]);
        }

        $status = Password::broker('users')->reset(
            $attributes,
            function (User $user, string $password) {
                $user->forceFill([
                    'password' => Hash::make($password),
                    'remember_token' => Str::random(60),
                ])->save();

                $user->authTokens()
                    ->whereNull('revoked_at')
                    ->update(['revoked_at' => now()]);

                event(new PasswordReset($user));
            },
        );

        if ($status !== Password::PASSWORD_RESET) {
            throw ValidationException::withMessages([
                'token' => ['This password reset link is invalid or has expired.'],
            ]);
        }

        return response()->json([
            'message' => 'Your password has been reset. You can now sign in.',
        ]);
    }
}
