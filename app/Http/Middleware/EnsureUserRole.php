<?php

namespace App\Http\Middleware;

use App\Support\Auth\ActorType;
use App\Support\Auth\AuthenticatedUserResolver;
use Closure;
use Illuminate\Http\Request;

class EnsureUserRole
{
    public function __construct(
        protected AuthenticatedUserResolver $authenticatedUserResolver
    ) {
    }

    public function handle(Request $request, Closure $next, string $role, string ...$guards)
    {
        $guards = $guards !== [] ? $guards : ['api', 'web'];
        $actor = $this->authenticatedUserResolver->current($guards);

        if (ActorType::hasUserRole($actor, $role)) {
            return $next($request);
        }

        if (! $actor) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'message' => 'Authentification utilisateur requise.',
                    'required_role' => $role,
                    'guards' => $guards,
                ], 401);
            }

            return redirect()->route('login');
        }

        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'message' => 'Accès refusé pour ce rôle.',
                'required_role' => $role,
            ], 403);
        }

        return redirect('/')->with('error', 'Accès refusé.');
    }
}
