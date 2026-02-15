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
        Schema::create('overtimes', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();

            // Relasi ke Absensi & Karyawan
            // attendance_id penting untuk validasi menit lembur vs menit di absen
            $table->foreignId('attendance_id')->constrained('attendances')->onDelete('cascade');
            $table->foreignId('employee_id')->constrained('employees')->onDelete('cascade');

            // Data Lembur
            $table->integer('duration_minutes'); // Diambil otomatis dari overtime_minutes di tabel attendance
            $table->text('reason'); // Alasan yang diisi karyawan di mobile

            // Status & Approval
            // 0: PENDING, 1: APPROVED, 2: REJECTED
            $table->tinyInteger('status')->default(0);

            $table->foreignId('approved_by_id')
                ->nullable()
                ->constrained('employees')
                ->nullOnDelete();

            $table->timestamp('approved_at')->nullable();
            $table->text('note')->nullable(); // Catatan jika ditolak atau ada revisi

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('overtimes');
    }
};
