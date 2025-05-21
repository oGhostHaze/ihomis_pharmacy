<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class BypassContentFilter
{
    public function handle(Request $request, Closure $next)
    {
        // If this is a Livewire request, add custom headers
        if (strpos($request->path(), 'livewire/message') !== false) {
            $response = $next($request);

            // Add headers to prevent network security devices from intercepting
            $response->header('X-Content-Type-Options', 'nosniff');
            $response->header('X-Frame-Options', 'SAMEORIGIN');
            $response->header('X-XSS-Protection', '1; mode=block');

            return $response;
        }

        return $next($request);
    }
}
