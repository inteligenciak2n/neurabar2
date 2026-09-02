<?php

namespace App\Http\Resources;

use App\Models\Orders\Order;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Order */
class ReadyDeliveryOrderResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'order_number' => $this->order_number,
            'status' => $this->status,
            'attendance' => [
                'customer_identifier' => $this->attendance->customer_identifier,
                'delivery_order' => [
                    'fulfillment_type' => $this->attendance->deliveryOrder->fulfillment_type,
                ],
            ],
        ];
    }
}
