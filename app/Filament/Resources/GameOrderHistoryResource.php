<?php

namespace App\Filament\Resources;

use App\Filament\Resources\GameOrderHistoryResource\Pages;
use App\Filament\Resources\GameOrderHistoryResource\RelationManagers;
use App\Models\GameOrderHistory;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class GameOrderHistoryResource extends Resource
{
    protected static ?string $model = GameOrderHistory::class;

    protected static ?string $navigationIcon = 'heroicon-o-shopping-cart';

    protected static ?string $navigationGroup = 'Games';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                //
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                // TextColumn::make('id'),

                //game_icon_url
                ImageColumn::make('game_icon_url')
                    ->label('Game Icon')
                    ->circular(),
                TextColumn::make('item_name')
                    ->searchable()
                    ->description(fn(GameOrderHistory $record): string => $record->game_name),
                // TextColumn::make('user_id')->searchable(),
                TextColumn::make('user.name')
                    ->searchable()
                    ->url(fn(GameOrderHistory $record) => UserResource::getUrl('edit', ['record' => $record->user]))
                    ->searchable()
                    ->description(function ($record) {
                        return number_format($record->balance_before) . ' | ' . number_format($record->balance_after);
                    })
                    ->label('User'),

                TextColumn::make('price')
                    ->formatStateUsing(fn(int $state): string => number_format($state) . ' MMK')
                    ->description(fn(GameOrderHistory $record): string => number_format($record->original_price) . ' MMK')
                    ->label('Price'),
                TextColumn::make('status')
                    ->formatStateUsing(fn(string $state): string => ucfirst($state))
                    ->icon(fn(string $state): string => match ($state) {
                        'processing' => 'heroicon-o-clock',
                        'completed' => 'heroicon-o-check-circle',
                        'refunded' => 'heroicon-o-x-circle',
                    })
                    ->color(fn(string $state): string => match ($state) {
                        'processing' => 'warning',
                        'completed' => 'success',
                        'refunded' => 'danger',
                    })
                    ->weight('bold'),
                //field_values 
                TextColumn::make('api_order_id')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->label('Order ID'),
                TextColumn::make('field_values')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->label('Field Values'),
                TextColumn::make('created_at')
                    ->dateTime('Y-m-d')
                    ->description(fn(GameOrderHistory $record): string => $record->created_at->format('H:i:s')),
            ])
            ->paginationPageOptions([10, 25, 50, 100])
            ->defaultSort('created_at', 'desc')
            ->filters([
                //status
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'processing' => 'Processing',
                        'completed' => 'Completed',
                        'refunded' => 'Refunded',
                    ]),
            ])
            ->actions([
                // Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                // Tables\Actions\BulkActionGroup::make([
                //     Tables\Actions\DeleteBulkAction::make(),
                // ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListGameOrderHistories::route('/'),
            // 'create' => Pages\CreateGameOrderHistory::route('/create'),
            // 'edit' => Pages\EditGameOrderHistory::route('/{record}/edit'),
        ];
    }
}
