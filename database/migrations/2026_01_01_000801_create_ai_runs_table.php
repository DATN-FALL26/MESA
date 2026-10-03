<?php

declare(strict_types=1);

use App\Enums\AiRunStatus;
use App\Enums\AiRunType;
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
        Schema::create(ConstantHelper::TABLE_AI_RUNS, function (Blueprint $table) {
            $table->id()->comment('Mã định danh phiên chạy mô hình AI');
            $table->foreignId('branch_id')->nullable()->constrained(ConstantHelper::TABLE_BRANCHES)->restrictOnDelete()->comment('Chi nhánh áp dụng (NULL nếu toàn chuỗi)');
            $table->enum('run_type', AiRunType::values())->comment('Loại phân tích AI (sales_analysis, demand_forecast, shortage_detection)');
            $table->json('params')->nullable()->comment('Tham số đầu vào của mô hình dạng JSON');
            $table->enum('status', AiRunStatus::values())->default(AiRunStatus::QUEUED->value)->comment('Trạng thái phiên chạy');
            $table->json('result_summary')->nullable()->comment('Tóm tắt kết quả phân tích / dự báo dạng JSON');
            $table->foreignId('triggered_by')->nullable()->constrained(ConstantHelper::TABLE_USERS)->restrictOnDelete()->comment('Người kích hoạt (NULL nếu chạy tự động)');
            $table->dateTime('started_at')->nullable()->comment('Thời điểm bắt đầu xử lý');
            $table->dateTime('finished_at')->nullable()->comment('Thời điểm hoàn thành');
            $table->dateTime('created_at')->nullable()->comment('Thời điểm tạo bản ghi');
            $table->dateTime('updated_at')->nullable()->comment('Thời điểm cập nhật bản ghi');
        });

        DB::statement(sprintf("ALTER TABLE `%s` COMMENT = 'Phiên thực thi mô hình phân tích / dự báo AI'", ConstantHelper::TABLE_AI_RUNS));
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists(ConstantHelper::TABLE_AI_RUNS);
    }
};
