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
        Schema::create('attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->date('date');

            $table->enum('status', ['present', 'absent']);
            $table->dateTime('clock_in')->nullable();
            $table->dateTime('clock_out')->nullable();

            $table->enum('input_type', ['system', 'manual'])->default('system');

            $table->integer('late_minutes')->default(0);
            $table->integer('early_leave_minutes')->default(0);
            $table->boolean('is_early_leave_approved')->default(false);
            $table->integer('work_minutes')->default(0);
            $table->integer('overtime_minutes')->default(0);

            $table->string('clock_in_photo')->nullable();
            $table->string('clock_out_photo')->nullable();

            $table->decimal('latitude_in', 10, 7)->nullable();
            $table->decimal('longitude_in', 10, 7)->nullable();
            $table->decimal('latitude_out', 10, 7)->nullable();
            $table->decimal('longitude_out', 10, 7)->nullable();

            $table->boolean('is_corrected')->default(false);

            $table->timestamps();

            $table->unique(['employee_id', 'date']);
            $table->index('status');
            $table->index('date');
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendances');
    }
};
