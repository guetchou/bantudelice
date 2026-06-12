<?php

namespace App\Http\Middleware;

use App\Services\SiteContextService;
use Closure;
use Illuminate\Http\Request;

class ResolveSiteContext
{
    public function handle(Request $request, Closure $next)
    {
        app(SiteContextService::class)->bootstrap($request);

        return $next($request);
    }
}
