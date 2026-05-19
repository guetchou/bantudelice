<?php

namespace App\Console\Commands;

use App\Delivery;
use App\Domain\Transport\Models\TransportBooking;
use App\Driver;
use App\Restaurant;
use App\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class ProvisionDemoAccounts extends Command
{
    protected $signature = 'demo:provision-accounts {--password=BantuDemo2026! : Mot de passe unique appliqué aux comptes démo}';

    protected $description = 'Crée ou met à jour des comptes démo génériques pour admin, client, restaurant, livreur et taximan.';

    public function handle(): int
    {
        $password = (string) $this->option('password');

        $restaurant = Restaurant::query()
            ->where('approved', 1)
            ->orderBy('id')
            ->first();

        if (! $restaurant) {
            $this->error('Aucun restaurant approuvé disponible pour créer le compte démo restaurant.');

            return self::FAILURE;
        }

        $deliveryDriver = Driver::query()
            ->where('approved', 1)
            ->whereNull('active_transport_vehicle_id')
            ->whereIn('id', Delivery::query()->select('driver_id')->distinct())
            ->orderBy('id')
            ->first();

        if (! $deliveryDriver) {
            $deliveryDriver = Driver::query()
                ->where('approved', 1)
                ->orderBy('id')
                ->first();
        }

        $taxiDriver = Driver::query()
            ->where('approved', 1)
            ->where(function ($query) {
                $query->whereNotNull('active_transport_vehicle_id')
                    ->orWhereIn('id', TransportBooking::query()->select('driver_id')->whereNotNull('driver_id')->distinct());
            })
            ->orderByDesc('active_transport_vehicle_id')
            ->orderBy('id')
            ->first();

        if (! $deliveryDriver || ! $taxiDriver) {
            $this->error('Aucun livreur ou taximan exploitable trouvé pour provisionner les comptes démo.');

            return self::FAILURE;
        }

        $admin = $this->upsertStandaloneUser(
            'demo.admin@bantudelice.cg',
            'Démo Super Admin',
            '+2420600000099',
            'admin',
            $password
        );

        $client = $this->upsertStandaloneUser(
            'demo.client@bantudelice.cg',
            'Démo Client',
            '+2420650000099',
            'user',
            $password
        );

        $restaurantUser = $this->upsertRestaurantDemoUser($restaurant, $password);
        $deliveryUser = $this->upsertDriverDemoUser(
            'demo.livreur@bantudelice.cg',
            $deliveryDriver,
            $password
        );
        $taxiUser = $this->upsertDriverDemoUser(
            'demo.taxi@bantudelice.cg',
            $taxiDriver,
            $password
        );

        $this->table(
            ['Profil', 'Email', 'Mot de passe', 'Profil métier relié'],
            [
                ['Admin', $admin->email, $password, 'Administration centrale'],
                ['Client', $client->email, $password, 'Client standard'],
                ['Restaurant', $restaurantUser->email, $password, $restaurant->name],
                ['Livreur', $deliveryUser->email, $password, $deliveryDriver->name],
                ['Taximan', $taxiUser->email, $password, $taxiDriver->name],
            ]
        );

        $this->info('Comptes démo provisionnés avec succès.');

        return self::SUCCESS;
    }

    protected function upsertStandaloneUser(string $email, string $name, string $phone, string $type, string $password): User
    {
        $user = User::query()->firstOrNew(['email' => $email]);
        $user->name = $name;
        $user->phone = $phone;
        $user->type = $type;
        $user->password = Hash::make($password);
        $user->email_verified_at = now();
        $user->save();

        return $user;
    }

    protected function upsertRestaurantDemoUser(Restaurant $restaurant, string $password): User
    {
        $user = $restaurant->user_id ? User::query()->find($restaurant->user_id) : null;

        if (! $user) {
            $user = User::query()->firstOrNew(['email' => 'demo.restaurant@bantudelice.cg']);
        }

        $user->name = $restaurant->name;
        $user->email = 'demo.restaurant@bantudelice.cg';
        $user->phone = $restaurant->phone;
        $user->type = 'restaurant';
        $user->password = Hash::make($password);
        $user->email_verified_at = now();
        $user->save();

        if ((int) $restaurant->user_id !== (int) $user->id) {
            $restaurant->user_id = $user->id;
            $restaurant->save();
        }

        return $user;
    }

    protected function upsertDriverDemoUser(string $email, Driver $driver, string $password): User
    {
        $user = User::query()->firstOrNew(['email' => $email]);
        $user->name = $driver->name;
        $user->email = $email;
        $user->phone = $driver->phone;
        $user->type = 'driver';
        $user->password = Hash::make($password);
        $user->email_verified_at = now();
        $user->save();

        return $user;
    }
}
