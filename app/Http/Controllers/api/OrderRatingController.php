<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Services\RatingService;
use App\Order;
use App\CompletedOrder;
use App\User;
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
            'rating' => 'nullable|integer|min:1|max:5',
            'comment' => 'nullable|string|max:1000',
            'driver_rating' => 'nullable|integer|min:1|max:5',
            'driver_comment' => 'nullable|string|max:1000',
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Données invalides',
                'errors' => $validator->errors(),
            ], 422);
        }

        if (!$request->filled('rating') && !$request->filled('driver_rating')) {
            return response()->json([
                'status' => false,
                'message' => 'Veuillez fournir au moins une note.',
            ], 422);
        }
        
        // Récupérer l'utilisateur authentifié
        $userId = $this->resolveUserIdFromRequest($request);

        if ($userId === null) {
            return response()->json([
                'status' => false,
                'message' => 'Utilisateur non authentifié.',
            ], 401);
        }

        if ($userId === false) {
            return response()->json([
                'status' => false,
                'message' => 'Utilisateur introuvable.',
            ], 404);
        }
        
        try {
            $rating = $this->ratingService->rateOrder(
                $orderId,
                $userId,
                $request->filled('rating') ? (int) $request->input('rating') : null,
                $request->input('comment'),
                $request->input('driver_rating') ? (int) $request->input('driver_rating') : null,
                $request->input('driver_comment')
            );
            
            return response()->json([
                'status' => true,
                'message' => 'Note enregistrée avec succès.',
                'data' => [
                    'restaurant_rating' => [
                        'rating' => $rating['restaurant_rating']->rating,
                        'comment' => $rating['restaurant_rating']->reviews,
                        'created_at' => $rating['restaurant_rating']->created_at,
                    ],
                    'driver_rating' => $rating['driver_review'] ? [
                        'rating' => $rating['driver_review']->rating,
                        'comment' => $rating['driver_review']->reviews,
                        'created_at' => $rating['driver_review']->created_at,
                    ] : null,
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
        $userId = $this->resolveUserIdFromRequest($request);

        if ($userId === null) {
            return response()->json([
                'status' => false,
                'message' => 'Utilisateur non authentifié.',
            ], 401);
        }

        if ($userId === false) {
            return response()->json([
                'status' => false,
                'message' => 'Utilisateur introuvable.',
            ], 404);
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
            'existing_driver_review' => $result['existing_driver_review'] ? [
                'rating' => $result['existing_driver_review']->rating,
                'comment' => $result['existing_driver_review']->reviews,
                'created_at' => $result['existing_driver_review']->created_at,
            ] : null,
        ]);
    }
    
    /**
     * Récupérer la note d'une commande
     * GET /api/orders/{order}/rating
     */
    public function show(Request $request, $orderId)
    {
        $userId = $this->resolveUserIdFromRequest($request);

        if ($userId === null) {
            return response()->json([
                'status' => false,
                'message' => 'Utilisateur non authentifié.',
            ], 401);
        }

        if ($userId === false) {
            return response()->json([
                'status' => false,
                'message' => 'Utilisateur introuvable.',
            ], 404);
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
        
        $driverReview = \Illuminate\Support\Facades\Schema::hasTable('reviews')
            ? \App\Review::where('user_id', $userId)
                ->when(\Illuminate\Support\Facades\Schema::hasColumn('reviews', 'order_id'), fn ($query) => $query->where('order_id', $orderId))
                ->latest('id')
                ->first()
            : null;

        return response()->json([
            'status' => true,
            'data' => [
                'restaurant_rating' => [
                    'rating' => $rating->rating,
                    'comment' => $rating->reviews,
                    'created_at' => $rating->created_at,
                ],
                'driver_rating' => $driverReview ? [
                    'rating' => $driverReview->rating,
                    'comment' => $driverReview->reviews,
                    'created_at' => $driverReview->created_at,
                ] : null,
            ],
        ]);
    }

    private function resolveUserIdFromRequest(Request $request)
    {
        if (Auth::check()) {
            return Auth::id();
        }

        $userId = $request->input('user_id');

        if (!$userId) {
            return null;
        }

        return User::whereKey($userId)->exists() ? (int) $userId : false;
    }
}
