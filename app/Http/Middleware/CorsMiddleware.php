<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CorsMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        if ($request->isMethod('options')) {
            $response = response('', 204);
        } else {
            $response = $next($request);
        }

        $allowedOriginsRaw = (string) env('CORS_ALLOWED_ORIGINS', '');
        $allowedOrigins = array_values(array_filter(array_map('trim', explode(',', $allowedOriginsRaw))));

        $origin = (string) $request->headers->get('Origin', '');
        if ($origin !== '' && in_array($origin, $allowedOrigins, true)) {
            $response->headers->set('Access-Control-Allow-Origin', $origin);
            $response->headers->set('Vary', 'Origin');
        } elseif ($allowedOriginsRaw === '') {
            // Backward-compatible dev default. In production, set CORS_ALLOWED_ORIGINS.
            $response->headers->set('Access-Control-Allow-Origin', '*');
        }

        $response->headers->set('Access-Control-Allow-Methods', 'GET, POST, OPTIONS');
        $response->headers->set('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-API-Key');
        $response->headers->set('Access-Control-Max-Age', '86400');

        return $response;
    }
}

