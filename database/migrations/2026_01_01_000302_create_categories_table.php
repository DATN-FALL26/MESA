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
        Schema::create(ConstantHelper::TABLE_CATEGORIES, function (Blueprint $table) {
            $table->id()->comment('Mã định danh danh mục');
            $table->foreignId('parent_id')->nullable()->constrained(ConstantHelper::TABLE_CATEGORIES)->restrictOnDelete()->comment('Mã danh mục cha');
            $table->string('name', 100)->comment('Tên danh mục');
            $table->integer('sort_order')->default(0)->comment('Thứ tự sắp xếp hiển thị');
            $table->boolean('is_active')->default(true)->comment('Trạng thái kích hoạt');
            $table->dateTime('created_at')->nullable()->comment('Thời điểm tạo bản ghi');
            $table->dateTime('updated_at')->nullable()->comment('Thời điểm cập nhật bản ghi');
        });

        DB::statement(sprintf("ALTER TABLE `%s` COMMENT = 'Danh mục thực đơn'", ConstantHelper::TABLE_CATEGORIES));
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists(ConstantHelper::TABLE_CATEGORIES);
    }
};
