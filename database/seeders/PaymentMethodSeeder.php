<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\PaymentMethod;
use App\Models\PaymentAccount;
use Illuminate\Support\Facades\File;

class PaymentMethodSeeder extends Seeder
{
    public function run(): void
    {
        $paymentMethods = [
            [
                'name' => 'KBZ Pay',
                'icon' => 'kpay.png',
                'currency' => 'mmk',
                'accounts' => [
                    [
                        'account_name' => 'John Doe',
                        'account_number' => '123456789',
                        'qr_code' => 'qr_placeholder.jpg',
                    ],
                ],
            ],
            [
                'name' => 'Wave Money',
                'icon' => 'wave.png',
                'currency' => 'mmk',
                'accounts' => [
                    [
                        'account_name' => 'Jane Smith',
                        'account_number' => '987654321',
                        'qr_code' => 'qr_placeholder.jpg',
                    ],
                ],
            ],
            [
                'name' => 'Bangkok Bank',
                'icon' => 'bangkok_bank.jpg',
                'currency' => 'thb',
                'accounts' => [
                    [
                        'account_name' => 'Alice Johnson',
                        'account_number' => '456789123',
                        'qr_code' => 'qr_placeholder.jpg',
                    ],
                ],
            ],

            [
                'name' => 'K Bank',
                'icon' => 'kbank.jpg',
                'currency' => 'thb',
                'accounts' => [
                    [
                        'account_name' => 'Bob Johnson',
                        'account_number' => '325235223',
                        'qr_code' => 'qr_placeholder.jpg',
                    ],
                ],
            ]
        ];

        foreach ($paymentMethods as $methodData) {
            $method = PaymentMethod::create([
                'name' => $methodData['name'],
                'currency' => $methodData['currency'],
            ]);

            // Add icon from /public/images/
            $iconPath = public_path('images/payment/' . $methodData['icon']);
            if (File::exists($iconPath)) {
                $method->addMedia($iconPath)
                    ->preservingOriginal()
                    ->toMediaCollection('icon');
            }

            foreach ($methodData['accounts'] as $accountData) {
                $account = $method->paymentAccounts()->create([
                    'account_name' => $accountData['account_name'],
                    'account_number' => $accountData['account_number'],
                ]);

                // Add QR code from /public/images/
                $qrPath = public_path('images/payment/' . $accountData['qr_code']);
                if (File::exists($qrPath)) {
                    $account->addMedia($qrPath)
                        ->preservingOriginal()
                        ->toMediaCollection('qr_code');
                }
            }
        }
    }
}
