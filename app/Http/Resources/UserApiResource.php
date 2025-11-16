<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserApiResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $avatar = $this->getMedia('avatar')->first()?->getUrl('avatar');

        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,

            'is_baned' => $this->is_baned,
            'ban_message' => $this->is_baned ? "သင့်အကောင့်ကိုယာယီ ပိတ်ထားပါသည်။ facebook page မှတဆင့် ဆက်သွယ်ပါ။" : null,

            'avatar' => $avatar ?? asset('images/user_pp.jpg'),

            'balance' => $this->balance,
            'has_pending_topup' => $this->has_pending_topup ?? false,
            'is_google_login' => $this->is_google_login ?? false,
        ];
    }
}
