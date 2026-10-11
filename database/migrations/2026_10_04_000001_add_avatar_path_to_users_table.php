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
        Schema::table(ConstantHelper::TABLE_USERS, function (Blueprint $table) {
            $table->string('avatar_path', 255)->nullable()->after('full_name')->comment('Đường dẫn ảnh đại diện');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table(ConstantHelper::TABLE_USERS, function (Blueprint $table) {
            $table->dropColumn('avatar_path');
        });
    }
};
