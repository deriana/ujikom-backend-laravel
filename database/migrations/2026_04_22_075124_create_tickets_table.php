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
        Schema::create('tickets', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            
            // Relasi
            // foreignId untuk reporter (Karyawan yang melapor)
            $table->foreignId('reporter_id')->constrained('employees')->onDelete('cascade');
            
            // foreignId untuk operator (User/Helpdesk yang menangani), dibuat nullable
            $table->foreignId('operator_id')->nullable()->constrained('users')->onDelete('set null');

            // Konten Tiket
            $table->string('subject');
            $table->longText('description');

            // Enum Priority & Status
            $table->enum('priority', ['low', 'mid', 'high'])->default('low');
            $table->enum('status', ['open', 'in progress', 'closed'])->default('open');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tickets');
    }
};