<?php

namespace App\Services;

use App\Restaurant;
use App\WorkingHour;
use Carbon\Carbon;

/**
 * Service pour gérer le statut ouvert/fermé des restaurants
 */
class RestaurantStatusService
{
    /**
     * Vérifier si un restaurant est ouvert maintenant
     * 
     * @param int|Restaurant $restaurant
     * @return array ['is_open' => bool, 'next_opening' => string|null, 'current_schedule' => array|null]
     */
    public static function getStatus($restaurant)
    {
        if (is_int($restaurant)) {
            $restaurant = Restaurant::with('working_hours')->find($restaurant);
        }
        
        if (!$restaurant) {
            return [
                'is_open' => false,
                'next_opening' => null,
                'current_schedule' => null,
                'message' => 'Restaurant introuvable'
            ];
        }
        
        $now = Carbon::now();
        $currentDay = strtolower($now->format('l')); // monday, tuesday, etc.
        
        // Mapper les jours en français si nécessaire
        $dayMapping = [
            'monday' => 'lundi',
            'tuesday' => 'mardi',
            'wednesday' => 'mercredi',
            'thursday' => 'jeudi',
            'friday' => 'vendredi',
            'saturday' => 'samedi',
            'sunday' => 'dimanche',
        ];
        
        $currentDayFr = $dayMapping[$currentDay] ?? $currentDay;
        
        // Chercher les horaires du jour actuel
        $todaySchedule = $restaurant->working_hours()
            ->where('Day', 'like', '%' . $currentDayFr . '%')
            ->orWhere('Day', 'like', '%' . ucfirst($currentDayFr) . '%')
            ->orWhere('Day', 'like', '%' . strtoupper($currentDayFr) . '%')
            ->first();
        
        // Si pas de correspondance exacte, essayer avec le nom anglais
        if (!$todaySchedule) {
            $todaySchedule = $restaurant->working_hours()
                ->where('Day', 'like', '%' . $currentDay . '%')
                ->orWhere('Day', 'like', '%' . ucfirst($currentDay) . '%')
                ->first();
        }
        
        // Si aucun horaire trouvé pour aujourd'hui, considérer comme fermé
        if (!$todaySchedule) {
            // Chercher le prochain jour d'ouverture
            $nextOpening = self::findNextOpening($restaurant, $now);
            
            return [
                'is_open' => false,
                'next_opening' => $nextOpening,
                'current_schedule' => null,
                'message' => 'Fermé aujourd\'hui'
            ];
        }
        
        // Parser les heures
        try {
            $openingTime = Carbon::parse($todaySchedule->opening_time);
            $closingTime = Carbon::parse($todaySchedule->closing_time);
            
            // Si l'heure de fermeture est avant l'heure d'ouverture, c'est que ça passe minuit
            if ($closingTime->lt($openingTime)) {
                // Le restaurant ferme le lendemain
                $closingTime->addDay();
            }
            
            // Créer les heures pour aujourd'hui
            $todayOpening = Carbon::today()->setTime($openingTime->hour, $openingTime->minute);
            $todayClosing = Carbon::today()->setTime($closingTime->hour, $closingTime->minute);
            
            if ($closingTime->lt($openingTime)) {
                $todayClosing->addDay();
            }
            
            $isOpen = $now->between($todayOpening, $todayClosing);
            
            $nextOpening = null;
            if (!$isOpen) {
                if ($now->lt($todayOpening)) {
                    // Le restaurant n'a pas encore ouvert aujourd'hui
                    $nextOpening = $todayOpening->format('H:i');
                } else {
                    // Le restaurant a déjà fermé, chercher le prochain jour
                    $nextOpening = self::findNextOpening($restaurant, $now->copy()->addDay()->startOfDay());
                }
            }
            
            return [
                'is_open' => $isOpen,
                'next_opening' => $nextOpening,
                'current_schedule' => [
                    'day' => $todaySchedule->Day,
                    'opening_time' => $todaySchedule->opening_time,
                    'closing_time' => $todaySchedule->closing_time,
                    'opening_formatted' => $todayOpening->format('H:i'),
                    'closing_formatted' => $todayClosing->format('H:i'),
                ],
                'message' => $isOpen ? 'Ouvert' : 'Fermé'
            ];
            
        } catch (\Exception $e) {
            \Log::error('RestaurantStatusService Error', [
                'restaurant_id' => $restaurant->id,
                'error' => $e->getMessage()
            ]);
            
            return [
                'is_open' => false,
                'next_opening' => null,
                'current_schedule' => null,
                'message' => 'Erreur de lecture des horaires'
            ];
        }
    }
    
    /**
     * Trouver le prochain horaire d'ouverture
     * 
     * @param Restaurant $restaurant
     * @param Carbon $fromDate
     * @return string|null Format "Jour à HH:mm"
     */
    protected static function findNextOpening($restaurant, $fromDate)
    {
        $workingHours = $restaurant->working_hours()->orderBy('Day')->get();
        
        if ($workingHours->isEmpty()) {
            return null;
        }
        
        // Chercher dans les 7 prochains jours
        for ($i = 0; $i < 7; $i++) {
            $checkDate = $fromDate->copy()->addDays($i);
            $dayName = strtolower($checkDate->format('l'));
            
            $dayMapping = [
                'monday' => 'lundi',
                'tuesday' => 'mardi',
                'wednesday' => 'mercredi',
                'thursday' => 'jeudi',
                'friday' => 'vendredi',
                'saturday' => 'samedi',
                'sunday' => 'dimanche',
            ];
            
            $dayFr = $dayMapping[$dayName] ?? $dayName;
            
            $schedule = $workingHours->first(function($wh) use ($dayFr, $dayName) {
                $day = strtolower($wh->Day);
                return strpos($day, $dayFr) !== false || strpos($day, $dayName) !== false;
            });
            
            if ($schedule) {
                try {
                    $openingTime = Carbon::parse($schedule->opening_time);
                    $openingDateTime = $checkDate->copy()->setTime($openingTime->hour, $openingTime->minute);
                    
                    if ($i === 0 && $openingDateTime->gt($fromDate)) {
                        // Aujourd'hui, mais pas encore ouvert
                        return $openingDateTime->format('H:i');
                    } elseif ($i > 0) {
                        // Un jour futur
                        $dayNames = ['lundi', 'mardi', 'mercredi', 'jeudi', 'vendredi', 'samedi', 'dimanche'];
                        return $dayNames[$checkDate->dayOfWeek] . ' à ' . $openingDateTime->format('H:i');
                    }
                } catch (\Exception $e) {
                    continue;
                }
            }
        }
        
        return null;
    }
    
    /**
     * Vérifier si un restaurant est ouvert (méthode simple)
     * 
     * @param int|Restaurant $restaurant
     * @return bool
     */
    public static function isOpen($restaurant)
    {
        $status = self::getStatus($restaurant);
        return $status['is_open'] ?? false;
    }
}

