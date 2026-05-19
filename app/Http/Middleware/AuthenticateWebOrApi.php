<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthenticateWebOrApi
{
    public function handle(Request $request, Closure $next)
    {
        foreach (['web', 'api'] as $guard) {
            if (Auth::guard($guard)->check()) {
                Auth::shouldUse($guard);

                return $next($request);
            }
        }

        return response()->json([
            'status' => false,
            'message' => 'Non authentifié',
        ], 401);
    }
}
