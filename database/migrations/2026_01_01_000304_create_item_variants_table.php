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
        Schema::create(ConstantHelper::TABLE_ITEM_VARIANTS, function (Blueprint $table) {
            $table->id()->comment('Mã định danh biến thể món');
            $table->foreignId('menu_item_id')->constrained(ConstantHelper::TABLE_MENU_ITEMS)->restrictOnDelete()->comment('Mã món ăn');
            $table->string('name', 50)->comment('Tên kích cỡ / biến thể (Nhỏ, Lớn, Đặc biệt)');
            $table->decimal('price_delta', 15, 0)->default(0)->comment('Mức chênh lệch giá so với giá gốc (VND)');
            $table->boolean('is_default')->default(false)->comment('Là biến thể mặc định');
            $table->boolean('is_active')->default(true)->comment('Trạng thái kích hoạt');
            $table->dateTime('created_at')->nullable()->comment('Thời điểm tạo bản ghi');
            $table->dateTime('updated_at')->nullable()->comment('Thời điểm cập nhật bản ghi');

            $table->unique(['menu_item_id', 'name'], 'uq_item_variants_item_name');
        });

        DB::statement(sprintf("ALTER TABLE `%s` COMMENT = 'Biến thể kích cỡ món ăn'", ConstantHelper::TABLE_ITEM_VARIANTS));
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists(ConstantHelper::TABLE_ITEM_VARIANTS);
    }
};
