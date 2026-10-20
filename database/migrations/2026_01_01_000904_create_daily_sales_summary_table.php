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
        Schema::create(ConstantHelper::TABLE_DAILY_SALES_SUMMARY, function (Blueprint $table) {
            $table->id()->comment('Mã định danh tổng hợp doanh thu ngày');
            $table->foreignId('branch_id')->constrained(ConstantHelper::TABLE_BRANCHES)->restrictOnDelete()->comment('Chi nhánh');
            $table->date('sales_date')->comment('Ngày bán hàng');
            $table->integer('order_count')->default(0)->comment('Tổng số đơn hoàn tất trong ngày');
            $table->integer('guest_count')->default(0)->comment('Tổng số lượt khách phục vụ');
            $table->decimal('gross_revenue', 15, 0)->default(0)->comment('Doanh thu trước chiết khấu (VND)');
            $table->decimal('discount_amount', 15, 0)->default(0)->comment('Tổng tiền chiết khấu (VND)');
            $table->decimal('net_revenue', 15, 0)->default(0)->comment('Doanh thu thực tế sau chiết khấu (VND)');
            $table->json('payment_breakdown')->nullable()->comment('Cơ cấu doanh thu theo phương thức thanh toán ({cash, qr, card})');
            $table->dateTime('created_at')->nullable()->comment('Thời điểm tạo bản ghi');
            $table->dateTime('updated_at')->nullable()->comment('Thời điểm cập nhật bản ghi');

            $table->unique(['branch_id', 'sales_date'], 'uq_daily_sales_summary_branch_date');
        });

        DB::statement(sprintf("ALTER TABLE `%s` COMMENT = 'Tổng hợp doanh số bán hàng hàng ngày (Dashboard)'", ConstantHelper::TABLE_DAILY_SALES_SUMMARY));
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists(ConstantHelper::TABLE_DAILY_SALES_SUMMARY);
    }
};
