<?php

declare(strict_types=1);

use App\Enums\AlertType;
use App\Enums\StockBatchStatus;
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
        // 1. Tạo bảng quản lý lô hàng & hạn sử dụng (stock_batches)
        if (! Schema::hasTable(ConstantHelper::TABLE_STOCK_BATCHES)) {
            Schema::create(ConstantHelper::TABLE_STOCK_BATCHES, function (Blueprint $table) {
                $table->id()->comment('Mã định danh lô nguyên liệu');
                $table->string('batch_code', 50)->comment('Mã số lô hàng (vd: LOT-20261121-001)');
                $table->foreignId('warehouse_id')->constrained(ConstantHelper::TABLE_WAREHOUSES)->restrictOnDelete()->comment('Kho lưu trữ');
                $table->foreignId('ingredient_id')->constrained(ConstantHelper::TABLE_INGREDIENTS)->restrictOnDelete()->comment('Nguyên liệu');
                $table->foreignId('purchase_order_id')->nullable()->constrained(ConstantHelper::TABLE_PURCHASE_ORDERS)->nullOnDelete()->comment('Đơn nhập hàng liên kết');
                $table->foreignId('supplier_id')->nullable()->constrained(ConstantHelper::TABLE_SUPPLIERS)->nullOnDelete()->comment('Nhà cung cấp');
                $table->decimal('initial_quantity', 15, 3)->comment('Số lượng nhập ban đầu theo UOM');
                $table->decimal('current_quantity', 15, 3)->comment('Số lượng tồn hiện tại của lô');
                $table->decimal('unit_cost', 15, 2)->default(0)->comment('Đơn giá nhập của lô (VND)');
                $table->date('import_date')->comment('Ngày nhập kho');
                $table->date('expiry_date')->comment('Hạn sử dụng của lô');
                $table->enum('status', StockBatchStatus::values())->default(StockBatchStatus::ACTIVE->value)->comment('Trạng thái lô hàng');
                $table->string('note', 255)->nullable()->comment('Ghi chú tình trạng bảo quản');
                $table->foreignId('created_by')->nullable()->constrained(ConstantHelper::TABLE_USERS)->nullOnDelete()->comment('Người ghi nhận');
                $table->dateTime('created_at')->nullable()->comment('Thời điểm tạo');
                $table->dateTime('updated_at')->nullable()->comment('Thời điểm cập nhật');

                // Compound indexes tối ưu hiệu năng truy vấn FEFO và Cronjob
                $table->index(['warehouse_id', 'ingredient_id', 'status', 'expiry_date'], 'idx_batches_wh_ing_status_exp');
                $table->index(['expiry_date', 'status'], 'idx_batches_expiry_status');
                $table->index('batch_code', 'idx_batches_code');
            });

            DB::statement(sprintf("ALTER TABLE `%s` COMMENT = 'Quản lý lô nguyên vật liệu và theo dõi hạn sử dụng (Batch & Expiry)'", ConstantHelper::TABLE_STOCK_BATCHES));
        }

        // 2. Bổ sung batch_no & expiry_date vào purchase_order_items (nếu chưa có)
        if (Schema::hasTable(ConstantHelper::TABLE_PURCHASE_ORDER_ITEMS)) {
            Schema::table(ConstantHelper::TABLE_PURCHASE_ORDER_ITEMS, function (Blueprint $table) {
                if (! Schema::hasColumn(ConstantHelper::TABLE_PURCHASE_ORDER_ITEMS, 'batch_no')) {
                    $table->string('batch_no', 50)->nullable()->after('received_qty')->comment('Số lô nhà cung cấp');
                }
                if (! Schema::hasColumn(ConstantHelper::TABLE_PURCHASE_ORDER_ITEMS, 'expiry_date')) {
                    $table->date('expiry_date')->nullable()->after('batch_no')->comment('Hạn sử dụng nhà cung cấp cam kết');
                }
            });
        }

        // 3. Bổ sung batch_id vào stock_movements (nếu chưa có)
        if (Schema::hasTable(ConstantHelper::TABLE_STOCK_MOVEMENTS)) {
            Schema::table(ConstantHelper::TABLE_STOCK_MOVEMENTS, function (Blueprint $table) {
                if (! Schema::hasColumn(ConstantHelper::TABLE_STOCK_MOVEMENTS, 'batch_id')) {
                    $table->foreignId('batch_id')->nullable()->after('ingredient_id')->constrained(ConstantHelper::TABLE_STOCK_BATCHES)->nullOnDelete()->comment('Lô hàng phát sinh biến động');
                }
            });
        }

        // 4. Cập nhật ENUM của ai_alerts để hỗ trợ đầy đủ các loại cảnh báo tồn kho và HSD mới
        if (Schema::hasTable(ConstantHelper::TABLE_AI_ALERTS)) {
            $enumValues = implode("', '", AlertType::values());
            DB::statement(sprintf("ALTER TABLE `%s` MODIFY COLUMN `alert_type` ENUM('%s') NOT NULL COMMENT 'Loại cảnh báo'", ConstantHelper::TABLE_AI_ALERTS, $enumValues));
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable(ConstantHelper::TABLE_STOCK_MOVEMENTS) && Schema::hasColumn(ConstantHelper::TABLE_STOCK_MOVEMENTS, 'batch_id')) {
            Schema::table(ConstantHelper::TABLE_STOCK_MOVEMENTS, function (Blueprint $table) {
                $table->dropForeign(['batch_id']);
                $table->dropColumn('batch_id');
            });
        }

        if (Schema::hasTable(ConstantHelper::TABLE_PURCHASE_ORDER_ITEMS)) {
            Schema::table(ConstantHelper::TABLE_PURCHASE_ORDER_ITEMS, function (Blueprint $table) {
                if (Schema::hasColumn(ConstantHelper::TABLE_PURCHASE_ORDER_ITEMS, 'expiry_date')) {
                    $table->dropColumn('expiry_date');
                }
                if (Schema::hasColumn(ConstantHelper::TABLE_PURCHASE_ORDER_ITEMS, 'batch_no')) {
                    $table->dropColumn('batch_no');
                }
            });
        }

        Schema::dropIfExists(ConstantHelper::TABLE_STOCK_BATCHES);
    }
};
