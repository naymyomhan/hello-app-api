<?php

namespace App\Models;

use App\Services\PushNotificationService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\MediaLibrary\MediaCollections\File;


class Topup extends Model implements HasMedia
{
    use InteractsWithMedia;

    protected $fillable = [
        'user_id',
        'admin_id',

        'payment_method_id',
        'payment_account_id',

        'transaction_number',
        'amount',

        'status',
        'response_at',
    ];

    protected $casts = [
        'response_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function admin()
    {
        return $this->belongsTo(Admin::class);
    }

    public function paymentMethod()
    {
        return $this->belongsTo(PaymentMethod::class);
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('payment_proof')
            ->acceptsFile(function (File $file) {
                $allowedMimeTypes = [
                    'image/jpeg',
                    'image/jpg',
                    'image/png',
                ];
                return in_array($file->mimeType, $allowedMimeTypes);
            })
            ->registerMediaConversions(function (Media $media) {
                $this->addMediaConversion('thumb')
                    ->width(1000)
                    ->height(1000)
                    ->nonQueued();
            });
    }


    public function approve($id, ?int $admin_id = null)
    {
        DB::beginTransaction();

        try {
            // Lock the row for update to prevent race conditions
            $topup = Topup::where('id', $id)->lockForUpdate()->first();

            if (!$topup) {
                throw new \Exception('Topup not found.');
            }

            $user = $topup->user;

            if (!$user) {
                throw new \Exception('User not found for this topup.');
            }

            if ($topup->status != 'pending') {
                throw new \Exception('Topup already ' . $topup->status . '.');
            }

            if (!$admin_id) {
                /** @var Admin $auth_admin */
                $auth_admin = Auth::guard('admin')->user();
                $admin_id = $auth_admin->id;
            }

            // Update status and related fields
            $topup->update([
                'status' => 'approved',
                'response_at' => now(),
                'admin_id' => $admin_id,
            ]);

            // Increment balance after successful update
            $user->increment('balance', $topup->amount);

            // $push_notification_service = new PushNotificationService();
            // $push_notification_service->sendNotificationToUser("Topup Approved", "Your topup is approved", $topup->user_id);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function restoreToPending($id)
    {
        DB::beginTransaction();

        try {
            $topup = Topup::find($id);

            if (!$topup) {
                throw new \Exception('Topup not found.');
            }

            if (!in_array($topup->status, ['approved', 'rejected'])) {
                throw new \Exception('Only approved or rejected topups can be restored to pending.');
            }

            $user = $topup->user;

            if (!$user) {
                throw new \Exception('User not found for this topup.');
            }

            if ($topup->status === 'approved') {
                // Take back the balance from user
                $currentBalance = $user->balance;
                $amountToReduce = min($topup->amount, $currentBalance);
                $user->decrement('balance', $amountToReduce);
            }

            $topup->update([
                'status' => 'pending',
                'response_at' => null,
                'admin_id' => null,
            ]);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function reject($id, ?int $admin_id = null)
    {
        $topup = Topup::find($id);

        if (!$topup) {
            throw new \Exception('Topup not found.');
        }

        if ($topup->status != 'pending') {
            throw new \Exception('Topup already ' . $topup->status . '.');
        }

        if (!$admin_id) {
            /** @var Admin $auth_admin */
            $auth_admin = Auth::user();
            $admin_id = $auth_admin->id;
        }

        $topup->update([
            'status' => 'rejected',
            'response_at' => now(),
            'admin_id' => $admin_id,
        ]);
    }
}
