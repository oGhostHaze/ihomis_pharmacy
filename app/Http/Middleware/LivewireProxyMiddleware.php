<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class LivewireProxyMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        // Only apply to our proxy route
        if ($request->is('livewire-proxy')) {
            $response = $next($request);

            // Add CORS headers
            $response->header('Access-Control-Allow-Origin', $request->header('Origin', '*'));
            $response->header('Access-Control-Allow-Methods', 'POST, GET, OPTIONS');
            $response->header('Access-Control-Allow-Headers', 'Content-Type, X-Requested-With, X-CSRF-TOKEN');
            $response->header('Access-Control-Allow-Credentials', 'true');

            return $response;
        }

        return $next($request);
    }
}
