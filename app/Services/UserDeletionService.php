<?php

namespace App\Services;

use App\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class UserDeletionService
{
    /**
     * Anonymiser un utilisateur (préserve l'intégrité référentielle des commandes).
     * - Supprime les données sensibles (email/téléphone/avatar/social tokens)
     * - Vide les paniers et données de session persistées si existantes
     */
    public static function anonymizeUser(User $user, array $context = []): void
    {
        DB::beginTransaction();
        try {
            $userId = (int) $user->id;

            // Supprimer image locale si présente
            try {
                if (!empty($user->image)) {
                    $path = public_path('images/profile_images/' . $user->image);
                    if (File::exists($path)) {
                        File::delete($path);
                    }
                }
            } catch (\Throwable $e) {
                Log::warning('UserDeletionService: suppression image échouée', [
                    'user_id' => $userId,
                    'error' => $e->getMessage(),
                ]);
            }

            // Nettoyage des données liées (sans casser les FK)
            self::safeDeleteByUserId('carts', $userId);
            self::safeDeleteByUserId('user_tokens', $userId);
            self::safeDeleteByUserId('user_address', $userId);
            self::safeDeleteByUserId('addresses', $userId); // compatibilité éventuelle
            self::safeDeleteByUserId('loyalty_transactions', $userId);
            self::safeDeleteByUserId('loyalty_points', $userId);

            // Anonymisation du compte
            $rand = Str::lower(Str::random(12));
            $user->name = 'Utilisateur supprimé';
            $user->email = "deleted-{$userId}-{$rand}@bantudelice.cg";
            $user->phone = null;
            $user->image = null;
            $user->social_provider = null;
            $user->social_id = null;
            $user->social_avatar = null;
            $user->api_token = null;
            $user->remember_token = null;
            $user->email_verified_at = null;
            $user->password = bcrypt(Str::random(32));
            $user->save();

            DB::commit();

            Log::info('UserDeletionService: utilisateur anonymisé', [
                'user_id' => $userId,
                'context' => $context,
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('UserDeletionService: échec anonymisation', [
                'user_id' => $user->id ?? null,
                'context' => $context,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Anonymise un utilisateur identifié par son Facebook user_id stocké (social_id).
     * Retourne true si un utilisateur a été trouvé et traité, false sinon.
     */
    public static function anonymizeByFacebookUserId(string $facebookUserId, array $context = []): bool
    {
        $user = User::where('social_provider', 'facebook')
            ->where('social_id', $facebookUserId)
            ->first();

        if (!$user) {
            Log::warning('UserDeletionService: utilisateur Facebook introuvable', [
                'facebook_user_id' => $facebookUserId,
                'context' => $context,
            ]);
            return false;
        }

        self::anonymizeUser($user, array_merge($context, [
            'provider' => 'facebook',
            'facebook_user_id' => $facebookUserId,
        ]));

        return true;
    }

    private static function safeDeleteByUserId(string $table, int $userId): void
    {
        try {
            if (Schema::hasTable($table)) {
                DB::table($table)->where('user_id', $userId)->delete();
            }
        } catch (\Throwable $e) {
            Log::warning('UserDeletionService: suppression table échouée', [
                'table' => $table,
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);
        }
    }
}


