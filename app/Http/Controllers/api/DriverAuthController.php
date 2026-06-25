<?php

namespace App\Http\Controllers\api;

use App\Driver;
use App\Http\Controllers\Controller;
use App\Mail\RegisterEmail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;

class DriverAuthController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:driver_api')->only('changePassword');
    }

    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name'              => 'required|string|max:255',
            'email'             => 'required|email|max:255|unique:drivers,email',
            'password'          => 'required|string|min:8',
            'phone'             => 'required|string|max:30|unique:drivers,phone',
            'address'           => 'nullable|string|max:1000',
            'image'             => 'nullable|image|mimes:jpeg,png,jpg,webp|max:8192',
            'account_name'      => 'nullable|string|max:255',
            'account_address'   => 'required|string|max:1000',
            'account_number'    => 'required|string|max:100',
            'bank_name'         => 'required|string|max:255',
            'branch_name'       => 'required|string|max:255',
            'branch_address'    => 'required|string|max:1000',
            'paypal_account_no' => 'required|string|max:255',
            'licence_image'     => 'required|image|mimes:jpeg,png,jpg,webp|max:8192',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'     => false,
                'error_code' => 101,
                'message'    => implode(',', $validator->messages()->all()),
            ], 422);
        }

        DB::beginTransaction();

        try {
            $payload = $validator->validated();
            $payload['password'] = Hash::make($payload['password']);
            $payload['approved'] = false;

            $driver = Driver::create($payload);

            foreach (['image' => 'image', 'licence_image' => 'licence_image'] as $field => $column) {
                if (! $request->hasFile($field)) {
                    continue;
                }

                $file = $request->file($field);
                $filename = strtolower(
                    pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME)
                    . '-' . bin2hex(random_bytes(6)) . '.' . $file->getClientOriginalExtension()
                );
                $file->move('images/driver_images', $filename);
                $driver->{$column} = $filename;
            }

            $driver->save();
            Mail::to($driver->email)->send(new RegisterEmail([
                'name' => $driver->name,
                'email' => $driver->email,
            ]));

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();

            report($e);

            return response()->json([
                'status' => false,
                'message' => 'Impossible de créer le compte livreur.',
            ], 500);
        }

        return response()->json([
            'status' => true,
            'driver_id' => $driver->id,
            'status_code' => 201,
            'approved' => false,
            'message' => 'Inscription enregistrée. Le compte doit être validé avant toute connexion.',
            'data' => null,
        ], 201);
    }

    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'phone'    => 'required|string',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'     => false,
                'error_code' => 101,
                'message'    => implode(',', $validator->messages()->all()),
            ], 422);
        }

        $driver = Driver::where('phone', $request->phone)->first();

        if (! $driver || ! Hash::check($request->password, $driver->password)) {
            return response()->json([
                'status' => false,
                'message' => 'Identifiants incorrects.',
            ], 403);
        }

        if (! (bool) $driver->approved) {
            return response()->json([
                'status' => false,
                'message' => 'Ce compte livreur n’est pas encore approuvé.',
            ], 403);
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

    /**
     * Étape 1 : phone uniquement, envoi d'un code à l'adresse e-mail enregistrée.
     * Étape 2 : phone + code + password, validation puis changement du mot de passe.
     */
    public function forgotPassword(Request $request)
    {
        $rules = [
            'phone' => 'required|string|max:30',
            'code' => 'nullable|digits:6',
        ];

        if ($request->filled('code')) {
            $rules['password'] = 'required|string|min:8';
        }

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'error_code' => 101,
                'message' => implode(',', $validator->messages()->all()),
            ], 422);
        }

        if (! Schema::hasTable('driver_password_resets')) {
            return response()->json([
                'status' => false,
                'message' => 'Le service de réinitialisation est temporairement indisponible.',
            ], 503);
        }

        $driver = Driver::where('phone', $request->phone)->first();

        if (! $request->filled('code')) {
            if (! $driver || empty($driver->email)) {
                return response()->json([
                    'status' => true,
                    'message' => 'Si ce compte existe, un code de vérification a été envoyé.',
                ]);
            }

            $code = (string) random_int(100000, 999999);

            DB::table('driver_password_resets')
                ->where('driver_id', $driver->id)
                ->whereNull('used_at')
                ->update(['used_at' => now(), 'updated_at' => now()]);

            $resetId = DB::table('driver_password_resets')->insertGetId([
                'driver_id' => $driver->id,
                'code_hash' => Hash::make($code),
                'attempts' => 0,
                'expires_at' => now()->addMinutes(10),
                'used_at' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            try {
                Mail::raw(
                    "Votre code de réinitialisation BantuDelice est {$code}. Il expire dans 10 minutes.",
                    function ($message) use ($driver) {
                        $message->to($driver->email, $driver->name)
                            ->subject('Code de réinitialisation livreur BantuDelice');
                    }
                );
            } catch (\Throwable $e) {
                DB::table('driver_password_resets')->where('id', $resetId)->delete();
                report($e);

                return response()->json([
                    'status' => false,
                    'message' => 'Le code n’a pas pu être envoyé. Réessayez plus tard.',
                ], 503);
            }

            return response()->json([
                'status' => true,
                'message' => 'Si ce compte existe, un code de vérification a été envoyé.',
            ]);
        }

        if (! $driver) {
            return response()->json([
                'status' => false,
                'message' => 'Code invalide ou expiré.',
            ], 422);
        }

        $changed = DB::transaction(function () use ($driver, $request): bool {
            $reset = DB::table('driver_password_resets')
                ->where('driver_id', $driver->id)
                ->whereNull('used_at')
                ->where('expires_at', '>', now())
                ->orderByDesc('id')
                ->lockForUpdate()
                ->first();

            if (! $reset || (int) $reset->attempts >= 5) {
                return false;
            }

            if (! Hash::check((string) $request->code, $reset->code_hash)) {
                $attempts = (int) $reset->attempts + 1;
                DB::table('driver_password_resets')->where('id', $reset->id)->update([
                    'attempts' => $attempts,
                    'used_at' => $attempts >= 5 ? now() : null,
                    'updated_at' => now(),
                ]);

                return false;
            }

            $driver->forceFill([
                'password' => Hash::make($request->password),
                'password_must_change' => false,
                'password_changed_at' => now(),
            ])->save();

            DB::table('driver_password_resets')->where('id', $reset->id)->update([
                'used_at' => now(),
                'updated_at' => now(),
            ]);

            $this->revokeDriverTokens($driver);

            return true;
        }, 3);

        if (! $changed) {
            return response()->json([
                'status' => false,
                'message' => 'Code invalide ou expiré.',
            ], 422);
        }

        return response()->json([
            'status' => true,
            'message' => 'Mot de passe mis à jour. Reconnectez-vous.',
        ]);
    }

    public function changePassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'current_password' => 'required|string',
            'password' => 'required|string|min:8|different:current_password',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'error_code' => 101,
                'message' => implode(',', $validator->messages()->all()),
            ], 422);
        }

        /** @var Driver|null $driver */
        $driver = auth('driver_api')->user();

        if (! $driver || ! (bool) $driver->approved) {
            return response()->json([
                'status' => false,
                'message' => 'Accès non autorisé.',
            ], 403);
        }

        if (! Hash::check($request->current_password, $driver->password)) {
            return response()->json([
                'status' => false,
                'message' => 'Mot de passe actuel incorrect.',
            ], 403);
        }

        $driver->forceFill([
            'password' => Hash::make($request->password),
            'password_must_change' => false,
            'password_changed_at' => now(),
        ])->save();

        $this->revokeDriverTokens($driver);

        return response()->json([
            'status' => true,
            'message' => 'Mot de passe mis à jour. Reconnectez-vous.',
            'password_change_required' => false,
        ]);
    }

    private function revokeDriverTokens(Driver $driver): void
    {
        if (! Schema::hasTable('oauth_access_tokens') || ! Schema::hasTable('oauth_clients')) {
            return;
        }

        $driverClientIds = DB::table('oauth_clients')
            ->where('provider', 'drivers')
            ->pluck('id');

        if ($driverClientIds->isEmpty()) {
            return;
        }

        DB::table('oauth_access_tokens')
            ->where('user_id', (string) $driver->id)
            ->whereIn('client_id', $driverClientIds)
            ->update(['revoked' => true]);
    }
}
