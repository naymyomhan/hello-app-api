<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class Game2xItemApiResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $profit_percent = $this->profit == 0 ? $this->default_profit : $this->profit;
        $original_price = $this->api_price;

        $profit = ceil($original_price * ($profit_percent / 100));
        $price = $original_price + $profit;


        $icon = $this->getMedia('icon')->first()?->getUrl('thumb');
        $default_icon = $this->game->getMedia('item_icon')->first()?->getUrl('thumb');

        return [
            'id' => $this->id,
            'name' => $this->name,
            'price' => $price,
            'icon' => $icon ?? $default_icon ?? asset('images/icons/diamond.png'),
        ];
    }
}
