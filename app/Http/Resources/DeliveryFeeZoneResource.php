<?php

namespace App\Http\Resources;

use App\Models\Settings\DeliveryFeeZone;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin DeliveryFeeZone */
class DeliveryFeeZoneResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'label' => $this->label,
            'zip_code_start' => $this->zip_code_start,
            'zip_code_end' => $this->zip_code_end,
            'fee' => (float) $this->fee,
            'active' => $this->active,
            'sort_order' => $this->sort_order,
        ];
    }
}
