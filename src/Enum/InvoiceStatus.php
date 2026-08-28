<?php

declare(strict_types=1);

namespace App\Enum;

enum InvoiceStatus: string
{
    case DRAFT = 'DRAFT';
    case SENT = 'SENT';
    case PAID = 'PAID';
    case OVERDUE = 'OVERDUE';
    case REJECTED = 'REJECTED';
}
