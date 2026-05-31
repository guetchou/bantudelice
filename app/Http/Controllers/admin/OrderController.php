<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Order;
use App\CompletedOrder;
use App\Restaurant;
use App\Services\NotificationService;
use App\Services\DeliveryService;
use App\Services\CommerceRefundService;
use App\Services\FoodOrderFinanceService;
use App\Services\FoodOrderStateMachineService;
use App\Services\OrderChatService;
use App\UserToken;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;

class OrderController extends Controller
{
    public function __construct(
        protected DeliveryService $deliveryService,
        protected FoodOrderFinanceService $foodOrderFinanceService,
        protected CommerceRefundService $refunds,
        protected FoodOrderStateMachineService $foodOrderStateMachine
    ) {}

    public function notification( $body,$title,$device_token,$key,$user_id)
    {
        $result = NotificationService::sendToDevice($device_token, $title, $body, $key, $user_id, 'user');

        return response()->json(['data' => !empty($result['success']) ? 'notification sent' : 'notification failed', 'action' => $result['action'] ?? null], 200);
    }
    
    public function userNotification( $body,$title,$device_token,$key,$user_id)
    {
        $result = NotificationService::sendToMultipleDevices($device_token, $title, $body, $key, $user_id, 'user');

        return response()->json(['data' => !empty($result['success']) ? 'notification sent' : 'notification failed', 'action' => $result['action'] ?? null], 200);
    }
    public function all_orders(Request $request)
    {
        $nearby_drivers = null;

        $query = Order::with(['user', 'restaurant'])->latest();

        if ($request->filled('date')) {
            $arr   = explode(' - ', $request->date, 2);
            $start = Carbon::createFromFormat('d/m/Y', trim($arr[0]))->startOfDay();
            $end   = Carbon::createFromFormat('d/m/Y', trim($arr[1] ?? $arr[0]))->endOfDay();
            $query->whereBetween('created_at', [$start, $end]);
        }

        // Paginer au lieu de charger toute la table
        $orders = $query->paginate(50);

        $chatService = app(OrderChatService::class);
        $orders->getCollection()->transform(function ($order) use ($chatService) {
            $order->chatBadge = $chatService->badgeDataForOrder($order, auth()->user());
            return $order;
        });

        return view('admin.order.all_orders', compact('nearby_drivers', 'orders'));
    }
    public function complete_orders(Request $request)
    {
        $query = $this->completedOrdersQuery();

        if ($request->filled('date')) {
            $arr   = explode(' - ', $request->date, 2);
            $start = Carbon::createFromFormat('d/m/Y', trim($arr[0]))->startOfDay();
            $end   = Carbon::createFromFormat('d/m/Y', trim($arr[1] ?? $arr[0]))->endOfDay();
            $query->whereBetween('created_at', [$start, $end]);
        }
        $orders = $query->latest()->paginate(50);

        $chatService = app(OrderChatService::class);
        $orders->getCollection()->transform(function ($order) use ($chatService) {
            if ($order instanceof \App\Order) {
                $order->chatBadge = $chatService->badgeDataForOrder($order, auth()->user());
            }
            return $order;
        });
        return view('admin.order.complete_orders')->with('orders', $orders);
    }
    public function pending_orders(Request $request)
    {
        $query = Order::with('user');

        if (Schema::hasColumn('orders', 'business_status')) {
            $query->where('business_status', 'pending_restaurant_acceptance');
        } else {
            $query->where('status', 'pending');
        }

        if($request->filled('date')) {
            $arr = explode(' - ', $request->date, 2);
            $start = Carbon::createFromFormat('d/m/Y', trim($arr[0]))->startOfDay();
            $end = Carbon::createFromFormat('d/m/Y', trim($arr[1] ?? $arr[0]))->endOfDay();
            $orders=$query->whereBetween('created_at', [$start, $end])->latest()->get()->unique('order_no');
        }else{
            $orders=$query->latest()->get()->unique('order_no');
        }
        $chatService = app(OrderChatService::class);
        $orders = $orders->map(function ($order) use ($chatService) {
            $order->chatBadge = $chatService->badgeDataForOrder($order, auth()->user());
            return $order;
        });
        return view('admin.order.pending_orders')->with('orders',$orders);
    }
    public function cancel_orders(Request $request)
    {
        $query = Order::with('user');

        if (Schema::hasColumn('orders', 'business_status')) {
            $query->where('business_status', 'cancelled');
        } else {
            $query->where('status', 'cancelled');
        }

        if($request->filled('date')) {
            $arr = explode(' - ', $request->date, 2);
            $start = Carbon::createFromFormat('d/m/Y', trim($arr[0]))->startOfDay();
            $end = Carbon::createFromFormat('d/m/Y', trim($arr[1] ?? $arr[0]))->endOfDay();
            $orders=$query->whereBetween('created_at', [$start, $end])->latest()->get()->unique('order_no');
        }else{
            $orders=$query->latest()->get()->unique('order_no');
        }
        $chatService = app(OrderChatService::class);
        $orders = $orders->map(function ($order) use ($chatService) {
            $order->chatBadge = $chatService->badgeDataForOrder($order, auth()->user());
            return $order;
        });
        return view('admin.order.cancel_orders')->with('orders',$orders);
    }
    public function prepaire_orders()
    {
        $query = Order::query();

        if (Schema::hasColumn('orders', 'business_status')) {
            $query->where('business_status', 'in_kitchen');
        } else {
            $query->where('status', 'prepairing');
        }

        $orders=$query->get()->unique('order_no');
        $chatService = app(OrderChatService::class);
        $orders = $orders->map(function ($order) use ($chatService) {
            $order->chatBadge = $chatService->badgeDataForOrder($order, auth()->user());
            return $order;
        });
        return view('admin.order.prepaire_orders')->with('orders',$orders);
    }
    public function schedule_orders()
    {
        $current_date = Carbon::now();
        $orders=Order::whereNotNull('scheduled_date')
            ->where('scheduled_date', '>=', $current_date)
            ->get();
        $chatService = app(OrderChatService::class);
        $orders = $orders->map(function ($order) use ($chatService) {
            $order->chatBadge = $chatService->badgeDataForOrder($order, auth()->user());
            return $order;
        });
        return view('admin.order.schedule_orders')->with('orders',$orders);

    }
    public function show_order($id)
    {
        $order=Order::with(['driver', 'delivery.driver', 'restaurant', 'user'])->where('order_no',$id)->first();
        $products=Order::with('product')->where('order_no',$id)->get();
        $chatData = app(OrderChatService::class)->viewDataForOrder($order, auth()->user());
        //dd($products);
        return view('admin.order.show_orders', compact('order','products','chatData'));
    }

