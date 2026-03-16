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
        Schema::create('assessments', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('evaluator_id') // Menilai
                ->nullable()
                ->constrained('employees')
                ->onDelete('cascade');
            $table->foreignId('evaluatee_id') // Dinilai
                ->nullable()
                ->constrained('employees')
                ->onDelete('cascade');
            $table->date('period');
            $table->text('note')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('assessments');
    }
};
