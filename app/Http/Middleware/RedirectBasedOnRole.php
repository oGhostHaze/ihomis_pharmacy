<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RedirectBasedOnRole
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->is('dashboard') && auth()->check() && auth()->user()->hasRole('inventory-viewer')) {
            return redirect()->route('dashboard2');
        }

        // Rest of your middleware code
        return $next($request);
    }
}
