<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentMethodDetailResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $icon = $this->getMedia('icon')->first()?->getUrl('thumb');

        return [
            'id' => $this->id,
            'name' => $this->name,
            'currency' => $this->currency,
            'icon' => $icon,
            'accounts' => PaymentAccountResource::collection($this->paymentAccounts->where('is_active', true)),
        ];
    }
}
