<?php

namespace App\Filament\Resources\TopupResource\Pages;

use App\Filament\Resources\TopupResource;
use App\Models\Promotion;
use App\Models\Topup;
use Filament\Infolists\Components\Actions\Action;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists;
use Filament\Infolists\Components\Actions;
use Filament\Infolists\Components\Group;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\SpatieMediaLibraryImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Support\Enums\Alignment;
use Filament\Tables\Columns\TextColumn;

class ViewTopup extends ViewRecord
{
    protected static string $resource = TopupResource::class;

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make('Topup Information')
                    ->icon('heroicon-m-document-text')
                    ->schema(
                        [
                            Infolists\Components\TextEntry::make('user.name')
                                ->url(fn(Topup $record) => route('filament.admin.resources.users.edit', $record->user_id))
                                ->icon('heroicon-o-user')
                                ->weight('bold')
                                ->size('2xl')
                                ->label(false),

                            Group::make()->schema([
                                SpatieMediaLibraryImageEntry::make('paymentMethod.icon')
                                    ->collection('icon')
                                    ->size(40)
                                    ->label(fn(Topup $record) => $record->paymentMethod->name),

                                Infolists\Components\TextEntry::make('transaction_number')
                                    ->weight('bold')
                                    ->color('primary')
                                    ->label('Transaction Number'),
                            ])->columns(2),

                            Group::make()->schema([
                                Infolists\Components\TextEntry::make('amount')
                                    ->color('success')
                                    ->weight('bold'),

                                Infolists\Components\TextEntry::make('created_at')
                                    ->formatStateUsing(fn(Topup $record) => $record->created_at->format('h:i A'))
                                    ->label(fn(Topup $record) => $record->created_at->format('M d, Y')),
                            ])->columns(2),


                            SpatieMediaLibraryImageEntry::make('payment_proof')
                                ->label('Payment Screenshot')
                                ->collection('payment_proof')
                                ->width('100%')
                                ->height('auto')
                                ->label(false)
                                ->defaultImageUrl(url('images/placeholder.png')),
                            Infolists\Components\TextEntry::make('status')
                                ->label(function (Topup $record) {
                                    if ($record->status === 'pending') {
                                        return 'Waiting for Approval';
                                    } else {
                                        return ucfirst($record->status) . ' by ' . $record->admin->name . ' at ' . $record->response_at->format('M d, Y') . ' ' . $record->response_at->format('h:i A');
                                    }
                                })
                                ->size('2xl')
                                ->icon(fn(string $state): string => match ($state) {
                                    'pending' => 'heroicon-o-clock',
                                    'approved' => 'heroicon-o-check-circle',
                                    'rejected' => 'heroicon-o-x-circle',
                                })
                                ->iconColor(fn(string $state): string => match ($state) {
                                    'pending' => 'warning',
                                    'approved' => 'success',
                                    'rejected' => 'danger',
                                })
                                ->color(fn(string $state): string => match ($state) {
                                    'pending' => 'warning',
                                    'approved' => 'success',
                                    'rejected' => 'danger',
                                }),
                        ]
                    )->footerActions([
                        Action::make('accept')
                            ->requiresConfirmation()
                            ->label('Accept Order')
                            ->color('success')
                            ->icon('heroicon-o-check')
                            ->hidden(fn($record) => $record->status !== 'pending')
                            ->action(function ($record) {
                                $record->approve($record->id);
                            }),
                        Action::make('reject')
                            ->requiresConfirmation()
                            ->label('Reject Order')
                            ->color('danger')
                            ->icon('heroicon-o-x-mark')
                            ->hidden(fn($record) => $record->status !== 'pending')
                            ->action(function ($record) {
                                $record->reject($record->id);
                            }),
                        Action::make('restore_pending')
                            ->requiresConfirmation()
                            ->label('Restore to Pending')
                            ->color('warning')
                            ->icon('heroicon-o-arrow-path')
                            ->hidden(fn($record) => $record->status === 'pending')
                            ->action(function ($record) {
                                $record->restoreToPending($record->id);
                            }),
                    ])->footerActionsAlignment(Alignment::Center)
                    ->columnSpan(1)
            ])->columns(2);
    }
}
