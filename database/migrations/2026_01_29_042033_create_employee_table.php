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

            // Primary identity
            $table->string('nik')->unique();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();

            // Organizational structure
            $table->foreignId('team_id')->constrained('teams')->cascadeOnDelete();
            $table->foreignId('position_id')->constrained('positions')->cascadeOnDelete();

            // Direct supervisor (self relation)
            $table->foreignId('manager_id')
                ->nullable()
                ->constrained('employees')
                ->nullOnDelete();

            // Employee status (0=permanent, 1=contract, 2=intern, resigned handled by resign_date)
            $table->tinyInteger('employee_status')->default(3);

            // Contract details (specifically for contract/intern)
            $table->date('contract_start')->nullable();
            $table->date('contract_end')->nullable();

            // Base salary (can be null if following position or unpaid intern)
            $table->decimal('base_salary', 15, 2)->nullable();

            // Personal data
            $table->string('phone')->nullable()->unique();
            $table->enum('gender', ['male', 'female']);
            $table->date('date_of_birth')->nullable();
            $table->text('address');

            // Employment data
            $table->date('join_date');
            $table->date('resign_date')->nullable();
            $table->enum('employment_state', ['active', 'resigned', 'terminated'])->default('active');
            $table->date('termination_date')->nullable();
            $table->text('termination_reason')->nullable();

            $table->foreignId('created_by_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->foreignId('updated_by_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->foreignId('deleted_by_id')
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
