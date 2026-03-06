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

            // Relationship to Attendance & Employee
            // attendance_id is important for validating overtime minutes vs attendance minutes
            $table->foreignId('attendance_id')->constrained('attendances')->onDelete('cascade');
            $table->foreignId('employee_id')->constrained('employees')->onDelete('cascade');

            // Overtime Data
            $table->integer('duration_minutes')->default(0); // Automatically taken from overtime_minutes in attendance table
            $table->text('reason'); // Reason filled by employee in mobile app

            // Status & Approval
            // 0: PENDING, 1: APPROVED, 2: REJECTED
            $table->tinyInteger('status')->default(0);

            $table->foreignId('approved_by_id')
                ->nullable()
                ->constrained('employees')
                ->nullOnDelete();

            $table->timestamp('approved_at')->nullable();
            $table->text('note')->nullable(); // Note if rejected or if there is a revision

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
