<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureFeature
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next, string $featureKey): Response
    {
        $company = $request->user()?->companyUser?->company;

        if (! $company?->subscriptionPlan?->features()->where('key', $featureKey)->exists()) {
            return response()->json([
                'message' => 'Upgrade your subscription to use this feature.',
            ], 403);
        }

        return $next($request);
    }
}
