<?php

namespace App\Http\Middleware;

use App\Models\AuthToken;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateApiToken
{
    /**
     * Authenticate the request using API token authentication.
     * Checks for a valid API token in the Authorization header and sets the authenticated user.
     * Returns a 401 Unauthorized response if authentication fails.
     * 
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $plainTextToken = $request->bearerToken();

        if (!is_string($plainTextToken) || $plainTextToken === '') {
            return $this->unauthorized();
        }

        $token = AuthToken::query()
            ->with('user')
            ->where('token_hash', hash('sha256', $plainTextToken))
            ->whereNull('revoked_at')
            ->where('expires_at', '>', now())
            ->first();

        if (!$token || !$token->user || $token->user->is_blocked) {
            return $this->unauthorized();
        }

        $token->forceFill(['last_used_at' => now()])->save();

        Auth::setUser($token->user);
        $request->setUserResolver(fn () => $token->user);
        $request->attributes->set('auth_token', $token);

        return $next($request);
    }

    private function unauthorized(): Response
    {
        return response()->json([
            'message' => 'Unauthenticated.',
        ], 401);
    }
}
