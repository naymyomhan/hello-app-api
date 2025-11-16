<?php

namespace App\Http\Resources;

use App\Models\GameItem;
use App\Models\MobileLegendItem;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GameDetailApiResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $featured_image = $this->getMedia('featured_image')->first()?->getUrl('thumb');
        $icon = $this->getMedia('icon')->first()?->getUrl('thumb');

        $gameItems = $this->gameItems
            ->filter(function ($item) {
                return $item->is_active;
            })
            ->map(function ($item) {
                $item->default_profit = $this->default_profit;
                return $item;
            })
            ->sortBy(function ($item) {
                return $item->api_price ?? PHP_FLOAT_MAX;
            })
            ->values();

        $twoXItems = $this->get2xDiamondItems($gameItems);
        $oneXItems = $this->getItems($gameItems, $twoXItems);

        return [
            'id'                  => $this->id,
            'name'                => $this->name,
            'slug'                => $this->slug,
            'description'         => $this->description,
            'icon' => $icon ?: ($this->mongold_image_url ?: asset('images/hello.jpg')),
            'featured_image'     => $featured_image ?? asset('images/hello.jpg'),
            'fields'              => json_decode($this->fields, true) ?? [],
            'is_direct_topup'     => $this->is_direct_topup,
            'two_x_items'    => Game2xItemApiResource::collection($twoXItems),
            'items'          => GameItemApiResource::collection($oneXItems),
        ];
    }

    private function get2xDiamondItems($gameItems)
    {
        $twoXItems = $gameItems->filter(function ($item) {
            return str_contains($item->name, '(First Top-Up Bonus)');
        });

        // foreach ($twoXItems as $item) {
        //     $item->name = str_replace(' (First Top-Up Bonus)', '', $item->name);
        //     // if quantity is something like "100 + 100", extract first number
        //     if (str_contains($item->name, ' + ')) {
        //         $item->quantity = intval(explode(' + ', $item->name)[0]);
        //     }
        // }

        return $twoXItems->values();
    }

    private function getItems($gameItems, $twoXItems)
    {
        // Exclude twoX items by ID
        $oneXItems = $gameItems->reject(function ($item) use ($twoXItems) {
            return $twoXItems->contains('id', $item->id);
        });

        return $oneXItems->values();
    }
}
