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
        Schema::create('tables', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete(); // Thuộc cơ sở nào
            $table->string('table_number'); // Số hiệu bàn (Ví dụ: Bàn 01, VIP 02)
            $table->integer('capacity')->default(4); // Số chỗ ngồi
            $table->string('status')->default('empty'); // Trạng thái: empty (trống), busy (đang dùng), reserved (đặt trước)
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tables');
    }
};
