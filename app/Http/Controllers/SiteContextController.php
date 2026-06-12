<?php

namespace App\Http\Controllers;

use App\Services\SiteContextService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;

class SiteContextController extends BaseController
{
    public function show(Request $request): JsonResponse
    {
        $context = app(SiteContextService::class)->bootstrap($request);

        return response()->json([
            'status' => true,
            'context' => $context,
        ]);
    }
}
