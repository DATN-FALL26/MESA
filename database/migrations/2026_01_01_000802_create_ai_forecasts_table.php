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
        Schema::create(ConstantHelper::TABLE_AI_FORECASTS, function (Blueprint $table) {
            $table->id()->comment('Mã định danh kết quả dự báo');
            $table->foreignId('ai_run_id')->constrained(ConstantHelper::TABLE_AI_RUNS)->restrictOnDelete()->comment('Phiên chạy mô hình AI');
            $table->foreignId('branch_id')->constrained(ConstantHelper::TABLE_BRANCHES)->restrictOnDelete()->comment('Chi nhánh dự báo');
            $table->foreignId('menu_item_id')->nullable()->constrained(ConstantHelper::TABLE_MENU_ITEMS)->restrictOnDelete()->comment('Món ăn dự báo (hoặc nguyên liệu)');
            $table->foreignId('ingredient_id')->nullable()->constrained(ConstantHelper::TABLE_INGREDIENTS)->restrictOnDelete()->comment('Nguyên liệu dự báo (hoặc món ăn)');
            $table->date('forecast_date')->comment('Ngày dự báo');
            $table->decimal('predicted_qty', 15, 3)->comment('Số lượng dự báo');
            $table->decimal('confidence', 5, 4)->nullable()->comment('Độ tin cậy của mô hình (0.0000 - 1.0000)');
            $table->string('model_version', 50)->comment('Phiên bản mô hình AI');
            $table->dateTime('created_at')->nullable()->comment('Thời điểm tạo bản ghi');
            $table->dateTime('updated_at')->nullable()->comment('Thời điểm cập nhật bản ghi');

            $table->index(['branch_id', 'forecast_date'], 'idx_ai_forecasts_branch_date');
        });

        DB::statement(sprintf(
            'ALTER TABLE `%s` ADD CONSTRAINT `chk_ai_forecasts_target` CHECK ((menu_item_id IS NOT NULL AND ingredient_id IS NULL) OR (menu_item_id IS NULL AND ingredient_id IS NOT NULL))',
            ConstantHelper::TABLE_AI_FORECASTS
        ));

        DB::statement(sprintf("ALTER TABLE `%s` COMMENT = 'Dự báo nhu cầu món ăn hoặc nguyên liệu từ AI'", ConstantHelper::TABLE_AI_FORECASTS));
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists(ConstantHelper::TABLE_AI_FORECASTS);
    }
};
