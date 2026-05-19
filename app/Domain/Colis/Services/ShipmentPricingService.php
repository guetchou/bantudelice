<?php

namespace App\Domain\Colis\Services;

class ShipmentPricingService
{
    /**
     * Calculer le prix d'un envoi
     * 
     * @param array $params [pickup, dropoff, weight_kg, service_level, cod_amount, declared_value]
     * @return array [price_breakdown, total_price]
     */
    public function calculate(array $params): array
    {
        $weight = $params['weight_kg'] ?? 0;
        $serviceLevel = $params['service_level'] ?? 'standard';
        $codAmount = $params['cod_amount'] ?? 0;
        $declaredValue = $params['declared_value'] ?? 0;

        $breakdown = [];
        $total = 0;

        // 1. Tarif de base selon le poids
        $basePrice = $this->getBasePrice($weight);
        $breakdown['base_price'] = $basePrice;
        $total += $basePrice;

        // 2. Surcharge Service Level (Express)
        if ($serviceLevel === 'express') {
            $expressSurcharge = $basePrice * 0.5; // +50%
            $breakdown['express_surcharge'] = $expressSurcharge;
            $total += $expressSurcharge;
        }

        // 3. Frais COD (Contre-remboursement)
        if ($codAmount > 0) {
            $codFee = 500 + ($codAmount * 0.02); // 500 XAF fixe + 2%
            $breakdown['cod_fee'] = $codFee;
            $total += $codFee;
        }

        // 4. Assurance (si valeur déclarée)
        if ($declaredValue > 0) {
            $insuranceFee = $declaredValue * 0.01; // 1% de la valeur
            $breakdown['insurance_fee'] = $insuranceFee;
            $total += $insuranceFee;
        }

        // 5. Surcharge Zone (Exemple: Brazzaville à Pointe-Noire)
        // Pour le MVP, on simule une surcharge de distance si renseignée
        if (isset($params['distance_km']) && $params['distance_km'] > 20) {
            $distanceSurcharge = ($params['distance_km'] - 20) * 100; // 100 XAF par km au-delà de 20km
            $breakdown['distance_surcharge'] = $distanceSurcharge;
            $total += $distanceSurcharge;
        }

        return [
            'price_breakdown' => $breakdown,
            'total_price' => $total,
        ];
    }

    /**
     * Grille tarifaire de base Congo-friendly
     */
    protected function getBasePrice(float $weight): float
    {
        if ($weight <= 1) return 1500;
        if ($weight <= 5) return 3000;
        if ($weight <= 10) return 5000;
        if ($weight <= 20) return 8000;
        
        // Au-delà de 20kg : 8000 + 300 par kg sup
        return 8000 + (($weight - 20) * 300);
    }
}

