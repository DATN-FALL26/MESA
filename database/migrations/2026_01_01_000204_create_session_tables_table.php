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
        Schema::create(ConstantHelper::TABLE_SESSION_TABLES, function (Blueprint $table) {
            $table->id()->comment('Mã định danh bàn trong lượt dùng bữa');
            $table->foreignId('session_id')->constrained(ConstantHelper::TABLE_DINING_SESSIONS)->restrictOnDelete()->comment('Mã lượt dùng bữa');
            $table->foreignId('table_id')->constrained(ConstantHelper::TABLE_DINING_TABLES)->restrictOnDelete()->comment('Mã bàn ăn');
            $table->dateTime('joined_at')->comment('Thời điểm bàn được gán vào lượt');
            $table->dateTime('left_at')->nullable()->comment('Thời điểm bàn rời khỏi lượt (chuyển bàn, tách bàn)');
            $table->unsignedBigInteger('open_guard')->storedAs('IF(left_at IS NULL, table_id, NULL)')->comment('Cột ảo đảm bảo một bàn chỉ thuộc tối đa một lượt đang mở');
            $table->dateTime('created_at')->nullable()->comment('Thời điểm tạo bản ghi');
            $table->dateTime('updated_at')->nullable()->comment('Thời điểm cập nhật bản ghi');

            $table->unique(['session_id', 'table_id', 'joined_at'], 'uq_session_tables_session_table_joined');
            $table->unique('open_guard', 'uq_session_tables_open_guard');
            $table->index(['table_id', 'left_at'], 'idx_session_tables_table_left');
        });

        DB::statement(sprintf("ALTER TABLE `%s` COMMENT = 'Chi tiết bàn gắn với lượt dùng bữa'", ConstantHelper::TABLE_SESSION_TABLES));
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists(ConstantHelper::TABLE_SESSION_TABLES);
    }
};
