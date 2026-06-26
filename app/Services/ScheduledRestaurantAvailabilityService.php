<?php

namespace App\Services;

use App\Exceptions\RestaurantClosedException;
use App\Restaurant;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class ScheduledRestaurantAvailabilityService
{
    public function guard(Restaurant $restaurant, Carbon $scheduledAt): void
    {
        $restaurant->loadMissing(['working_hours', 'special_closures']);

        $closure = $restaurant->special_closures->first(function ($item) use ($scheduledAt) {
            $start = Carbon::parse($item->starts_on)->startOfDay();
            $end = Carbon::parse($item->ends_on ?? $item->starts_on)->endOfDay();
            return $scheduledAt->betweenIncluded($start, $end);
        });

        if ($closure) {
            throw new RestaurantClosedException(
                'Le restaurant est fermé à la date programmée : ' . ($closure->label ?? 'fermeture exceptionnelle') . '.',
                Carbon::parse($closure->ends_on ?? $closure->starts_on)->addDay()->format('d/m/Y'),
                $restaurant->id
            );
        }

        /** @var Collection $hours */
        $hours = $restaurant->working_hours;
        if ($hours->isEmpty()) {
            // Compatibilité avec les restaurants historiques configurés sans grille horaire.
            return;
        }

        $todaySchedule = $this->scheduleForDate($hours, $scheduledAt);
        if ($todaySchedule && $this->contains($todaySchedule, $scheduledAt, $scheduledAt)) {
            return;
        }

        // Une plage du jour précédent peut se prolonger après minuit.
        $previousDate = $scheduledAt->copy()->subDay();
        $previousSchedule = $this->scheduleForDate($hours, $previousDate);
        if ($previousSchedule && $this->contains($previousSchedule, $scheduledAt, $previousDate)) {
            return;
        }

        throw new RestaurantClosedException(
            'Le restaurant est fermé à la date et à l’heure programmées.',
            null,
            $restaurant->id
        );
    }

    protected function scheduleForDate(Collection $hours, Carbon $date): mixed
    {
        $english = strtolower($date->format('l'));
        $french = [
            'monday' => 'lundi',
            'tuesday' => 'mardi',
            'wednesday' => 'mercredi',
            'thursday' => 'jeudi',
            'friday' => 'vendredi',
            'saturday' => 'samedi',
            'sunday' => 'dimanche',
        ][$english] ?? $english;

        return $hours->first(function ($hour) use ($english, $french) {
            $day = Str::lower(Str::ascii((string) $hour->Day));
            return str_contains($day, Str::ascii($english))
                || str_contains($day, Str::ascii($french));
        });
    }

    protected function contains(mixed $schedule, Carbon $target, Carbon $scheduleDate): bool
    {
        try {
            $openingTime = Carbon::parse($schedule->opening_time);
            $closingTime = Carbon::parse($schedule->closing_time);
            $opening = $scheduleDate->copy()->setTime($openingTime->hour, $openingTime->minute, 0);
            $closing = $scheduleDate->copy()->setTime($closingTime->hour, $closingTime->minute, 0);

            if ($closing->lessThanOrEqualTo($opening)) {
                $closing->addDay();
            }

            return $target->betweenIncluded($opening, $closing);
        } catch (\Throwable) {
            return false;
        }
    }
}
