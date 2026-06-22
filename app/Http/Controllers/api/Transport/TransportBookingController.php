<?php

namespace App\Http\Controllers\api\Transport;

use App\Driver;
use App\Http\Controllers\Controller;
use App\Domain\Transport\Models\TransportPricingRule;
use App\Domain\Transport\Models\TransportBooking;
use App\Domain\Transport\Services\TransportService;
use App\Domain\Transport\Enums\TransportStatus;
use App\Domain\Transport\Enums\TransportType;
use App\Domain\Transport\Events\TransportRequestBroadcasted;
use App\Services\PaymentExperienceService;
use App\Services\TransportGeoService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Arr;

class TransportBookingController extends Controller
{
    protected $transportService;

    public function __construct(
        TransportService $transportService,
        protected PaymentExperienceService $paymentExperienceService,
        protected TransportGeoService $transportGeoService
    )
    {
        $this->transportService = $transportService;
    }

    public function index()
    {
        $bookings = TransportBooking::where('user_id', auth()->id())
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($bookings);
    }

    public function estimate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'type' => 'required|in:taxi,carpool,rental,bus',
            'distance' => 'required|numeric',
            'duration' => 'required|numeric',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 400);
        }

        $price = $this->transportService->estimate($request->all());

        return response()->json([
            'estimated_price' => $price,
            'currency' => 'FCFA',
        ]);
    }

    public function geocode(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'q' => 'required|string|min:3',
            'limit' => 'sometimes|integer|min:1|max:8',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Recherche invalide',
                'errors' => $validator->errors(),
                'data' => [],
            ], 422);
        }

        return response()->json(
            $this->transportGeoService->geocodeSearch(
                $request->string('q')->toString(),
                (int) $request->input('limit', 5)
            )
        );
    }

    public function reverseGeocode(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'lat' => 'required|numeric',
            'lng' => 'required|numeric',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Coordonnees invalides',
                'errors' => $validator->errors(),
            ], 422);
        }

        return response()->json([
            'data' => $this->transportGeoService->reverse(
                (float) $request->input('lat'),
                (float) $request->input('lng')
            ),
        ]);
    }

    public function routeSummary(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'type' => 'sometimes|in:taxi,carpool,rental,bus',
            'pickup_lat' => 'required|numeric',
            'pickup_lng' => 'required|numeric',
            'dropoff_lat' => 'required|numeric',
            'dropoff_lng' => 'required|numeric',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Trajet invalide',
                'errors' => $validator->errors(),
            ], 422);
        }

        $route = $this->transportGeoService->route(
            (float) $request->input('pickup_lat'),
            (float) $request->input('pickup_lng'),
            (float) $request->input('dropoff_lat'),
            (float) $request->input('dropoff_lng')
        );
        $type = TransportType::from($request->input('type', 'taxi'));
        $pricingRule = $this->activePricingRule($type);
        $withinZone = $this->isTaxiRouteWithinZone($route['distance_km']);
        $tripWindow = $this->tripWindowMinutes($route['duration_minutes']);

        $estimatedPrice = $this->transportService->estimate([
            'type' => $type->value,
            'distance' => $route['distance_km'],
            'duration' => $route['duration_minutes'],
        ]);

        $availableDrivers = $this->countAvailableDriversNear(
            (float) $request->input('pickup_lat'),
            (float) $request->input('pickup_lng')
        );
        $bestDriver = $this->findBestAvailableDriverNear(
            (float) $request->input('pickup_lat'),
            (float) $request->input('pickup_lng')
        );

        return response()->json([
            'data' => $route + [
                'estimated_price' => round($estimatedPrice),
                'currency' => 'FCFA',
                'available_drivers_count' => $availableDrivers,
                'serviceable' => $availableDrivers > 0 && $withinZone,
                'operating_zone' => $pricingRule?->zone,
                'supply_state' => $this->supplyState($availableDrivers),
                'retry_after_minutes' => $availableDrivers > 0 ? 0 : 6,
                'within_zone' => $withinZone,
                'service_radius_km' => 35,
                'trip_window_minutes' => $tripWindow,
                'best_driver' => $this->driverCandidatePayload(
                    $bestDriver,
                    (float) $request->input('pickup_lat'),
                    (float) $request->input('pickup_lng')
                ),
            ],
        ]);
    }

    public function store(Request $request)
    {
        if (! auth()->check()) {
            return response()->json([
                'message' => 'Authentification requise pour reserver un taxi.',
            ], 401);
        }

        $validator = Validator::make($request->all(), [
            'type' => 'required|in:taxi,carpool,rental,bus',
            'pickup_address' => 'required|string',
            'pickup_lat' => 'required|numeric',
            'pickup_lng' => 'required|numeric',
            'pickup_precision_level' => 'sometimes|string|in:door,street,district,area,blind',
            'pickup_pin_confirmed' => 'sometimes|boolean',
            'pickup_accuracy_meters' => 'sometimes|numeric|min:0|max:5000',
            'dropoff_address' => 'sometimes|string',
            'dropoff_lat' => 'sometimes|numeric',
            'dropoff_lng' => 'sometimes|numeric',
            'dropoff_precision_level' => 'sometimes|string|in:door,street,district,area,blind',
            'dropoff_pin_confirmed' => 'sometimes|boolean',
            'dropoff_accuracy_meters' => 'sometimes|numeric|min:0|max:5000',
            'scheduled_at' => 'sometimes|date',
            'estimated_distance' => 'nullable|numeric',
            'estimated_duration' => 'nullable|numeric',
            'estimated_price' => 'nullable|numeric',
            'total_price' => 'nullable|numeric',
            'payment_method' => 'sometimes|string|in:cash,momo,airtel',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 400);
        }

        $data = $request->all();
        $data['user_id'] = auth()->id();
        $data['status'] = TransportStatus::REQUESTED;
        $type = TransportType::from($data['type']);
        $pricingRule = $this->activePricingRule($type);
        $availableDrivers = null;

        if ($type === TransportType::TAXI) {
            if (empty($data['dropoff_address']) || !isset($data['dropoff_lat'], $data['dropoff_lng'])) {
                return response()->json([
                    'message' => 'La destination complete est obligatoire pour une course taxi.',
                ], 422);
            }

            if ($this->requiresPrecisePinConfirmation(
                (string) ($data['pickup_precision_level'] ?? ''),
                (bool) ($data['pickup_pin_confirmed'] ?? false)
            )) {
                return response()->json([
                    'message' => 'Confirmez precisement votre point de depart sur la carte avant de reserver.',
                    'address_confirmation' => [
                        'pickup_requires_pin_confirmation' => true,
                        'dropoff_requires_pin_confirmation' => false,
                    ],
                ], 422);
            }

            if ($this->requiresPrecisePinConfirmation(
                (string) ($data['dropoff_precision_level'] ?? ''),
                (bool) ($data['dropoff_pin_confirmed'] ?? false)
            )) {
                return response()->json([
                    'message' => 'Confirmez precisement votre destination sur la carte avant de reserver.',
                    'address_confirmation' => [
                        'pickup_requires_pin_confirmation' => false,
                        'dropoff_requires_pin_confirmation' => true,
                    ],
                ], 422);
            }

            $availableDrivers = $this->countAvailableDriversNear(
                (float) $data['pickup_lat'],
                (float) $data['pickup_lng']
            );

            if ($availableDrivers < 1) {
                return response()->json([
                    'message' => 'Aucun chauffeur disponible autour du point de depart pour le moment.',
                    'available_drivers_count' => 0,
                    'serviceable' => false,
                    'operating_zone' => $pricingRule?->zone,
                    'supply_state' => 'unavailable',
                    'retry_after_minutes' => 6,
                    'best_driver' => null,
                ], 409);
            }

            $route = $this->transportGeoService->route(
                (float) $data['pickup_lat'],
                (float) $data['pickup_lng'],
                (float) $data['dropoff_lat'],
                (float) $data['dropoff_lng']
            );

            if (! $this->isTaxiRouteWithinZone($route['distance_km'])) {
                return response()->json([
                    'message' => 'Course hors zone Kende pour le moment.',
                    'within_zone' => false,
                    'operating_zone' => $pricingRule?->zone,
                    'service_radius_km' => 35,
                    'available_drivers_count' => $availableDrivers,
                    'serviceable' => false,
                    'supply_state' => $this->supplyState($availableDrivers),
                    'best_driver' => null,
                ], 409);
            }

            $data['estimated_distance'] = $route['distance_km'];
            $data['estimated_duration'] = $route['duration_minutes'];
        }

        $serverEstimatedPrice = $this->transportService->estimate([
            'type' => $data['type'],
            'distance' => $data['estimated_distance'] ?? 0,
            'duration' => $data['estimated_duration'] ?? 0,
        ]);
        $data['estimated_price'] = $data['estimated_price'] ?? $serverEstimatedPrice;
        $data['estimated_price'] = $serverEstimatedPrice;
        $data['total_price'] = $serverEstimatedPrice;

        $booking = $this->transportService->createBooking($data);
        $bestDriver = null;
        $assignmentStatus = 'pending_dispatch';
        $tripWindow = $type === TransportType::TAXI
            ? $this->tripWindowMinutes((int) ($data['estimated_duration'] ?? 0))
            : null;
        $offerWindowSeconds = $type === TransportType::TAXI ? 45 : null;

        if ($type === TransportType::TAXI) {
            $nearbyDrivers = $this->findAvailableDriversNear(
                (float) $data['pickup_lat'],
                (float) $data['pickup_lng']
            )->sortBy(function (Driver $driver) use ($data) {
                return $this->haversineKm(
                    (float) $driver->latitude,
                    (float) $driver->longitude,
                    (float) $data['pickup_lat'],
                    (float) $data['pickup_lng']
                );
            })->values();
            $bestDriver = $nearbyDrivers->first();

            if ($nearbyDrivers->isNotEmpty()) {
                event(new TransportRequestBroadcasted(
                    $booking->fresh(['driver', 'vehicle']),
                    $nearbyDrivers->pluck('id')->map(fn ($id) => (int) $id)->all(),
                    $offerWindowSeconds
                ));
                $assignmentStatus = 'broadcasting';
            }
        }

        return response()->json([
            'message' => 'Réservation créée avec succès',
            'booking' => $booking->fresh(['driver', 'vehicle']),
            'serviceability' => [
                'available_drivers_count' => $type === TransportType::TAXI ? $availableDrivers : null,
                'serviceable' => $type === TransportType::TAXI ? true : null,
                'operating_zone' => $type === TransportType::TAXI ? $pricingRule?->zone : null,
                'supply_state' => $type === TransportType::TAXI ? $this->supplyState($availableDrivers) : null,
                'retry_after_minutes' => $type === TransportType::TAXI && $assignmentStatus !== 'assigned' ? 1 : 0,
                'within_zone' => $type === TransportType::TAXI ? true : null,
                'service_radius_km' => $type === TransportType::TAXI ? 35 : null,
                'trip_window_minutes' => $type === TransportType::TAXI ? $tripWindow : null,
                'offer_window_seconds' => $offerWindowSeconds,
                'first_accept_wins' => $type === TransportType::TAXI ? true : null,
                'best_driver' => $type === TransportType::TAXI ? $this->driverCandidatePayload(
                    $bestDriver,
                    (float) $data['pickup_lat'],
                    (float) $data['pickup_lng']
                ) : null,
                'assignment_status' => $type === TransportType::TAXI ? $assignmentStatus : null,
            ],
        ], 201);
    }

    public function show($id)
    {
        $booking = TransportBooking::where('uuid', $id)
            ->orWhere('id', $id)
            ->with(['driver', 'vehicle', 'trackingPoints' => function ($query) {
                $query->latest('recorded_at')->limit(10);
            }])
            ->firstOrFail();

        $this->authorize('view', $booking);

        $payload = $booking->toArray();
        $payload['live_trip'] = $this->liveTripPayload($booking);
        $payload['payment_experience'] = $this->paymentExperienceService->describe($booking->payments()->latest('id')->first());

        return response()
            ->json($payload)
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0, private')
            ->header('Pragma', 'no-cache')
            ->header('Expires', '0');
    }

    public function cancel($id)
    {
        $booking = TransportBooking::where('uuid', $id)->firstOrFail();
        $this->authorize('cancel', $booking);

        $this->transportService->updateStatus($booking, TransportStatus::CANCELLED);

        return response()->json(['message' => 'Réservation annulée']);
    }

    public function pay(Request $request, $id)
    {
        $booking = TransportBooking::where('uuid', $id)->firstOrFail();

        $request->validate([
            'provider' => 'required|string|in:momo,airtel',
            'phone' => 'nullable|string|max:30',
        ]);

        $result = $this->transportService->initiatePayment($booking, $request->provider, [
            'phone' => $request->input('phone'),
        ]);

        return response()->json([
            'message' => 'Paiement initié',
            'payment' => $result['payment'],
            'payment_id' => $result['payment']->id ?? null,
            'redirect_url' => $result['redirect_url'],
            'payment_experience' => $this->paymentExperienceService->describe($result['payment'] ?? null),
        ]);
    }

    protected function liveTripPayload(TransportBooking $booking): array
    {
        $latestPoint = $booking->trackingPoints->sortByDesc('recorded_at')->first();
        $targetLat = null;
        $targetLng = null;

        if (in_array($booking->status->value, ['assigned', 'driver_arriving'], true)) {
            $targetLat = $booking->pickup_lat;
            $targetLng = $booking->pickup_lng;
        } elseif (in_array($booking->status->value, ['picked_up', 'in_progress'], true)) {
            $targetLat = $booking->dropoff_lat;
            $targetLng = $booking->dropoff_lng;
        }

        $remainingDistanceKm = null;
        $etaMinutes = null;

        if ($latestPoint && $targetLat && $targetLng) {
            $remainingDistanceKm = $this->haversineKm(
                (float) $latestPoint->lat,
                (float) $latestPoint->lng,
                (float) $targetLat,
                (float) $targetLng
            );

            $etaMinutes = (int) max(2, ceil(($remainingDistanceKm / 28) * 60));
        }

        return [
            'driver_availability' => optional($booking->driver)->status,
            'latest_tracking_point' => $latestPoint ? Arr::only($latestPoint->toArray(), ['lat', 'lng', 'speed', 'recorded_at']) : null,
            'remaining_distance_km' => $remainingDistanceKm ? round($remainingDistanceKm, 2) : null,
            'eta_minutes' => $etaMinutes,
        ];
    }

    protected function haversineKm(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earth = 6371;
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);

        $a = sin($dLat / 2) * sin($dLat / 2) +
            cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
            sin($dLng / 2) * sin($dLng / 2);

        return $earth * (2 * atan2(sqrt($a), sqrt(1 - $a)));
    }

    protected function countAvailableDriversNear(float $lat, float $lng, float $radiusKm = 5): int
    {
        return $this->findAvailableDriversNear($lat, $lng, $radiusKm)->count();
    }

    protected function findBestAvailableDriverNear(float $lat, float $lng, float $radiusKm = 5): ?Driver
    {
        return $this->findAvailableDriversNear($lat, $lng, $radiusKm)
            ->sortBy(function (Driver $driver) use ($lat, $lng) {
                return $this->haversineKm(
                    (float) $driver->latitude,
                    (float) $driver->longitude,
                    $lat,
                    $lng
                );
            })
            ->first();
    }

    protected function findAvailableDriversNear(float $lat, float $lng, float $radiusKm = 5)
    {
        $latDelta = $radiusKm / 111;
        $lngDelta = $radiusKm / max(1, (111 * cos(deg2rad($lat))));

        return Driver::query()
            ->where('status', 'online')
            ->whereNotNull('active_transport_vehicle_id')
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->whereBetween('latitude', [$lat - $latDelta, $lat + $latDelta])
            ->whereBetween('longitude', [$lng - $lngDelta, $lng + $lngDelta])
            ->with('activeTransportVehicle')
            ->get()
            ->filter(function (Driver $driver) use ($lat, $lng, $radiusKm) {
                if (! $driver->activeTransportVehicle || $driver->activeTransportVehicle->status !== 'active') {
                    return false;
                }

                $activeTrips = $driver->transportBookings()
                    ->whereIn('status', [
                        TransportStatus::ASSIGNED,
                        TransportStatus::DRIVER_ARRIVING,
                        TransportStatus::PICKED_UP,
                        TransportStatus::IN_PROGRESS,
                    ])
                    ->count();

                if ($activeTrips > 0) {
                    return false;
                }

                return $this->haversineKm(
                    (float) $driver->latitude,
                    (float) $driver->longitude,
                    $lat,
                    $lng
                ) <= $radiusKm;
            })
            ->values();
    }

    protected function driverCandidatePayload(?Driver $driver, float $lat, float $lng): ?array
    {
        if (! $driver) {
            return null;
        }

        $distanceKm = $this->haversineKm(
            (float) $driver->latitude,
            (float) $driver->longitude,
            $lat,
            $lng
        );

        return [
            'id' => $driver->id,
            'name' => $driver->name,
            'phone' => $driver->phone,
            'vehicle_id' => $driver->active_transport_vehicle_id,
            'vehicle_label' => $driver->activeTransportVehicle
                ? trim(($driver->activeTransportVehicle->make ?? '') . ' ' . ($driver->activeTransportVehicle->model ?? ''))
                : null,
            'distance_to_pickup_km' => round($distanceKm, 2),
            'pickup_eta_minutes' => (int) max(2, ceil(($distanceKm / 28) * 60)),
        ];
    }

    protected function tripWindowMinutes(int $durationMinutes): array
    {
        $min = max(5, $durationMinutes);
        $max = $min + max(6, (int) ceil($durationMinutes * 0.35));

        return [
            'min' => $min,
            'max' => $max,
        ];
    }

    protected function isTaxiRouteWithinZone(float $distanceKm): bool
    {
        return $distanceKm <= 35;
    }

    protected function requiresPrecisePinConfirmation(string $precisionLevel, bool $pinConfirmed): bool
    {
        return in_array($precisionLevel, ['district', 'area', 'blind'], true) && ! $pinConfirmed;
    }

    protected function activePricingRule(TransportType $type): ?TransportPricingRule
    {
        return TransportPricingRule::query()
            ->where('type', $type)
            ->where('is_active', true)
            ->first();
    }

    protected function supplyState(?int $availableDrivers): ?string
    {
        if ($availableDrivers === null) {
            return null;
        }

        return match (true) {
            $availableDrivers < 1 => 'unavailable',
            $availableDrivers === 1 => 'tight',
            $availableDrivers <= 3 => 'steady',
            default => 'healthy',
        };
    }
}
