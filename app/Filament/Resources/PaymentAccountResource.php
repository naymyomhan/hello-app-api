<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PaymentAccountResource\Pages;
use App\Filament\Resources\PaymentAccountResource\RelationManagers;
use App\Models\PaymentAccount;
use Filament\Forms;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class PaymentAccountResource extends Resource
{
    protected static ?string $model = PaymentAccount::class;

    protected static ?string $navigationIcon = 'heroicon-o-user';

    protected static ?string $navigationGroup = 'Payment';

    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Payment Account')
                    ->schema([
                        Select::make('payment_method_id')
                            ->relationship('paymentMethod', 'name')
                            ->required(),
                        TextInput::make('account_number')
                            ->required(),
                        TextInput::make('account_name')
                            ->required(),
                        SpatieMediaLibraryFileUpload::make('qr_code')
                            ->collection('qr_code')
                            ->required(),
                        Toggle::make('is_active')
                            ->default(true),
                    ])->columnSpan(1),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                SpatieMediaLibraryImageColumn::make('paymentMethod.icon')
                    ->label('')
                    ->conversion('thumb')
                    ->collection('icon'),
                TextColumn::make('account_name')
                    ->description(fn(PaymentAccount $record): string => $record->paymentMethod->name),
                TextColumn::make('account_number')
                    ->color('primary')
                    ->weight('bold'),
                IconColumn::make('is_active')
                    ->boolean(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
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
            'index' => Pages\ListPaymentAccounts::route('/'),
            'create' => Pages\CreatePaymentAccount::route('/create'),
            'edit' => Pages\EditPaymentAccount::route('/{record}/edit'),
        ];
    }
}
