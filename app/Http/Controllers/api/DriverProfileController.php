<?php

namespace App\Http\Controllers\api;

use App\Delivery;
use App\Driver;
use App\DriverHistory;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

if (! defined('BASE_URL_PROFILE')) {
    define('BASE_URL_PROFILE', URL::to('/') . '/images/driver_images/');
}

class DriverProfileController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:driver_api');
    }

    public function profile($driverId)
    {
        $driver = $this->approvedDriver();

        if (! $driver || (int) $driver->id !== (int) $driverId) {
            return response()->json([
                'status' => false,
                'message' => 'Accès non autorisé.',
            ], 403);
        }

        $data = Driver::select('id', 'name', 'email', 'image', 'phone', 'created_at')
            ->find($driver->id);

        $data->image_url = ! empty($data->image)
            ? (filter_var($data->image, FILTER_VALIDATE_URL)
                ? $data->image
                : URL::to('/') . '/images/driver_images/' . $data->image)
            : null;

        return response()->json([
            'status' => true,
            'data' => $data,
            'years' => now()->diffInYears($data->created_at),
            'BASE_URL_PROFILE' => BASE_URL_PROFILE,
        ]);
    }

    public function updateProfile(Request $request)
    {
        $driver = $this->approvedDriver();

        if (! $driver) {
            return response()->json([
                'status' => false,
                'message' => 'Accès non autorisé.',
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'nullable|string|max:255',
            'email' => [
                'nullable',
                'email',
                'max:255',
                Rule::unique('drivers', 'email')->ignore($driver->id),
            ],
            'paypal_account_no' => 'nullable|string|max:255',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:8192',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'error_code' => 101,
                'message' => implode(',', $validator->messages()->all()),
            ], 422);
        }

        $driver->fill(array_filter([
            'name' => $request->input('name'),
            'email' => $request->input('email'),
            'paypal_account_no' => $request->input('paypal_account_no'),
        ], static fn ($value) => $value !== null));

        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $filename = strtolower(
                pathinfo($image->getClientOriginalName(), PATHINFO_FILENAME)
                . '-' . bin2hex(random_bytes(6)) . '.' . $image->getClientOriginalExtension()
            );
            $image->move('images/driver_images', $filename);
            $driver->image = $filename;
        }

        $driver->save();

        return response()->json([
            'status' => true,
            'status_code' => 200,
            'data' => [
                'name' => $driver->name,
                'email' => $driver->email,
                'paypal_account_no' => $driver->paypal_account_no,
                'image' => $driver->image,
            ],
        ]);
    }

    public function setOnline(Request $request, $driverId)
    {
        $driver = $this->approvedDriver();

        if (! $driver || (int) $driver->id !== (int) $driverId) {
            return response()->json([
                'status' => false,
                'message' => 'Accès non autorisé.',
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'device_token' => 'nullable|string|max:512',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'error_code' => 101,
                'message' => implode(',', $validator->messages()->all()),
            ], 422);
        }

        $driver->status = 'online';
        if ($request->filled('latitude')) {
            $driver->latitude = $request->latitude;
        }
        if ($request->filled('longitude')) {
            $driver->longitude = $request->longitude;
        }
        if ($request->filled('device_token') && Schema::hasColumn('drivers', 'device_token')) {
            $driver->device_token = $request->device_token;
        }
        if (Schema::hasColumn('drivers', 'is_available')) {
            $driver->is_available = true;
        }
        $driver->save();

        return response()->json([
            'status' => true,
            'message' => 'Vous êtes maintenant en ligne.',
        ]);
    }

    public function setOnlineTime(Request $request)
    {
        $driver = $this->approvedDriver();

        if (! $driver) {
            return response()->json([
                'status' => false,
                'message' => 'Accès non autorisé.',
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'error_code' => 101,
                'message' => implode(',', $validator->messages()->all()),
            ], 422);
        }

        DriverHistory::create([
            'driver_id' => $driver->id,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
        ]);

        return response()->json(['status' => true]);
    }

    public function setOffline($driverId)
    {
        $driver = $this->approvedDriver();

        if (! $driver || (int) $driver->id !== (int) $driverId) {
            return response()->json([
                'status' => false,
                'message' => 'Accès non autorisé.',
            ], 403);
        }

        $hasActiveMission = Delivery::where('driver_id', $driver->id)
            ->whereIn('status', ['ASSIGNED', 'PICKED_UP', 'ON_THE_WAY'])
            ->exists();

        if ($hasActiveMission) {
            return response()->json([
                'status' => false,
                'message' => 'Impossible de passer hors ligne pendant une mission active.',
            ], 409);
        }

        $driver->status = 'offline';
        if (Schema::hasColumn('drivers', 'is_available')) {
            $driver->is_available = false;
        }
        $driver->save();

        return response()->json([
            'status' => true,
            'message' => 'Vous êtes maintenant hors ligne.',
        ]);
    }

    private function approvedDriver(): ?Driver
    {
        $driver = auth('driver_api')->user();

        return $driver instanceof Driver && (bool) $driver->approved
            ? $driver
            : null;
    }
}