    protected function completedOrdersQuery()
    {
        if (Schema::hasColumn('orders', 'business_status')) {
            return Order::whereIn('business_status', ['delivered', 'picked_up_by_customer', 'closed']);
        }

        return CompletedOrder::where('status', 'completed');
    }
    public function show_completed_order($id)
    {
        $order=CompletedOrder::with('driver')->find($id);;
        //dd($order->driver);
        return view('admin.order.show_orders')->with('order',$order);
    }
    // public function prepaire_order(Request $request)
    // {
    //     $ids=$request->id;
    //     $users=DB::table('orders')
    //     ->whereIn('id', $ids)->get();
        
    //     $userOrderId=$users->pluck('user_id')->toArray();
    //     $Ids=array_unique($userOrderId);
        
    //     $getTokens=UserToken::whereIn('user_id',$Ids)->get();
    //     $device_token=$getTokens->pluck('device_tokens')->toArray();
    //     ///notification
    //     $body='You Order is Preparing Now!';
    //     $title='Preparing';
    //     $key='newOrder';
    //     $user_id=$Ids;
    //     $update=DB::table('orders')
    //     ->whereIn('id', $ids)
    //     ->update(['status' => 'prepairing']);
    //     if($update){
    //       $data=$this->userNotification($body,$title,$device_token,$key,$user_id); 
    //     }
    //     //dd($data);
    //     $alert = [];
    //     $alert['type'] = 'success';
    //     $alert['message'] = 'Statut de la commande mis à jour avec succès';
    //     return redirect()->back()->with('alert', $alert);
    // }
    
    // public function deliver_order(Order $order)
    // {
    //     $order->status = 'completed';
    //     $order->save();
    //     $alert = [];
    //     $alert['type'] = 'success';
    //     $alert['message'] = 'Commande livrée avec succès';
    //     return redirect()->route('admin.all_orders')->with('alert', $alert);
        
