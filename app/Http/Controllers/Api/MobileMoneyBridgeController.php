<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\MobileMoneyBridgeService;
use Illuminate\Http\Request;

class MobileMoneyBridgeController extends Controller
{
    public function __construct(protected MobileMoneyBridgeService $bridgeService) {}

    public function store(Request $request)
    {
        $payload = $request->validate([
            'external_reference' => 'required|string|max:120',
            'amount' => 'required|numeric|min:100',
            'currency' => 'nullable|string|size:3',
            'phone' => 'required|string|max:40',
            'operator' => 'nullable|in:auto,mtn,airtel',
            'customer_name' => 'nullable|string|max:120',
            'callback_url' => 'nullable|url|max:255',
            'metadata' => 'nullable|array',
        ]);

        $payload['operator'] = $payload['operator'] ?? 'auto';

        return response()->json(
            $this->bridgeService->initiate(
                $payload,
                $request->attributes->get('bridge_client', [])
            ),
            201
        );
    }

    public function show(Request $request, string $reference)
    {
        $reconcile = $request->boolean('reconcile');

        return response()->json($this->bridgeService->status(
            $reference,
            $request->attributes->get('bridge_client', []),
            $reconcile
        ));
    }

    public function reconcile(Request $request, string $reference)
    {
        return response()->json($this->bridgeService->reconcile(
            $reference,
            $request->attributes->get('bridge_client', [])
        ));
    }
}
