<?php

declare(strict_types=1);

use App\Enums\OrderItemStatus;
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
        Schema::create(ConstantHelper::TABLE_ORDER_ITEMS, function (Blueprint $table) {
            $table->id()->comment('Mã định danh dòng món trong đơn');
            $table->foreignId('order_id')->constrained(ConstantHelper::TABLE_ORDERS)->restrictOnDelete()->comment('Đơn gọi món trực thuộc');
            $table->foreignId('batch_id')->nullable()->constrained(ConstantHelper::TABLE_ORDER_BATCHES)->restrictOnDelete()->comment('Đợt gọi món trực thuộc');
            $table->foreignId('menu_item_id')->constrained(ConstantHelper::TABLE_MENU_ITEMS)->restrictOnDelete()->comment('Món ăn đã gọi');
            $table->foreignId('variant_id')->nullable()->constrained(ConstantHelper::TABLE_ITEM_VARIANTS)->restrictOnDelete()->comment('Biến thể kích cỡ');
            $table->string('item_name_snapshot', 150)->comment('Ảnh chụp tên món tại thời điểm gọi');
            $table->string('variant_name_snapshot', 50)->nullable()->comment('Ảnh chụp tên biến thể tại thời điểm gọi');
            $table->decimal('unit_price_snapshot', 15, 0)->comment('Ảnh chụp đơn giá bán tại thời điểm gọi (VND)');
            $table->unsignedSmallInteger('quantity')->default(1)->comment('Số lượng món');
            $table->decimal('line_total', 15, 0)->default(0)->comment('Tổng tiền dòng món sau khi nhân số lượng (VND)');
            $table->string('note', 255)->nullable()->comment('Ghi chú món');
            $table->enum('status', OrderItemStatus::values())->default(OrderItemStatus::PENDING->value)->comment('Trạng thái chế biến món');
            $table->string('cancelled_reason', 255)->nullable()->comment('Lý do hủy món');
            $table->foreignId('cancelled_by')->nullable()->constrained(ConstantHelper::TABLE_USERS)->restrictOnDelete()->comment('Người hủy món');
            $table->dateTime('cancelled_at')->nullable()->comment('Thời điểm hủy món');
            $table->dateTime('stock_deducted_at')->nullable()->comment('Thời điểm đã thực hiện trừ kho');
            $table->dateTime('created_at')->nullable()->comment('Thời điểm tạo bản ghi');
            $table->dateTime('updated_at')->nullable()->comment('Thời điểm cập nhật bản ghi');

            $table->index('order_id', 'idx_order_items_order');
            $table->index('batch_id', 'idx_order_items_batch');
        });

        DB::statement(sprintf("ALTER TABLE `%s` COMMENT = 'Chi tiết món gọi trong đơn'", ConstantHelper::TABLE_ORDER_ITEMS));
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists(ConstantHelper::TABLE_ORDER_ITEMS);
    }
};
