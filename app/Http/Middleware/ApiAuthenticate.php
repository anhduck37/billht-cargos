<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class ApiAuthenticate
{
    public function handle($request, Closure $next): Response
    {
        $response = $next($request);

        if (!auth('api_jwt')->check()) {
            return new JsonResponse(['message' => 'Unauthorized'], 401);
        }
        return $response;
    }
}
