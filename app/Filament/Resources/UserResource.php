<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Filament\Resources\UserResource\RelationManagers\TopupsRelationManager;
use App\Models\User;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';
    protected static ?string $navigationGroup = 'User Management';
    protected static ?string $navigationLabel = 'Users';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Basic Information')
                    ->schema([
                        TextInput::make('name')
                            ->label('Full Name')
                            ->required()
                            ->maxLength(255),

                        TextInput::make('email')
                            ->email()
                            ->unique(ignoreRecord: true)
                            ->required(),

                            // only display in create
                        TextInput::make('password')
                            ->password()
                            ->revealable()
                            ->dehydrateStateUsing(fn($state) => !empty($state) ? bcrypt($state) : null)
                            ->dehydrated(fn($state) => filled($state))
                            ->required(fn(string $operation): bool => $operation === 'create')
                            ->visible(fn(string $operation): bool => $operation === 'create'),
                    ])->columns(2),

                Section::make('Detail')
                    ->schema([
                        TextInput::make('balance')
                            ->numeric()
                            ->default(0)
                            ->disabled(fn(string $operation): bool => $operation === 'create')
                            ->visible(fn(string $operation): bool => $operation === 'edit')
                            ->readonly(),

                        Toggle::make('is_active')
                            ->inline(false)
                            ->label('Account Active')
                            ->default(true),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')->sortable(),
                Tables\Columns\TextColumn::make('name')
                    ->description(fn(User $record) => $record->email)
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('balance')
                    ->formatStateUsing(fn($state) => number_format($state, 0))
                    ->weight('bold')
                    ->size('md')
                    ->color(fn(User $record) => $record->balance > 1000 ? 'success' : 'danger')
                    ->sortable(),
                TextColumn::make('level')
                    ->badge()
                    ->colors([
                        'pro' => 'success',
                        'normal' => 'secondary',
                    ]),
                Tables\Columns\IconColumn::make('is_active')
                    ->boolean()
                    ->label('Active'),
                Tables\Columns\IconColumn::make('is_subscription_active')
                    ->boolean()
                    ->label('Subscription'),
                // Tables\Columns\TextColumn::make('email_verified_at')
                //     ->dateTime('Y-m-d H:i')
                //     ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime('Y-m-d')
                    ->label('Joined'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('level')
                    ->options([
                        'normal' => 'Normal',
                        'pro' => 'Pro',
                    ]),
                Tables\Filters\TernaryFilter::make('is_active')->label('Active Users'),
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
            TopupsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }

    protected static function mutateFormDataBeforeCreate(array $data): array
    {
        // Ensure verified + random reseller ID
        $data['email_verified_at'] = now();
        return $data;
    }
}
