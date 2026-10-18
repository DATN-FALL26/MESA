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
        Schema::create(ConstantHelper::TABLE_SUPPLIERS, function (Blueprint $table) {
            $table->id()->comment('Mã định danh nhà cung cấp');
            $table->string('name', 150)->comment('Tên nhà cung cấp');
            $table->string('contact', 150)->nullable()->comment('Người liên hệ đại diện');
            $table->string('phone', 20)->nullable()->comment('Số điện thoại liên hệ');
            $table->string('address', 255)->nullable()->comment('Địa chỉ nhà cung cấp');
            $table->boolean('is_active')->default(true)->comment('Trạng thái hợp tác hoạt động');
            $table->dateTime('created_at')->nullable()->comment('Thời điểm tạo bản ghi');
            $table->dateTime('updated_at')->nullable()->comment('Thời điểm cập nhật bản ghi');
        });

        DB::statement(sprintf("ALTER TABLE `%s` COMMENT = 'Nhà cung cấp nguyên vật liệu'", ConstantHelper::TABLE_SUPPLIERS));
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists(ConstantHelper::TABLE_SUPPLIERS);
    }
};
