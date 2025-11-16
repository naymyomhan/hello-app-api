<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\MediaLibrary\MediaCollections\File;

class PaymentMethod extends Model implements HasMedia
{
    use InteractsWithMedia;

    protected $fillable = ['name', 'currency', 'is_active'];

    public function paymentAccounts()
    {
        return $this->hasMany(PaymentAccount::class);
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('icon')
            ->acceptsFile(function (File $file) {
                $allowedMimeTypes = [
                    'image/jpeg',
                    'image/jpg',
                    'image/png',
                    'image/webp',
                ];
                return in_array($file->mimeType, $allowedMimeTypes);
            })
            ->registerMediaConversions(function (Media $media) {
                $this->addMediaConversion('thumb')
                    ->width(240)
                    ->height(240)
                    ->nonQueued();
            });
    }
}
