<?php

namespace App\Enums;

enum TransactionStatusEnum: string
{
    case POSTED = 'POSTED';
    case REVERSED = 'REVERSED';
    case FAILED = 'FAILED';
}
