<?php

declare(strict_types=1);

use App\Enums\SessionStatus;
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
        Schema::create(ConstantHelper::TABLE_DINING_SESSIONS, function (Blueprint $table) {
            $table->id()->comment('Mã định danh lượt dùng bữa');
            $table->foreignId('branch_id')->constrained(ConstantHelper::TABLE_BRANCHES)->restrictOnDelete()->comment('Chi nhánh phục vụ');
            $table->foreignId('opened_by')->constrained(ConstantHelper::TABLE_USERS)->restrictOnDelete()->comment('Nhân viên mở lượt dùng');
            $table->dateTime('opened_at')->comment('Thời điểm mở lượt dùng');
            $table->dateTime('closed_at')->nullable()->comment('Thời điểm kết thúc lượt dùng');
            $table->smallInteger('guest_count')->default(1)->comment('Số lượng khách của lượt');
            $table->enum('status', SessionStatus::values())->default(SessionStatus::OPEN->value)->comment('Trạng thái lượt (open, closed, cancelled)');
            $table->string('note', 255)->nullable()->comment('Ghi chú của lượt dùng bữa');
            $table->dateTime('created_at')->nullable()->comment('Thời điểm tạo bản ghi');
            $table->dateTime('updated_at')->nullable()->comment('Thời điểm cập nhật bản ghi');

            $table->index(['branch_id', 'status'], 'idx_dining_sessions_branch_status');
        });

        DB::statement(sprintf("ALTER TABLE `%s` COMMENT = 'Lượt khách dùng bữa tại quán'", ConstantHelper::TABLE_DINING_SESSIONS));
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists(ConstantHelper::TABLE_DINING_SESSIONS);
    }
};
