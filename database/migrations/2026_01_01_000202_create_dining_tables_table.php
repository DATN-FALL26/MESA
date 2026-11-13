<?php

declare(strict_types=1);

use App\Enums\TableShape;
use App\Enums\TableStatus;
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
        Schema::create(ConstantHelper::TABLE_DINING_TABLES, function (Blueprint $table) {
            $table->id()->comment('Mã định danh bàn ăn');
            $table->foreignId('branch_id')->constrained(ConstantHelper::TABLE_BRANCHES)->restrictOnDelete()->comment('Chi nhánh trực thuộc');
            $table->foreignId('area_id')->nullable()->constrained(ConstantHelper::TABLE_AREAS)->restrictOnDelete()->comment('Khu vực đặt bàn');
            $table->string('code', 20)->comment('Mã bàn ăn (ví dụ: A01, B02)');
            $table->smallInteger('seats')->default(4)->comment('Số lượng ghế');
            $table->enum('shape', TableShape::values())->default(TableShape::SQUARE->value)->comment('Hình dạng bàn (square, round, rect)');
            $table->integer('pos_x')->nullable()->comment('Tọa độ cột X trên sơ đồ lưới');
            $table->integer('pos_y')->nullable()->comment('Tọa độ hàng Y trên sơ đồ lưới');
            $table->integer('width')->default(1)->comment('Chiều rộng ô bàn');
            $table->integer('height')->default(1)->comment('Chiều cao ô bàn');
            $table->integer('sort_order')->default(0)->comment('Thứ tự sắp xếp hiển thị');
            $table->enum('status', TableStatus::values())->default(TableStatus::AVAILABLE->value)->comment('Trạng thái bàn hiện tại');
            $table->boolean('is_active')->default(true)->comment('Trạng thái kích hoạt bàn');
            $table->softDeletes('deleted_at', 0)->comment('Thời điểm xóa mềm');
            $table->tinyInteger('active_flag')->storedAs('IF(deleted_at IS NULL, 1, NULL)')->comment('Cờ hỗ trợ UNIQUE khi chưa xóa mềm');
            $table->dateTime('created_at')->nullable()->comment('Thời điểm tạo bản ghi');
            $table->dateTime('updated_at')->nullable()->comment('Thời điểm cập nhật bản ghi');

            $table->unique(['branch_id', 'code', 'active_flag'], 'uq_dining_tables_branch_code_active');
            $table->index('area_id', 'idx_dining_tables_area');
        });

        DB::statement(sprintf("ALTER TABLE `%s` COMMENT = 'Bàn ăn tại chi nhánh'", ConstantHelper::TABLE_DINING_TABLES));
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists(ConstantHelper::TABLE_DINING_TABLES);
    }
};
