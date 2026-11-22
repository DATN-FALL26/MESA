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
        Schema::create(ConstantHelper::TABLE_NOTIFICATION_READS, function (Blueprint $table) {
            $table->foreignId('notification_id')->constrained(ConstantHelper::TABLE_NOTIFICATIONS)->cascadeOnDelete()->comment('Mã thông báo');
            $table->foreignId('user_id')->constrained(ConstantHelper::TABLE_USERS)->restrictOnDelete()->comment('Người dùng đã đọc');
            $table->dateTime('read_at')->comment('Thời điểm đọc thông báo');
            $table->primary(['notification_id', 'user_id']);
        });

        DB::statement(sprintf("ALTER TABLE `%s` COMMENT = 'Trạng thái đã đọc thông báo của người dùng'", ConstantHelper::TABLE_NOTIFICATION_READS));
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists(ConstantHelper::TABLE_NOTIFICATION_READS);
    }
};
