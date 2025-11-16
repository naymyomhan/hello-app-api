<?php

namespace App\Filament\Resources\TopupResource\Pages;

use App\Filament\Resources\TopupResource;
use App\Models\PaymentMethod;
use Filament\Actions;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListTopups extends ListRecords
{
    protected static string $resource = TopupResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    public function getTabs(): array
    {
        $tabs = [
            'all' => Tab::make('All'),
        ];

        // Get all payment methods and create a tab for each
        $paymentMethods = PaymentMethod::all();

        foreach ($paymentMethods as $paymentMethod) {
            $tabs[$paymentMethod->id] = Tab::make($paymentMethod->name)
                ->modifyQueryUsing(
                    fn(Builder $query) => $query->where('payment_method_id', $paymentMethod->id)
                );
        }

        return $tabs;
    }
}
