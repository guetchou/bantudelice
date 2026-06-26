<?php

namespace App\Http\Controllers\Api\GePay\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class HealthController extends Controller
{
    public function __invoke(): JsonResponse
    {
        return response()->json([
            'service' => 'GePay',
            'status' => config('gepay.enabled') ? 'ready' : 'disabled',
            'version' => '0.1.0',
        ]);
    }
}
