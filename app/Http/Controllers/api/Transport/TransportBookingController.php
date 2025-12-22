<?php

namespace App\Http\Controllers\api\Transport;

use App\Http\Controllers\Controller;
use App\Domain\Transport\Models\TransportBooking;
use App\Domain\Transport\Services\TransportService;
use App\Domain\Transport\Enums\TransportStatus;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class TransportBookingController extends Controller
{
    protected $transportService;

    public function __construct(TransportService $transportService)
    {
        $this->transportService = $transportService;
    }

    public function index()
    {
        $bookings = TransportBooking::where('user_id', auth()->id())
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($bookings);
    }

    public function estimate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'type' => 'required|in:taxi,carpool,rental',
            'distance' => 'required|numeric',
            'duration' => 'required|numeric',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 400);
        }

        $price = $this->transportService->estimate($request->all());

        return response()->json([
            'estimated_price' => $price,
            'currency' => 'XAF'
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'type' => 'required|in:taxi,carpool,rental',
            'pickup_address' => 'required|string',
            'pickup_lat' => 'required|numeric',
            'pickup_lng' => 'required|numeric',
            'dropoff_address' => 'sometimes|string',
            'dropoff_lat' => 'sometimes|numeric',
            'dropoff_lng' => 'sometimes|numeric',
            'scheduled_at' => 'sometimes|date',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 400);
        }

        $data = $request->all();
        $data['user_id'] = auth()->id();
        $data['status'] = TransportStatus::REQUESTED;

        // Estimate price if not provided or to verify
        $data['estimated_price'] = $this->transportService->estimate($data);

        $booking = $this->transportService->createBooking($data);

        return response()->json([
            'message' => 'Réservation créée avec succès',
            'booking' => $booking
        ], 201);
    }

    public function show($id)
    {
        $booking = TransportBooking::where('uuid', $id)
            ->orWhere('id', $id)
            ->with(['driver', 'vehicle'])
            ->firstOrFail();

        $this->authorize('view', $booking);

        return response()->json($booking);
    }

    public function cancel($id)
    {
        $booking = TransportBooking::where('uuid', $id)->firstOrFail();
        
        $this->authorize('cancel', $booking);

        $booking->update([
            'status' => TransportStatus::CANCELLED,
            'cancelled_at' => now()
        ]);

        return response()->json(['message' => 'Réservation annulée']);
    }

    public function pay(Request $request, $id)
    {
        $booking = TransportBooking::where('uuid', $id)->firstOrFail();
        
        $request->validate([
            'provider' => 'required|string|in:momo,paypal,stripe'
        ]);

        $result = $this->transportService->initiatePayment($booking, $request->provider);

        return response()->json([
            'message' => 'Paiement initié',
            'payment' => $result['payment'],
            'redirect_url' => $result['redirect_url']
        ]);
    }
}

