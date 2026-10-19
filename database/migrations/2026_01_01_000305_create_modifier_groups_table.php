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
        Schema::create(ConstantHelper::TABLE_MODIFIER_GROUPS, function (Blueprint $table) {
            $table->id()->comment('Mã định danh nhóm tùy chọn');
            $table->string('name', 100)->comment('Tên nhóm tùy chọn (Thêm thịt, Topping, Ghi chú)');
            $table->smallInteger('min_select')->default(0)->comment('Số lượng chọn tối thiểu');
            $table->smallInteger('max_select')->default(1)->comment('Số lượng chọn tối đa');
            $table->boolean('is_active')->default(true)->comment('Trạng thái kích hoạt');
            $table->dateTime('created_at')->nullable()->comment('Thời điểm tạo bản ghi');
            $table->dateTime('updated_at')->nullable()->comment('Thời điểm cập nhật bản ghi');
        });

        DB::statement(sprintf("ALTER TABLE `%s` COMMENT = 'Nhóm tùy chọn món ăn'", ConstantHelper::TABLE_MODIFIER_GROUPS));
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists(ConstantHelper::TABLE_MODIFIER_GROUPS);
    }
};
