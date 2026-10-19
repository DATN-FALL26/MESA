<?php

declare(strict_types=1);

use App\Enums\MovementType;
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
        Schema::create(ConstantHelper::TABLE_STOCK_MOVEMENTS, function (Blueprint $table) {
            $table->id()->comment('Mã định danh biến động kho');
            $table->foreignId('warehouse_id')->constrained(ConstantHelper::TABLE_WAREHOUSES)->restrictOnDelete()->comment('Mã kho');
            $table->foreignId('ingredient_id')->constrained(ConstantHelper::TABLE_INGREDIENTS)->restrictOnDelete()->comment('Mã nguyên liệu');
            $table->enum('movement_type', MovementType::values())->comment('Loại biến động kho (purchase_in, sale_out, sale_return, adjust, waste)');
            $table->decimal('quantity', 15, 3)->comment('Số lượng biến động (dương = nhập, âm = xuất)');
            $table->decimal('unit_cost', 15, 2)->nullable()->comment('Đơn giá vốn xuất/nhập tại thời điểm biến động');
            $table->string('ref_type', 30)->nullable()->comment('Loại chứng từ tham chiếu (order_item, purchase_order, stock_adjustment)');
            $table->unsignedBigInteger('ref_id')->nullable()->comment('Mã định danh chứng từ tham chiếu');
            $table->string('idempotency_key', 100)->nullable()->unique()->comment('Khóa chống ghi nhận biến động trùng lặp');
            $table->string('note', 255)->nullable()->comment('Ghi chú biến động kho');
            $table->foreignId('created_by')->nullable()->constrained(ConstantHelper::TABLE_USERS)->restrictOnDelete()->comment('Người thực hiện');
            $table->dateTime('created_at')->nullable()->comment('Thời điểm ghi nhận biến động');

            $table->index(['warehouse_id', 'ingredient_id', 'created_at'], 'idx_stock_movements_wh_ing_date');
            $table->index(['ref_type', 'ref_id'], 'idx_stock_movements_ref');
        });

        DB::statement(sprintf("ALTER TABLE `%s` COMMENT = 'Sổ cái biến động kho (chỉ thêm không sửa/xóa)'", ConstantHelper::TABLE_STOCK_MOVEMENTS));
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists(ConstantHelper::TABLE_STOCK_MOVEMENTS);
    }
};
