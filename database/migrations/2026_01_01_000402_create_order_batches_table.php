<?php

declare(strict_types=1);

use App\Enums\BatchStatus;
use App\Enums\ServeMode;
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
        Schema::create(ConstantHelper::TABLE_ORDER_BATCHES, function (Blueprint $table) {
            $table->id()->comment('Mã định danh đợt gọi món');
            $table->foreignId('order_id')->constrained(ConstantHelper::TABLE_ORDERS)->restrictOnDelete()->comment('Đơn gọi món trực thuộc');
            $table->unsignedSmallInteger('batch_no')->comment('Số thứ tự đợt gọi món trong đơn');
            $table->enum('serve_mode', ServeMode::values())->comment('Cách thức phục vụ (together, as_ready)');
            $table->enum('status', BatchStatus::values())->default(BatchStatus::OPEN->value)->comment('Trạng thái đợt gọi món');
            $table->dateTime('sent_at')->nullable()->comment('Thời điểm gửi đợt xuống bếp');
            $table->dateTime('target_ready_at')->nullable()->comment('Thời gian dự kiến hoàn thành toàn đợt');
            $table->dateTime('all_ready_at')->nullable()->comment('Thời điểm tất cả món trong đợt đã xong');
            $table->dateTime('served_at')->nullable()->comment('Thời điểm đã phục vụ đợt xong');
            $table->foreignId('released_by')->nullable()->constrained(ConstantHelper::TABLE_USERS)->restrictOnDelete()->comment('Nhân viên ép xả đợt');
            $table->dateTime('created_at')->nullable()->comment('Thời điểm tạo bản ghi');
            $table->dateTime('updated_at')->nullable()->comment('Thời điểm cập nhật bản ghi');

            $table->unique(['order_id', 'batch_no'], 'uq_order_batches_order_batch_no');
        });

        DB::statement(sprintf("ALTER TABLE `%s` COMMENT = 'Đợt gọi món'", ConstantHelper::TABLE_ORDER_BATCHES));
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists(ConstantHelper::TABLE_ORDER_BATCHES);
    }
};
