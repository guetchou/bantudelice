<?php

namespace App\Domain\Finance\Data;

final class PostingReceipt
{
    public function __construct(
        public readonly string $batchUuid,
        public readonly bool $reused,
    ) {
        if (trim($batchUuid) === '') {
            throw new \InvalidArgumentException('A posting batch UUID is required.');
        }
    }
}
