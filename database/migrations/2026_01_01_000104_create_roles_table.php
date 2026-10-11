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
        Schema::create(ConstantHelper::TABLE_ROLES, function (Blueprint $table) {
            $table->id()->comment('Mã định danh vai trò');
            $table->string('code', 50)->unique()->comment('Mã vai trò chức vụ (ví dụ: ADMIN, CASHIER)');
            $table->string('name', 100)->comment('Tên vai trò chức vụ');
            $table->string('description', 255)->nullable()->comment('Mô tả vai trò chức vụ');
            $table->boolean('is_system')->default(false)->comment('Đánh dấu vai trò hệ thống không được xóa');
            $table->dateTime('created_at')->nullable()->comment('Thời điểm tạo bản ghi');
            $table->dateTime('updated_at')->nullable()->comment('Thời điểm cập nhật bản ghi');
        });

        DB::statement(sprintf("ALTER TABLE `%s` COMMENT = 'Vai trò chức vụ hệ thống'", ConstantHelper::TABLE_ROLES));
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists(ConstantHelper::TABLE_ROLES);
    }
};
