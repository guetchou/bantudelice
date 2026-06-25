<?php

namespace App\Http\Controllers\api;

use App\Driver;
use App\DriverHistory;
use App\Http\Controllers\Controller;
use App\News;
use App\Order;
use App\Product;
use App\Restaurant;
use App\Services\DriverLocationIngestionService;
use App\Services\NotificationService;
use App\UserToken;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;

class DriverOrderController extends Controller
{
    public function __construct(
        private DriverLocationIngestionService $driverLocations
    ) {
    }

    public function orderRequests($driver)
    {
        $tokenDriver = auth('driver_api')->user();
        if (!$tokenDriver || (int)$tokenDriver->id !== (int)$driver) {
            return response()->json(['status' => false, 'message' => 'Accès non autorisé'], 403);
        }

        $driverModel = Driver::find($driver);

        if (!$driverModel) {
            return response()->json(['status' => false, 'message' => 'Livreur introuvable', 'data' => null], 404);
        }

        $driverOrders = Order::where('driver_id', $driver)->get();

        if ($driverOrders->isEmpty()) {
            $driverModel['orders'] = collect();
            return response()->json(['status' => true, 'data' => $driverModel]);
        }

        $uniqueOrderNos = $driverOrders->pluck('order_no')->unique()->toArray();
        $firstOrder = $driverOrders->first();
        $restaurant = Restaurant::find($firstOrder->restaurant_id);

        $restaurant['orders'] = Order::whereIn('order_no', $uniqueOrderNos)
            ->with('user')
            ->take(count($uniqueOrderNos))
            ->get();

        return response()->json(['status' => true, 'data' => $restaurant]);
    }

    public function ordersProducts($orderno)
    {
        $orders = Order::where('order_no', $orderno)->get();

        if ($orders->isEmpty()) {
            return response()->json(['status' => false, 'message' => 'Commande introuvable', 'products' => []], 404);
        }

        $products = Product::whereIn('id', $orders->pluck('product_id')->toArray())->get();

        return response()->json(['status' => true, 'products' => $products]);
    }

    public function acceptOrderRequests(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'driver_id' => 'required',
            'status' => 'required|in:1,3',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'error_code' => 101,
                'message' => implode(',', $validator->messages()->all()),
            ], 422);
        }

        $driverId = $request->driver_id;

        if (!Driver::where('id', $driverId)->exists()) {
            return response()->json(['status' => false, 'message' => 'Livreur introuvable'], 404);
        }

        $tokens = UserToken::whereIn(
            'user_id',
            Order::get()->unique('order_no')->pluck('user_id')->toArray()
        )->pluck('device_tokens')->toArray();

        if ($request->status == 1) {
            Log::info('acceptOrderRequests: notification assign envoyée', ['driver_id' => $driverId]);
            NotificationService::sendToMultipleDevices($tokens, 'Order Assign', 'Your is assign to driver', 'assignOrder', $driverId, 'user');
            $status = true;
        } elseif ($request->status == 3) {
            Log::info('acceptOrderRequests: notification pickup envoyée', ['driver_id' => $driverId]);
            NotificationService::sendToMultipleDevices($tokens, 'Order Pickup', 'Your Order is pickup from', 'pickipOrder', $driverId, 'user');
            $status = true;
        } else {
            $status = false;
        }

        return response()->json(['status' => $status]);
    }

    public function deliverySummary($driver)
    {
        $tokenDriver = auth('driver_api')->user();
        if (!$tokenDriver || (int)$tokenDriver->id !== (int)$driver) {
            return response()->json(['status' => false, 'message' => 'Accès non autorisé'], 403);
        }

        $driverModel = Driver::find($driver);

        if (!$driverModel) {
            return response()->json(['status' => false, 'message' => 'Livreur introuvable', 'derlieries' => 0, 'total' => 0], 404);
        }

        $records = DB::table('completed_orders')
            ->whereRaw('DATE(created_at) = CURDATE()')
            ->count();

        if (!Schema::hasTable('driver_histories')) {
            return response()->json(['status' => true, 'derlieries' => $records, 'total' => 0, 'starttime' => null, 'to' => 0]);
        }

        $history = DriverHistory::where('driver_id', $driver)->latest()->first();

        if (!$history) {
            return response()->json(['status' => true, 'derlieries' => $records, 'total' => 0, 'starttime' => null, 'to' => 0]);
        }

        $start = Carbon::parse($history->start_date);
        $end = $history->end_date ? Carbon::parse($history->end_date) : now();
        $hours = $start->diffInHours($end);
        $earnings = $driverModel->hourly_pay * $hours;

        return response()->json([
            'status' => true,
            'derlieries' => $records,
            'total' => $earnings,
            'starttime' => $history->start_date,
            'to' => $hours,
        ]);
    }

    public function driverEarningHistory(Request $request, $driver)
    {
        $tokenDriver = auth('driver_api')->user();
        if (!$tokenDriver || (int)$tokenDriver->id !== (int)$driver) {
            return response()->json(['status' => false, 'message' => 'Accès non autorisé'], 403);
        }

        if (!Driver::whereKey($driver)->exists()) {
            return response()->json(['status' => false, 'message' => 'Livreur introuvable', 'totalEarning' => 0, 'weeks' => []], 404);
        }

        $totalEarning = DriverHistory::where('driver_id', $driver)->sum('earnings');
        $query = DriverHistory::where('driver_id', $driver)->latest();

        if ($request->start_date != '' || $request->end_date != '') {
            $query->whereBetween('created_at', [$request->start_date, $request->end_date]);
        }

        return response()->json(['status' => true, 'totalEarning' => $totalEarning, 'weeks' => $query->get()]);
    }

    public function latestNews()
    {
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

        $authDriver = auth('driver_api')->user();
        if (! $authDriver) {
            return response()->json(['status' => false, 'message' => 'Non authentifié'], 401);
        }

        $driver = Driver::find($driverId);
        if (! $driver) {
            return response()->json(['status' => false, 'message' => 'Livreur non trouvé'], 404);
        }

        if ((int) $authDriver->id !== (int) $driver->id) {
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
}
