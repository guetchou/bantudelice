<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class RequireGePayNonce
{
    public function handle(Request $request, Closure $next): Response
    {
        if (trim((string) $request->header('X-GePay-Nonce')) === '') {
            return response()->json([
                'success' => false,
                'message' => 'En-tête X-GePay-Nonce obligatoire.',
            ], 401);
        }

        return $next($request);
    }
}
