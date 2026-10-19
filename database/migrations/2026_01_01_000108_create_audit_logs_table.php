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
        Schema::create(ConstantHelper::TABLE_AUDIT_LOGS, function (Blueprint $table) {
            $table->id()->comment('Mã định danh nhật ký kiểm toán');
            $table->unsignedBigInteger('branch_id')->nullable()->comment('Mã chi nhánh phát sinh hành động');
            $table->unsignedBigInteger('user_id')->nullable()->comment('Mã người dùng thực hiện hành động');
            $table->string('action', 60)->comment('Tên hành động (create, update, delete, status_change...)');
            $table->string('entity_type', 50)->comment('Tên bảng hoặc loại đối tượng bị tác động');
            $table->unsignedBigInteger('entity_id')->nullable()->comment('Mã định danh đối tượng bị tác động');
            $table->json('old_value')->nullable()->comment('Dữ liệu trước khi thay đổi');
            $table->json('new_value')->nullable()->comment('Dữ liệu sau khi thay đổi');
            $table->string('ip_address', 45)->nullable()->comment('Địa chỉ IP nguồn');
            $table->dateTime('created_at')->nullable()->comment('Thời điểm ghi nhận nhật ký');

            $table->index(['entity_type', 'entity_id'], 'idx_audit_logs_entity');
            $table->index(['branch_id', 'created_at'], 'idx_audit_logs_branch_date');
            $table->index(['user_id', 'created_at'], 'idx_audit_logs_user_date');
        });

        DB::statement(sprintf("ALTER TABLE `%s` COMMENT = 'Nhật ký kiểm toán hệ thống'", ConstantHelper::TABLE_AUDIT_LOGS));
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists(ConstantHelper::TABLE_AUDIT_LOGS);
    }
};
