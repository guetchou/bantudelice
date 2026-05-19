<?php

namespace App\Http\Controllers;

use App\Address;
use App\Services\GeolocationService;
use Illuminate\Http\Request;

class UserAddressController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:100'],
            'building_no' => ['nullable', 'string', 'max:100'],
            'street_no' => ['nullable', 'string', 'max:100'],
            'area' => ['required', 'string', 'max:255'],
            'floor' => ['nullable', 'string', 'max:100'],
            'complete_address' => ['required', 'string', 'max:500'],
            'is_default' => ['nullable', 'boolean'],
        ]);

        $userId = auth()->id();
        $resolved = GeolocationService::geocode($data['complete_address']);

        $address = Address::create([
            'title' => $data['title'],
            'user_id' => $userId,
            'building_no' => $data['building_no'] ?? null,
            'street_no' => $data['street_no'] ?? null,
            'area' => $data['area'],
            'floor' => $data['floor'] ?? null,
            'latitude' => $resolved['lat'] ?? null,
            'longitude' => $resolved['lng'] ?? null,
            'complete_address' => $resolved['formatted_address'] ?? $data['complete_address'],
            'is_default' => (bool) ($data['is_default'] ?? false),
        ]);

        $makeDefault = (bool) ($data['is_default'] ?? false) || Address::where('user_id', $userId)->count() === 1;
        if ($makeDefault) {
            Address::where('user_id', $userId)->update(['is_default' => false]);
            Address::where('id', $address->id)->update(['is_default' => true]);
        }

        return back()->with('message', 'Adresse enregistrée.');
    }

    public function update(Request $request, Address $address)
    {
        abort_unless($address->user_id === auth()->id(), 403);

        $data = $request->validate([
            'title' => ['required', 'string', 'max:100'],
            'building_no' => ['nullable', 'string', 'max:100'],
            'street_no' => ['nullable', 'string', 'max:100'],
            'area' => ['required', 'string', 'max:255'],
            'floor' => ['nullable', 'string', 'max:100'],
            'complete_address' => ['required', 'string', 'max:500'],
            'is_default' => ['nullable', 'boolean'],
        ]);

        $resolved = GeolocationService::geocode($data['complete_address']);
        $address->update([
            'title' => $data['title'],
            'building_no' => $data['building_no'] ?? null,
            'street_no' => $data['street_no'] ?? null,
            'area' => $data['area'],
            'floor' => $data['floor'] ?? null,
            'latitude' => $resolved['lat'] ?? null,
            'longitude' => $resolved['lng'] ?? null,
            'complete_address' => $resolved['formatted_address'] ?? $data['complete_address'],
            'is_default' => (bool) ($data['is_default'] ?? false),
        ]);

        if ((bool) ($data['is_default'] ?? false)) {
            Address::where('user_id', $address->user_id)->update(['is_default' => false]);
            Address::where('id', $address->id)->update(['is_default' => true]);
        }

        return back()->with('message', 'Adresse mise à jour.');
    }

    public function destroy(Address $address)
    {
        abort_unless($address->user_id === auth()->id(), 403);

        $wasDefault = (bool) $address->is_default;
        $address->delete();

        if ($wasDefault) {
            $next = Address::where('user_id', auth()->id())->orderByDesc('id')->first();
            if ($next) {
                Address::where('user_id', $next->user_id)->update(['is_default' => false]);
                Address::where('id', $next->id)->update(['is_default' => true]);
            }
        }

        return back()->with('message', 'Adresse supprimée.');
    }

    public function makeDefault(Address $address)
    {
        abort_unless($address->user_id === auth()->id(), 403);

        Address::where('user_id', $address->user_id)->update(['is_default' => false]);
        Address::where('id', $address->id)->update(['is_default' => true]);

        return back()->with('message', 'Adresse définie comme adresse principale.');
    }

    protected function setDefaultAddress(Address $address): void
    {
        Address::where('user_id', $address->user_id)->update(['is_default' => false]);
        Address::where('id', $address->id)->update(['is_default' => true]);
    }
}
