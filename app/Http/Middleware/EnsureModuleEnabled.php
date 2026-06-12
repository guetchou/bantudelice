<?php

namespace App\Http\Middleware;

use Closure;

class EnsureModuleEnabled
{
    public function handle($request, Closure $next, string $module)
    {
        $enabled = (bool) config("bantudelice_modules.{$module}.enabled", true);

        if ($enabled) {
            return $next($request);
        }

        $payload = [
            'message' => "Le module {$module} est temporairement indisponible.",
            'module' => $module,
            'enabled' => false,
        ];

        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json($payload, 503);
        }

        return response($payload['message'], 503);
    }
}
