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
        Schema::create('point_rules', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('category'); // Menggunakan Enum (ATTENDANCE, PERFORMANCE, dll)
            $table->string('event_name'); // Nama aturan (misal: "Telat > 30 Menit")
            $table->integer('points');
            $table->enum('operator', ['<', '<=', '>', '>=', '==', 'BETWEEN'])->default('==');
            $table->integer('min_value')->nullable();
            $table->integer('max_value')->nullable(); // Digunakan jika operator 'BETWEEN'
            $table->boolean('is_active')->default(true);
            $table->text('description')->nullable();
            $table->boolean('system_reserve')->default(false); // Menandai aturan yang dicadangkan untuk sistem internal
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('point_rules');
    }
};
