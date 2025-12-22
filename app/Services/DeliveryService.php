<?php

namespace App\Services;

use App\Delivery;
use App\Driver;
use App\Order;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * Service pour gérer les livraisons
 */
class DeliveryService
{
    /**
     * Créer une livraison pour une commande
     * 
     * @param Order $order
     * @return Delivery
     */
    public function createForOrder(Order $order): Delivery
    {
        return Delivery::create([
            'order_id'      => $order->id,
            'restaurant_id' => $order->restaurant_id,
            'status'        => 'PENDING',
            'delivery_fee'  => (int)($order->delivery_charges ?? 0),
        ]);
    }
    
    /**
     * Assigner un livreur à une livraison
     * 
     * @param Delivery $delivery
     * @param Driver $driver
     * @return Delivery
     * @throws \Exception
     */
    public function assignDriver(Delivery $delivery, Driver $driver): Delivery
    {
        if ($delivery->status !== 'PENDING') {
            throw new \Exception('Cette livraison ne peut pas être assignée (statut: ' . $delivery->status . ')');
        }
        
        // Vérifier la disponibilité du livreur (champ status ou is_available)
        $isAvailable = $driver->status === 'online' || ($driver->is_available ?? true);
        if (!$isAvailable) {
            throw new \Exception('Ce livreur n\'est pas disponible');
        }
        
        return DB::transaction(function () use ($delivery, $driver) {
            $delivery->update([
                'driver_id'   => $driver->id,
                'status'      => 'ASSIGNED',
                'assigned_at' => now(),
            ]);
            
            // Marquer le livreur comme non disponible (si le champ existe)
            if (Schema::hasColumn('drivers', 'is_available')) {
                $driver->update(['is_available' => false]);
            } elseif (Schema::hasColumn('drivers', 'status')) {
                // Utiliser le champ status si is_available n'existe pas
                $driver->update(['status' => 'busy']);
            }
            
            // Mettre à jour aussi l'order pour cohérence
            $delivery->order->update(['driver_id' => $driver->id, 'status' => 'assign']);
            
            return $delivery->fresh();
        });
    }
    
    /**
     * Mettre à jour le statut d'une livraison
     * 
     * @param Delivery $delivery
     * @param string $status
     * @return Delivery
     * @throws \Exception
     */
    public function updateStatus(Delivery $delivery, string $status): Delivery
    {
        $allowedStatuses = ['ASSIGNED', 'PICKED_UP', 'ON_THE_WAY', 'DELIVERED', 'CANCELLED'];
        
        if (!in_array($status, $allowedStatuses)) {
            throw new \Exception('Statut invalide: ' . $status);
        }
        
        // Vérifier la transition de statut
        $currentStatus = $delivery->status;
        $validTransitions = [
            'PENDING' => ['ASSIGNED', 'CANCELLED'],
            'ASSIGNED' => ['PICKED_UP', 'CANCELLED'],
            'PICKED_UP' => ['ON_THE_WAY', 'CANCELLED'],
            'ON_THE_WAY' => ['DELIVERED', 'CANCELLED'],
            'DELIVERED' => [],
            'CANCELLED' => [],
        ];
        
        if (!in_array($status, $validTransitions[$currentStatus] ?? [])) {
            throw new \Exception('Transition de statut invalide: ' . $currentStatus . ' → ' . $status);
        }
        
        return DB::transaction(function () use ($delivery, $status) {
            $now = now();
            $data = ['status' => $status];
            
            // Mettre à jour les timestamps selon le statut
            if ($status === 'PICKED_UP') {
                $data['picked_up_at'] = $now;
            } elseif ($status === 'DELIVERED') {
                $data['delivered_at'] = $now;
                
                // Libérer le livreur
                if ($delivery->driver) {
                    if (Schema::hasColumn('drivers', 'is_available')) {
                        $delivery->driver->update(['is_available' => true]);
                    } elseif (Schema::hasColumn('drivers', 'status')) {
                        $delivery->driver->update(['status' => 'online']);
                    }
                }
                
                // Mettre à jour l'order
                $delivery->order->update(['status' => 'completed', 'delivered_time' => $now]);
            } elseif ($status === 'CANCELLED') {
                // Libérer le livreur si assigné
                if ($delivery->driver) {
                    if (Schema::hasColumn('drivers', 'is_available')) {
                        $delivery->driver->update(['is_available' => true]);
                    } elseif (Schema::hasColumn('drivers', 'status')) {
                        $delivery->driver->update(['status' => 'online']);
                    }
                }
                
                // Mettre à jour l'order
                $delivery->order->update(['status' => 'cancelled']);
            } elseif ($status === 'ON_THE_WAY') {
                // Mettre à jour l'order
                $delivery->order->update(['status' => 'assign']); // ou un nouveau statut 'on_way' si tu l'ajoutes
            }
            
            $delivery->update($data);
            
            return $delivery->fresh();
        });
    }
    
    /**
     * Récupérer les livraisons actives d'un livreur
     * 
     * @param Driver $driver
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getActiveDeliveriesForDriver(Driver $driver)
    {
        return Delivery::with(['order', 'restaurant', 'order.user'])
            ->where('driver_id', $driver->id)
            ->whereIn('status', ['ASSIGNED', 'PICKED_UP', 'ON_THE_WAY'])
            ->orderBy('created_at', 'desc')
            ->get();
    }
    
    /**
     * Récupérer les livraisons en attente d'assignation
     * 
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getPendingDeliveries()
    {
        return Delivery::with(['order', 'restaurant'])
            ->where('status', 'PENDING')
            ->orderBy('created_at', 'asc')
            ->get();
    }
}

