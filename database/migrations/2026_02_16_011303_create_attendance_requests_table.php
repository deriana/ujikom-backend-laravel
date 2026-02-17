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
        Schema::create('attendance_requests', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();

            // Relasi ke Employee
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();

            // Tipe Request: Ganti Jam (SHIFT) atau Ganti Mode Kerja (WORK_MODE)
            $table->enum('request_type', ['SHIFT', 'WORK_MODE']);

            // Jika request_type = SHIFT, maka ini wajib diisi
            $table->foreignId('shift_template_id')
                ->nullable()
                ->constrained('shift_templates')
                ->nullOnDelete();

            // Jika request_type = WORK_MODE, maka ini wajib diisi (untuk Jadwal Kerja)
            $table->foreignId('work_schedules_id')
                ->nullable()
                ->constrained('work_schedules')
                ->nullOnDelete();

            // Rentang waktu perubahan
            $table->date('start_date');
            $table->date('end_date')->nullable(); // Bisa null jika cuma minta ganti shift 1 hari

            // Alasan dari karyawan
            $table->text('reason')->nullable();

            // Alur Persetujuan
            // Status & Approval
            // 0: PENDING, 1: APPROVED, 2: REJECTED
            $table->tinyInteger('status')->default(0);

            // Siapa yang menyetujui/menolak
            $table->foreignId('approved_by_id')
                ->nullable()
                ->constrained('employees')
                ->nullOnDelete();

            $table->text('note')->nullable(); // Catatan jika ditolak

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendance_requests');
    }
};
