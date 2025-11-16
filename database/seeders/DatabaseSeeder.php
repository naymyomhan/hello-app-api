<?php

namespace Database\Seeders;

use App\Models\Admin;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        Admin::factory()->create([
            'name' => 'Admin',
            'email' => 'wtf@gmail.com',
        ]);

        Setting::create([
            'profit' => 10,
            'is_testing' => true,
            'is_maintenance' => false,
            'version_code' => 1,
            'required_update' => false,
            'update_url' => "https://google.com",
        ]);

        $this->call([
            PaymentMethodSeeder::class
        ]);
    }
}
