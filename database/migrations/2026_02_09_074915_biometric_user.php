<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('biometric_users', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->json('descriptor');
            $table->timestamps();

            $table->index('employee_id');
        });

    }

    public function down(): void
    {
        Schema::dropIfExists('biometric_users');
    }
};
