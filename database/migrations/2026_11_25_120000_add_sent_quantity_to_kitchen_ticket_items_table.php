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
     * MOD04: lưu số lượng đã gửi bếp. Phục vụ sửa số lượng món đã gửi chỉ có hiệu lực với bếp và thanh toán
     * sau khi bấm "Cập nhật bếp".
     */
    public function up(): void
    {
        Schema::table(ConstantHelper::TABLE_KITCHEN_TICKET_ITEMS, function (Blueprint $table) {
            $table->unsignedSmallInteger('sent_quantity')
                ->nullable()
                ->after('notified_quantity')
                ->comment('Số lượng đã gửi bếp (lúc Gửi bếp / Cập nhật bếp); bếp và thanh toán dùng số này');
        });

        $ticketItems = ConstantHelper::TABLE_KITCHEN_TICKET_ITEMS;
        $orderItems = ConstantHelper::TABLE_ORDER_ITEMS;

        // Món đã báo bếp xong: lấy số lượng hiện tại.
        DB::table($ticketItems)
            ->join($orderItems, "{$orderItems}.id", '=', "{$ticketItems}.order_item_id")
            ->whereColumn("{$orderItems}.updated_at", '<=', "{$ticketItems}.updated_at")
            ->update(["{$ticketItems}.sent_quantity" => DB::raw("{$orderItems}.quantity")]);

        // Món đang sửa dở (chưa bấm Cập nhật bếp): lấy số lượng bếp đã biết.
        DB::table($ticketItems)
            ->join($orderItems, "{$orderItems}.id", '=', "{$ticketItems}.order_item_id")
            ->whereNull("{$ticketItems}.sent_quantity")
            ->update(["{$ticketItems}.sent_quantity" => DB::raw("COALESCE({$ticketItems}.notified_quantity, {$orderItems}.quantity)")]);
    }

    public function down(): void
    {
        Schema::table(ConstantHelper::TABLE_KITCHEN_TICKET_ITEMS, function (Blueprint $table) {
            $table->dropColumn('sent_quantity');
        });
    }
};
