<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureRole
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        if (! in_array($this->role($request), $roles, true)) {
            return response()->json([
                'message' => 'Forbidden.',
            ], 403);
        }

        return $next($request);
    }

    private function role(Request $request): string
    {
        $user = $request->user();

        if ($user?->is_admin) {
            return 'admin';
        }

        return $user?->companyUser?->role ?? 'user';
    }
}
