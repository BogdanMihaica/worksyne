<?php

namespace App\Http\Controllers;

use App\Models\AuthToken;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    private const MAX_LOGIN_ATTEMPTS = 5;

    public function login(Request $request): JsonResponse
    {
        $attributes = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'token_name' => ['sometimes', 'nullable', 'string', 'max:255'],
        ]);

        $throttleKey = $this->throttleKey($request, $attributes['email']);

        if (RateLimiter::tooManyAttempts($throttleKey, self::MAX_LOGIN_ATTEMPTS)) {
            throw ValidationException::withMessages([
                'email' => ['Too many login attempts. Please try again later.'],
            ])->status(429);
        }

        $user = User::query()
            ->where('email', $attributes['email'])
            ->first();

        if (! $this->credentialsAreValid($user, $attributes['password'])) {
            RateLimiter::hit($throttleKey, 60);

            throw ValidationException::withMessages([
                'email' => ['The provided credentials are invalid.'],
            ]);
        }

        if ($user->is_blocked) {
            RateLimiter::hit($throttleKey, 60);

            throw ValidationException::withMessages([
                'email' => ['The provided credentials are invalid.'],
            ]);
        }

        RateLimiter::clear($throttleKey);

        $plainTextToken = $this->generateToken();
        $token = AuthToken::query()->create([
            'user_id' => $user->id,
            'name' => $attributes['token_name'] ?? null,
            'token_hash' => hash('sha256', $plainTextToken),
            'expires_at' => now()->addMinutes(config('auth.api_token_expiration_minutes')),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return response()->json([
            'token' => $plainTextToken,
            'token_type' => 'Bearer',
            'expires_at' => $token->expires_at,
            'user' => $user,
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        $user = $request->user()->load(['companyUser.company']);
        $response = $user->toArray();

        $role = $user->is_admin ? 'admin' : 'user';

        if ($user->companyUser) {
            $role = $user->companyUser->role;
        }

        $response['role'] = $role;

        return response()->json($response);
    }

    public function logout(Request $request): JsonResponse
    {
        $token = $request->attributes->get('auth_token');

        $token?->forceFill(['revoked_at' => now()])->save();

        return response()->json(null, 204);
    }

    private function credentialsAreValid(?User $user, string $password): bool
    {
        $passwordHash = $user?->password ?? '$2y$12$gk/lege35hxzO7RJZwgjfuAUV2Z06EI2nvG8ROdjRN1pG9Km5idNW';

        return Hash::check($password, $passwordHash) && $user !== null;
    }

    private function throttleKey(Request $request, string $email): string
    {
        return 'login:'.sha1(Str::lower($email).'|'.$request->ip());
    }

    private function generateToken(): string
    {
        return rtrim(strtr(base64_encode(random_bytes(64)), '+/', '-_'), '=');
    }
}
