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
        Schema::create('payrolls', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique()->index();

            $table->foreignId('employee_id')
                ->constrained('employees')
                ->cascadeOnDelete();

            // Periode payroll
            $table->date('period_start');
            $table->date('period_end');

            // Snapshot salary
            $table->decimal('base_salary', 15, 2)->default(0);
            $table->decimal('allowance_total', 15, 2)->default(0);
            $table->decimal('overtime_pay', 15, 2)->default(0);
            $table->decimal('assessment_bonus', 15, 2)->default(0);

            // Optional adjustment by finance
            $table->decimal('manual_adjustment', 15, 2)->default(0);
            $table->text('adjustment_note')->nullable();

            // Final amount
            $table->decimal('gross_salary', 15, 2)->default(0);

            // Status
            // 0 = draft
            // 1 = finalized
            // 2 = void
            $table->tinyInteger('status')->default(0);

            $table->decimal('late_deduction', 15, 2)->default(0);
            $table->decimal('early_leave_deduction', 15, 2)->default(0);
            $table->decimal('total_deduction', 15, 2)->default(0);
            $table->decimal('ptkp', 15, 2)->default(0);
            $table->decimal('tax_rate', 15, 2)->default(0);
            $table->decimal('taxable_income', 15, 2)->default(0);
            $table->decimal('tax_amount', 15, 2)->default(0);
            $table->decimal('net_salary', 15, 2)->default(0);
            $table->string('slip_path')->nullable();
            $table->timestamp('slip_generated_at')->nullable();

            $table->boolean('is_void')->default(false);
            $table->text('void_note')->nullable();
            $table->foreignId('created_by_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->foreignId('updated_by_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamp('finalized_at')->nullable();

            $table->timestamps();

            $table->index(['employee_id', 'period_start', 'period_end']);
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payrolls');
    }
};
