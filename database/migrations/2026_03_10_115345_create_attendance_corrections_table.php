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
        Schema::create('attendance_corrections', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('attendance_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained();

            // Data yang ingin diubah
            $table->dateTime('clock_in_requested')->nullable();
            $table->dateTime('clock_out_requested')->nullable();

            $table->text('reason');
            $table->string('attachment')->nullable();

            $table->integer('status')->default(0); // 0: Pending, 1: Approved, 2: Rejected
            $table->foreignId('approver_id')->nullable()->constrained('employees');
            $table->timestamp('approved_at')->nullable();
            $table->text('note')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendance_corrections');
    }
};
