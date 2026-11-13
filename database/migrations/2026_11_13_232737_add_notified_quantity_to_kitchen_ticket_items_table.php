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
     * MOD04 – M04-T06: lưu số lượng bếp đã nhận để báo "Đã sửa" khi phục vụ đổi số lượng món đã gửi bếp.
     */
    public function up(): void
    {
        Schema::table(ConstantHelper::TABLE_KITCHEN_TICKET_ITEMS, function (Blueprint $table) {
            $table->unsignedSmallInteger('notified_quantity')
                ->nullable()
                ->after('order_item_id')
                ->comment('Số lượng bếp đã nhận/xác nhận; khác số lượng trên đơn nghĩa là món vừa bị sửa');
        });

        DB::table(ConstantHelper::TABLE_KITCHEN_TICKET_ITEMS)
            ->join(
                ConstantHelper::TABLE_ORDER_ITEMS,
                ConstantHelper::TABLE_ORDER_ITEMS.'.id',
                '=',
                ConstantHelper::TABLE_KITCHEN_TICKET_ITEMS.'.order_item_id'
            )
            ->update([
                ConstantHelper::TABLE_KITCHEN_TICKET_ITEMS.'.notified_quantity' => DB::raw(ConstantHelper::TABLE_ORDER_ITEMS.'.quantity'),
            ]);
    }

    public function down(): void
    {
        Schema::table(ConstantHelper::TABLE_KITCHEN_TICKET_ITEMS, function (Blueprint $table) {
            $table->dropColumn('notified_quantity');
        });
    }
};
