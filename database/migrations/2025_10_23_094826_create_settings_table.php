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
        Schema::create('settings', function (Blueprint $table) {
            $table->id();

            $table->unsignedInteger('profit')->default(0);

            $table->boolean('is_testing')->default(false);
            $table->boolean('is_maintenance')->default(false);

            $table->unsignedInteger('version_code')->default(1);
            $table->boolean('required_update')->default(false);
            $table->string('update_url')->nullable();
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
