<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class RequireAdmin2FA
{
    public function handle(Request $request, Closure $next)
    {
        $user = auth()->user();

        if (!$user || $user->type !== 'admin') {
            return $next($request);
        }

        if (!$user->two_factor_enabled) {
            return $next($request);
        }

        if ($request->session()->get('admin_2fa_verified')) {
            return $next($request);
        }

        // Allow 2FA challenge routes through to avoid infinite redirect
        if ($request->routeIs('admin.2fa.*')) {
            return $next($request);
        }

        return redirect()->route('admin.2fa.challenge');
    }
}
