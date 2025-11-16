<?php

namespace App\Filament\Resources\GameOrderHistoryResource\Pages;

use App\Filament\Resources\GameOrderHistoryResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditGameOrderHistory extends EditRecord
{
    protected static string $resource = GameOrderHistoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
