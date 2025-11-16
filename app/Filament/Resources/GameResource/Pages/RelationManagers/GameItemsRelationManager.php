<?php

namespace App\Filament\Resources\GameResource\RelationManagers;

use App\Models\GameItem;
use App\Models\Setting;
use Filament\Forms;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;

class GameItemRelationManager extends RelationManager
{
    protected static string $relationship = 'gameItems';

    protected static ?string $title = 'Items';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('name')
                    ->required(),

                TextInput::make('api_price')
                    ->numeric()
                    ->label('မူရင်းစျေး')
                    ->required(),

                TextInput::make('profit')
                    ->numeric()
                    ->label('အမြတ်ငွေ')
                    ->required(),

                SpatieMediaLibraryFileUpload::make('icon')
                    ->collection('icon')
                    ->image(),

                TextInput::make('product_id')
                    ->readOnly()
                    ->visible(fn($livewire) => $livewire->getOwnerRecord()->product_type === 'game'),
                TextInput::make('quantity')
                    ->visible(fn($livewire) => $livewire->getOwnerRecord()->product_type === 'game'),
                TextInput::make('game_price')
                    ->label('API Price')
                    ->disabled()
                    ->helperText('Price from API')
                    ->visible(fn($livewire) => $livewire->getOwnerRecord()->product_type === 'game'),

                Toggle::make('is_active')
                    ->default(true),
            ]);
    }

    public function table(Table $table): Table
    {
        $setting = Setting::first();
        $default_profit= $setting->profit;

        return $table
            ->recordTitleAttribute('name')
            ->columns([
                ImageColumn::make('icon')
                    ->label('Icon')
                    ->getStateUsing(function ($record) {
                        $media = $record->getFirstMediaUrl('icon', 'thumb');
                        if ($media) {
                            return $media;
                        }

                        // Check if item_icon exists
                        $itemIcon = $record->game->getFirstMediaUrl('item_icon', 'thumb');
                        if ($itemIcon) {
                            return $itemIcon;
                        }

                        return asset('images/icons/diamond.png');
                    })
                    ->circular()
                    ->width(50)
                    ->height(50),
                TextColumn::make('name')
                    ->description(fn(GameItem $record): string => $record->game->name)
                    ->color('primary')
                    ->weight('bold'),
                TextColumn::make('api_price')
                    ->formatStateUsing(function (GameItem $record) {
                        return number_format($record->api_price);
                    })
                    ->weight('bold')
                    ->label('မူရင်းစျေး')
                    ->color('warning'),

                // correct the percentage calculation 
                TextColumn::make('profit')
                    ->formatStateUsing(function (GameItem $record) use ($default_profit) {
                        $profit_percent = $record->profit == 0 ? $default_profit : $record->profit;
                        $original_price = $record->api_price;
                        $profit = ceil($original_price * ($profit_percent / 100));
                        $price = $original_price + $profit;
                        return number_format($profit);
                    })
                    ->label('အမြတ်(N)')
                    ->color('success'),

                ToggleColumn::make('is_active')
                    ->label('Active')
                    ->onIcon('heroicon-o-check-circle')
                    ->offIcon('heroicon-o-x-circle'),
                // TextColumn::make('updated_at')
                //     ->label('Updated At')
                //     ->date(),
            ])
            ->defaultSort('api_price', 'asc')
            ->paginationPageOptions([1000])
            ->defaultPaginationPageOption(1000)
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active')
                    ->trueLabel('Active')
                    ->falseLabel('Inactive')
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Add Item')
                    ->visible(fn($livewire) => $livewire->getOwnerRecord()->type === 'manual'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                // Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
