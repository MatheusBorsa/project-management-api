<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Utils\ApiResponseUtil;

class CheckPremium
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user || !$user->isPremium()) {
            return ApiResponseUtil::error(
                'Premium feature',
                null,
                403
            );
        }

        return $next($request);
    }
}
