<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TopupResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $payment_proof = $this->getMedia('payment_proof')->first()?->getUrl('thumb');

        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'user_name' => $this->user->name,

            'transaction_number' => $this->transaction_number,
            'amount' => $this->amount,

            'status' => $this->status,

            'date' => $this->created_at->format('d M Y'),
            'time' => $this->created_at->format('H:i A'),

            'payment_proof' => $payment_proof,

            'payment_method_id' => new PaymentMethodResource($this->paymentMethod),
        ];
    }
}
