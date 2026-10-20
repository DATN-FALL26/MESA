<?php

declare(strict_types=1);

use App\Enums\UserStatus;
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
        Schema::create(ConstantHelper::TABLE_USERS, function (Blueprint $table) {
            $table->id()->comment('Mã định danh người dùng');
            $table->string('username', 50)->unique()->comment('Tên đăng nhập hệ thống');
            $table->string('employee_code', 30)->nullable()->unique()->comment('Mã số nhân viên');
            $table->string('full_name', 150)->comment('Họ và tên nhân viên');
            $table->string('email', 150)->nullable()->unique()->comment('Địa chỉ thư điện tử');
            $table->string('phone', 20)->nullable()->comment('Số điện thoại liên lạc');
            $table->string('password', 255)->comment('Mật khẩu đăng nhập đã băm');
            $table->string('pin_hash', 255)->nullable()->comment('Mã PIN đăng nhập nhanh POS');
            $table->foreignId('department_id')->nullable()->constrained(ConstantHelper::TABLE_DEPARTMENTS)->restrictOnDelete()->comment('Phòng ban trực thuộc');
            $table->enum('status', UserStatus::values())->default(UserStatus::ACTIVE->value)->comment('Trạng thái tài khoản người dùng');
            $table->dateTime('last_login_at')->nullable()->comment('Thời điểm đăng nhập gần nhất');
            $table->rememberToken()->comment('Token ghi nhớ đăng nhập');
            $table->softDeletes('deleted_at', 0)->comment('Thời điểm xóa mềm');
            $table->dateTime('created_at')->nullable()->comment('Thời điểm tạo bản ghi');
            $table->dateTime('updated_at')->nullable()->comment('Thời điểm cập nhật bản ghi');
        });

        DB::statement(sprintf("ALTER TABLE `%s` COMMENT = 'Tài khoản nhân viên hệ thống'", ConstantHelper::TABLE_USERS));
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists(ConstantHelper::TABLE_USERS);
    }
};
