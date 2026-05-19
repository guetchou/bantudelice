<?php

namespace App\Http\Controllers;

use App\Order;
use App\Services\OrderChatService;
use Illuminate\Http\Request;

class OrderChatController extends Controller
{
    public function __construct(protected OrderChatService $chatService)
    {
    }

    public function messages(Request $request, string $orderNo)
    {
        if (!auth()->check()) {
            return response()->json(['status' => false, 'message' => 'Non authentifié'], 401);
        }

        $order = Order::with(['restaurant', 'delivery.driver', 'user'])->where('order_no', $orderNo)->firstOrFail();
        $chatData = $this->chatService->viewDataForOrder($order, $request->user(), true);

        if (!$chatData || !($chatData['can_view'] ?? false)) {
            return response()->json(['status' => false, 'message' => 'Accès non autorisé'], 403);
        }

        $html = view('frontend.partials.order_chat_messages', [
            'messages' => $chatData['messages'],
            'currentRole' => $chatData['role'],
        ])->render();

        return response()->json([
            'status' => true,
            'html' => $html,
            'messages_count' => count($chatData['messages']),
            'unread_count' => $chatData['unread_count'] ?? 0,
            'updated_at' => optional($order->updated_at)->toIso8601String(),
        ]);
    }

    public function store(Request $request, string $orderNo)
    {
        $request->validate([
            'message' => 'required|string|min:1|max:2000',
        ]);

        if (!auth()->check()) {
            return redirect()->back()->with('message', 'Veuillez vous connecter pour discuter.');
        }

        $order = Order::with(['restaurant', 'delivery.driver', 'user'])->where('order_no', $orderNo)->firstOrFail();
        $chatData = $this->chatService->viewDataForOrder($order, $request->user(), false);

        if (!$chatData || !($chatData['can_write'] ?? false)) {
            abort(403, 'Accès non autorisé');
        }

        try {
            $this->chatService->sendMessage($order, $request->user(), $request->input('message'));
        } catch (\Throwable $e) {
            return redirect()->back()->with('message', $e->getMessage());
        }

        if ($request->wantsJson()) {
            return response()->json([
                'status' => true,
                'message' => 'Message envoyé',
            ]);
        }

        return redirect()->back()->with('success', 'Message envoyé.');
    }
}
