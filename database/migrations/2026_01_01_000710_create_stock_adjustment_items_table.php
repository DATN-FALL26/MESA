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
        Schema::create(ConstantHelper::TABLE_STOCK_ADJUSTMENT_ITEMS, function (Blueprint $table) {
            $table->id()->comment('Mã định danh dòng kiểm kê');
            $table->foreignId('adjustment_id')->constrained(ConstantHelper::TABLE_STOCK_ADJUSTMENTS)->cascadeOnDelete()->comment('Phiếu kiểm kê trực thuộc');
            $table->foreignId('ingredient_id')->constrained(ConstantHelper::TABLE_INGREDIENTS)->restrictOnDelete()->comment('Nguyên liệu kiểm kê');
            $table->decimal('system_qty', 15, 3)->comment('Số lượng sổ sách hệ thống tại thời điểm kiểm');
            $table->decimal('actual_qty', 15, 3)->comment('Số lượng thực tế đếm được');
            $table->string('note', 255)->nullable()->comment('Ghi chú lý do chênh lệch');
            $table->dateTime('created_at')->nullable()->comment('Thời điểm tạo bản ghi');
            $table->dateTime('updated_at')->nullable()->comment('Thời điểm cập nhật bản ghi');
        });

        DB::statement(sprintf("ALTER TABLE `%s` COMMENT = 'Chi tiết mặt hàng kiểm kê điều chỉnh'", ConstantHelper::TABLE_STOCK_ADJUSTMENT_ITEMS));
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists(ConstantHelper::TABLE_STOCK_ADJUSTMENT_ITEMS);
    }
};
