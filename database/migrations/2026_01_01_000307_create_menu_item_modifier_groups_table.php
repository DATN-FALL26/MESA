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
        Schema::create(ConstantHelper::TABLE_MENU_ITEM_MODIFIER_GROUPS, function (Blueprint $table) {
            $table->foreignId('menu_item_id')->constrained(ConstantHelper::TABLE_MENU_ITEMS)->cascadeOnDelete()->comment('Mã món ăn');
            $table->foreignId('group_id')->constrained(ConstantHelper::TABLE_MODIFIER_GROUPS)->cascadeOnDelete()->comment('Mã nhóm tùy chọn');
            $table->integer('sort_order')->default(0)->comment('Thứ tự sắp xếp hiển thị');
            $table->primary(['menu_item_id', 'group_id']);
        });

        DB::statement(sprintf("ALTER TABLE `%s` COMMENT = 'Gán nhóm tùy chọn cho món ăn'", ConstantHelper::TABLE_MENU_ITEM_MODIFIER_GROUPS));
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists(ConstantHelper::TABLE_MENU_ITEM_MODIFIER_GROUPS);
    }
};
