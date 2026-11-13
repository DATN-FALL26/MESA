<?php

declare(strict_types=1);

use App\Helpers\ConstantHelper;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table(ConstantHelper::TABLE_KITCHEN_DAILY_CHECKINS, function (Blueprint $table) {
            $table->foreignId('purchase_order_id')
                ->nullable()
                ->after('user_id')
                ->constrained(ConstantHelper::TABLE_PURCHASE_ORDERS)
                ->nullOnDelete()
                ->comment('Liên kết với đơn nhập hàng trong hệ thống Admin');
        });

        Schema::table(ConstantHelper::TABLE_KITCHEN_DAILY_CHECKIN_ITEMS, function (Blueprint $table) {
            $table->decimal('unit_price', 15, 2)
                ->default(0)
                ->after('quantity')
                ->comment('Đơn giá nhập thực tế (VND)');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table(ConstantHelper::TABLE_KITCHEN_DAILY_CHECKIN_ITEMS, function (Blueprint $table) {
            $table->dropColumn('unit_price');
        });

        Schema::table(ConstantHelper::TABLE_KITCHEN_DAILY_CHECKINS, function (Blueprint $table) {
            $table->dropForeign(['purchase_order_id']);
            $table->dropColumn('purchase_order_id');
        });
    }
};
