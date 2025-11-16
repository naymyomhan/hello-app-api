<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GameApiResource extends JsonResource
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

        return [
            'id'                  => $this->id,
            'name'                => $this->name,
            'slug'                => $this->slug,
            'description'         => $this->description,

            'fields'              => json_decode($this->fields, true) ?? [],

            'is_direct_topup'     => $this->is_direct_topup,

            'icon'                => $icon ?? asset('images/hello.jpg'),
            'featured_image'      => $featured_image ?? asset('images/hello.jpg'),
        ];
    }
}
