<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\TopupRequest;
use App\Http\Resources\TopupResource;
use App\Models\PaymentMethod;
use App\Models\Topup;
use App\Models\User;
use App\Traits\ResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Http\Resources\PaymentMethodDetailResource;

class TopupController extends Controller
{
    use ResponseTrait;

    public function getPaymentMethods(Request $request)
    {
        if ($request->currency && in_array($request->currency, ['mmk', 'thb'])) {
            $payment_methods = PaymentMethod::where('currency', $request->currency)
                ->where('is_active', true)
                ->get();
        } else {
            $payment_methods = PaymentMethod::where('is_active', true)
                ->get();
        }

        return $this->success('Get Payment Methods successfully', PaymentMethodDetailResource::collection($payment_methods));
    }

    public function topup(TopupRequest $request)
    {
        DB::beginTransaction();

        try {
            /** @var User $user */
            $user = Auth::guard('sanctum')->user();

            // Check for duplicate transaction with lock to prevent race condition
            $old_topup_with_same_transaction = Topup::where('transaction_number', $request->transaction_number)
                ->where('payment_method_id', $request->payment_method_id)
                ->whereMonth('created_at', now()->month)
                ->lockForUpdate()
                ->first();

            if ($old_topup_with_same_transaction) {
                DB::rollBack();
                return $this->fail('Invalid transaction number. Please try again.');
            }

            // Check for pending topup with lock
            $pending_topup = Topup::where('user_id', $user->id)
                ->where('status', 'pending')
                ->lockForUpdate()
                ->first();

            if ($pending_topup) {
                DB::rollBack();
                return $this->fail('You have a pending topup. Please wait to approve it.');
            }

            $payment_method = PaymentMethod::find($request->payment_method_id);

            $topup = Topup::create([
                'user_id' => $user->id,
                'payment_method_id' => $request->payment_method_id,
                'payment_account_id' => $request->payment_account_id ?? null,
                'transaction_number' => $request->transaction_number,
                'amount' => $request->amount,
                'status' => 'pending',
            ]);

            if ($request->hasFile('payment_proof')) {
                $topup->addMediaFromRequest('payment_proof')
                    ->toMediaCollection('payment_proof');
            }

            DB::commit();

            // Send notifications after successful transaction
            // $this->sendNotiToAdmin($topup);
            // $this->sendNotiToUser($topup);

            return $this->success('Topup successfully', new TopupResource($topup));
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->fail('Failed to topup: ' . $e->getMessage());
        }
    }

    /**
     * Topup history
     */
    public function getTopupHistory(Request $request)
    {
        /** @var User $user */
        $user = Auth::guard('sanctum')->user();

        $page = $request->query('page', 1);
        $per_page = $request->query('per_page', 10);
        $topups = Topup::where('user_id', $user->id)->orderBy('created_at', 'desc')->paginate($per_page, ['*'], 'page', $page);

        $data = [
            'can_load_more' => $topups->hasMorePages(),
            'topups' => TopupResource::collection($topups->items()),
        ];

        return $this->success('Topup history', $data);
    }
}
