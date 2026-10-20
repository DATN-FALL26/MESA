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
        Schema::create(ConstantHelper::TABLE_MODIFIERS, function (Blueprint $table) {
            $table->id()->comment('Mã định danh tùy chọn');
            $table->foreignId('group_id')->constrained(ConstantHelper::TABLE_MODIFIER_GROUPS)->restrictOnDelete()->comment('Mã nhóm tùy chọn');
            $table->string('name', 100)->comment('Tên tùy chọn (Thêm thịt tái, Thêm quẩy...)');
            $table->decimal('price', 15, 0)->default(0)->comment('Giá phụ thu (VND)');
            $table->foreignId('ingredient_id')->nullable()->constrained(ConstantHelper::TABLE_INGREDIENTS)->restrictOnDelete()->comment('Nguyên liệu trừ kho tương ứng');
            $table->decimal('ingredient_qty', 15, 3)->nullable()->comment('Định lượng nguyên liệu tiêu hao');
            $table->boolean('is_active')->default(true)->comment('Trạng thái kích hoạt');
            $table->dateTime('created_at')->nullable()->comment('Thời điểm tạo bản ghi');
            $table->dateTime('updated_at')->nullable()->comment('Thời điểm cập nhật bản ghi');
        });

        DB::statement(sprintf("ALTER TABLE `%s` COMMENT = 'Tùy chọn chi tiết món ăn'", ConstantHelper::TABLE_MODIFIERS));
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists(ConstantHelper::TABLE_MODIFIERS);
    }
};
