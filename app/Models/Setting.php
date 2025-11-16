<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Http;

class Setting extends Model
{
    protected $fillable = [
        'profit',
        'is_testing',
        'is_maintenance',
        'version_code',
        'required_update',
        'update_url',
    ];

    protected $casts = [
        'is_testing' => 'boolean',
        'is_maintenance' => 'boolean',
        'required_update' => 'boolean',
    ];
}
