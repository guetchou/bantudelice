<?php

namespace App\Http\Controllers;

use App\PartnerWithdrawal;
use Illuminate\Http\Request;

class WithdrawalStatusController extends Controller
{
    public function show(Request $request, int $id)
    {
        $withdrawal = PartnerWithdrawal::findOrFail($id);

        // Ownership check: verify the authenticated user owns this withdrawal
        $user = auth()->user();
        $authorized = false;

        if ($withdrawal->partner_type === 'restaurant') {
            $restaurant = $user->restaurant()->first();
            $authorized = $restaurant && $restaurant->id === $withdrawal->partner_id;
        } elseif ($withdrawal->partner_type === 'driver') {
            $driver = $user->driver()->first();
            $authorized = $driver && $driver->id === $withdrawal->partner_id;
        }

        if (!$authorized) {
            return response()->json(['success' => false, 'message' => 'Accès non autorisé.'], 403);
        }

        return response()->json([
            'success' => true,
            'withdrawal' => [
                'id'           => $withdrawal->id,
                'reference'    => $withdrawal->reference(),
                'status'       => $withdrawal->status,
                'amount'       => $withdrawal->net_amount,
                'phone_masked' => $withdrawal->maskedPhone(),
                'paid_at'      => $withdrawal->paid_at?->toIso8601String(),
                'failed_at'    => $withdrawal->failed_at?->toIso8601String(),
                'failure_message' => $withdrawal->isFailed() ? $withdrawal->failure_message : null,
            ],
        ]);
    }
}
