<?php

namespace App\Domain\Payment;

final class PaymentOperatingModel
{
    public const COLLECTION_CONFIRMED = ['paid', 'success'];
    public const COLLECTION_UNRESOLVED = ['initiated', 'pending', 'processing', 'unknown'];
    public const COLLECTION_FAILURE = ['failed', 'cancelled', 'expired'];
    public const COLLECTION_REVERSAL = ['reversed', 'refunded', 'disputed'];

    public const WITHDRAWAL_RESERVED = ['created', 'reserved', 'submitted', 'pending', 'unknown'];
    public const WITHDRAWAL_PAID = ['paid'];
    public const WITHDRAWAL_FAILURE = ['failed', 'cancelled'];
    public const WITHDRAWAL_REVERSAL = ['reversed'];

    public static function canonicalCollection(?string $status): string
    {
        return match (strtoupper(trim((string) $status))) {
            'INITIATED', 'CREATED' => 'initiated',
            'PENDING' => 'pending',
            'AUTHORIZED', 'PROCESSING', 'SUBMITTED' => 'processing',
            'SUCCESS', 'SUCCESSFUL', 'COMPLETED', 'CAPTURED', 'APPROVED' => 'success',
            'PAID' => 'paid',
            'FAILED', 'REJECTED', 'DECLINED' => 'failed',
            'CANCELLED', 'CANCELED' => 'cancelled',
            'EXPIRED', 'TIMEOUT' => 'expired',
            'REFUNDED', 'PARTIALLY_REFUNDED' => 'refunded',
            'REVERSED', 'REVERSAL', 'ROLLED_BACK' => 'reversed',
            'DISPUTED', 'CHARGEBACK' => 'disputed',
            'UNKNOWN', '' => 'unknown',
            default => 'unknown',
        };
    }

    public static function collectionFamily(?string $status): string
    {
        $canonical = self::canonicalCollection($status);

        return match (true) {
            in_array($canonical, self::COLLECTION_CONFIRMED, true) => 'confirmed',
            in_array($canonical, self::COLLECTION_UNRESOLVED, true) => 'unresolved',
            in_array($canonical, self::COLLECTION_FAILURE, true) => 'failed',
            in_array($canonical, self::COLLECTION_REVERSAL, true) => 'reversal',
            default => 'unknown',
        };
    }

    public static function isConfirmedCollection(?string $status): bool
    {
        return in_array(self::canonicalCollection($status), self::COLLECTION_CONFIRMED, true);
    }

    public static function isUnresolvedCollection(?string $status): bool
    {
        return in_array(self::canonicalCollection($status), self::COLLECTION_UNRESOLVED, true);
    }

    public static function canReconcileCollection(?string $status): bool
    {
        return in_array(strtoupper(trim((string) $status)), [
            'PAID', 'SUCCESS', 'SUCCESSFUL',
            'INITIATED', 'PENDING', 'AUTHORIZED', 'PROCESSING', 'UNKNOWN',
        ], true);
    }

    public static function canonicalWithdrawal(?string $status): string
    {
        return match (strtolower(trim((string) $status))) {
            'created', 'requested' => 'created',
            'reserved' => 'reserved',
            'submitted' => 'submitted',
            'pending', 'processing' => 'pending',
            'paid', 'successful', 'success', 'completed' => 'paid',
            'failed', 'rejected', 'declined', 'expired' => 'failed',
            'cancelled', 'canceled' => 'cancelled',
            'reversed', 'refunded' => 'reversed',
            'unknown', '' => 'unknown',
            default => 'unknown',
        };
    }

    public static function withdrawalFamily(?string $status): string
    {
        $canonical = self::canonicalWithdrawal($status);

        return match (true) {
            in_array($canonical, self::WITHDRAWAL_RESERVED, true) => 'reserved',
            in_array($canonical, self::WITHDRAWAL_PAID, true) => 'paid',
            in_array($canonical, self::WITHDRAWAL_FAILURE, true) => 'failed',
            in_array($canonical, self::WITHDRAWAL_REVERSAL, true) => 'reversal',
            default => 'unknown',
        };
    }

    public static function isReservedWithdrawal(?string $status): bool
    {
        return in_array(self::canonicalWithdrawal($status), self::WITHDRAWAL_RESERVED, true);
    }
}
