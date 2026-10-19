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
        Schema::create(ConstantHelper::TABLE_BRANCH_MENU_ITEMS, function (Blueprint $table) {
            $table->id()->comment('Mã định danh cấu hình món tại chi nhánh');
            $table->foreignId('branch_id')->constrained(ConstantHelper::TABLE_BRANCHES)->restrictOnDelete()->comment('Mã chi nhánh');
            $table->foreignId('menu_item_id')->constrained(ConstantHelper::TABLE_MENU_ITEMS)->restrictOnDelete()->comment('Mã món ăn');
            $table->decimal('price', 15, 0)->nullable()->comment('Giá bán ghi đè theo chi nhánh (NULL = dùng base_price)');
            $table->boolean('is_available')->default(true)->comment('Trạng thái còn hàng / hết hàng trong ngày tại chi nhánh');
            $table->dateTime('created_at')->nullable()->comment('Thời điểm tạo bản ghi');
            $table->dateTime('updated_at')->nullable()->comment('Thời điểm cập nhật bản ghi');

            $table->unique(['branch_id', 'menu_item_id'], 'uq_branch_menu_items_branch_item');
        });

        DB::statement(sprintf("ALTER TABLE `%s` COMMENT = 'Cấu hình món ăn theo chi nhánh'", ConstantHelper::TABLE_BRANCH_MENU_ITEMS));
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists(ConstantHelper::TABLE_BRANCH_MENU_ITEMS);
    }
};
