<?php

namespace App\Http\Controllers\Api\V1\Colis;

use App\Http\Controllers\Controller;
use App\Http\Requests\Colis\CreateQuoteRequest;
use App\Domain\Colis\Services\ShipmentPricingService;
use Illuminate\Http\JsonResponse;

class QuoteController extends Controller
{
    public function __invoke(CreateQuoteRequest $request, ShipmentPricingService $pricingService): JsonResponse
    {
        $quote = $pricingService->calculate($request->validated());

        return response()->json($quote);
    }
}

