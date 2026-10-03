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
        Schema::create(ConstantHelper::TABLE_RECIPES, function (Blueprint $table) {
            $table->id()->comment('Mã định danh công thức định lượng');
            $table->foreignId('menu_item_id')->constrained(ConstantHelper::TABLE_MENU_ITEMS)->restrictOnDelete()->comment('Mã món ăn');
            $table->foreignId('variant_id')->nullable()->constrained(ConstantHelper::TABLE_ITEM_VARIANTS)->restrictOnDelete()->comment('Mã biến thể (NULL = công thức mặc định)');
            $table->unsignedBigInteger('variant_key')->storedAs('IFNULL(variant_id, 0)')->comment('Cột ảo hỗ trợ UNIQUE khi variant_id là NULL');
            $table->integer('version')->default(1)->comment('Phiên bản công thức định lượng');
            $table->boolean('is_active')->default(true)->comment('Trạng thái kích hoạt công thức');
            $table->dateTime('created_at')->nullable()->comment('Thời điểm tạo bản ghi');
            $table->dateTime('updated_at')->nullable()->comment('Thời điểm cập nhật bản ghi');

            $table->unique(['menu_item_id', 'variant_key', 'version'], 'uq_recipes_item_variant_version');
        });

        DB::statement(sprintf("ALTER TABLE `%s` COMMENT = 'Công thức định lượng món ăn'", ConstantHelper::TABLE_RECIPES));
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists(ConstantHelper::TABLE_RECIPES);
    }
};
