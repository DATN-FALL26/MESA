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
        Schema::create(ConstantHelper::TABLE_AREAS, function (Blueprint $table) {
            $table->id()->comment('Mã định danh khu vực');
            $table->foreignId('branch_id')->constrained(ConstantHelper::TABLE_BRANCHES)->restrictOnDelete()->comment('Chi nhánh trực thuộc');
            $table->string('code', 20)->comment('Mã khu vực bàn ăn (tiền tố bàn: A, B, VIP...)');
            $table->string('name', 100)->comment('Tên hiển thị của khu vực');
            $table->smallInteger('default_seats')->default(4)->comment('Số lượng ghế mặc định của bàn trong khu');
            $table->json('layout_config')->nullable()->comment('Cấu hình lưới hiển thị sơ đồ bàn (cols, rows, cell_size...)');
            $table->integer('sort_order')->default(0)->comment('Thứ tự sắp xếp hiển thị');
            $table->boolean('is_active')->default(true)->comment('Trạng thái hoạt động của khu vực');
            $table->softDeletes('deleted_at', 0)->comment('Thời điểm xóa mềm');
            $table->tinyInteger('active_flag')->storedAs('IF(deleted_at IS NULL, 1, NULL)')->comment('Cờ hỗ trợ UNIQUE khi chưa xóa mềm');
            $table->dateTime('created_at')->nullable()->comment('Thời điểm tạo bản ghi');
            $table->dateTime('updated_at')->nullable()->comment('Thời điểm cập nhật bản ghi');

            $table->unique(['branch_id', 'code', 'active_flag'], 'uq_areas_branch_code_active');
        });

        DB::statement(sprintf("ALTER TABLE `%s` COMMENT = 'Khu vực bàn ăn trong chi nhánh'", ConstantHelper::TABLE_AREAS));
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists(ConstantHelper::TABLE_AREAS);
    }
};
