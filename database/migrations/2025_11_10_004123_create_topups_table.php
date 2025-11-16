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
        Schema::create('topups', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedInteger('admin_id')->nullable();
            
            $table->unsignedBigInteger('payment_method_id');
            $table->unsignedBigInteger('payment_account_id');
            
            $table->string('transaction_number');
            $table->unsignedInteger('amount');

            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->timestamp('response_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('topups');
    }
};
