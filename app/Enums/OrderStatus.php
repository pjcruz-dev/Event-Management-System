<?php

declare(strict_types=1);

namespace App\Enums;

enum OrderStatus: string
{
    case Pending = 'pending';
    case PendingPayment = 'pending_payment';
    case Paid = 'paid';
    case Failed = 'failed';
    case Cancelled = 'cancelled';
    case Refunded = 'refunded';
}
