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
        Schema::create(ConstantHelper::TABLE_KITCHEN_DAILY_CHECKINS, function (Blueprint $table) {
            $table->id()->comment('Mã định danh lần xác nhận nhập nguyên liệu bếp');
            $table->foreignId('branch_id')->constrained(ConstantHelper::TABLE_BRANCHES)->restrictOnDelete()->comment('Chi nhánh áp dụng');
            $table->foreignId('warehouse_id')->constrained(ConstantHelper::TABLE_WAREHOUSES)->restrictOnDelete()->comment('Kho chi nhánh nhập hàng');
            $table->date('checkin_date')->comment('Ngày xác nhận ca');
            $table->foreignId('user_id')->constrained(ConstantHelper::TABLE_USERS)->restrictOnDelete()->comment('Nhân viên bếp thực hiện');
            $table->string('type', 30)->default('start_shift')->comment('Loại xác nhận (start_shift, in_shift_addition)');
            $table->string('note', 255)->nullable()->comment('Ghi chú của bếp');
            $table->dateTime('created_at')->nullable()->comment('Thời điểm tạo');
            $table->dateTime('updated_at')->nullable()->comment('Thời điểm cập nhật');

            $table->index(['branch_id', 'checkin_date'], 'idx_kitchen_checkins_branch_date');
        });

        DB::statement(sprintf("ALTER TABLE `%s` COMMENT = 'Nhật ký chốt & nhập nguyên liệu hàng ngày tại Bếp'", ConstantHelper::TABLE_KITCHEN_DAILY_CHECKINS));

        Schema::create(ConstantHelper::TABLE_KITCHEN_DAILY_CHECKIN_ITEMS, function (Blueprint $table) {
            $table->id()->comment('Mã định danh dòng chi tiết nguyên liệu');
            $table->foreignId('checkin_id')->constrained(ConstantHelper::TABLE_KITCHEN_DAILY_CHECKINS)->cascadeOnDelete()->comment('Thuộc lần xác nhận nào');
            $table->foreignId('ingredient_id')->constrained(ConstantHelper::TABLE_INGREDIENTS)->restrictOnDelete()->comment('Mã nguyên liệu');
            $table->decimal('quantity', 15, 3)->default(0)->comment('Số lượng nhập (0 nếu dùng tồn cũ hoặc hết hàng)');
            $table->string('action', 30)->default('inward')->comment('Hành động (inward: nhập hàng, use_existing: dùng tồn cũ, out_of_stock: hết hàng)');
            $table->string('note', 255)->nullable()->comment('Ghi chú chi tiết');
            $table->dateTime('created_at')->nullable()->comment('Thời điểm tạo');
            $table->dateTime('updated_at')->nullable()->comment('Thời điểm cập nhật');

            $table->index(['checkin_id', 'ingredient_id'], 'idx_checkin_items_checkin_ing');
        });

        DB::statement(sprintf("ALTER TABLE `%s` COMMENT = 'Chi tiết nguyên liệu chốt nhập hàng ngày tại Bếp'", ConstantHelper::TABLE_KITCHEN_DAILY_CHECKIN_ITEMS));
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists(ConstantHelper::TABLE_KITCHEN_DAILY_CHECKIN_ITEMS);
        Schema::dropIfExists(ConstantHelper::TABLE_KITCHEN_DAILY_CHECKINS);
    }
};
