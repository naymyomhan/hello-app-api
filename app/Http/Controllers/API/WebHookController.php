<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\GameOrderHistory;
use App\Models\GamePurchaseHistory;
use App\Models\User;
use App\Services\GameSyncService;
use App\Services\PushNotificationService;
use App\Traits\ResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class WebHookController extends Controller
{
    use ResponseTrait;

    // public function exchangeRateUpdate(): void
    // {
    //     $game_sync_service = new GameSyncService();
    //     $game_sync_service->sync();
    // }


    public function orderUpdate(Request $request)
    {
        DB::beginTransaction();

        try {
            $validated = $request->validate([
                'status' => 'required|string',
                'order_number' => 'required|string',
                'redeem_code' => 'nullable|string',
            ]);

            $game_order_history = GameOrderHistory::where('api_order_id', $validated['order_number'])->first();

            if (!$game_order_history) {
                throw new \Exception('No matching order found.');
            }

            if ($game_order_history->status === $validated['status']) {
                throw new \Exception('Order status is already updated.');
            }

            if ($game_order_history->status === 'refunded' && $validated['status'] === 'refunded') {
                throw new \Exception('Order already refunded.');
            }

            $game_order_history->status = $validated['status'];

            if (!empty($validated['redeem_code'])) {
                $game_order_history->redeem_code = $validated['redeem_code'];
            }

            $game_order_history->save();

            if ($validated['status'] === 'refunded') {
                $user = User::where('id', $game_order_history->user_id)
                    ->lockForUpdate()
                    ->first();

                if (!$user) {
                    throw new \Exception('User not found.');
                }

                $refund_amount = $game_order_history->price;

                if (empty($refund_amount) || $refund_amount <= 0) {
                    throw new \Exception('Invalid refund amount.');
                }

                $user->balance += $refund_amount;
                $user->save();

                $this->sendCallBackToApp($game_order_history->api_order_id, 'refunded', null);
            } else {
                $this->sendCallBackToApp($game_order_history->api_order_id, 'completed', null);
            }

            DB::commit();

            return $this->success('Order status updated successfully.');
        } catch (\Throwable $th) {
            DB::rollBack();
            return $this->fail($th->getMessage());
        }
    }

    private function sendCallBackToApp($order_number, $status, $redeem_code = null): bool
    {
        // $base_url = "https://tgigameshop.com/api/web-hook/order-update";
        // $data = [
        //     'status' => $status,
        //     'order_number' => $order_number,
        //     'redeem_code' => $redeem_code,
        // ];

        // $request = Http::withHeaders([
        //     'Content-Type' => 'application/json',
        // ])->post($base_url, $data);

        // $response_data = $request->json();

        // if ($response_data['success'] == true) {
        //     return true;
        // } else {
        //     return false;
        // }
        return true;
    }
}
