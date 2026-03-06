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
        Schema::create('leave_approvals', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('leave_id')->constrained()->cascadeOnDelete();
            $table->foreignId('approver_id')->nullable()->constrained('employees'); // manager or HR
            $table->tinyInteger('level'); // 0=Manager, 1=HR
            $table->tinyInteger('status')->default(0); // PENDING=0, APPROVED=1, REJECTED=2
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
        Schema::dropIfExists('leave_approvals');
    }
};
