<?php

declare(strict_types=1);

use App\Enums\ScopeType;
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
        Schema::create(ConstantHelper::TABLE_USER_ROLES, function (Blueprint $table) {
            $table->id()->comment('Mã định danh gán chức vụ');
            $table->foreignId('user_id')->constrained(ConstantHelper::TABLE_USERS)->restrictOnDelete()->comment('Mã người dùng');
            $table->foreignId('role_id')->constrained(ConstantHelper::TABLE_ROLES)->restrictOnDelete()->comment('Mã vai trò chức vụ');
            $table->enum('scope_type', ScopeType::values())->comment('Phạm vi hiệu lực (ALL, BRANCH)');
            $table->unsignedBigInteger('scope_id')->nullable()->comment('Mã chi nhánh nếu scope là BRANCH, NULL nếu ALL');
            $table->unsignedBigInteger('scope_key')->storedAs('IFNULL(scope_id, 0)')->comment('Khóa hỗ trợ UNIQUE khi scope_id là NULL');
            $table->dateTime('valid_from')->useCurrent()->comment('Thời điểm bắt đầu có hiệu lực');
            $table->dateTime('valid_to')->nullable()->comment('Thời điểm hết hiệu lực');
            $table->foreignId('granted_by')->nullable()->constrained(ConstantHelper::TABLE_USERS)->nullOnDelete()->comment('Người cấp quyền');
            $table->dateTime('created_at')->nullable()->comment('Thời điểm tạo bản ghi');
            $table->dateTime('updated_at')->nullable()->comment('Thời điểm cập nhật bản ghi');

            $table->unique(['user_id', 'role_id', 'scope_type', 'scope_key'], 'uq_user_role_scope');
            $table->index(['scope_type', 'scope_id'], 'idx_user_roles_scope');
            $table->index('user_id', 'idx_user_roles_user');
        });

        DB::statement(sprintf(
            "ALTER TABLE `%s` ADD CONSTRAINT `chk_user_roles_scope` CHECK ((scope_type = 'ALL' AND scope_id IS NULL) OR (scope_type <> 'ALL' AND scope_id IS NOT NULL))",
            ConstantHelper::TABLE_USER_ROLES
        ));

        DB::statement(sprintf("ALTER TABLE `%s` COMMENT = 'Gán chức vụ cho nhân viên theo phạm vi'", ConstantHelper::TABLE_USER_ROLES));
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists(ConstantHelper::TABLE_USER_ROLES);
    }
};
