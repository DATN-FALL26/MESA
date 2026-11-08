<?php

declare(strict_types=1);

use App\Enums\TicketItemStatus;
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
        Schema::create(ConstantHelper::TABLE_KITCHEN_TICKET_ITEMS, function (Blueprint $table) {
            $table->id()->comment('Mã định danh dòng món trong phiếu bếp');
            $table->foreignId('ticket_id')->constrained(ConstantHelper::TABLE_KITCHEN_TICKETS)->restrictOnDelete()->comment('Phiếu bếp trực thuộc');
            $table->foreignId('order_item_id')->constrained(ConstantHelper::TABLE_ORDER_ITEMS)->restrictOnDelete()->comment('Dòng món trong đơn');
            $table->enum('status', TicketItemStatus::values())->default(TicketItemStatus::NEW->value)->comment('Trạng thái chế biến của món');
            $table->dateTime('fire_at')->nullable()->comment('Thời điểm dự kiến bắt đầu nấu theo lịch');
            $table->dateTime('started_at')->nullable()->comment('Thời điểm thực tế bắt đầu nấu');
            $table->dateTime('ready_at')->nullable()->comment('Thời điểm món đã nấu xong');
            $table->dateTime('created_at')->nullable()->comment('Thời điểm tạo bản ghi');
            $table->dateTime('updated_at')->nullable()->comment('Thời điểm cập nhật bản ghi');

            $table->unique(['ticket_id', 'order_item_id'], 'uq_kitchen_ticket_items_ticket_item');
        });

        DB::statement(sprintf("ALTER TABLE `%s` COMMENT = 'Món cần chế biến trong phiếu bếp'", ConstantHelper::TABLE_KITCHEN_TICKET_ITEMS));
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists(ConstantHelper::TABLE_KITCHEN_TICKET_ITEMS);
    }
};
