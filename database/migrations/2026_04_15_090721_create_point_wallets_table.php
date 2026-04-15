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
        Schema::create('point_wallets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->onDelete('cascade');
            $table->foreignId('point_period_id')->constrained('point_periods'); // Saldo per periode
            $table->integer('current_balance')->default(0);
            $table->timestamps();

            // Satu karyawan hanya punya satu wallet per periode
            $table->unique(['employee_id', 'point_period_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('point_wallets');
    }
};
