<?php

declare(strict_types=1);

use App\Helpers\ConstantHelper;
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
        // 1. Bổ sung các trường lưu vết Giá vốn (COGS) và Lợi nhuận gộp trên từng dòng OrderItem
        Schema::table(ConstantHelper::TABLE_ORDER_ITEMS, function (Blueprint $table) {
            $table->decimal('cost_price_snapshot', 15, 2)->nullable()->after('line_total')
                ->comment('Đơn giá vốn nguyên liệu 1 suất món ăn tại thời điểm trừ kho (VND)');
            $table->decimal('cogs_total', 15, 2)->nullable()->after('cost_price_snapshot')
                ->comment('Tổng giá vốn dòng món ăn đã nhân số lượng và topping (VND)');
            $table->decimal('gross_profit', 15, 2)->nullable()->after('cogs_total')
                ->comment('Lợi nhuận gộp của dòng món ăn (line_total - cogs_total) (VND)');
        });

        // 2. Bổ sung giá vốn và lợi nhuận vào bảng tổng hợp món bán hàng ngày (DailyItemSales)
        Schema::table(ConstantHelper::TABLE_DAILY_ITEM_SALES, function (Blueprint $table) {
            $table->decimal('total_cogs', 15, 2)->default(0)->after('gross_revenue')
                ->comment('Tổng giá vốn nguyên liệu của món đã bán trong ngày (VND)');
            $table->decimal('gross_profit', 15, 2)->default(0)->after('total_cogs')
                ->comment('Lợi nhuận gộp của món trong ngày (gross_revenue - total_cogs) (VND)');
        });

        // 3. Bổ sung giá vốn và lợi nhuận vào bảng tổng hợp doanh số ngày (DailySalesSummary)
        Schema::table(ConstantHelper::TABLE_DAILY_SALES_SUMMARY, function (Blueprint $table) {
            $table->decimal('total_cogs', 15, 2)->default(0)->after('net_revenue')
                ->comment('Tổng giá vốn hàng bán trong ngày (VND)');
            $table->decimal('gross_profit', 15, 2)->default(0)->after('total_cogs')
                ->comment('Tổng lợi nhuận gộp thực tế trong ngày (net_revenue - total_cogs) (VND)');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table(ConstantHelper::TABLE_DAILY_SALES_SUMMARY, function (Blueprint $table) {
            $table->dropColumn(['total_cogs', 'gross_profit']);
        });

        Schema::table(ConstantHelper::TABLE_DAILY_ITEM_SALES, function (Blueprint $table) {
            $table->dropColumn(['total_cogs', 'gross_profit']);
        });

        Schema::table(ConstantHelper::TABLE_ORDER_ITEMS, function (Blueprint $table) {
            $table->dropColumn(['cost_price_snapshot', 'cogs_total', 'gross_profit']);
        });
    }
};
