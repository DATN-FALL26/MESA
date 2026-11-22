<?php

declare(strict_types=1);

use App\Enums\TicketStatus;
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
        Schema::create(ConstantHelper::TABLE_KITCHEN_TICKETS, function (Blueprint $table) {
            $table->id()->comment('Mã định danh phiếu bếp');
            $table->foreignId('branch_id')->constrained(ConstantHelper::TABLE_BRANCHES)->restrictOnDelete()->comment('Chi nhánh tiếp nhận phiếu');
            $table->foreignId('order_id')->constrained(ConstantHelper::TABLE_ORDERS)->restrictOnDelete()->comment('Đơn gọi món tương ứng');
            $table->foreignId('batch_id')->nullable()->constrained(ConstantHelper::TABLE_ORDER_BATCHES)->restrictOnDelete()->comment('Đợt gọi món tương ứng');
            $table->foreignId('station_id')->constrained(ConstantHelper::TABLE_STATIONS)->restrictOnDelete()->comment('Quầy chế biến xử lý');
            $table->string('ticket_no', 30)->comment('Số phiếu bếp trong chi nhánh');
            $table->enum('status', TicketStatus::values())->default(TicketStatus::NEW->value)->comment('Trạng thái phiếu bếp');
            $table->dateTime('fired_at')->comment('Thời điểm kích hoạt phiếu bếp');
            $table->dateTime('ready_at')->nullable()->comment('Thời điểm hoàn thành tất cả món trong phiếu');
            $table->foreignId('updated_by')->nullable()->constrained(ConstantHelper::TABLE_USERS)->restrictOnDelete()->comment('Nhân viên cập nhật gần nhất');
            $table->dateTime('created_at')->nullable()->comment('Thời điểm tạo bản ghi');
            $table->dateTime('updated_at')->nullable()->comment('Thời điểm cập nhật bản ghi');

            $table->unique(['branch_id', 'ticket_no'], 'uq_kitchen_tickets_branch_ticket_no');
            $table->index(['station_id', 'status'], 'idx_kitchen_tickets_station_status');
            $table->index('batch_id', 'idx_kitchen_tickets_batch');
        });

        DB::statement(sprintf("ALTER TABLE `%s` COMMENT = 'Phiếu điều phối chế biến bếp'", ConstantHelper::TABLE_KITCHEN_TICKETS));
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists(ConstantHelper::TABLE_KITCHEN_TICKETS);
    }
};
