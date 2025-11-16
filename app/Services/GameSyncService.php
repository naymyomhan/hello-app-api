<?php

namespace App\Services;

use App\Models\Game;
use App\Models\GameItem;

class GameSyncService
{
    public function sync(): void
    {
        $this->syncGames();

        $games = Game::where('is_active', true)->get();
        foreach ($games as $game) {
            $this->syncGameItems($game);
        }
    }

    public function syncGames(): void
    {
        $game_api_service = new GameApiService();
        $apiGames = $game_api_service->getGames();

        $apiSlugs = collect($apiGames)->pluck('slug')->toArray();
        Game::whereNotIn('slug', $apiSlugs)
            ->update(['is_active' => false]);

        foreach ($apiGames as $apiGame) {
            $existingGame = Game::where('slug', $apiGame['slug'])->first();

            if ($existingGame) {
                $existingGame->update([
                    'name' => $apiGame['name'],
                    'fields' => json_encode($apiGame['fields']),
                    'is_direct_topup' => $apiGame['direct_topup'],
                    'is_active' => true,
                ]);
                continue;
            }

            $maxIndex = Game::max('index');
            $newIndex = ($maxIndex !== null) ? $maxIndex + 1 : 0;

            Game::create([
                'game_category_id' => 1,
                'name' => $apiGame['name'],
                'slug' => $apiGame['slug'],
                'fields' => json_encode($apiGame['fields']),
                'is_direct_topup' => $apiGame['direct_topup'],
                'is_active' => true,
                'index' => $newIndex,
            ]);
        }
    }


    public function syncGameItems($game): void
    {
        $game_api_service = new GameApiService();
        $items = $game_api_service->getItemList($game->slug);

        $api_item_ids = array_column($items, 'id');

        // inactivate item not in the new list
        GameItem::where('game_id', $game->id)
            ->whereNotIn('api_id', $api_item_ids)
            ->update(['is_active' => false]);

        foreach ($items as $item) {
            $gameItem = GameItem::where('game_id', $game->id)
                ->where('api_id', $item['id'])
                ->first();

            if ($gameItem) {
                $gameItem->update([
                    'game_id' => $game->id,
                    'name' => $item['name'],
                    'api_id' => $item['id'],
                    'api_price' => $item['price'],
                    'is_active' => true,
                ]);
            } else {
                GameItem::create([
                    'game_id' => $game->id,
                    'name' => $item['name'],
                    'api_id' => $item['id'],
                    'api_price' => $item['price'],
                    'is_active' => true,
                ]);
            }
        }
    }
}
