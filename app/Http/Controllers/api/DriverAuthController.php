<?php

namespace App\Http\Controllers\api;

use App\Driver;
use App\Http\Controllers\Controller;
use App\Mail\RegisterEmail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;

class DriverAuthController extends Controller
{
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name'             => 'required',
            'email'            => 'required|email|max:255|unique:users',
            'password'         => 'required',
            'phone'            => 'required|unique:users',
            'address'          => 'nullable',
            'image'            => 'nullable|image|mimes:jpeg,png,jpg,webp|max:8192',
            'account_name'     => 'nullable',
            'account_address'  => 'required',
            'account_number'   => 'required',
            'bank_name'        => 'required',
            'branch_name'      => 'required',
            'branch_address'   => 'required',
            'paypal_account_no'=> 'required',
            'licence_image'    => 'required|image|mimes:jpeg,png,jpg,webp|max:8192',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'     => false,
                'error_code' => 101,
                'message'    => implode(',', $validator->messages()->all()),
            ], 422);
        }

        $request['password'] = bcrypt($request->password);
        $request['approved'] = 0;

        \Illuminate\Support\Facades\DB::beginTransaction();
        try {
            $driver = Driver::create($request->all());

            foreach (['image' => 'image', 'licence_image' => 'licence_image'] as $field => $column) {
                if ($request->hasFile($field)) {
                    $file     = $request->file($field);
                    $filename = strtolower(
                        pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME)
                        . '-' . uniqid() . '.' . $file->getClientOriginalExtension()
                    );
                    $file->move('images/driver_images', $filename);
                    $driver->{$column} = $filename;
                }
            }
            $driver->save();

            Mail::to($request->email)->send(new RegisterEmail(['name' => $driver->name, 'email' => $driver->email]));
            $token = 'Bearer ' . $driver->createToken('MyApp')->accessToken;

            \Illuminate\Support\Facades\DB::commit();
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 403);
        }

        return response()->json(['status' => true, 'driver_id' => $driver->id, 'status_code' => 200, 'data' => $token]);
    }

    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'phone'    => 'required',
            'password' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'     => false,
                'error_code' => 101,
                'message'    => implode(',', $validator->messages()->all()),
            ], 422);
        }

        $driver = Driver::where('phone', $request->phone)->first();

        if (!$driver) {
            return response()->json(['status' => false, 'message' => 'Phone Dose Not Exist!'], 403);
        }

        if ($driver->approved == 0) {
            return response()->json(['status' => false, 'message' => 'You are not approved by admin!'], 403);
        }

        if (!Hash::check($request->password, $driver->password)) {
            return response()->json(['status' => false, 'message' => 'Incorrect Password'], 403);
        }

        $token = 'Bearer ' . $driver->createToken('MyApp')->accessToken;

        return response()->json([
            'user_id'                  => $driver->id,
            'name'                     => $driver->name,
            'email'                    => $driver->email,
            'image'                    => $driver->image,
            'password_change_required' => (bool) ($driver->password_must_change ?? false),
            'status'                   => true,
            'status_code'              => 200,
            'message'                  => 'Connexion réussie',
            'data'                     => $token,
        ]);
    }

    public function forgotPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'phone'    => 'required',
            'password' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'     => false,
                'error_code' => 101,
                'message'    => implode(',', $validator->messages()->all()),
            ], 422);
        }

        $driver = Driver::where('phone', $request->phone)->first();

        if (!$driver) {
            return response()->json(['status' => false, 'message' => 'Livreur introuvable'], 404);
        }

        $driver->password           = bcrypt($request->password);
        $driver->password_must_change = false;
        $driver->password_changed_at  = now();
        $driver->save();

        return response()->json(['status' => true, 'message' => 'Mis à jour avec succès !']);
    }

    public function changePassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'phone'            => 'required',
            'current_password' => 'required',
            'password'         => 'required|min:8|different:current_password',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'     => false,
                'error_code' => 101,
                'message'    => implode(',', $validator->messages()->all()),
            ], 422);
        }

        $driver = Driver::where('phone', $request->phone)->first();

        if (!$driver) {
            return response()->json(['status' => false, 'message' => 'Livreur introuvable'], 404);
        }

        if (!Hash::check($request->current_password, $driver->password)) {
            return response()->json(['status' => false, 'message' => 'Mot de passe actuel incorrect'], 403);
        }

        $driver->password             = bcrypt($request->password);
        $driver->password_must_change = false;
        $driver->password_changed_at  = now();
        $driver->save();

        return response()->json([
            'status'                   => true,
            'message'                  => 'Mot de passe mis à jour avec succès',
            'password_change_required' => false,
        ]);
    }
}
