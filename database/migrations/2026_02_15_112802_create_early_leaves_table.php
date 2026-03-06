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

            // Relationship to daily attendance & Employee
            $table->foreignId('attendance_id')->constrained('attendances')->onDelete('cascade');
            $table->foreignId('employee_id')->constrained('employees')->onDelete('cascade');

            // Incident Data
            $table->integer('minutes_early')->default(0); // Difference in minutes from the scheduled clock-out time
            $table->text('reason'); // Reason (Sick, Family Matters, etc.)
            $table->string('attachment')->nullable(); // Photo of doctor's note or other evidence

            // Status & Approval (By Manager)
            $table->tinyInteger('status')->default(0); // PENDING=0, APPROVED=1, REJECTED=2
            $table->foreignId('approved_by_id') // Employee ID of the Manager who approves
                ->nullable()
                ->constrained('employees')
                ->nullOnDelete();

            $table->timestamp('approved_at')->nullable();
            $table->text('note')->nullable(); // Additional notes from the manager (e.g., reason for rejection)

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
