<?php

namespace App\Filament\Resources;

use App\Filament\Resources\GameResource\Pages;
use App\Filament\Resources\GameResource\RelationManagers\GameItemRelationManager;
use App\Models\Game;
use App\Services\GameSyncService;
use Filament\Forms;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Support\Enums\FontWeight;
use Filament\Tables;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;
use Illuminate\Support\HtmlString;
use Filament\Tables\Columns\ImageColumn;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\DB;

class GameResource extends Resource
{
    protected static ?string $model = Game::class;

    protected static ?string $navigationIcon = 'heroicon-o-puzzle-piece';

    protected static ?string $navigationGroup = 'Games';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Basic Information')
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->live(onBlur: true)
                            ->afterStateUpdated(function (string $state, Forms\Set $set) {
                                $set('slug', str()->slug($state));
                            })
                            ->columnSpan(1),
                        TextInput::make('slug')
                            ->readOnly()
                            ->disabled()
                            ->columnSpan(1),

                        Toggle::make('is_active')
                            ->default(true)
                            ->label('Active')
                            ->inline(false),

                        TextInput::make('fields')
                            ->label('Fields')
                            ->maxLength(255)
                            ->disabled()
                            ->readOnly()
                            ->columnSpanFull()
                            ->visible(fn(string $operation) => $operation === 'edit'),

                        Textarea::make('description')
                            ->maxLength(65535)
                            ->nullable()
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Section::make('Game Images')
                    ->schema([
                        SpatieMediaLibraryFileUpload::make('featured_image')
                            ->collection('featured_image'),
                        SpatieMediaLibraryFileUpload::make('icon')
                            ->label('Game Logo')
                            ->collection('icon'),
                        SpatieMediaLibraryFileUpload::make('item_icon')
                            ->label('Default Item icon')
                            ->collection('item_icon'),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('icon')
                    ->label('Icon')
                    ->getStateUsing(function ($record) {
                        $media = $record->getFirstMediaUrl('icon', 'thumb');
                        if ($media) {
                            return $media;
                        }
                        return asset('images/hello.jpg');
                    })
                    ->circular()
                    ->width(50)
                    ->height(50),
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('items_count')
                    ->label('Item Count')
                    ->getStateUsing(function (Game $record): string {
                        return $record->gameItems->where('is_active', true)->count() . ' / ' . $record->gameItems->count();
                    })
                    ->sortable(),
                TextColumn::make('is_direct_topup')
                    ->label('Type')
                    ->getStateUsing(fn(Game $record): string => $record->is_direct_topup ? 'Direct Topup' : 'Voucher')
                    ->color(fn($state) => $state === 'Direct Topup' ? 'success' : 'warning')
                    ->sortable(),
                ToggleColumn::make('is_active')
                    ->label('Active')
                    ->onIcon('heroicon-o-check-circle')
                    ->offIcon('heroicon-o-x-circle'),
                ToggleColumn::make('is_popular')
                    ->label('Popular')
                    ->onIcon('heroicon-o-check-circle')
                    ->offIcon('heroicon-o-x-circle'),
            ])
            ->defaultSort('index', 'asc')
            ->paginationPageOptions([50, 100])
            ->defaultPaginationPageOption(50)
            ->filters([
                //
            ])
            ->actions([
                // Move Up Action
                Tables\Actions\Action::make('move_up')
                    ->icon('heroicon-o-arrow-up')
                    ->label('')
                    ->color('info')
                    ->tooltip('Move Up')
                    ->hidden(fn(Game $record) => self::isFirstInOrder($record))
                    ->action(function (Game $record) {
                        self::moveItem($record, 'up');
                    }),

                // Move Down Action
                Tables\Actions\Action::make('move_down')
                    ->icon('heroicon-o-arrow-down')
                    ->color('danger')
                    ->label('')
                    ->tooltip('Move Down')
                    ->hidden(fn(Game $record) => self::isLastInOrder($record))
                    ->action(function (Game $record) {
                        self::moveItem($record, 'down');
                    }),

                Tables\Actions\Action::make('sync_items')
                    ->button()
                    ->label('Sync')
                    ->icon('heroicon-o-arrow-path')
                    ->action(function (Game $game) {
                        app(GameSyncService::class)->syncGameItems($game);
                    })
                    ->requiresConfirmation()
                    ->color('success'),
            ])
            ->bulkActions([
                // Bulk actions if needed
            ])
            ->reorderable('index')
            ->defaultSort('index', 'asc');
    }

    /**
     * Check if the record is first in order
     */
    protected static function isFirstInOrder(Game $record): bool
    {
        $minIndex = Game::query()->min('index');
        return $record->index <= ($minIndex ?? 0);
    }

    /**
     * Check if the record is last in order
     */
    protected static function isLastInOrder(Game $record): bool
    {
        $maxIndex = Game::query()->max('index');
        return $record->index >= ($maxIndex ?? PHP_INT_MAX);
    }

    /**
     * Move item up or down efficiently using a single transaction
     */
    protected static function moveItem(Game $record, string $direction): void
    {
        DB::transaction(function () use ($record, $direction) {
            $currentIndex = $record->index;

            // Find the adjacent item
            $adjacentItem = Game::query()
                ->where('id', '!=', $record->id)
                ->when($direction === 'up', fn($q) => $q->where('index', '<', $currentIndex)->orderBy('index', 'desc'))
                ->when($direction === 'down', fn($q) => $q->where('index', '>', $currentIndex)->orderBy('index', 'asc'))
                ->first();

            if (!$adjacentItem) {
                Notification::make()
                    ->warning()
                    ->title('Cannot move')
                    ->body('Item is already at the ' . ($direction === 'up' ? 'top' : 'bottom'))
                    ->send();
                return;
            }

            // Swap indices using a temporary value to avoid conflicts
            $tempIndex = -999999;
            $adjacentIndex = $adjacentItem->index;

            // Use updateQuietly to avoid triggering observers if any
            $record->updateQuietly(['index' => $tempIndex]);
            $adjacentItem->updateQuietly(['index' => $currentIndex]);
            $record->updateQuietly(['index' => $adjacentIndex]);
        });
    }

    public static function getRelations(): array
    {
        return [
            GameItemRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListGames::route('/'),
            'create' => Pages\CreateGame::route('/create'),
            'edit' => Pages\EditGame::route('/{record}/edit'),
        ];
    }
}
