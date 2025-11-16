<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TopupResource\Pages;
use App\Filament\Resources\TopupResource\RelationManagers;
use App\Models\Topup;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class TopupResource extends Resource
{
    protected static ?string $model = Topup::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationGroup = 'Payment';

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
                TextColumn::make('id')
                    ->sortable(),
                TextColumn::make('user.name')
                    ->url(fn(Topup $record) => route('filament.admin.resources.users.edit', $record->user_id))
                    ->description(fn(Topup $record) => $record->user->email)
                    ->color('primary')
                    ->searchable(),
                TextColumn::make('amount')
                    ->weight('bold'),
                SpatieMediaLibraryImageColumn::make('paymentMethod.icon')
                    ->label('Payment')
                    ->collection('icon'),
                TextColumn::make('transaction_number')
                    ->searchable(),
                TextColumn::make('created_at')
                    ->description(fn(Topup $record) => $record->created_at->format('h:i A'))
                    ->date(),
                TextColumn::make('status')
                    ->icon(fn(string $state): string => match ($state) {
                        'pending' => 'heroicon-o-clock',
                        'approved' => 'heroicon-o-check-circle',
                        'rejected' => 'heroicon-o-x-circle',
                    })
                    ->color(fn(string $state): string => match ($state) {
                        'pending' => 'warning',
                        'approved' => 'success',
                        'rejected' => 'danger',
                    })
                    ->weight('bold'),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\Filter::make('created_at')
                    ->form([
                        Forms\Components\DatePicker::make('created_from'),
                        Forms\Components\DatePicker::make('created_until'),
                        Forms\Components\Toggle::make('today')
                            ->label('Today only')
                            ->inline(false),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['today'],
                                fn(Builder $query): Builder => $query->whereDate('created_at', now()->toDateString()),
                                fn(Builder $query) => $query
                                    ->when(
                                        $data['created_from'],
                                        fn(Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                                    )
                                    ->when(
                                        $data['created_until'],
                                        fn(Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                                    )
                            );
                    }),

                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'approved' => 'Approved',
                        'rejected' => 'Rejected',
                    ])
                    ->label('Status'),
            ])
            ->paginationPageOptions([10, 25, 50, 100])
            ->actions([
                // Tables\Actions\EditAction::make(),
                // Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([
                // Tables\Actions\BulkActionGroup::make([
                //     Tables\Actions\DeleteBulkAction::make(),
                // ]),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with('paymentMethod');
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
            'index' => Pages\ListTopups::route('/'),
            'create' => Pages\CreateTopup::route('/create'),
            // 'edit' => Pages\EditTopup::route('/{record}/edit'),
            'view' => Pages\ViewTopup::route('/{record}'),
        ];
    }
}
