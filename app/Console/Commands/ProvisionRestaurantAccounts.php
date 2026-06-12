<?php

namespace App\Console\Commands;

use App\Restaurant;
use App\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class ProvisionRestaurantAccounts extends Command
{
    protected $signature = 'restaurants:provision-accounts
        {--password=Restaurant2026! : Mot de passe par défaut appliqué aux comptes restaurant}
        {--domain=bantudelice.cg : Domaine utilisé pour les emails générés si nécessaire}';

    protected $description = 'Crée ou met à jour un compte utilisateur pour chaque restaurant avec un mot de passe par défaut.';

    public function handle(): int
    {
        $password = (string) $this->option('password');
        $domain = trim((string) $this->option('domain'));

        $restaurants = Restaurant::query()->orderBy('id')->get();

        if ($restaurants->isEmpty()) {
            $this->warn('Aucun restaurant trouvé.');

            return self::SUCCESS;
        }

        $rows = [];

        foreach ($restaurants as $restaurant) {
            $user = $restaurant->user_id ? User::query()->find($restaurant->user_id) : null;

            if (! $user) {
                $email = $this->resolveRestaurantEmail($restaurant, $domain);
                $user = User::query()->firstOrNew(['email' => $email]);
            }

            $user->name = $restaurant->name;
            $user->email = $user->email ?: $this->resolveRestaurantEmail($restaurant, $domain);
            $user->phone = $restaurant->phone;
            $user->type = 'restaurant';
            $user->password = Hash::make($password);
            $user->email_verified_at = now();
            $user->save();

            if ((int) $restaurant->user_id !== (int) $user->id) {
                $restaurant->user_id = $user->id;
                $restaurant->save();
            }

            $rows[] = [
                $restaurant->id,
                Str::limit($restaurant->name, 30),
                $user->email,
                $password,
            ];
        }

        $this->table(['ID', 'Restaurant', 'Email de connexion', 'Mot de passe'], $rows);
        $this->info('Comptes restaurant provisionnés avec succès.');

        return self::SUCCESS;
    }

    protected function resolveRestaurantEmail(Restaurant $restaurant, string $domain): string
    {
        $rawEmail = trim((string) $restaurant->email);

        if (filter_var($rawEmail, FILTER_VALIDATE_EMAIL)) {
            return strtolower($rawEmail);
        }

        $slug = Str::slug($restaurant->name ?: 'restaurant');

        if ($slug === '') {
            $slug = 'restaurant-' . $restaurant->id;
        }

        return strtolower($slug . '.' . $restaurant->id . '@' . ltrim($domain, '@'));
    }
}
