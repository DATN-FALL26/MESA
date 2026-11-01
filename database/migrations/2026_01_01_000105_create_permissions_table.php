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
        Schema::create(ConstantHelper::TABLE_PERMISSIONS, function (Blueprint $table) {
            $table->id()->comment('Mã định danh quyền hạn');
            $table->string('code', 80)->unique()->comment('Mã định danh quyền hạn duy nhất');
            $table->string('module', 50)->comment('Phân hệ / module quản lý');
            $table->string('name', 150)->comment('Tên quyền hạn tiếng Việt');
            $table->string('description', 255)->nullable()->comment('Mô tả chi tiết quyền hạn');
        });

        DB::statement(sprintf("ALTER TABLE `%s` COMMENT = 'Danh mục quyền hạn chi tiết'", ConstantHelper::TABLE_PERMISSIONS));
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists(ConstantHelper::TABLE_PERMISSIONS);
    }
};
