<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class RequireAdminWorkspace
{
    /**
     * Workspace values accepted as $workspace parameter:
     *   bantudelice | kende | mema  → user must hold that workspace or '*'
     *   any                         → user must hold at least one active permission
     *   super                       → user must hold the '*' wildcard (true super admin)
     */
    public function handle(Request $request, Closure $next, string $workspace): mixed
    {
        $user = auth()->user();

        if (!$user || $user->type !== 'admin') {
            abort(403, 'Acces admin requis.');
        }

        $allowed = match ($workspace) {
            'super' => $user->adminPermissions()->where('workspace', '*')->exists(),
            'any'   => $user->adminPermissions()->exists(),
            default => $user->adminPermissions()->whereIn('workspace', [$workspace, '*'])->exists(),
        };

        if (!$allowed) {
            abort(403, 'Vous n\'avez pas acces a ce module.');
        }

        return $next($request);
    }
}
