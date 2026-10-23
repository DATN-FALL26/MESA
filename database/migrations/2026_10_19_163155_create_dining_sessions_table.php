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
        Schema::create('dining_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('table_id')->constrained('tables')->onDelete('cascade');
            $table->unsignedSmallInteger('customer_count')->default(1);
            $table->unsignedBigInteger('employee_id')->nullable(); // Nhân viên phục vụ
            $table->enum('status', ['open', 'closed'])->default('open');
            $table->timestamp('opened_at')->useCurrent();
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();

            $table->index(['table_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dining_sessions');
    }
};
