<?php

namespace App\Filament\Resources\GameResource\Pages;

use App\Filament\Resources\GameResource;
use App\Models\GameCategory;
use App\Services\GameSyncService;
use Filament\Resources\Pages\ListRecords;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListGames extends ListRecords
{
    protected static string $resource = GameResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('sync_games')
                ->label('Sync Games Only')
                ->icon('heroicon-o-arrow-path')
                ->action(fn() => $this->syncGameOnly())
                ->requiresConfirmation()
                ->color('success'),

            Action::make('sync_products')
                ->label('Sync Game Items')
                ->icon('heroicon-o-arrow-path')
                ->action(fn() => $this->syncGameItems())
                ->requiresConfirmation()
                ->color('warning'),

            // Actions\CreateAction::make(),
        ];
    }

    protected function syncGameItems(): void
    {
        app(GameSyncService::class)->sync();
        Notification::make()
            ->title('Sync Complete')
            ->body('Game items updated successfully.')
            ->success()
            ->send();
    }

    protected function syncGameOnly(): void
    {
        app(GameSyncService::class)->syncGames();
        Notification::make()
            ->title('Sync Games Complete')
            ->body('Games updated successfully.')
            ->success()
            ->send();
    }
}
