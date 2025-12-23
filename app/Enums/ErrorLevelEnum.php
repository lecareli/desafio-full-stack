<?php

namespace App\Enums;

enum ErrorLevelEnum: string
{
    case ERROR = 'ERROR';
    case WARNING = 'WARNING';
    case CRITICAL = 'CRITICAL';
}