    // }
    // public function assign_order(Order $order)
    // {
    //   //dd($order);
    //     $order->status = 'assign';
    //     $order->save();
    //     $alert = [];
    //     $alert['type'] = 'success';
    //     $alert['message'] = 'Statut de la commande mis à jour avec succès';
    //     return redirect()->route('admin.all_orders')->with('alert', $alert);
        
    // }
    // public function assign_driver(Request $request)
    // {
    //     $request->validate([
    //         'id'=>'required',
    //     ]);
        
    //     $restuarantId=auth()->user()->name;
        
    //     $restaurant=Restaurant::where('name',$restuarantId)->first();
    //     $latitude=$restaurant->latitude;
    //     $longitude=$restaurant->longitude;
    //     $radius=6371;
    //     $drivers = Driver::selectRaw("id, name, address, latitude, longitude,device_token,
        
    //                  ( 6371 * acos( cos( radians(?) ) *
    //                   cos( radians( latitude ) )
    //                   * cos( radians( longitude ) - radians(?)
    //                   ) + sin( radians(?) ) *
    //                   sin( radians( latitude ) ) )
    //                  ) AS distance", [$latitude, $longitude, $latitude])
    //     ->where('id', '=', 32)->where('status', '=', 'online')
    //     ->having("distance", "<", $radius)
    //     ->orderBy("distance",'asc')
    //     ->first();
        
    //     $orders=Order::whereIn('id',$request->id)->get();
    //     $cartOrderNo=$orders->pluck('order_no')->toArray();
    //     $Ids=array_unique($cartOrderNo);
            
    //     $getorderData['orders']=Order::whereIn('order_no',$Ids)->with('user')->take(count($Ids))->get();
        
   
        
    //     //Notification for New Ride

    //     $body='You Have A New Order';
    //     $title='New Order';
    //     $key='newOrder';
    //     $user_id=$drivers->id;
    //     //$getDriver=Driver::where('id',$vehicle->driver_id)->latest()->first();
    //     $device_token=$drivers->device_token;
    //     $data=$this->notification($body,$title,$device_token,$key,$user_id);
        
    //     dd($data);
    //     $alert = [];
    //     $alert['type'] = 'success';
    //     $alert['message'] = 'Livreur assigné avec succès';
    //     return redirect()->back()->with('alert', $alert);
        
    // }
    public function cancel_order(Order $order, Request $request)
    {
        $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        // Enregistrer la raison d'annulation dans la table dédiée
        $user = auth()->user();
        if ($user && method_exists($user, 'cancellation_reasons')) {
            try {
                $user->cancellation_reasons()->create([
                    'reason' => $request->input('reason'),
                    'user_id' => $user->id,
                ]);
            } catch (\Throwable $e) {
                \Log::warning('cancel_order: impossile de sauver cancellation_reason', ['err' => $e->getMessage()]);
            }
        }

        try {
            // Annuler le groupe de commandes (state machine + livraison + points fidélité)
            $this->foodOrderFinanceService->cancelOrderGroup($order->order_no, [
                'actor_type' => 'admin',
                'actor_id'   => $user?->id,
                'reason_code'=> 'admin_cancelled',
                'notes'      => $request->input('reason'),
            ]);

            // Marquer le remboursement si nécessaire
            $this->refunds->refundOrder($order, 'admin_cancelled', [
                'actor_type'      => 'admin',
                'actor_id'        => $user?->id,
                'idempotency_key' => 'admin-cancelled-' . $order->id,
                'amount'          => (float) ($order->total ?? 0),
            ]);

            // Notifier le client par email si possible
            try {
                $order->loadMissing('user');
                if ($order->user?->email) {
                    $orderNo   = $order->order_no;
                    $userEmail = $order->user->email;
                    $userName  = $order->user->name ?? 'Client';
                    $reason    = $request->input('reason');
                    $contactUrl= route('contact.us');
                    \Illuminate\Support\Facades\Mail::send([], [], function ($m) use ($userEmail, $userName, $orderNo, $reason, $contactUrl) {
                        $m->to($userEmail, $userName)
                          ->subject("Commande #$orderNo annulée par l'administration — BantuDelice")
                          ->html(
                            "<div style='font-family:sans-serif;max-width:480px;margin:0 auto;padding:24px;'>"
                            . "<h2 style='color:#ef4444;'>Commande annulée</h2>"
                            . "<p>Bonjour " . htmlspecialchars($userName, ENT_QUOTES) . ",</p>"
                            . "<p>Votre commande <strong>#" . htmlspecialchars($orderNo, ENT_QUOTES) . "</strong> a été annulée par notre équipe.</p>"
                            . "<div style='background:#fef2f2;border-left:4px solid #ef4444;padding:12px 16px;border-radius:0 8px 8px 0;margin:16px 0;'>"
                            . "<strong>Motif :</strong> " . htmlspecialchars($reason, ENT_QUOTES)
                            . "</div>"
                            . "<p>Si vous avez été débité, un remboursement est en cours de traitement.</p>"
                            . "<a href='" . htmlspecialchars($contactUrl, ENT_QUOTES) . "' style='color:#009543;'>Contacter le support</a>"
                            . "</div>"
                          );
                    });
                }
            } catch (\Throwable $e) {
                \Log::warning('cancel_order admin: email client échoué', ['err' => $e->getMessage()]);
            }

            return redirect()->route('admin.all_orders')->with('alert', [
                'type'    => 'success',
                'message' => 'Commande #' . $order->order_no . ' annulée avec succès.',
            ]);

        } catch (\Throwable $e) {
            \Log::error('cancel_order admin: échec', ['order_no' => $order->order_no, 'err' => $e->getMessage()]);
            return redirect()->back()->with('alert', [
                'type'    => 'danger',
                'message' => 'Erreur lors de l\'annulation : ' . $e->getMessage(),
            ]);
        }
    }

