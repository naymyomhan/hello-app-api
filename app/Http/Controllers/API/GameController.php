<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\GamePlaceOrderRequest;
use App\Http\Resources\GameApiResource;
use App\Http\Resources\GameDetailApiResource;
use App\Http\Resources\GameOrderHistoryApiResource;
use App\Models\Game;
use App\Models\GameItem;
use App\Models\GameOrderHistory;
use App\Models\Setting;
use App\Models\User;
use App\Services\GameApiService;
use App\Traits\ResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Ramsey\Collection\Set;

class GameController extends Controller
{
    use ResponseTrait;

    private GameApiService $game_api_service;

    public function __construct(GameApiService $game_api_service)
    {
        $this->game_api_service = $game_api_service;
    }






    public function getPopularGames()
    {
        $games = Game::orderBy('index', 'asc')
            ->where('is_popular', true)
            ->where('is_active', true)
            ->get();
        return $this->success('Popular games retrieved successfully', GameApiResource::collection($games));
    }

    public function getGames(Request $request)
    {
        $games = Game::orderBy('index', 'asc')
            ->where('is_popular', false)
            ->where('is_active', true)
            ->when($request->filled('q'), function ($query) use ($request) {
                $q = trim($request->get('q'));
                $query->where('name', 'like', "%{$q}%");
            })
            ->get();

        return $this->success('Games retrieved successfully', GameApiResource::collection($games));
    }

    public function getGameList()
    {
        $games = Game::orderBy('index', 'asc')
            ->where('is_active', true)
            ->get();
        return $this->success('Games retrieved successfully', GameApiResource::collection($games));
    }















    /**
     * Game Detail
     */
    public function gameDetail($slug)
    {
        $game = Game::where('slug', $slug)->first();
        if (!$game) {
            return $this->fail('Game not found', 404);
        }

        /** @var User $user */
        $user = Auth::user();

        $setting = Setting::first();
        $game['default_profit'] = $setting->profit;

        return $this->success('Game detail retrieved successfully', new GameDetailApiResource($game));
    }













    /**
     * Check Account
     */
    public function checkGameAccount(Request $request, $slug)
    {
        try {
            $game = Game::where('slug', $slug)
                ->where('is_active', true)
                ->first();
            if (!$game) {
                return $this->fail('Game not found', 404);
            }

            // Dynamic fields start //
            $field_data = [];

            if (!empty($game->fields)) {
                $fields = json_decode($game->fields, true);

                foreach ($fields as $field) {
                    if (!$request->filled($field)) {
                        throw new \Exception("{$field} is required", 400);
                    }
                    $field_data[$field] = $request->input($field);
                }
            }

            $account_name = $this->game_api_service->checkAccount($slug, $field_data);
            if (!$account_name) {
                return $this->fail('Invlid account', 400);
            }

            return $this->success('Account checked successfully', ['account_name' => $account_name]);
        } catch (\Throwable $th) {
            $error_message = $th->getMessage() ?: 'Failed to check account';
            $error_code = $th->getCode() ?: 500;

            return $this->fail($error_message, $error_code);
        }
    }













