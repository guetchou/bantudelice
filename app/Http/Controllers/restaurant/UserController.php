<?php

namespace App\Http\Controllers\restaurant;

use App\Http\Controllers\Controller;
use App\Restaurant;
use App\RestaurantSpecialClosure;
use App\Services\DataSyncService;
use App\Services\UnifiedMediaLibraryService;
use App\User;
use App\Voucher;
use App\WorkingHour;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function __construct(private UnifiedMediaLibraryService $unifiedMediaLibraryService)
    {
    }

    public function profile()
    {
        $user = auth()->user();
        $restaurantProfile = $user->restaurant; // Model Restaurant lié
        $mediaLibraryOptions = $this->unifiedMediaLibraryService->groupedOptions();

        $restaurantId = $restaurantProfile instanceof Restaurant ? $restaurantProfile->id : null;

        $workingHours    = $restaurantId ? WorkingHour::where('restaurant_id', $restaurantId)->orderBy('id')->get() : collect();
        $specialClosures = $restaurantId ? RestaurantSpecialClosure::where('restaurant_id', $restaurantId)->orderBy('starts_on')->get() : collect();
        $vouchers        = $restaurantId ? Voucher::where('restaurant_id', $restaurantId)->latest()->get() : collect();

        return view('restaurant.profile', [
            // compat: la vue utilise $restaurant pour le USER (compte restaurant)
            'restaurant' => $user,
            'restaurantProfile' => $restaurantProfile,
            'mediaLibraryOptions' => $mediaLibraryOptions,
            'workingHours' => $workingHours,
            'specialClosures' => $specialClosures,
            'vouchers' => $vouchers,
        ]);
    }
    public function profile_update(Request $request)
    {
        $user = auth()->user();
        $restaurantProfile = $user->restaurant;
        $section = (string) $request->input('profile_section', 'account');

        // 1) Changement de mot de passe (si formulaire password)
        if ($section === 'password' || $request->filled('old_password') || $request->filled('password')) {
            $request->validate([
                'old_password' => ['required', 'string'],
                'password' => ['required', 'string', 'min:6', 'max:191', 'confirmed'],
            ]);

            if (!Hash::check($request->old_password, $user->password)) {
                return back()->withErrors(['old_password' => 'Mot de passe actuel incorrect.'])->withInput();
            }

            $user->password = Hash::make($request->password);
            $user->save();

            return redirect()->route('restaurant.profile')->with('alert', [
                'type' => 'success',
                'message' => 'Mot de passe mis à jour avec succès',
            ]);
        }

        if ($section === 'account') {
            $request->validate([
                'name' => ['nullable', 'string', 'max:191'],
                'phone' => ['nullable', 'string', 'max:191', Rule::unique('users', 'phone')->ignore($user->id)],
            ]);

            if ($request->filled('name')) {
                $user->name = $request->name;
            }
            $user->phone = $request->phone ?: $user->phone;
            $user->save();

            return redirect()->route('restaurant.profile')->with('alert', [
                'type' => 'success',
                'message' => 'Informations du compte mises à jour avec succès',
            ]);
        }

        $request->validate([
            'restaurant_name' => ['nullable', 'string', 'max:191'],
            'city' => ['nullable', 'string', 'max:191'],
            'address' => ['nullable', 'string', 'max:255'],
            'slogan' => ['nullable', 'string', 'max:191'],
            'description' => ['nullable', 'string', 'max:2000'],
            'min_order' => ['nullable', 'numeric', 'min:0'],
            'delivery_charges' => ['nullable', 'numeric', 'min:0'],
            'avg_delivery_time' => ['nullable', 'string', 'max:20'],
            'logo' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:8192'],
            'cover_image' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:8192'],
            'logo_url' => ['nullable', 'url', 'max:2048'],
            'cover_image_url' => ['nullable', 'url', 'max:2048'],
            'logo_media_path' => ['nullable', 'string', 'max:2048'],
            'cover_image_media_path' => ['nullable', 'string', 'max:2048'],
        ]);

        // Update restaurant profile
        if ($restaurantProfile instanceof Restaurant) {
            if ($request->filled('restaurant_name')) $restaurantProfile->name = $request->restaurant_name;
            if ($request->filled('city')) $restaurantProfile->city = $request->city;
            if ($request->filled('address')) $restaurantProfile->address = $request->address;
            if ($request->filled('slogan')) $restaurantProfile->slogan = $request->slogan;
            if ($request->filled('description')) $restaurantProfile->description = $request->description;
            if (!is_null($request->min_order)) $restaurantProfile->min_order = $request->min_order;
            if (!is_null($request->delivery_charges)) $restaurantProfile->delivery_charges = $request->delivery_charges;
            if ($request->filled('avg_delivery_time')) $restaurantProfile->avg_delivery_time = $request->avg_delivery_time;

            // Logo / cover: si upload -> fichier, sinon URL si fourni
            $restaurantImgDir = public_path('images/restaurant_images');
            if (!is_dir($restaurantImgDir)) {
                @mkdir($restaurantImgDir, 0775, true);
            }

            if ($request->hasFile('logo')) {
                $file = $request->file('logo');
                $filename = Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME))
                    . '-' . uniqid() . '.' . $file->getClientOriginalExtension();
                $file->move($restaurantImgDir, $filename);
                $restaurantProfile->logo = $filename;
            } elseif ($request->filled('logo_media_path')) {
                $restaurantProfile->logo = $this->unifiedMediaLibraryService->copyToDirectory(
                    $request->input('logo_media_path'),
                    'images/restaurant_images',
                    'restaurant-logo-' . $restaurantProfile->id
                );
            } elseif ($request->filled('logo_url')) {
                $restaurantProfile->logo = $request->logo_url;
            }

            if ($request->hasFile('cover_image')) {
                $file = $request->file('cover_image');
                $filename = Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME))
                    . '-' . uniqid() . '.' . $file->getClientOriginalExtension();
                $file->move($restaurantImgDir, $filename);
                $restaurantProfile->cover_image = $filename;
            } elseif ($request->filled('cover_image_media_path')) {
                $restaurantProfile->cover_image = $this->unifiedMediaLibraryService->copyToDirectory(
                    $request->input('cover_image_media_path'),
                    'images/restaurant_images',
                    'restaurant-cover-' . $restaurantProfile->id
                );
            } elseif ($request->filled('cover_image_url')) {
                $restaurantProfile->cover_image = $request->cover_image_url;
            }

            $restaurantProfile->save();
            DataSyncService::invalidateRestaurantCache($restaurantProfile->id);
        }

        return redirect()->route('restaurant.profile')->with('alert', [
            'type' => 'success',
            'message' => 'Identité du restaurant mise à jour avec succès',
        ]);

    }
}
