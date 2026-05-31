<?php

namespace App\Http\Controllers\api;

use App\Driver;
use App\DriverHistory;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Validator;

if (!defined('BASE_URL_PROFILE')) {
    define('BASE_URL_PROFILE', URL::to('/') . '/images/driver_images/');
}

class DriverProfileController extends Controller
{
    public function profile($user)
    {
        $driver = Driver::select('id', 'name', 'email', 'image', 'phone', 'created_at')
            ->where('id', $user)
            ->first();

        if (!$driver) {
            return response()->json([
                'status'           => false,
                'message'          => 'Livreur introuvable',
                'data'             => null,
                'years'            => 0,
                'BASE_URL_PROFILE' => BASE_URL_PROFILE,
            ], 404);
        }

        $driver->image_url = !empty($driver->image)
            ? (filter_var($driver->image, FILTER_VALIDATE_URL)
                ? $driver->image
                : URL::to('/') . '/images/profile_images/' . $driver->image)
            : null;

        return response()->json([
            'status'           => true,
            'data'             => $driver,
            'years'            => now()->diffInYears($driver->created_at),
            'BASE_URL_PROFILE' => BASE_URL_PROFILE,
        ]);
    }

    public function updateProfile(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'driver_id'         => 'required',
            'name'              => 'nullable',
            'paypal_account_no' => 'required',
            'image'             => 'nullable|image|mimes:jpeg,png,jpg,webp|max:8192',
        ]);

        $driver = Driver::find($request->driver_id);

        if ($validator->fails()) {
            return response()->json([
                'status'     => false,
                'error_code' => 101,
                'message'    => implode(',', $validator->messages()->all()),
            ], 422);
        }

        if (!$driver) {
            return response()->json(['status' => false, 'message' => 'Livreur introuvable'], 404);
        }

        $driver->name  = $request->name;
        $driver->email = $request->email;
        $driver->save();

        if ($request->hasFile('image')) {
            $image    = $request->file('image');
            $filename = strtolower(
                pathinfo($image->getClientOriginalName(), PATHINFO_FILENAME)
                . '-' . uniqid() . '.' . $image->getClientOriginalExtension()
            );
            $image->move('images/driver_images', $filename);
            $driver->image = $filename;
            $driver->save();
        }

        return response()->json([
            'status'      => true,
            'status_code' => 200,
            'data'        => [
                'name'              => $driver->name,
                'email'             => $driver->email,
                'paypal_account_no' => $driver->paypal_account_no,
            ],
        ]);
    }

    public function setOnline(Request $request, $driver)
    {
        // IDOR guard: if driver_api token present, it must belong to this driver
        $tokenDriver = auth('driver_api')->user();
        if ($tokenDriver && (int)$tokenDriver->id !== (int)$driver) {
            return response()->json(['status' => false, 'message' => 'Accès non autorisé'], 403);
        }

        $validator = Validator::make($request->all(), [
            'latitude'     => 'nullable|numeric|between:-90,90',
            'longitude'    => 'nullable|numeric|between:-180,180',
            'device_token' => 'nullable|string|max:512',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'     => false,
                'error_code' => 101,
                'message'    => implode(',', $validator->messages()->all()),
            ], 422);
        }

        $driverModel = Driver::find($driver);

        if (!$driverModel) {
            return response()->json(['status' => false, 'message' => 'Livreur introuvable'], 404);
        }

        $driverModel->status = 'online';
        if ($request->filled('latitude'))  $driverModel->latitude  = $request->latitude;
        if ($request->filled('longitude')) $driverModel->longitude = $request->longitude;
        if ($request->filled('device_token') && \Illuminate\Support\Facades\Schema::hasColumn('drivers', 'device_token')) {
            $driverModel->device_token = $request->device_token;
        }
        $driverModel->save();

        return response()->json(['status' => true, 'message' => 'You are online now!']);
    }

    public function setOnlineTime(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'driver_id'  => 'required',
            'start_date' => 'required',
            'end_date'   => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'     => false,
                'error_code' => 101,
                'message'    => implode(',', $validator->messages()->all()),
            ], 422);
        }

        if (!Driver::where('id', $request->driver_id)->exists()) {
            return response()->json(['status' => false, 'message' => 'Livreur introuvable'], 404);
        }

        DriverHistory::create($request->all());

        return response()->json(['status' => true]);
    }

    public function setOffline($user)
    {
        // IDOR guard
        $tokenDriver = auth('driver_api')->user();
        if ($tokenDriver && (int)$tokenDriver->id !== (int)$user) {
            return response()->json(['status' => false, 'message' => 'Accès non autorisé'], 403);
        }

        $driver = Driver::where('id', $user)->first();

        if (!$driver) {
            return response()->json(['status' => false, 'message' => 'Livreur introuvable'], 404);
        }

        $driver->status = 'offline';
        $driver->save();

        return response()->json(['status' => true, 'message' => 'You are Offline Now!']);
    }
}
