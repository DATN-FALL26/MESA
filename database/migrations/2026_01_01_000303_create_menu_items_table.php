<?php

declare(strict_types=1);

use App\Enums\ItemType;
use App\Enums\StationCode;
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
        Schema::create(ConstantHelper::TABLE_MENU_ITEMS, function (Blueprint $table) {
            $table->id()->comment('Mã định danh món ăn');
            $table->foreignId('category_id')->constrained(ConstantHelper::TABLE_CATEGORIES)->restrictOnDelete()->comment('Mã danh mục trực thuộc');
            $table->string('sku', 40)->unique()->comment('Mã định danh món duy nhất');
            $table->string('name', 150)->comment('Tên món');
            $table->string('description', 255)->nullable()->comment('Mô tả chi tiết món');
            $table->enum('item_type', ItemType::values())->default(ItemType::DISH->value)->comment('Phân loại món (dish, drink, side, retail)');
            $table->enum('station_code', StationCode::values())->comment('Mã quầy chế biến (PHO, DRINK, SIDE)');
            $table->decimal('base_price', 15, 0)->comment('Giá bán cơ bản (VND)');
            $table->decimal('tax_rate', 5, 2)->default(8.00)->comment('Thuế suất VAT (%)');
            $table->integer('prep_time_seconds')->default(180)->comment('Thời gian chế biến tiêu chuẩn tính bằng giây');
            $table->string('image_path', 255)->nullable()->comment('Đường dẫn ảnh đại diện món');
            $table->boolean('is_active')->default(true)->comment('Trạng thái kinh doanh');
            $table->softDeletes('deleted_at', 0)->comment('Thời điểm xóa mềm');
            $table->dateTime('created_at')->nullable()->comment('Thời điểm tạo bản ghi');
            $table->dateTime('updated_at')->nullable()->comment('Thời điểm cập nhật bản ghi');

            $table->index('category_id', 'idx_menu_items_category');
        });

        DB::statement(sprintf("ALTER TABLE `%s` COMMENT = 'Món trong thực đơn'", ConstantHelper::TABLE_MENU_ITEMS));
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists(ConstantHelper::TABLE_MENU_ITEMS);
    }
};
