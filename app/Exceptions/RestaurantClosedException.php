<?php

namespace App\Exceptions;

use RuntimeException;

class RestaurantClosedException extends RuntimeException
{
    public function __construct(
        string $message,
        public readonly ?string $nextOpening = null,
        public readonly ?int $restaurantId = null
    ) {
        parent::__construct($message);
    }
}
