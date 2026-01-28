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
        Schema::create('allowances', function (Blueprint $table) {
            $table->id();
            $table->uuid()->unique();
            $table->string('name', 100);
            $table->enum('type', ['fixed', 'percentage']);
            $table->decimal('amount', 15, 2);
            $table->foreignId('created_by_id')
                ->constrained('users')
                ->cascadeOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('allowances');
    }
};
