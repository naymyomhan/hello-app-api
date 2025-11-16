<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GameOrderHistory extends Model
{
    protected $fillable = [
        'user_id',

        'game_name',
        'game_icon_url',

        'item_name',

        'original_price',
        'price',

        'balance_before',
        'balance_after',

        'fields',
        'field_values',

        'api_order_id',
        'status',

        'is_direct_topup',
        'redeem_code',
    ];

    protected $casts = [
        'fields' => 'array',
        'field_values' => 'array',
        'is_direct_topup' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
