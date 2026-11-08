<?php

declare(strict_types=1);

use App\Enums\WarehouseType;
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
        Schema::create(ConstantHelper::TABLE_WAREHOUSES, function (Blueprint $table) {
            $table->id()->comment('Mã định danh kho');
            $table->foreignId('branch_id')->constrained(ConstantHelper::TABLE_BRANCHES)->restrictOnDelete()->comment('Chi nhánh quản lý kho');
            $table->string('name', 100)->comment('Tên kho lưu trữ');
            $table->enum('type', WarehouseType::values())->default(WarehouseType::BRANCH->value)->comment('Loại kho (branch, central)');
            $table->boolean('is_active')->default(true)->comment('Trạng thái hoạt động của kho');
            $table->dateTime('created_at')->nullable()->comment('Thời điểm tạo bản ghi');
            $table->dateTime('updated_at')->nullable()->comment('Thời điểm cập nhật bản ghi');
        });

        DB::statement(sprintf("ALTER TABLE `%s` COMMENT = 'Kho lưu trữ nguyên liệu'", ConstantHelper::TABLE_WAREHOUSES));
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists(ConstantHelper::TABLE_WAREHOUSES);
    }
};
