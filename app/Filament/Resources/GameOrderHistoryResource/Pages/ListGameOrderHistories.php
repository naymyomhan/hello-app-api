<?php

namespace App\Filament\Resources\GameOrderHistoryResource\Pages;

use App\Filament\Resources\GameOrderHistoryResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListGameOrderHistories extends ListRecords
{
    protected static string $resource = GameOrderHistoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Actions\CreateAction::make(),
        ];
    }
}
