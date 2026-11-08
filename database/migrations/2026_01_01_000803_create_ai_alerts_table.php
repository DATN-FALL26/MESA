<?php

declare(strict_types=1);

use App\Enums\AlertSeverity;
use App\Enums\AlertStatus;
use App\Enums\AlertType;
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
        Schema::create(ConstantHelper::TABLE_AI_ALERTS, function (Blueprint $table) {
            $table->id()->comment('Mã định danh cảnh báo');
            $table->foreignId('branch_id')->constrained(ConstantHelper::TABLE_BRANCHES)->restrictOnDelete()->comment('Chi nhánh áp dụng cảnh báo');
            $table->enum('alert_type', AlertType::values())->comment('Loại cảnh báo (low_stock_risk, sales_anomaly)');
            $table->enum('severity', AlertSeverity::values())->default(AlertSeverity::INFO->value)->comment('Mức độ nghiêm trọng (info, warning, critical)');
            $table->foreignId('ingredient_id')->nullable()->constrained(ConstantHelper::TABLE_INGREDIENTS)->restrictOnDelete()->comment('Nguyên liệu liên quan (nếu cảnh báo tồn kho)');
            $table->json('payload')->nullable()->comment('Dữ liệu chi tiết cảnh báo dạng JSON');
            $table->enum('status', AlertStatus::values())->default(AlertStatus::NEW->value)->comment('Trạng thái xử lý cảnh báo');
            $table->foreignId('acknowledged_by')->nullable()->constrained(ConstantHelper::TABLE_USERS)->restrictOnDelete()->comment('Người xác nhận tiếp nhận cảnh báo');
            $table->dateTime('resolved_at')->nullable()->comment('Thời điểm giải quyết xong cảnh báo');
            $table->dateTime('created_at')->nullable()->comment('Thời điểm tạo bản ghi');
            $table->dateTime('updated_at')->nullable()->comment('Thời điểm cập nhật bản ghi');

            $table->index(['branch_id', 'status'], 'idx_ai_alerts_branch_status');
        });

        DB::statement(sprintf("ALTER TABLE `%s` COMMENT = 'Cảnh báo thông minh từ phân hệ AI'", ConstantHelper::TABLE_AI_ALERTS));
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists(ConstantHelper::TABLE_AI_ALERTS);
    }
};