    /**
     * Place Order
     */
    public function placeOrder(GamePlaceOrderRequest $request, $slug)
    {
        try {
            return DB::transaction(function () use ($request, $slug) {
                // 1️⃣ Validate Game
                $game = Game::where('slug', $slug)
                    ->where('is_active', true)
                    ->lockForUpdate()
                    ->first();

                if (!$game) {
                    throw new \Exception('Game not found', 404);
                }


                // 2️⃣ Validate Game Item
                $game_item = GameItem::where('game_id', $game->id)
                    ->where('id', $request->item_id)
                    ->lockForUpdate()
                    ->first();

                if (!$game_item) {
                    throw new \Exception('Game item not found', 404);
                }

                // 3️⃣ Dynamic Fields Validation
                $field_data = [];

                if (!empty(json_decode($game->fields, true))) {
                    $fields = json_decode($game->fields, true);

                    foreach ($fields as $field) {
                        if (!$request->filled($field)) {
                            throw new \Exception("{$field} is required", 400);
                        }
                        $field_data[$field] = $request->input($field);
                    }
                }

                // 4️⃣ Authenticated User
                /** @var User $user */
                $user = Auth::guard('sanctum')->user();
                if (!$user) {
                    throw new \Exception('Unauthorized', 401);
                }

                // 5️⃣ Price Calculation
                $setting = Setting::first();
                $profit_percent = $game_item->profit == 0 ? $setting->profit : $game_item->profit;
                $original_price = $game_item->api_price;

                $profit = ceil($original_price * ($profit_percent / 100));
                $item_price = $original_price + $profit;

                // 6️⃣ Lock user row to prevent race condition
                $user = User::where('id', $user->id)->lockForUpdate()->first();

                if ($user->balance < $item_price) {
                    throw new \Exception('You do not have enough balance', 400);
                }

                // 7️⃣ Deduct balance safely
                $balanceBefore = $user->balance;
                $user->decrement('balance', $item_price);
                $user->refresh();
                $balanceAfter = $user->balance;

                // 8️⃣ actual order
                $field_data['item_id'] = $game_item->api_id;

                // $place_order_response = $this->game_api_service->placeOrder($slug, $field_data);
                $place_order_response = [
                    'order_number' => "FakeOrderNo-{$user->id}-" . date('YmdHis'),
                    'status' => "processing",
                ];

                // 9️⃣ Get Game Icon
                $game_icon = $game->getMedia('icon')->first()?->getUrl('thumb');
                $game_icon = $game_icon ?: ($game->mongold_image_url ?: asset('images/hello.jpg'));

                // 🔟 Save purchase history (fields + field_values stored separately)
                $history = GameOrderHistory::create([
                    'user_id' => $user->id,

                    'game_name' => $game->name,
                    'game_icon_url' => $game_icon,

                    'item_name' => $game_item->name,

                    'original_price' => $game_item->api_price,
                    'price' => $item_price,

                    'balance_before' => $balanceBefore,
                    'balance_after' => $balanceAfter,

                    'fields' => $field_data ? json_encode(array_keys($field_data)) : null,
                    'field_values' => $field_data ? json_encode(array_values($field_data)) : null,

                    'api_order_id' => $place_order_response['order_number'],
                    'status' => $place_order_response['status'],

                    'is_direct_topup' => $game->is_direct_topup,
                    'redeem_code' => $place_order_response['redeem_code'] ?? null,
                ]);

                // ✅ Success — transaction auto-commits
                return $this->success('Game purchased successfully', new GameOrderHistoryApiResource($history));
            }, 3); // retry 3 times if deadlock occurs
        } catch (\Exception $e) {
            Log::error('Game order failed', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $code = $e->getCode();
            if ($code < 100 || $code > 599) {
                $code = 400;
            }

            $error_message = $e->getMessage() ?? 'Failed to place order';

            return $this->fail($error_message, $code);
        }
    }



    public function getOrderDetail($order_id)
    {
        /** @var User $user */
        $user = Auth::guard('sanctum')->user();

        $game_purchase_history = GameOrderHistory::where('id', $order_id)->where('user_id', $user->id)->first();
        if (!$game_purchase_history) {
            throw new \Exception('Order not found', 404);
        }

        return $this->success('Order detail retrieved successfully', new GameOrderHistoryApiResource($game_purchase_history));
    }

    public function getOrderHistory()
    {
        /** @var User $user */
        $user = Auth::guard('sanctum')->user();

        $game_purchase_histories = GameOrderHistory::where('user_id', $user->id)->orderByDesc('id')->paginate(20);
        $data = [
            'order_histories' => GameOrderHistoryApiResource::collection($game_purchase_histories),
            'can_load_more' => $game_purchase_histories->hasMorePages(),
        ];
        return $this->success('Order history retrieved successfully', $data);
    }
}
