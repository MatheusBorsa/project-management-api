<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use App\Utils\ApiResponseUtil;
use Symfony\Component\HttpFoundation\Response;

class RateLimitMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $key = "rate_limit:{$request->ip()}";
        $limit = 60;
        $decay = 60;

        $requests = Cache::get($key, 0);

        if ($requests >= $limit) {
            return ApiResponseUtil::error(
                'Too many requests.',
                null,
                Response::HTTP_TOO_MANY_REQUESTS,
            );
        }

        Cache::put($key, $requests + 1, $decay);

        return $next($request);
    }
}
