<?php

declare(strict_types=1);

use App\Enums\InvoiceStatus;
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
        Schema::create(ConstantHelper::TABLE_INVOICES, function (Blueprint $table) {
            $table->id()->comment('Mã định danh hóa đơn');
            $table->foreignId('branch_id')->constrained(ConstantHelper::TABLE_BRANCHES)->restrictOnDelete()->comment('Chi nhánh xuất hóa đơn');
            $table->foreignId('order_id')->constrained(ConstantHelper::TABLE_ORDERS)->restrictOnDelete()->comment('Đơn hàng tương ứng');
            $table->string('invoice_no', 30)->comment('Mã số hóa đơn');
            $table->dateTime('issued_at')->comment('Thời điểm phát hành hóa đơn');
            $table->decimal('total_amount', 15, 0)->comment('Tổng tiền trên hóa đơn (VND)');
            $table->json('buyer_info')->nullable()->comment('Thông tin người mua / xuất hóa đơn VAT dạng JSON');
            $table->enum('status', InvoiceStatus::values())->default(InvoiceStatus::ISSUED->value)->comment('Trạng thái hóa đơn (issued, void)');
            $table->unsignedSmallInteger('print_count')->default(0)->comment('Số lần đã in hóa đơn');
            $table->dateTime('created_at')->nullable()->comment('Thời điểm tạo bản ghi');
            $table->dateTime('updated_at')->nullable()->comment('Thời điểm cập nhật bản ghi');

            $table->unique(['branch_id', 'invoice_no'], 'uq_invoices_branch_invoice_no');
            $table->index('order_id', 'idx_invoices_order');
        });

        DB::statement(sprintf("ALTER TABLE `%s` COMMENT = 'Hóa đơn thanh toán'", ConstantHelper::TABLE_INVOICES));
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists(ConstantHelper::TABLE_INVOICES);
    }
};
