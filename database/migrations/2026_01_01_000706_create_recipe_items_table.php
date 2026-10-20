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
        Schema::create(ConstantHelper::TABLE_RECIPE_ITEMS, function (Blueprint $table) {
            $table->id()->comment('Mã định danh dòng định lượng nguyên liệu');
            $table->foreignId('recipe_id')->constrained(ConstantHelper::TABLE_RECIPES)->cascadeOnDelete()->comment('Mã công thức định lượng');
            $table->foreignId('ingredient_id')->constrained(ConstantHelper::TABLE_INGREDIENTS)->restrictOnDelete()->comment('Mã nguyên liệu tiêu hao');
            $table->decimal('quantity', 15, 3)->comment('Định lượng nguyên liệu tiêu hao theo đơn vị cơ sở (base_uom)');
            $table->dateTime('created_at')->nullable()->comment('Thời điểm tạo bản ghi');
            $table->dateTime('updated_at')->nullable()->comment('Thời điểm cập nhật bản ghi');

            $table->unique(['recipe_id', 'ingredient_id'], 'uq_recipe_items_recipe_ingredient');
        });

        DB::statement(sprintf("ALTER TABLE `%s` COMMENT = 'Chi tiết nguyên liệu trong công thức'", ConstantHelper::TABLE_RECIPE_ITEMS));
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists(ConstantHelper::TABLE_RECIPE_ITEMS);
    }
};