    public function assign_order(Order $order)
    {
        $this->foodOrderStateMachine->transitionOrderGroup($order->order_no, 'dispatching', [
            'actor_type' => 'admin',
            'actor_id' => auth()->id(),
            'reason_code' => 'admin_dispatch_requested',
            'force' => true,
        ]);

        return redirect()->back()->with('alert', [
            'type' => 'success',
            'message' => 'Commande transmise au dispatch avec succès',
        ]);
    }

    public function deliver_order(Order $order)
    {
        $this->foodOrderStateMachine->transitionOrderGroup($order->order_no, 'delivered', [
            'actor_type' => 'admin',
            'actor_id' => auth()->id(),
            'reason_code' => 'admin_completed_manually',
            'force' => true,
        ]);

        return redirect()->back()->with('alert', [
            'type' => 'success',
            'message' => 'Commande livrée avec succès',
        ]);
    }

    public function resolveIncident(Request $request, $orderNo)
    {
        $request->validate([
            'resolution' => 'required|in:resolved,redelivery,cancelled',
            'support_notes' => 'nullable|string|max:1000',
        ]);

        $order = Order::with('delivery')->where('order_no', $orderNo)->firstOrFail();
        if (!$order->delivery) {
            return redirect()->back()->with('alert', [
                'type' => 'danger',
                'message' => 'Aucune livraison associée à cette commande'
            ]);
        }

        try {
            if ($request->input('resolution') === 'cancelled') {
                $this->foodOrderFinanceService->cancelOrderGroup($order->order_no, [
                    'actor_type' => 'admin',
                    'actor_id' => auth()->id(),
                    'reason_code' => 'support_cancelled',
                    'notes' => $request->input('support_notes'),
                ]);
                $this->refunds->refundOrder($order, 'support_cancelled', [
                    'actor_type' => 'admin',
                    'actor_id' => auth()->id(),
                    'idempotency_key' => 'support-cancelled-' . $order->id,
                    'amount' => (float) ($order->total ?? 0),
                ]);
            } else {
                $this->deliveryService->resolveSupportCase($order->delivery, $request->input('resolution'), [
                    'actor_type' => 'admin',
                    'actor_id' => auth()->id(),
                    'support_notes' => $request->input('support_notes'),
                    'notes' => $request->input('support_notes'),
                ]);
            }

            return redirect()->back()->with('alert', [
                'type' => 'success',
                'message' => 'Décision support appliquée avec succès'
            ]);
        } catch (\Throwable $e) {
            return redirect()->back()->with('alert', [
                'type' => 'danger',
                'message' => $e->getMessage()
            ]);
        }
    }
}
