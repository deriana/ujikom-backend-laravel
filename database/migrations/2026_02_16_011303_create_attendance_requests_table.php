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

            // Relationship to Employee
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();

            // Request Type: Change Shift (SHIFT) or Change Work Mode (WORK_MODE)
            $table->enum('request_type', ['SHIFT', 'WORK_MODE']);

            // Required if request_type = SHIFT
            $table->foreignId('shift_template_id')
                ->nullable()
                ->constrained('shift_templates')
                ->nullOnDelete();

            // Required if request_type = WORK_MODE (for Work Schedule)
            $table->foreignId('work_schedules_id')
                ->nullable()
                ->constrained('work_schedules')
                ->nullOnDelete();

            // Date range for the change
            $table->date('start_date');
            $table->date('end_date')->nullable(); // Can be null if requesting a shift change for only 1 day

            // Reason from employee
            $table->text('reason')->nullable();

            // Approval Workflow: Status & Approval
            // 0: PENDING, 1: APPROVED, 2: REJECTED
            $table->tinyInteger('status')->default(0);

            // Approver identity
            $table->foreignId('approved_by_id')
                ->nullable()
                ->constrained('employees')
                ->nullOnDelete();

            $table->text('note')->nullable(); // Note if rejected

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
