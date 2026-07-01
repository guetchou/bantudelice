<?php

namespace App\Domain\Payment\Enums;

enum ReconciliationCaseStatus: string
{
    case OPEN = 'open';
    case INVESTIGATING = 'investigating';
    case RESOLVED = 'resolved';
    case IGNORED = 'ignored';
}
