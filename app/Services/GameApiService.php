<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class GameApiService
{
    private $tgi_api_url = "https://api.tgigameshop.com/api";
    private $api_key = "e5RUmR5wIggp5Zq1btMrOnyQ89bIi3Xy";

    private function generateApiKey(): string
    {
        $timestamp = time(); // current Unix time
        $rawKey = $this->api_key . $timestamp;
        $apiKey = base64_encode($rawKey);
        return $apiKey;
    }

    public function getGames()
    {
        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'api-key' => $this->generateApiKey(),
        ])->get($this->tgi_api_url . "/games");
        $data = $response->json();
        return $data['data'];
    }

    // Get Item List
    public function getItemList($slug)
    {
        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'api-key' => $this->generateApiKey(),
        ])->get($this->tgi_api_url . "/games/" . $slug);
        $data = $response->json();
        return $data['data'];
    }

    public function checkAccount($game_slug, $field_data): string
    {
        // header access application/json
        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'api-key' => $this->generateApiKey(),
        ])->post($this->tgi_api_url . '/games/' . $game_slug . "/check-account", $field_data);
        $data = $response->json();

        if ($response->status() != 200) {
            throw new \Exception('Failed to check account');
        }

        if (!isset($data['data']['name'])) {
            throw new \Exception('Failed to check account');
        }

        return $data['data']['name'];
    }

    public function placeOrder($game_slug, $field_data)
    {
        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'api-key' => $this->generateApiKey(),
        ])->post($this->tgi_api_url . '/games/' . $game_slug . "/place-order", $field_data);
        $data = $response->json();

        if ($response->status() != 200) {
            // display error message from api response
            throw new \Exception($data['message'] ?? 'Failed to place order (ERR:GASPO-70)');
        }

        if (!isset($data['data']['order_number'])) {
            throw new \Exception($data['message'] ?? 'Failed to place order (ERR:GASPO-74)');
        }

        try {
            $return_data = [
                "order_number" => $data['data']['order_number'],
                "status" => $data['data']['status'],
                "redeem_code" => $data['data']['redeem_code'] ?? null,
            ];

            return $return_data;
        } catch (\Throwable $th) {
            throw new \Exception('Order created but failed to store order number (ERR:GASPO-87)');
        }
    }
}
