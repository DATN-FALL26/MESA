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
        Schema::create(ConstantHelper::TABLE_ORDER_STATUS_HISTORY, function (Blueprint $table) {
            $table->id()->comment('Mã định danh bản ghi lịch sử trạng thái');
            $table->foreignId('order_id')->constrained(ConstantHelper::TABLE_ORDERS)->restrictOnDelete()->comment('Đơn gọi món');
            $table->foreignId('order_item_id')->nullable()->constrained(ConstantHelper::TABLE_ORDER_ITEMS)->restrictOnDelete()->comment('Chi tiết món ăn (nếu thay đổi mức món)');
            $table->foreignId('batch_id')->nullable()->constrained(ConstantHelper::TABLE_ORDER_BATCHES)->restrictOnDelete()->comment('Đợt gọi món (nếu thay đổi mức đợt)');
            $table->string('from_status', 20)->nullable()->comment('Trạng thái trước khi chuyển đổi');
            $table->string('to_status', 20)->comment('Trạng thái sau khi chuyển đổi');
            $table->foreignId('changed_by')->nullable()->constrained(ConstantHelper::TABLE_USERS)->restrictOnDelete()->comment('Người thực hiện thay đổi');
            $table->string('note', 255)->nullable()->comment('Ghi chú lý do thay đổi');
            $table->dateTime('created_at')->nullable()->comment('Thời điểm ghi nhận thay đổi');

            $table->index(['order_id', 'created_at'], 'idx_order_status_history_order_date');
        });

        DB::statement(sprintf("ALTER TABLE `%s` COMMENT = 'Lịch sử thay đổi trạng thái đơn và món'", ConstantHelper::TABLE_ORDER_STATUS_HISTORY));
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists(ConstantHelper::TABLE_ORDER_STATUS_HISTORY);
    }
};
