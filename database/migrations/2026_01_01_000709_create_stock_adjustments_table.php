<?php

declare(strict_types=1);

use App\Enums\AdjustmentReason;
use App\Enums\AdjustmentStatus;
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
        Schema::create(ConstantHelper::TABLE_STOCK_ADJUSTMENTS, function (Blueprint $table) {
            $table->id()->comment('Mã định danh phiếu kiểm kê / điều chỉnh');
            $table->foreignId('warehouse_id')->constrained(ConstantHelper::TABLE_WAREHOUSES)->restrictOnDelete()->comment('Kho thực hiện kiểm kê');
            $table->string('adjustment_no', 30)->unique()->comment('Mã số phiếu kiểm kê duy nhất');
            $table->enum('reason', AdjustmentReason::values())->comment('Lý do kiểm kê / điều chỉnh (stocktake, waste, damage, correction, other)');
            $table->string('note', 255)->nullable()->comment('Ghi chú chi tiết');
            $table->enum('status', AdjustmentStatus::values())->default(AdjustmentStatus::DRAFT->value)->comment('Trạng thái phiếu kiểm kê (draft, pending_approval, approved, rejected)');
            $table->foreignId('created_by')->constrained(ConstantHelper::TABLE_USERS)->restrictOnDelete()->comment('Người lập phiếu');
            $table->foreignId('approved_by')->nullable()->constrained(ConstantHelper::TABLE_USERS)->restrictOnDelete()->comment('Người phê duyệt điều chỉnh');
            $table->dateTime('approved_at')->nullable()->comment('Thời điểm phê duyệt');
            $table->dateTime('created_at')->nullable()->comment('Thời điểm tạo bản ghi');
            $table->dateTime('updated_at')->nullable()->comment('Thời điểm cập nhật bản ghi');
        });

        DB::statement(sprintf("ALTER TABLE `%s` COMMENT = 'Phiếu kiểm kê và điều chỉnh kho'", ConstantHelper::TABLE_STOCK_ADJUSTMENTS));
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists(ConstantHelper::TABLE_STOCK_ADJUSTMENTS);
    }
};
