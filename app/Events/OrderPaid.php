<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\Order;
use Illuminate\Foundation\Events\Dispatchable;

final class OrderPaid
{
    use Dispatchable;

    public readonly int $orderId;
    public readonly int $organizationId;

    public function __construct(public Order $order)
    {
        $this->orderId = $order->id;
        $this->organizationId = $order->organization_id;
    }
}
