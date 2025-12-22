<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Services\RatingService;
use App\Order;
use App\CompletedOrder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;

class OrderRatingController extends Controller
{
    protected $ratingService;
    
    public function __construct(RatingService $ratingService)
    {
        $this->ratingService = $ratingService;
    }
    
    /**
     * Noter une commande
     * POST /api/orders/{order}/rating
     */
    public function store(Request $request, $orderId)
    {
        $validator = Validator::make($request->all(), [
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string|max:1000',
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Données invalides',
                'errors' => $validator->errors(),
            ], 422);
        }
        
        // Récupérer l'utilisateur authentifié
        $user = Auth::user();
        if (!$user) {
            // Essayer avec user_id dans la requête (pour compatibilité API mobile)
            $userId = $request->input('user_id');
            if (!$userId) {
                return response()->json([
                    'status' => false,
                    'message' => 'Utilisateur non authentifié.',
                ], 401);
            }
        } else {
            $userId = $user->id;
        }
        
        try {
            $rating = $this->ratingService->rateOrder(
                $orderId,
                $userId,
                (int) $request->input('rating'),
                $request->input('comment')
            );
            
            return response()->json([
                'status' => true,
                'message' => 'Note enregistrée avec succès.',
                'data' => [
                    'rating' => $rating->rating,
                    'comment' => $rating->reviews,
                    'created_at' => $rating->created_at,
                ],
            ], 201);
            
        } catch (\RuntimeException $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage(),
            ], 422);
        } catch (\Exception $e) {
            \Log::error('Erreur lors de la notation', [
                'order_id' => $orderId,
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);
            
            return response()->json([
                'status' => false,
                'message' => 'Une erreur est survenue lors de l\'enregistrement de la note.',
            ], 500);
        }
    }
    
    /**
     * Vérifier si une commande peut être notée
     * GET /api/orders/{order}/rating/check
     */
    public function check(Request $request, $orderId)
    {
        $userId = Auth::id() ?? $request->input('user_id');
        
        if (!$userId) {
            return response()->json([
                'status' => false,
                'message' => 'Utilisateur non authentifié.',
            ], 401);
        }
        
        $result = $this->ratingService->canRateOrder($orderId, $userId);
        
        return response()->json([
            'status' => true,
            'can_rate' => $result['can_rate'],
            'message' => $result['message'],
            'existing_rating' => $result['existing_rating'] ? [
                'rating' => $result['existing_rating']->rating,
                'comment' => $result['existing_rating']->reviews,
                'created_at' => $result['existing_rating']->created_at,
            ] : null,
        ]);
    }
    
    /**
     * Récupérer la note d'une commande
     * GET /api/orders/{order}/rating
     */
    public function show(Request $request, $orderId)
    {
        $userId = Auth::id() ?? $request->input('user_id');
        
        if (!$userId) {
            return response()->json([
                'status' => false,
                'message' => 'Utilisateur non authentifié.',
            ], 401);
        }
        
        // Chercher la note
        $rating = \App\Rating::where('order_id', $orderId)
            ->where('user_id', $userId)
            ->first();
        
        if (!$rating) {
            return response()->json([
                'status' => false,
                'message' => 'Aucune note trouvée pour cette commande.',
            ], 404);
        }
        
        return response()->json([
            'status' => true,
            'data' => [
                'rating' => $rating->rating,
                'comment' => $rating->reviews,
                'created_at' => $rating->created_at,
            ],
        ]);
    }
}

