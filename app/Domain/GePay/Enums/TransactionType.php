<?php

namespace App\Domain\GePay\Enums;

enum TransactionType: string
{
    case COLLECTION = 'collection';
    case DISBURSEMENT = 'disbursement';
    case REMITTANCE = 'remittance';
}
