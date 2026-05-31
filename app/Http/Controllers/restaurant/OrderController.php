<?php

namespace App\Http\Controllers\restaurant;

use App\Http\Controllers\Controller;
use App\Order;
use App\CompletedOrder;
use App\Driver;
use App\Restaurant;
use App\Services\FoodOrderNotificationService;
use App\Services\NotificationService;
use App\Services\FoodOrderFinanceService;
use App\Services\FoodOrderStateMachineService;
use App\Services\CommerceRefundService;
use App\Services\OrderChatService;
use Carbon\Carbon;
use App\UserToken;
use http\Exception;
use Session;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class OrderController extends Controller
{
    public function __construct(
        protected FoodOrderStateMachineService $foodOrderStateMachine,
        protected FoodOrderFinanceService $foodOrderFinanceService,
        protected CommerceRefundService $refunds
    ) {}

     public function notification( $body,$title,$device_token,$key,$user_id)
    {
        $result = NotificationService::sendToDevice($device_token, $title, $body, $key, $user_id, 'driver');

        return response()->json(['data' => !empty($result['success']) ? 'notification sent' : 'notification failed', 'action' => $result['action'] ?? null], 200);
    }
    
    public function userNotification( $body,$title,$device_token,$key,$user_id)
    {
        $result = NotificationService::sendToMultipleDevices($device_token, $title, $body, $key, $user_id, 'user');

        return response()->json(['data' => !empty($result['success']) ? 'notification sent' : 'notification failed', 'action' => $result['action'] ?? null], 200);
    }
    function getDistance($latitude2, $longitude2, $latitude1, $longitude1)
    {
        $earth_radius = 6356 ;

        $dLat = deg2rad($latitude2 - $latitude1);
        $dLon = deg2rad($longitude2 - $longitude1);

        $a = sin($dLat / 2) * sin($dLat / 2) + cos(deg2rad($latitude1)) * cos(deg2rad($latitude2)) * sin($dLon / 2) * sin($dLon / 2);
        $c = 2 * asin(sqrt($a));
        $d = $earth_radius * $c;

        return $d;
    }
    
    /**
     * Endpoint de polling pour le restaurant connecté (sans passer l'id en URL).
     * Utilisé par le polling JS toutes les 15 secondes.
     */
    public function notificationsForCurrentRestaurant()
    {
        $restaurant = Restaurant::where('user_id', auth()->id())->first();
        if (! $restaurant) {
            return response()->json(['status' => false, 'count' => 0, 'orders' => [], 'new' => false]);
        }
        return $this->notifications($restaurant->id);
    }

    public function notifications($id){
        $orders = Order::where('restaurant_id',$id);

        if (Schema::hasColumn('orders', 'business_status')) {
            $orders->where('business_status', 'pending_restaurant_acceptance');
        } else {
            $orders->where('status', 'pending');
        }

        $orders = $orders->groupBy('order_no')->select('id','order_no','created_at')->get();
        $count = $orders->count();
        $new = false;
        foreach ($orders as $key => $value) {
            $value['time'] = Carbon::parse($value->created_at)->diffForHumans();
            $time = Carbon::parse($value->created_at)->diffInSeconds();
            if($time < 10){
                $new = true;
            }
            
        }
        
        return response()->json([
            'status'=>true,
            'orders'=>$orders,
            'count'=>$count,
            'new'=>$new
        ]);
    }
    
    public function all_orders(Request $request)
    {
        $restuarantId=auth()->user()->id;
        
        $restaurant=Restaurant::where('user_id',$restuarantId)->first();
        $query = Order::where('restaurant_id', $restaurant->id);

        if (Schema::hasColumn('orders', 'business_status')) {
            $query->where('business_status', 'pending_restaurant_acceptance');
        } else {
            $query->where('status', 'pending');
        }

        if($request->filled('date')) {
            $arr   = explode(' - ', $request->date, 2);
            $start = Carbon::createFromFormat('d/m/Y', trim($arr[0]))->startOfDay();
            $end   = Carbon::createFromFormat('d/m/Y', trim($arr[1] ?? $arr[0]))->endOfDay();
            $orders = $query->whereBetween('created_at', [$start, $end])->latest()->get()->unique('order_no');
        } else {
            $orders = $query->latest()->get()->unique('order_no');
        }
        $chatService = app(OrderChatService::class);
        $orders = $orders->map(function ($order) use ($chatService) {
            $order->chatBadge = $chatService->badgeDataForOrder($order, auth()->user());
            return $order;
        });
        return view('restaurant.order.all_orders', compact('orders'));
    }
    public function complete_orders(Request $request)
    {
        $name=auth()->user()->id;
        $restaurant=Restaurant::where('user_id',$name)->first();
        $query = Order::where('restaurant_id', $restaurant->id);

        if (Schema::hasColumn('orders', 'business_status')) {
            $query->whereIn('business_status', ['delivered', 'picked_up_by_customer', 'closed']);
        } else {
            $query->where('status', 'completed');
        }

        if($request->filled('date')) {
            $arr   = explode(' - ', $request->date, 2);
            $start = Carbon::createFromFormat('d/m/Y', trim($arr[0]))->startOfDay();
            $end   = Carbon::createFromFormat('d/m/Y', trim($arr[1] ?? $arr[0]))->endOfDay();
            $orders = $query->whereBetween('created_at', [$start, $end])->latest()->get()->unique('order_no');
        } else {
            $orders = $query->latest()->get()->unique('order_no');
        }
        $chatService = app(OrderChatService::class);
        $orders = $orders->map(function ($order) use ($chatService) {
            $order->chatBadge = $chatService->badgeDataForOrder($order, auth()->user());
            return $order;
        });

        return view('restaurant.order.complete_orders')->with('orders', $orders);
    }
    public function pending_orders(Request $request)
    {
        $name=auth()->user()->id;
        $restaurant=Restaurant::where('user_id',$name)->first();
        $query = Order::where('restaurant_id', $restaurant->id);

        if (Schema::hasColumn('orders', 'business_status')) {
            $query->whereIn('business_status', [
                'ready_for_pickup',
                'dispatching',
                'driver_assigned',
                'driver_arrived_at_restaurant',
                'picked_up',
                'out_for_delivery',
                'delivery_attempt_failed',
                'customer_arrived',
            ]);
        } else {
            $query->where('status', 'assign');
        }

        if($request->filled('date')) {
            $arr   = explode(' - ', $request->date, 2);
            $start = Carbon::createFromFormat('d/m/Y', trim($arr[0]))->startOfDay();
            $end   = Carbon::createFromFormat('d/m/Y', trim($arr[1] ?? $arr[0]))->endOfDay();
            $orders = $query->whereBetween('created_at', [$start, $end])->latest()->get()->unique('order_no');
        } else {
            $orders = $query->latest()->get()->unique('order_no');
        }
        $chatService = app(OrderChatService::class);
        $orders = $orders->map(function ($order) use ($chatService) {
            $order->chatBadge = $chatService->badgeDataForOrder($order, auth()->user());
            return $order;
        });
        return view('restaurant.order.pending_orders')->with('orders', $orders);
    }
    public function cancel_orders(Request $request)
    {
        $name=auth()->user()->id;
        $restaurant=Restaurant::where('user_id',$name)->first();
        $query = Order::where('restaurant_id', $restaurant->id);

        if (Schema::hasColumn('orders', 'business_status')) {
            $query->where('business_status', 'cancelled');
        } else {
            $query->where('status', 'cancelled');
        }

        if($request->filled('date')) {
            $arr   = explode(' - ', $request->date, 2);
            $start = Carbon::createFromFormat('d/m/Y', trim($arr[0]))->startOfDay();
            $end   = Carbon::createFromFormat('d/m/Y', trim($arr[1] ?? $arr[0]))->endOfDay();
            $orders = $query->whereBetween('created_at', [$start, $end])->latest()->get()->unique('order_no');
        } else {
            $orders = $query->latest()->get()->unique('order_no');
        }
        $chatService = app(OrderChatService::class);
        $orders = $orders->map(function ($order) use ($chatService) {
            $order->chatBadge = $chatService->badgeDataForOrder($order, auth()->user());
            return $order;
        });
        return view('restaurant.order.cancel_orders')->with('orders', $orders);
    }
    public function getPreparingOrders()
    {
        $name=auth()->user()->id;
        $restaurant=Restaurant::where('user_id',$name)->first();
        $query = Order::where('restaurant_id', $restaurant->id);

        if (Schema::hasColumn('orders', 'business_status')) {
            $query->where('business_status', 'in_kitchen');
        } else {
            $query->where('status', 'prepairing');
        }

        $orders=$query->get();
        $chatService = app(OrderChatService::class);
        $orders = $orders->map(function ($order) use ($chatService) {
            $order->chatBadge = $chatService->badgeDataForOrder($order, auth()->user());
            return $order;
        });
        return view('restaurant.order.prepaire_orders')->with('orders',$orders);
    }
    public function cancel_order($order, \Illuminate\Http\Request $request = null)
    {
        $reason    = $request?->input('reason', 'restaurant_cancelled') ?? 'restaurant_cancelled';
        $note      = $request?->input('cancel_note', '') ?? '';

        $reasonLabels = [
            'restaurant_closed'      => 'Restaurant ferme',
            'product_unavailable'    => 'Produit indisponible',
            'too_many_orders'        => 'Trop de commandes en cours',
            'delivery_zone_issue'    => 'Zone non couverte',
            'other'                  => 'Raison non precisee',
        ];

        $notes = $reasonLabels[$reason] ?? $reason;
        if ($note) $notes .= ' — ' . $note;

        $this->foodOrderFinanceService->cancelOrderGroup($order, [
            'actor_type'  => 'restaurant',
            'actor_id'    => auth()->id(),
            'reason_code' => 'restaurant_cancelled',
            'notes'       => $notes,
        ]);

        $orderModel = Order::with(['user'])->where('order_no', $order)->first();
        if ($orderModel) {
            $this->refunds->refundOrder($orderModel, 'restaurant_cancelled', [
                'actor_type'       => 'restaurant',
                'actor_id'         => auth()->id(),
                'idempotency_key'  => 'restaurant-cancelled-' . $orderModel->id,
                'amount'           => (float) ($orderModel->total ?? 0),
            ]);

            // Notifier le client avec le motif
            try {
                app(FoodOrderNotificationService::class)->notifyStatusChange($orderModel, 'cancelled', [
                    'cancel_reason' => $notes,
                ]);

                // Email de refus au client
                if ($orderModel->user?->email) {
                    $orderNo      = $orderModel->order_no;
                    $userEmail    = $orderModel->user->email;
                    $userName     = $orderModel->user->name ?? 'Client';
                    $contactUrl   = route('contact.us');
                    $logoUrl      = url('frontend/images/BuntuDelice.png');
                    \Illuminate\Support\Facades\Mail::send([], [], function ($m) use ($userEmail, $userName, $orderNo, $notes, $contactUrl, $logoUrl) {
                        $m->to($userEmail, $userName)
                          ->subject("Commande #$orderNo annulée — BantuDelice")
                          ->html(
                            "<div style='font-family:sans-serif;max-width:480px;margin:0 auto;padding:24px;'>"
                            . "<img src='$logoUrl' alt='BantuDelice' style='height:36px;margin-bottom:16px;'>"
                            . "<h2 style='color:#ef4444;'>Commande annulée</h2>"
                            . "<p>Bonjour " . htmlspecialchars($userName, ENT_QUOTES) . ",</p>"
                            . "<p>Votre commande <strong>#" . htmlspecialchars($orderNo, ENT_QUOTES) . "</strong> a été annulée par le restaurant.</p>"
                            . "<div style='background:#fef2f2;border-left:4px solid #ef4444;padding:12px 16px;border-radius:0 8px 8px 0;margin:16px 0;'>"
                            . "<strong>Motif :</strong> " . htmlspecialchars($notes, ENT_QUOTES)
                            . "</div>"
                            . "<p>Si vous avez été débité, un remboursement est en cours de traitement.</p>"
                            . "<a href='" . htmlspecialchars($contactUrl, ENT_QUOTES) . "' style='color:#009543;'>Contacter le support</a>"
                            . "<p style='color:#94a3b8;font-size:12px;margin-top:20px;'>BantuDelice — Brazzaville &amp; Pointe-Noire</p>"
                            . "</div>"
                          );
                    });
                }
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning('Erreur notification annulation', ['error' => $e->getMessage()]);
            }
        }

        // T1.2 — Activité restaurant mise à jour
        $this->touchRestaurantActivity();

        Session::flash('success', 'Commande annulée avec succès.');
        return redirect()->back();
    }
    public function schedule_orders()
    {
        $current_date = Carbon::now();
        $orders=Order::where('restaurant_id', Restaurant::where('user_id', auth()->id())->value('id'))
            ->whereNotNull('scheduled_date')
            ->where('scheduled_date', '>=', $current_date)
            ->get();
        $chatService = app(OrderChatService::class);
        $orders = $orders->map(function ($order) use ($chatService) {
            $order->chatBadge = $chatService->badgeDataForOrder($order, auth()->user());
            return $order;
        });
        return view('restaurant.order.schedule_orders')->with('orders',$orders);

    }
    public function prepaire_order(Request $request)
    {
        $ids = $request->input('id');

        if (empty($ids)) {
            $routeOrder = $request->route('order');
            if (!empty($routeOrder)) {
                $ids = [$routeOrder];
            }
        }

        $ids = array_values(array_filter((array) $ids));

        if (empty($ids)) {
            $alert = [];
            $alert['type'] = 'danger';
            $alert['message'] = 'Aucune commande a preparer n\'a ete fournie';
            return redirect()->back()->with('alert', $alert);
        }

        $users = DB::table('orders')
            ->whereIn('order_no', $ids)
            ->get();

        $Ids = array_values(array_unique($users->pluck('user_id')->filter()->toArray()));

        $device_token = [];
        if (!empty($Ids)) {
            $getTokens = UserToken::whereIn('user_id', $Ids)->get();
            $device_token = $getTokens->pluck('device_tokens')->filter()->toArray();
        }

        $body = 'You Order is Preparing Now!';
        $title = 'Preparing';
        $key = 'orderPreparing';
        $user_id = $Ids;

        foreach ($ids as $orderNo) {
            $this->foodOrderStateMachine->transitionOrderGroup($orderNo, 'in_kitchen', [
                'actor_type' => 'restaurant',
                'actor_id' => auth()->id(),
                'reason_code' => 'kitchen_started',
            ]);
        }

        if (!empty($device_token) && !empty($user_id)) {
            $this->userNotification($body, $title, $device_token, $key, $user_id);
        }

        // T1.2 — Mettre à jour last_activity_at (E2C auto-pause)
        $this->touchRestaurantActivity();

        $alert = [];
        $alert['type'] = 'success';
        $alert['message'] = 'Statut de la commande mis a jour avec succes';
        return redirect()->back()->with('alert', $alert);
    }

    /**
     * T1.2 — Mettre à jour last_activity_at du restaurant connecté.
     * Appelé à chaque action restaurant (préparation, annulation, livraison).
     */
    private function touchRestaurantActivity(): void
    {
        try {
            Restaurant::where('user_id', auth()->id())
                ->update(['last_activity_at' => now()]);
        } catch (\Throwable $e) {
            // Non bloquant
        }
    }
    public function deliver_order(Order $order)
    {
        $this->foodOrderStateMachine->transitionOrderGroup($order->order_no, 'delivered', [
            'actor_type' => 'restaurant',
            'actor_id' => auth()->id(),
            'reason_code' => 'restaurant_completed_manually',
            'force' => true,
        ]);
        $alert = [];
        $alert['type'] = 'success';
        $alert['message'] = 'Commande livrée avec succès';
        return redirect()->back()->with('alert', $alert);
    }
    public function show_order($id)
    {
        $order    = Order::with(['driver', 'delivery.driver', 'restaurant', 'user'])->where('order_no', $id)->first();
        $products = Order::with('product')->where('order_no', $id)->get();
        $chatData = app(OrderChatService::class)->viewDataForOrder($order, auth()->user());
        return view('restaurant.order.show_orders', compact('order', 'products', 'chatData'));
    }
    public function assign_order(Order $order)
    {
        $this->foodOrderStateMachine->transitionOrderGroup($order->order_no, 'dispatching', [
            'actor_type' => 'restaurant',
            'actor_id' => auth()->id(),
            'reason_code' => 'restaurant_ready_for_dispatch',
            'force' => true,
        ]);
        
        $alert = [];
        $alert['type'] = 'success';
        $alert['message'] = 'Statut de la commande mis à jour avec succès';
        return redirect()->back()->with('alert', $alert);
    }
    public function assign_driver(Request $request,Order $order)
    {
        $request->validate([
            'id'=>'required',
        ]);
        
        $restaurant = Restaurant::where('user_id', auth()->id())->first();
        if (!$restaurant) {
            return redirect()->back()->with('alert', ['type' => 'danger', 'message' => 'Restaurant introuvable']);
        }
        $latitude  = $restaurant->latitude;
        $longitude = $restaurant->longitude;
        if (!$latitude || !$longitude) {
            return redirect()->back()->with('alert', ['type' => 'danger', 'message' => 'Coordonnées du restaurant non configurées — impossible d\'assigner un livreur']);
        }
        $radius=6371;
        
         //     //Notification for New Ride

        $body='You Have A New Order';
        $title='New Order';
        $key='newOrder';
       
      
        $orders=Order::whereIn('order_no',$request->id)->get();
       
            // dd($orders);
        $getorderData['orders']=Order::whereIn('order_no',$request->id)->with('user')->take(count($request->id))->get();
        
        $checkDriverData=DB::table('reason_reject')->whereIn('order_no',$request->id)->get();
        //dd($checkDriverData);
        $checkIds=$checkDriverData->pluck('driver_id')->toArray();
        if(!$checkDriverData->count()){
            $selectedDriver = Driver::selectRaw("id, name, address, latitude, longitude, device_token,
                         ( 6371 * acos( cos( radians(?) ) *
                           cos( radians( latitude ) )
                           * cos( radians( longitude ) - radians(?)
                           ) + sin( radians(?) ) *
                           sin( radians( latitude ) ) )
                         ) AS distance", [$latitude, $longitude, $latitude])
            ->where('status', '=', 'online')
            ->having("distance", "<", $radius)
            ->orderBy("distance", 'asc')
            ->first();
        } else {
            $selectedDriver = Driver::selectRaw("id, name, address, latitude, longitude, device_token,
                         ( 6371 * acos( cos( radians(?) ) *
                           cos( radians( latitude ) )
                           * cos( radians( longitude ) - radians(?)
                           ) + sin( radians(?) ) *
                           sin( radians( latitude ) ) )
                         ) AS distance", [$latitude, $longitude, $latitude])
            ->whereNotIn("id", $checkIds)->where('status', '=', 'online')
            ->having("distance", "<", $radius)
            ->orderBy("distance", 'asc')
            ->first();
        }

        if (!$selectedDriver) {
            return redirect()->back()->with('alert', ['type' => 'danger', 'message' => 'Aucun livreur disponible dans la zone']);
        }

        $driver_id    = $selectedDriver->id;
        $device_token = $selectedDriver->device_token;

        $updateProduct = Order::whereIn('id', $request->id)
            ->update(['driver_id' => $selectedDriver->id]);
         $data=$this->notification($body,$title,$device_token,$key,$driver_id);
        //dd($data);
        $alert = [];
        $alert['type'] = 'success';
        $alert['message'] = 'Demande envoyée avec succès';
        return redirect()->back()->with('alert', $alert);
    }

}
