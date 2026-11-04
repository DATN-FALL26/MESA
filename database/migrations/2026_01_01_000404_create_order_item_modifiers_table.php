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
        Schema::create(ConstantHelper::TABLE_ORDER_ITEM_MODIFIERS, function (Blueprint $table) {
            $table->id()->comment('Mã định danh tùy chọn của món gọi');
            $table->foreignId('order_item_id')->constrained(ConstantHelper::TABLE_ORDER_ITEMS)->restrictOnDelete()->comment('Mã dòng món trực thuộc');
            $table->foreignId('modifier_id')->constrained(ConstantHelper::TABLE_MODIFIERS)->restrictOnDelete()->comment('Mã tùy chọn');
            $table->string('name_snapshot', 100)->comment('Ảnh chụp tên tùy chọn tại thời điểm gọi');
            $table->decimal('price_snapshot', 15, 0)->default(0)->comment('Ảnh chụp đơn giá tùy chọn tại thời điểm gọi (VND)');
            $table->unsignedSmallInteger('quantity')->default(1)->comment('Số lượng tùy chọn');
            $table->dateTime('created_at')->nullable()->comment('Thời điểm tạo bản ghi');
            $table->dateTime('updated_at')->nullable()->comment('Thời điểm cập nhật bản ghi');

            $table->index('order_item_id', 'idx_order_item_modifiers_item');
        });

        DB::statement(sprintf("ALTER TABLE `%s` COMMENT = 'Tùy chọn kèm theo món gọi'", ConstantHelper::TABLE_ORDER_ITEM_MODIFIERS));
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists(ConstantHelper::TABLE_ORDER_ITEM_MODIFIERS);
    }
};
