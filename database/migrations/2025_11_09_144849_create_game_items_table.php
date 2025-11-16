<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('game_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('game_id')->constrained()->cascadeOnDelete();

            $table->string('name');
            $table->string('api_id')->nullable();
            $table->unsignedBigInteger('api_price')->nullable();
            
            $table->unsignedInteger('profit')->default(0);
            // sale_price_mmk = (api_price * usd_exchange_rate) + profit
            // one_thb_price = 135mmk
            // sale_price_thb = sale_price_mmk / one_thb_price

            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('game_items');
    }
};
