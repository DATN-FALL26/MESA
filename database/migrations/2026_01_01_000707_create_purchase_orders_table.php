<?php

declare(strict_types=1);

use App\Enums\PurchaseOrderSource;
use App\Enums\PurchaseOrderStatus;
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
        Schema::create(ConstantHelper::TABLE_PURCHASE_ORDERS, function (Blueprint $table) {
            $table->id()->comment('Mã định danh đơn nhập hàng');
            $table->foreignId('branch_id')->constrained(ConstantHelper::TABLE_BRANCHES)->restrictOnDelete()->comment('Chi nhánh đặt hàng');
            $table->foreignId('warehouse_id')->constrained(ConstantHelper::TABLE_WAREHOUSES)->restrictOnDelete()->comment('Kho tiếp nhận hàng nhập');
            $table->foreignId('supplier_id')->nullable()->constrained(ConstantHelper::TABLE_SUPPLIERS)->restrictOnDelete()->comment('Nhà cung cấp');
            $table->string('po_no', 30)->comment('Mã số đơn nhập hàng duy nhất theo chi nhánh');
            $table->enum('status', PurchaseOrderStatus::values())->default(PurchaseOrderStatus::DRAFT->value)->comment('Trạng thái đơn nhập');
            $table->enum('source', PurchaseOrderSource::values())->default(PurchaseOrderSource::MANUAL->value)->comment('Nguồn gốc tạo đơn (manual, ai_suggestion)');
            $table->string('note', 255)->nullable()->comment('Ghi chú đơn nhập');
            $table->foreignId('created_by')->constrained(ConstantHelper::TABLE_USERS)->restrictOnDelete()->comment('Nhân viên tạo đơn');
            $table->foreignId('approved_by')->nullable()->constrained(ConstantHelper::TABLE_USERS)->restrictOnDelete()->comment('Người phê duyệt đơn nhập');
            $table->dateTime('approved_at')->nullable()->comment('Thời điểm phê duyệt đơn nhập');
            $table->foreignId('received_by')->nullable()->constrained(ConstantHelper::TABLE_USERS)->restrictOnDelete()->comment('Thủ kho xác nhận nhập');
            $table->dateTime('received_at')->nullable()->comment('Thời điểm thực tế nhập kho');
            $table->dateTime('created_at')->nullable()->comment('Thời điểm tạo bản ghi');
            $table->dateTime('updated_at')->nullable()->comment('Thời điểm cập nhật bản ghi');

            $table->unique(['branch_id', 'po_no'], 'uq_purchase_orders_branch_po_no');
        });

        DB::statement(sprintf("ALTER TABLE `%s` COMMENT = 'Phiếu đặt và nhập hàng từ nhà cung cấp'", ConstantHelper::TABLE_PURCHASE_ORDERS));
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists(ConstantHelper::TABLE_PURCHASE_ORDERS);
    }
};
