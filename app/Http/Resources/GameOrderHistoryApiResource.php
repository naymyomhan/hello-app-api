<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GameOrderHistoryApiResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'item_name' => $this->item_name,
            'game_name' => $this->game_name,
            'game_icon_url' => $this->game_icon_url,

            'price' => $this->price,

            "is_direct_topup" => (bool) $this->is_direct_topup,
            "redeem_code" => $this->redeem_code,

            'account_details' => $this->getAccountDetail(),

            'order_id' => $this->api_order_id,
            'status' => $this->status,
            'date' => $this->created_at->format('M d, Y'),
            'time' => $this->created_at->format('h:i A'),
        ];
    }



    private function getAccountDetail()
    {
        $field_keys = json_decode($this->fields, true);
        $field_values = json_decode($this->field_values, true);

        $account_details = [];
        for ($i = 0; $i < count($field_keys); $i++) {
            $account_details[$field_keys[$i]] = $field_values[$i];
        }
        return $account_details;
    }
}
