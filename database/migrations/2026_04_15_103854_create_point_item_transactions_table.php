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
        Schema::create('point_item_transactions', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('employee_id')->constrained('employees')->onDelete('cascade');
            $table->foreignId('point_item_id')->constrained('point_items')->onDelete('cascade');
            $table->foreignId('point_period_id')->constrained('point_periods')->onDelete('cascade');
            $table->integer('quantity');
            $table->integer('total_points');
            $table->tinyInteger('status')->default(1)->comment('Value from StatusEnum');
            $table->text('note')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('point_item_transactions');
    }
};
