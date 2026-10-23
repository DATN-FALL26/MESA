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
            $table->string('name');                                     // "Bàn 1", "Bàn 2"
            $table->string('zone')->default('Tầng 1');                  // Khu vực / Tầng
            $table->unsignedTinyInteger('capacity')->default(4);         // Sức chứa số chỗ
            $table->enum('status', ['empty', 'occupied', 'cleaning'])->default('empty'); // Trạng thái bàn
            $table->timestamps();

            // Index giúp tối ưu tốc độ query khi lọc bàn theo khu vực & trạng thái
            $table->index(['zone', 'status']);
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
