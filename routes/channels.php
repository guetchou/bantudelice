<?php

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Here you may register all of the event broadcasting channels that your
| application supports. The given channel authorization callbacks are
| used to check if an authenticated user can listen to the channel.
|
*/

Broadcast::channel('App.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('food.order.{orderNo}.status', function ($user, $orderNo) {
    return app(\App\Services\RealtimeChannelAuthorizer::class)->canAccessFoodOrderStatus($user, (string) $orderNo);
});

Broadcast::channel('food.order.{orderNo}.presence', function ($user, $orderNo) {
    return app(\App\Services\RealtimeChannelAuthorizer::class)->canAccessFoodOrderStatus($user, (string) $orderNo);
});

Broadcast::channel('food.restaurant.{restaurantId}.orders', function ($user, $restaurantId) {
    return app(\App\Services\RealtimeChannelAuthorizer::class)->canAccessFoodRestaurantOrders($user, (int) $restaurantId);
});

Broadcast::channel('food.delivery.{driverId}.orders', function ($user, $driverId) {
    return app(\App\Services\RealtimeChannelAuthorizer::class)->canAccessFoodDriverOrders($user, (int) $driverId);
});

Broadcast::channel('transport.booking.{bookingUuid}.status', function ($user, $bookingUuid) {
    return app(\App\Services\RealtimeChannelAuthorizer::class)->canAccessTransportBooking($user, (string) $bookingUuid);
});

Broadcast::channel('transport.booking.{bookingUuid}.tracking', function ($user, $bookingUuid) {
    return app(\App\Services\RealtimeChannelAuthorizer::class)->canAccessTransportBooking($user, (string) $bookingUuid);
});

Broadcast::channel('transport.booking.{bookingUuid}.presence', function ($user, $bookingUuid) {
    return app(\App\Services\RealtimeChannelAuthorizer::class)->canAccessTransportBooking($user, (string) $bookingUuid);
});

Broadcast::channel('transport.driver.{driverId}.requests', function ($user, $driverId) {
    return app(\App\Services\RealtimeChannelAuthorizer::class)->canAccessTransportDriverRequests($user, (int) $driverId);
});

Broadcast::channel('colis.shipment.{shipmentId}.status', function ($user, $shipmentId) {
    return app(\App\Services\RealtimeChannelAuthorizer::class)->canAccessColisShipment($user, (int) $shipmentId);
});

Broadcast::channel('colis.shipment.{shipmentId}.presence', function ($user, $shipmentId) {
    return app(\App\Services\RealtimeChannelAuthorizer::class)->canAccessColisShipment($user, (int) $shipmentId);
});
