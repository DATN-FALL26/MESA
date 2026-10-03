<?php

declare(strict_types=1);

use App\Helpers\ConstantHelper;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create(ConstantHelper::TABLE_STOCK_LEVELS, function (Blueprint $table) {
            $table->id()->comment('Mã định danh mức tồn kho');
            $table->foreignId('warehouse_id')->constrained(ConstantHelper::TABLE_WAREHOUSES)->restrictOnDelete()->comment('Mã kho');
            $table->foreignId('ingredient_id')->constrained(ConstantHelper::TABLE_INGREDIENTS)->restrictOnDelete()->comment('Mã nguyên liệu');
            $table->decimal('quantity', 15, 3)->default(0)->comment('Số lượng tồn hiện tại theo đơn vị cơ sở');
            $table->decimal('avg_cost', 15, 2)->default(0)->comment('Giá vốn bình quân gia quyền (VND)');
            $table->dateTime('created_at')->nullable()->comment('Thời điểm tạo bản ghi');
            $table->dateTime('updated_at')->nullable()->comment('Thời điểm cập nhật bản ghi');

            $table->unique(['warehouse_id', 'ingredient_id'], 'uq_stock_levels_wh_ingredient');
        });

        DB::statement(sprintf("ALTER TABLE `%s` COMMENT = 'Mức tồn kho tức thời (bảng cache)'", ConstantHelper::TABLE_STOCK_LEVELS));
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists(ConstantHelper::TABLE_STOCK_LEVELS);
    }
};
