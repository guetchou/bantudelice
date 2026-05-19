<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\RemembersFrontendBrand;
use App\Address;
use App\Order;
use App\User;
use App\Services\LoyaltyService;
use App\Services\OrderChatService;
use App\Services\UnifiedMediaLibraryService;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    use RemembersFrontendBrand;

   public function profile(){
       if (!auth()->check()) {
           return redirect()->route('user.login')->with('message', 'Veuillez vous connecter pour acceder a votre profil.');
       }

       $addresses = collect();
       if (\Illuminate\Support\Facades\Schema::hasTable('user_address')) {
           $addresses = Address::where('user_id', auth()->id())
               ->orderByDesc('is_default')
               ->orderByDesc('id')
               ->get();
       }

       $orders = Order::where('user_id', auth()->id())
           ->with(['restaurant', 'rating', 'delivery.driver', 'driver'])
           ->orderBy('created_at', 'desc')
           ->get();

       $completedOrders = \App\CompletedOrder::where('user_id', auth()->id())
           ->where('status', 'completed')
           ->with(['restaurant', 'driver'])
           ->orderBy('created_at', 'desc')
           ->get();

       $allOrders = $orders->concat($completedOrders)->sortByDesc('created_at');
       $chatService = app(OrderChatService::class);
       $allOrders = $allOrders->map(function ($order) use ($chatService) {
           $order->chatBadge = $chatService->badgeDataForOrderNo((string) ($order->order_no ?? ''), 'customer');
           return $order;
       });
       $totalOrders = Order::where('user_id', auth()->id())->count();
       $completedOrdersCount = Order::where('user_id', auth()->id())
           ->with('delivery')
           ->get()
           ->filter(function ($order) {
               return method_exists($order, 'resolveEffectiveBusinessStatus')
                   ? $order->resolveEffectiveBusinessStatus() === 'delivered'
                   : ($order->status ?? null) === 'completed';
           })
           ->count();
       $loyaltyPoints = \App\Services\LoyaltyService::getBalance(auth()->id());
       $loyaltyHistory = \App\Services\LoyaltyService::getHistory(auth()->id(), 10);
       $loyaltyDiscount = \App\Services\LoyaltyService::calculateDiscount($loyaltyPoints);
       [$dashboardLink, $dashboardLabel] = $this->resolveProfileDashboardAccess(auth()->user());
       $mediaLibraryOptions = app(UnifiedMediaLibraryService::class)->groupedOptions();

       return view('frontend.profile', compact(
           'addresses',
           'orders',
           'completedOrders',
           'allOrders',
           'totalOrders',
           'completedOrdersCount',
           'loyaltyPoints',
           'loyaltyHistory',
           'loyaltyDiscount',
           'dashboardLink',
           'dashboardLabel',
           'mediaLibraryOptions'
       ));
   }

   protected function resolveProfileDashboardAccess(User $user): array
   {
       $userType = $user->type ?? 'user';

       if ($userType === 'admin') {
           return [route('admin.dashboard'), "Accéder à l'administration"];
       }

       if ($userType === 'restaurant') {
           return [route('restaurant.dashboard'), 'Accéder à mon espace restaurant'];
       }

       if ($userType === 'delivery') {
           return [route('delivery.dashboard'), 'Accéder à mon espace livraison'];
       }

       if ($userType === 'driver') {
           return [route('driver.deliveries'), 'Accéder à mon espace livreur'];
       }

       return [null, null];
   }

    public function updateProfile(Request $request)
    {
        if (!auth()->check()) {
            return redirect()->route('user.login')->with('message', 'Veuillez vous connecter pour acceder a votre profil.');
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
        ]);

        $user = auth()->user();
        $user->name = $request->name;
        $user->phone = $request->phone;
        $user->save();

        return back()->with('success', 'Profil mis à jour avec succès !');
    }

    public function updatePassword(Request $request)
    {
        if (!auth()->check()) {
            return redirect()->route('user.login')->with('message', 'Veuillez vous connecter pour acceder a votre profil.');
        }

        $request->validate([
            'current_password' => 'required',
            'password' => 'required|min:6|confirmed',
        ]);

        $user = auth()->user();

        if (!\Hash::check($request->current_password, $user->password)) {
            return back()->withErrors(['current_password' => 'Le mot de passe actuel est incorrect.']);
        }

        $user->password = \Hash::make($request->password);
        $user->save();

        return back()->with('success', 'Mot de passe mis à jour avec succès !');
    }

    public function updateAvatar(Request $request)
    {
        if (!auth()->check()) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['message' => 'Veuillez vous connecter pour acceder a votre profil.'], 401);
            }

            return redirect()->route('user.login')->with('message', 'Veuillez vous connecter pour acceder a votre profil.');
        }

        $request->validate([
            'avatar' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:8192|required_without:avatar_media_path',
            'avatar_media_path' => 'nullable|string|max:2048|required_without:avatar',
        ]);

        $user = auth()->user();

        if (! $request->hasFile('avatar') && ! $request->filled('avatar_media_path')) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['message' => "Aucune image n'a ete recue."], 422);
            }

            return back()->withErrors(['avatar' => "Aucune image n'a ete recue."]);
        }

        $directory = public_path('images/profile_images');

        if (! is_dir($directory) && ! @mkdir($directory, 0755, true) && ! is_dir($directory)) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['message' => 'Le dossier de destination des photos est indisponible.'], 500);
            }

            return back()->withErrors(['avatar' => 'Le dossier de destination des photos est indisponible.']);
        }

        try {
            if ($request->hasFile('avatar')) {
                $image = $request->file('avatar');
                $imageName = time() . '_' . $user->id . '.' . $image->getClientOriginalExtension();
                $image->move($directory, $imageName);
            } else {
                $imageName = app(UnifiedMediaLibraryService::class)->copyToDirectory(
                    $request->input('avatar_media_path'),
                    'images/profile_images',
                    'user-avatar-' . $user->id
                );
            }

            if ($user->image && file_exists($directory . '/' . $user->image)) {
                @unlink($directory . '/' . $user->image);
            }

            $user->image = $imageName;
            $user->save();
        } catch (\Throwable $e) {
            \Log::error('Avatar upload failed', [
                'user_id' => $user?->id,
                'message' => $e->getMessage(),
            ]);

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['message' => 'Impossible de televerser la photo pour le moment.'], 500);
            }

            return back()->withErrors(['avatar' => 'Impossible de televerser la photo pour le moment.']);
        }

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'message' => 'Photo de profil mise a jour !',
                'avatar_url' => $user->avatarUrl(),
            ]);
        }

        return back()->with('success', 'Photo de profil mise a jour !');
    }

    /**
     * Suppression self-service: anonymise le compte et déconnecte l'utilisateur.
     */
    public function deleteAccount(Request $request)
    {
        if (!auth()->check()) {
            return redirect()->route('user.login')->with('error', 'Veuillez vous connecter.');
        }

        $request->validate([
            'confirm' => 'required|string|in:SUPPRIMER',
        ], [
            'confirm.in' => 'Veuillez saisir "SUPPRIMER" pour confirmer.',
        ]);

        $user = auth()->user();
        \App\Services\UserDeletionService::anonymizeUser($user, [
            'source' => 'self_service',
        ]);

        auth()->logout();
        return redirect()->route('home')->with('message', 'Votre compte a été supprimé et vos données personnelles ont été anonymisées.');
    }

    /**
     * S5.6 — Page "mes commandes" dédiée avec pagination (50 max/page).
     */
    public function orders(Request $request)
    {
        if (!auth()->check()) {
            return redirect()->route('user.login');
        }

        $perPage = 20;
        $tab     = $request->query('tab', 'active');

        if ($tab === 'completed') {
            $orders = \App\CompletedOrder::where('user_id', auth()->id())
                ->with(['restaurant'])
                ->orderByDesc('created_at')
                ->paginate($perPage);
        } else {
            $orders = Order::where('user_id', auth()->id())
                ->with(['restaurant', 'delivery'])
                ->orderByDesc('created_at')
                ->paginate($perPage);
        }

        return view('frontend.orders', compact('orders', 'tab'));
    }
}
