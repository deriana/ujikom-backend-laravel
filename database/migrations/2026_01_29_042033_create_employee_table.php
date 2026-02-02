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
        Schema::create('employees', function (Blueprint $table) {
            $table->id();

            // Identitas utama
            $table->string('nik')->unique();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();

            // Struktur organisasi
            $table->foreignId('team_id')->constrained('teams')->cascadeOnDelete();
            $table->foreignId('position_id')->constrained('positions')->cascadeOnDelete();

            // Atasan langsung (self relation)
            $table->foreignId('manager_id')
                ->nullable()
                ->constrained('employees')
                ->nullOnDelete();

            // Status karyawan (0=permanent,1=contract,2=intern,resigned handled by resign_date)
            $table->tinyInteger('employee_status')->default(0);

            // Kontrak (khusus contract/intern)
            $table->date('contract_start')->nullable();
            $table->date('contract_end')->nullable();

            // Gaji dasar (boleh null kalau ikut position atau intern unpaid)
            $table->decimal('base_salary', 15, 2)->nullable();

            // Data personal
            $table->string('phone')->nullable()->unique();
            $table->enum('gender', ['male', 'female']);
            $table->date('date_of_birth')->nullable();
            $table->text('address');

            // Data kerja
            $table->date('join_date');
            $table->date('resign_date')->nullable();
            $table->enum('employment_state', ['active', 'resigned', 'terminated'])->default('active');
            $table->date('termination_date')->nullable();
            $table->text('termination_reason')->nullable();

            // Audit
            $table->foreignId('created_by_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->foreignId('updated_by_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employee');
    }
};
