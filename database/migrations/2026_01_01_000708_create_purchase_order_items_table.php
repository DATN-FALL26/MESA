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
        Schema::create(ConstantHelper::TABLE_PURCHASE_ORDER_ITEMS, function (Blueprint $table) {
            $table->id()->comment('Mã định danh dòng hàng nhập');
            $table->foreignId('purchase_order_id')->constrained(ConstantHelper::TABLE_PURCHASE_ORDERS)->cascadeOnDelete()->comment('Đơn nhập hàng trực thuộc');
            $table->foreignId('ingredient_id')->constrained(ConstantHelper::TABLE_INGREDIENTS)->restrictOnDelete()->comment('Nguyên liệu nhập');
            $table->decimal('quantity', 15, 3)->comment('Số lượng đặt mua theo đơn vị cơ sở');
            $table->decimal('unit_price', 15, 2)->default(0)->comment('Đơn giá nhập mua dự kiến (VND)');
            $table->decimal('received_qty', 15, 3)->default(0)->comment('Số lượng thực tế đã nhập kho');
            $table->dateTime('created_at')->nullable()->comment('Thời điểm tạo bản ghi');
            $table->dateTime('updated_at')->nullable()->comment('Thời điểm cập nhật bản ghi');
        });

        DB::statement(sprintf("ALTER TABLE `%s` COMMENT = 'Chi tiết mặt hàng trong phiếu nhập'", ConstantHelper::TABLE_PURCHASE_ORDER_ITEMS));
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists(ConstantHelper::TABLE_PURCHASE_ORDER_ITEMS);
    }
};
