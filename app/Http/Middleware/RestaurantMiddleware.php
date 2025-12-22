<?php

namespace App\Http\Middleware;

use Closure;

class RestaurantMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if (!auth()->check()) {
            return redirect()->route('login')->with('error', 'Veuillez vous connecter pour accéder à cette page.');
        }
        
        if (auth()->user()->type === 'restaurant') {
            return $next($request);
        }

        return redirect('/')->with('error', 'Accès refusé. Cette page est réservée aux restaurants.');
    }
}
