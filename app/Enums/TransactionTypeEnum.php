<?php

namespace App\Enums;

enum TransactionTypeEnum: string
{
    case DEPOSIT = 'DEPOSIT';
    case TRANSFER = 'TRANSFER';
    case REVERSAL = 'REVERSAL';
}
