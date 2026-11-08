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
        Schema::create(ConstantHelper::TABLE_ROLE_PERMISSIONS, function (Blueprint $table) {
            $table->foreignId('role_id')->constrained(ConstantHelper::TABLE_ROLES)->cascadeOnDelete()->comment('Mã vai trò chức vụ');
            $table->foreignId('permission_id')->constrained(ConstantHelper::TABLE_PERMISSIONS)->cascadeOnDelete()->comment('Mã quyền hạn');
            $table->primary(['role_id', 'permission_id']);
        });

        DB::statement(sprintf("ALTER TABLE `%s` COMMENT = 'Bảng phân quyền cho vai trò'", ConstantHelper::TABLE_ROLE_PERMISSIONS));
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists(ConstantHelper::TABLE_ROLE_PERMISSIONS);
    }
};
