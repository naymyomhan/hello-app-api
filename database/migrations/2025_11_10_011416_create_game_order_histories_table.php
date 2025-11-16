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
        Schema::create('game_order_histories', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');

            $table->string('game_name');
            $table->string('game_icon_url');

            $table->string('item_name');

            $table->unsignedInteger('original_price');
            $table->unsignedInteger('price');

            $table->unsignedInteger('balance_before');
            $table->unsignedInteger('balance_after');

            $table->string('fields')->nullable();
            $table->string('field_values')->nullable();

            $table->string('api_order_id')->nullable();
            $table->enum('status', ['processing', 'completed', 'refunded'])->default('processing');

            $table->boolean('is_direct_topup')->default(false);
            $table->string('redeem_code')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('game_order_histories');
    }
};
