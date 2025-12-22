<?php

namespace App\Http\Controllers\restaurant;

use App\Http\Controllers\Controller;
use App\Restaurant;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function profile()
    {
        $user = auth()->user();
        $restaurantProfile = $user->restaurant; // Model Restaurant lié

        return view('restaurant.profile', [
            // compat: la vue utilise $restaurant pour le USER (compte restaurant)
            'restaurant' => $user,
            'restaurantProfile' => $restaurantProfile,
        ]);
    }
    public function profile_update(Request $request)
    {
        $user = auth()->user();
        $restaurantProfile = $user->restaurant;

        // 1) Changement de mot de passe (si formulaire password)
        if ($request->filled('old_password') || $request->filled('password')) {
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

        // 2) Mise à jour profil + restaurant (si disponible)
        $request->validate([
            // Peut être absent lors de l'upload rapide (form caché). Dans ce cas, on conserve la valeur existante.
            'name' => ['nullable', 'string', 'max:191'],
            'phone' => ['nullable', 'string', 'max:191', Rule::unique('users', 'phone')->ignore($user->id)],
            'image' => ['nullable', 'image', 'mimes:jpeg,png,jpg', 'max:2048'],

            // Champs restaurant (optionnels)
            'restaurant_name' => ['nullable', 'string', 'max:191'],
            'city' => ['nullable', 'string', 'max:191'],
            'address' => ['nullable', 'string', 'max:255'],
            'slogan' => ['nullable', 'string', 'max:191'],
            'description' => ['nullable', 'string', 'max:2000'],
            'min_order' => ['nullable', 'numeric', 'min:0'],
            'delivery_charges' => ['nullable', 'numeric', 'min:0'],
            'avg_delivery_time' => ['nullable', 'string', 'max:20'],

            // Images restaurant: upload ou URL
            'logo' => ['nullable', 'image', 'mimes:jpeg,png,jpg', 'max:4096'],
            'cover_image' => ['nullable', 'image', 'mimes:jpeg,png,jpg', 'max:6144'],
            'logo_url' => ['nullable', 'url', 'max:2048'],
            'cover_image_url' => ['nullable', 'url', 'max:2048'],
        ]);

        if ($request->filled('name')) {
            $user->name = $request->name;
        }
        $user->phone = $request->phone ?: $user->phone;
        $user->save();

        // Upload image profil (compte)
        if ($request->hasFile('image')) {
            $file = $request->file('image');
            $destination = public_path('images/profile_images');
            if (!is_dir($destination)) {
                @mkdir($destination, 0775, true);
            }
            $filename = Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME))
                . '-' . uniqid() . '.' . $file->getClientOriginalExtension();
            $file->move($destination, $filename);
            $user->image = $filename;
            $user->save();
        }

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
            } elseif ($request->filled('logo_url')) {
                $restaurantProfile->logo = $request->logo_url;
            }

            if ($request->hasFile('cover_image')) {
                $file = $request->file('cover_image');
                $filename = Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME))
                    . '-' . uniqid() . '.' . $file->getClientOriginalExtension();
                $file->move($restaurantImgDir, $filename);
                $restaurantProfile->cover_image = $filename;
            } elseif ($request->filled('cover_image_url')) {
                $restaurantProfile->cover_image = $request->cover_image_url;
            }

            $restaurantProfile->save();
        }

        return redirect()->route('restaurant.profile')->with('alert', [
            'type' => 'success',
            'message' => 'Profil mis à jour avec succès',
        ]);

    }
}
