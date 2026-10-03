<?php

declare(strict_types=1);

use App\Enums\NotificationType;
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
        Schema::create(ConstantHelper::TABLE_NOTIFICATIONS, function (Blueprint $table) {
            $table->id()->comment('Mã định danh thông báo');
            $table->foreignId('branch_id')->nullable()->constrained(ConstantHelper::TABLE_BRANCHES)->restrictOnDelete()->comment('Chi nhánh nhận thông báo (NULL nếu toàn chuỗi)');
            $table->foreignId('target_user_id')->nullable()->constrained(ConstantHelper::TABLE_USERS)->restrictOnDelete()->comment('Người dùng cụ thể nhận thông báo');
            $table->string('target_role_code', 50)->nullable()->comment('Nhóm vai trò nhận thông báo trong chi nhánh (ví dụ: KITCHEN, CASHIER)');
            $table->enum('type', NotificationType::values())->comment('Phân loại thông báo');
            $table->string('title', 200)->comment('Tiêu đề thông báo');
            $table->string('body', 500)->nullable()->comment('Nội dung chi tiết thông báo');
            $table->json('payload')->nullable()->comment('Dữ liệu đính kèm thông báo dạng JSON');
            $table->dateTime('expires_at')->nullable()->comment('Thời điểm hết hạn hiển thị');
            $table->dateTime('created_at')->nullable()->comment('Thời điểm phát hành thông báo');

            $table->index(['branch_id', 'created_at'], 'idx_notifications_branch_date');
            $table->index(['target_user_id', 'created_at'], 'idx_notifications_user_date');
        });

        DB::statement(sprintf("ALTER TABLE `%s` COMMENT = 'Thông báo hệ thống phục vụ cơ chế polling'", ConstantHelper::TABLE_NOTIFICATIONS));
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists(ConstantHelper::TABLE_NOTIFICATIONS);
    }
};
