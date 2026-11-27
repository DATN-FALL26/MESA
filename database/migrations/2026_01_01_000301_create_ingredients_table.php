<?php

declare(strict_types=1);

use App\Enums\IngredientType;
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
        Schema::create(ConstantHelper::TABLE_INGREDIENTS, function (Blueprint $table) {
            $table->id()->comment('Mã định danh nguyên liệu');
            $table->string('sku', 40)->unique()->comment('Mã định danh nguyên liệu duy nhất');
            $table->string('name', 150)->comment('Tên nguyên liệu');
            $table->enum('ingredient_type', IngredientType::values())->default(IngredientType::RAW->value)->comment('Phân loại nguyên liệu (raw, semi_finished)');
            $table->string('base_uom', 20)->comment('Đơn vị tính cơ sở (g, ml, cái...)');
            $table->decimal('min_stock', 15, 3)->default(0)->comment('Mức tồn kho tối thiểu cảnh báo');
            $table->boolean('is_active')->default(true)->comment('Trạng thái sử dụng');
            $table->softDeletes('deleted_at', 0)->comment('Thời điểm xóa mềm');
            $table->dateTime('created_at')->nullable()->comment('Thời điểm tạo bản ghi');
            $table->dateTime('updated_at')->nullable()->comment('Thời điểm cập nhật bản ghi');
        });

        DB::statement(sprintf("ALTER TABLE `%s` COMMENT = 'Nguyên liệu và bán thành phẩm'", ConstantHelper::TABLE_INGREDIENTS));
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists(ConstantHelper::TABLE_INGREDIENTS);
    }
};
