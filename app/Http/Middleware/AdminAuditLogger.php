<?php

namespace App\Http\Middleware;

use App\AdminAuditLog;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AdminAuditLogger
{
    // Méthodes à ne pas logger (lecture seule sans effet de bord)
    private const SKIP_METHODS = ['GET', 'HEAD', 'OPTIONS'];

    // Routes de polling à exclure même en POST (bruit sans valeur d'audit)
    private const SKIP_ROUTES = [
        'admin.notifications',
        'session.keepalive',
        'admin.api.status',
        'admin.metrics.realtime',
    ];

    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        try {
            $this->log($request, $response->getStatusCode());
        } catch (\Throwable $e) {
            Log::error('AdminAuditLogger failed', ['error' => $e->getMessage()]);
        }

        return $response;
    }

    private function log(Request $request, int $status): void
    {
        if (in_array($request->method(), self::SKIP_METHODS, true)) {
            return;
        }

        $routeName = $request->route()?->getName();
        if ($routeName && in_array($routeName, self::SKIP_ROUTES, true)) {
            return;
        }

        $user = auth()->user();

        // Payload sans secrets
        $payload = $request->except(['password', 'password_confirmation', 'current_password', '_token', 'secret']);
        if (mb_strlen(json_encode($payload)) > 4096) {
            $payload = ['_truncated' => true, 'keys' => array_keys($payload)];
        }

        AdminAuditLog::create([
            'admin_id'        => $user?->id,
            'admin_email'     => $user?->email,
            'method'          => $request->method(),
            'path'            => $request->path(),
            'route_name'      => $routeName,
            'action'          => $request->route()?->getActionName(),
            'payload'         => $payload ?: null,
            'ip'              => $request->ip(),
            'user_agent'      => substr($request->userAgent() ?? '', 0, 500),
            'response_status' => $status,
        ]);
    }
}
