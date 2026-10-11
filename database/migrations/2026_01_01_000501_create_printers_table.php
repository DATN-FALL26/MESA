<?php

declare(strict_types=1);

use App\Enums\PrinterType;
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
        Schema::create(ConstantHelper::TABLE_PRINTERS, function (Blueprint $table) {
            $table->id()->comment('Mã định danh máy in');
            $table->foreignId('branch_id')->constrained(ConstantHelper::TABLE_BRANCHES)->restrictOnDelete()->comment('Chi nhánh quản lý');
            $table->string('name', 100)->comment('Tên hiển thị máy in');
            $table->enum('type', PrinterType::values())->comment('Phân loại máy in (kitchen, receipt)');
            $table->string('connection', 150)->comment('Thông số kết nối (IP, LAN, COM...)');
            $table->boolean('is_active')->default(true)->comment('Trạng thái kích hoạt');
            $table->dateTime('created_at')->nullable()->comment('Thời điểm tạo bản ghi');
            $table->dateTime('updated_at')->nullable()->comment('Thời điểm cập nhật bản ghi');
        });

        DB::statement(sprintf("ALTER TABLE `%s` COMMENT = 'Thiết bị máy in tại chi nhánh'", ConstantHelper::TABLE_PRINTERS));
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists(ConstantHelper::TABLE_PRINTERS);
    }
};
