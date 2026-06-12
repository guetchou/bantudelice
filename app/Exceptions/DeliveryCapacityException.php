<?php

namespace App\Exceptions;

use RuntimeException;

class DeliveryCapacityException extends RuntimeException
{
    public function __construct(
        string $message,
        protected array $serviceability = []
    ) {
        parent::__construct($message);
    }

    public function serviceability(): array
    {
        return $this->serviceability;
    }
}
