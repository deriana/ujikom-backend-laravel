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
            $table->foreignId('employee_id')->constrained()->onDelete('cascade');
            $table->enum('view', ['front', 'side', 'back']);
            $table->json('descriptor');
            $table->timestamps();

            $table->unique(['employee_id', 'view']);
        });

    }

    public function down(): void
    {
        Schema::dropIfExists('biometric_users');
    }
};
