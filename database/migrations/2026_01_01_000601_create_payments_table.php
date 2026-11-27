<?php

declare(strict_types=1);

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
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
        Schema::create(ConstantHelper::TABLE_PAYMENTS, function (Blueprint $table) {
            $table->id()->comment('Mã định danh giao dịch thanh toán');
            $table->foreignId('branch_id')->constrained(ConstantHelper::TABLE_BRANCHES)->restrictOnDelete()->comment('Chi nhánh giao dịch');
            $table->foreignId('order_id')->constrained(ConstantHelper::TABLE_ORDERS)->restrictOnDelete()->comment('Đơn hàng cần thanh toán');
            $table->enum('method', PaymentMethod::values())->comment('Phương thức thanh toán (cash, qr, card)');
            $table->decimal('amount', 15, 0)->comment('Số tiền thanh toán (VND)');
            $table->decimal('tendered_amount', 15, 0)->nullable()->comment('Số tiền khách đưa (VND)');
            $table->decimal('change_amount', 15, 0)->nullable()->comment('Số tiền thừa trả lại khách (VND)');
            $table->enum('status', PaymentStatus::values())->default(PaymentStatus::PENDING->value)->comment('Trạng thái thanh toán');
            $table->string('gateway_txn_id', 100)->nullable()->unique()->comment('Mã giao dịch từ cổng thanh toán / ngân hàng');
            $table->json('gateway_payload')->nullable()->comment('Dữ liệu phản hồi từ cổng thanh toán dạng JSON');
            $table->foreignId('confirmed_by')->nullable()->constrained(ConstantHelper::TABLE_USERS)->restrictOnDelete()->comment('Nhân viên xác nhận thu tiền');
            $table->dateTime('paid_at')->nullable()->comment('Thời điểm thanh toán thành công');
            $table->dateTime('created_at')->nullable()->comment('Thời điểm tạo bản ghi');
            $table->dateTime('updated_at')->nullable()->comment('Thời điểm cập nhật bản ghi');

            $table->index('order_id', 'idx_payments_order');
            $table->index(['branch_id', 'paid_at'], 'idx_payments_branch_paid');
        });

        DB::statement(sprintf("ALTER TABLE `%s` COMMENT = 'Giao dịch thanh toán'", ConstantHelper::TABLE_PAYMENTS));
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists(ConstantHelper::TABLE_PAYMENTS);
    }
};
