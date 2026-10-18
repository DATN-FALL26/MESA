<?php

declare(strict_types=1);

use App\Enums\OrderChannel;
use App\Enums\OrderStatus;
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
        Schema::create(ConstantHelper::TABLE_ORDERS, function (Blueprint $table) {
            $table->id()->comment('Mã định danh đơn hàng');
            $table->foreignId('branch_id')->constrained(ConstantHelper::TABLE_BRANCHES)->restrictOnDelete()->comment('Chi nhánh phát sinh đơn');
            $table->foreignId('session_id')->nullable()->constrained(ConstantHelper::TABLE_DINING_SESSIONS)->restrictOnDelete()->comment('Lượt dùng bữa (NULL với đơn mang đi)');
            $table->string('order_no', 30)->comment('Mã số đơn hàng duy nhất trong chi nhánh');
            $table->enum('channel', OrderChannel::values())->default(OrderChannel::DINE_IN->value)->comment('Kênh bán (dine_in, takeaway)');
            $table->enum('status', OrderStatus::values())->default(OrderStatus::DRAFT->value)->comment('Trạng thái đơn hàng');
            $table->decimal('subtotal', 15, 0)->default(0)->comment('Tổng tiền hàng trước chiết khấu và thuế (VND)');
            $table->decimal('discount_amount', 15, 0)->default(0)->comment('Số tiền chiết khấu (VND)');
            $table->decimal('tax_amount', 15, 0)->default(0)->comment('Tiền thuế VAT (VND)');
            $table->decimal('total_amount', 15, 0)->default(0)->comment('Tổng tiền thanh toán sau thuế và chiết khấu (VND)');
            $table->string('customer_name', 150)->nullable()->comment('Tên khách hàng');
            $table->string('customer_phone', 20)->nullable()->comment('Số điện thoại khách hàng');
            $table->string('note', 255)->nullable()->comment('Ghi chú đơn hàng');
            $table->foreignId('created_by')->constrained(ConstantHelper::TABLE_USERS)->restrictOnDelete()->comment('Nhân viên tạo đơn');
            $table->foreignId('payment_requested_by')->nullable()->constrained(ConstantHelper::TABLE_USERS)->restrictOnDelete()->comment('Nhân viên gửi yêu cầu thanh toán');
            $table->dateTime('payment_requested_at')->nullable()->comment('Thời điểm yêu cầu thanh toán');
            $table->foreignId('cancelled_by')->nullable()->constrained(ConstantHelper::TABLE_USERS)->restrictOnDelete()->comment('Người hủy đơn');
            $table->string('cancel_reason', 255)->nullable()->comment('Lý do hủy đơn');
            $table->dateTime('cancelled_at')->nullable()->comment('Thời điểm hủy đơn');
            $table->dateTime('completed_at')->nullable()->comment('Thời điểm hoàn thành đơn');
            $table->dateTime('created_at')->nullable()->comment('Thời điểm tạo bản ghi');
            $table->dateTime('updated_at')->nullable()->comment('Thời điểm cập nhật bản ghi');

            $table->unique(['branch_id', 'order_no'], 'uq_orders_branch_order_no');
            $table->index(['branch_id', 'status'], 'idx_orders_branch_status');
            $table->index(['branch_id', 'created_at'], 'idx_orders_branch_created');
            $table->index('session_id', 'idx_orders_session');
        });

        DB::statement(sprintf("ALTER TABLE `%s` COMMENT = 'Đơn gọi món'", ConstantHelper::TABLE_ORDERS));
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists(ConstantHelper::TABLE_ORDERS);
    }
};
