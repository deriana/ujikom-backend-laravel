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
        Schema::create('point_items', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->integer('required_points');
            $table->integer('stock')->default(0);
            $table->string('power_up_type')->nullable(); // Contoh: 'ANTI_LATE_LIGHT'
            $table->enum('category', ['VOUCHER', 'GOODS', 'SERVICE']);
            $table->boolean('is_active')->default(true);
            $table->boolean('system_reserve')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('point_items');
    }
};
