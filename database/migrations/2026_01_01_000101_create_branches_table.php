<?php

declare(strict_types=1);

use App\Enums\BranchStatus;
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
        Schema::create(ConstantHelper::TABLE_BRANCHES, function (Blueprint $table) {
            $table->id()->comment('Mã định danh chi nhánh');
            $table->string('code', 20)->unique()->comment('Mã chi nhánh duy nhất (ví dụ: PHO01)');
            $table->string('name', 150)->comment('Tên chi nhánh');
            $table->string('address', 255)->nullable()->comment('Địa chỉ chi nhánh');
            $table->string('phone', 20)->nullable()->comment('Số điện thoại liên hệ');
            $table->string('timezone', 50)->default('Asia/Ho_Chi_Minh')->comment('Múi giờ hoạt động');
            $table->enum('status', BranchStatus::values())->default(BranchStatus::ACTIVE->value)->comment('Trạng thái hoạt động');
            $table->date('opened_at')->nullable()->comment('Ngày chính thức khai trương');
            $table->json('settings')->nullable()->comment('Cấu hình riêng ghi đè theo chi nhánh');
            $table->softDeletes('deleted_at', 0)->comment('Thời điểm xóa mềm');
            $table->dateTime('created_at')->nullable()->comment('Thời điểm tạo bản ghi');
            $table->dateTime('updated_at')->nullable()->comment('Thời điểm cập nhật bản ghi');
        });

        DB::statement(sprintf("ALTER TABLE `%s` COMMENT = 'Chi nhánh chuỗi quán phở'", ConstantHelper::TABLE_BRANCHES));
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists(ConstantHelper::TABLE_BRANCHES);
    }
};
