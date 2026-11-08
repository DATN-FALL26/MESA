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
        Schema::create(ConstantHelper::TABLE_DAILY_ITEM_SALES, function (Blueprint $table) {
            $table->id()->comment('Mã định danh báo cáo món bán ngày');
            $table->foreignId('branch_id')->constrained(ConstantHelper::TABLE_BRANCHES)->restrictOnDelete()->comment('Chi nhánh');
            $table->date('sales_date')->comment('Ngày bán hàng');
            $table->foreignId('menu_item_id')->constrained(ConstantHelper::TABLE_MENU_ITEMS)->restrictOnDelete()->comment('Món ăn');
            $table->integer('quantity')->default(0)->comment('Số lượng món đã bán');
            $table->decimal('gross_revenue', 15, 0)->default(0)->comment('Tổng doanh thu món trước chiết khấu (VND)');
            $table->dateTime('created_at')->nullable()->comment('Thời điểm tạo bản ghi');
            $table->dateTime('updated_at')->nullable()->comment('Thời điểm cập nhật bản ghi');

            $table->unique(['branch_id', 'sales_date', 'menu_item_id'], 'uq_daily_item_sales_branch_date_item');
            $table->index(['branch_id', 'sales_date', 'quantity'], 'idx_daily_item_sales_branch_date_qty');
        });

        DB::statement(sprintf("ALTER TABLE `%s` COMMENT = 'Báo cáo thống kê món bán hàng ngày (Món bán chạy)'", ConstantHelper::TABLE_DAILY_ITEM_SALES));
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists(ConstantHelper::TABLE_DAILY_ITEM_SALES);
    }
};
