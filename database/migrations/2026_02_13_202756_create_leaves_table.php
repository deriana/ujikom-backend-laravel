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
        Schema::create('leaves', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->foreignId('leave_type_id')->constrained('leave_types');
            $table->date('date_start');
            $table->date('date_end');
            $table->text('reason')->nullable();
            $table->string('attachment')->nullable();
            $table->tinyInteger('approval_status')->default(0); // 0=PENDING, 1=APPROVED, 2=REJECTED
            $table->boolean('is_half_day')->default(false);
            $table->timestamps();
            $table->index(['employee_id']);
            $table->index(['date_start', 'date_end']);
            $table->index('approval_status');
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('leaves');
    }
};
