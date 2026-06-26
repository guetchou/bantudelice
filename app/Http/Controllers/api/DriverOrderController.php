<?php

namespace App\Http\Controllers\api;

use App\Delivery;
use App\Driver;
use App\DriverHistory;
use App\Http\Controllers\Controller;
use App\News;
use App\Order;
use App\Product;
use App\Restaurant;
use App\Services\DriverLocationIngestionService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;

class DriverOrderController extends Controller
{
    public function __construct(
        private DriverLocationIngestionService $driverLocations
    ) {
        $this->middleware('auth:driver_api');
    }

    public function orderRequests($driver)
    {
        $tokenDriver = $this->approvedDriver();
        if (! $tokenDriver || (int) $tokenDriver->id !== (int) $driver) {
            return response()->json(['status' => false, 'message' => 'Accès non autorisé'], 403);
        }

        $driverOrders = Order::where('driver_id', $driver)->get();

        if ($driverOrders->isEmpty()) {
            $tokenDriver->setAttribute('orders', collect());
            return response()->json(['status' => true, 'data' => $tokenDriver]);
        }

        $uniqueOrderNos = $driverOrders->pluck('order_no')->filter()->unique()->values();
        $firstOrder = $driverOrders->first();
        $restaurant = Restaurant::find($firstOrder->restaurant_id);

        if (! $restaurant) {
            return response()->json([
                'status' => false,
                'message' => 'Restaurant introuvable pour cette mission.',
            ], 404);
        }

        $restaurant->setAttribute('orders', Order::whereIn('order_no', $uniqueOrderNos)
            ->with('user')
            ->orderBy('id')
            ->get());

        return response()->json(['status' => true, 'data' => $restaurant]);
    }

    public function ordersProducts($orderNo)
    {
        $driver = $this->approvedDriver();
        if (! $driver) {
            return response()->json(['status' => false, 'message' => 'Accès non autorisé'], 403);
        }

        $ownsMission = Delivery::where('driver_id', $driver->id)
            ->whereHas('order', fn ($query) => $query->where('order_no', $orderNo))
            ->exists();

        if (! $ownsMission) {
            return response()->json(['status' => false, 'message' => 'Commande introuvable'], 404);
        }

        $orders = Order::where('order_no', $orderNo)->get();
        $products = Product::whereIn('id', $orders->pluck('product_id')->filter()->all())->get();

        return response()->json(['status' => true, 'products' => $products]);
    }

    /**
     * Ancien endpoint incompatible avec le workflow DeliveryOffer.
     * L'assignation doit désormais passer par une offre appartenant au livreur.
     */
    public function acceptOrderRequests(Request $request)
    {
        return response()->json([
            'status' => false,
            'message' => 'Cette ancienne méthode d’assignation est désactivée. Utilisez les offres de livraison.',
        ], 410);
    }

    public function deliverySummary($driver)
    {
        $tokenDriver = $this->approvedDriver();
        if (! $tokenDriver || (int) $tokenDriver->id !== (int) $driver) {
            return response()->json(['status' => false, 'message' => 'Accès non autorisé'], 403);
        }

        $records = Delivery::where('driver_id', $driver)
            ->where('status', 'DELIVERED')
            ->whereDate('delivered_at', today())
            ->count();

        if (! Schema::hasTable('driver_histories')) {
            return response()->json([
                'status' => true,
                'derlieries' => $records,
                'deliveries' => $records,
                'total' => 0,
                'starttime' => null,
                'to' => 0,
            ]);
        }

        $history = DriverHistory::where('driver_id', $driver)->latest()->first();

        if (! $history) {
            return response()->json([
                'status' => true,
                'derlieries' => $records,
                'deliveries' => $records,
                'total' => 0,
                'starttime' => null,
                'to' => 0,
            ]);
        }

        $start = Carbon::parse($history->start_date);
        $end = $history->end_date ? Carbon::parse($history->end_date) : now();
        $hours = $start->diffInHours($end);
        $earnings = (float) $tokenDriver->hourly_pay * $hours;

        return response()->json([
            'status' => true,
            'derlieries' => $records,
            'deliveries' => $records,
            'total' => $earnings,
            'starttime' => $history->start_date,
            'to' => $hours,
        ]);
    }

    public function driverEarningHistory(Request $request, $driver)
    {
        $tokenDriver = $this->approvedDriver();
        if (! $tokenDriver || (int) $tokenDriver->id !== (int) $driver) {
            return response()->json(['status' => false, 'message' => 'Accès non autorisé'], 403);
        }

        $totalEarning = DriverHistory::where('driver_id', $driver)->sum('earnings');
        $query = DriverHistory::where('driver_id', $driver)->latest();

        if ($request->filled('start_date') || $request->filled('end_date')) {
            $validator = Validator::make($request->all(), [
                'start_date' => 'required_with:end_date|date',
                'end_date' => 'required_with:start_date|date|after_or_equal:start_date',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => implode(',', $validator->messages()->all()),
                ], 422);
            }

            $query->whereBetween('created_at', [
                Carbon::parse($request->start_date)->startOfDay(),
                Carbon::parse($request->end_date)->endOfDay(),
            ]);
        }

        return response()->json([
            'status' => true,
            'totalEarning' => $totalEarning,
            'weeks' => $query->get(),
        ]);
    }

    public function latestNews()
    {
        if (! $this->approvedDriver()) {
            return response()->json(['status' => false, 'message' => 'Accès non autorisé'], 403);
        }

        return response()->json(['status' => true, 'data' => News::latest()->get()]);
    }

    public function updateLocation(Request $request, $driverId)
    {
        $validator = Validator::make($request->all(), [
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'accuracy' => ['nullable', 'numeric', 'between:0,5000'],
            'heading' => ['nullable', 'numeric', 'between:0,360'],
            'speed' => ['nullable', 'numeric', 'between:0,100'],
            'recorded_at' => ['nullable', 'date'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Données invalides',
                'errors' => $validator->errors(),
            ], 422);
        }

        $driver = $this->approvedDriver();
        if (! $driver) {
            return response()->json(['status' => false, 'message' => 'Non authentifié'], 401);
        }

        if (! Driver::whereKey($driverId)->exists()) {
            return response()->json([
                'status' => false,
                'message' => 'Livreur non trouvé',
            ], 404);
        }

        if ((int) $driver->id !== (int) $driverId) {
            return response()->json(['status' => false, 'message' => 'Non autorisé'], 403);
        }

        $result = $this->driverLocations->ingest(
            $driver,
            $validator->validated(),
            markOnline: true,
            broadcast: true
        );

        return response()->json([
            'status' => true,
            'accepted' => $result['accepted'],
            'duplicate' => $result['duplicate'],
            'stale' => $result['stale'],
            'message' => $result['stale']
                ? 'Position ancienne ignorée'
                : 'Position mise à jour avec succès',
            'driver' => [
                'id' => $result['driver']->id,
                'name' => $result['driver']->name,
                'latitude' => (float) $result['driver']->latitude,
                'longitude' => (float) $result['driver']->longitude,
                'status' => $result['driver']->status,
            ],
            'recorded_at' => optional($result['location']?->timestamp)->toIso8601String(),
        ], $result['stale'] ? 202 : 200);
    }

    private function approvedDriver(): ?Driver
    {
        $driver = auth('driver_api')->user();

        return $driver instanceof Driver && (bool) $driver->approved
            ? $driver
            : null;
    }
}
