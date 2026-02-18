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
        Schema::create('early_leaves', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();

            // Relasi ke Absensi hari itu & Karyawan
            $table->foreignId('attendance_id')->constrained('attendances')->onDelete('cascade');
            $table->foreignId('employee_id')->constrained('employees')->onDelete('cascade');

            // Data Kejadian
            $table->integer('minutes_early')->default(0); // Selisih menit dari jam pulang seharusnya
            $table->text('reason'); // Alasan (Sakit, Urusan Keluarga, dll)
            $table->string('attachment')->nullable(); // Foto surat dokter atau bukti lainnya

            // Status & Approval (Oleh Manager)
            $table->tinyInteger('status')->default(0); // PENDING=0, APPROVED=1, REJECTED=2
            $table->foreignId('approved_by_id') // ID User (Manager) yang approve
                ->nullable()
                ->constrained('employees')
                ->nullOnDelete();

            $table->timestamp('approved_at')->nullable();
            $table->text('note')->nullable(); // Catatan tambahan dari manager (misal alasan nolak)

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('early_leaves');
    }
};
