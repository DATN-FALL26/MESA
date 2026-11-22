<?php

declare(strict_types=1);

use App\Enums\StationCode;
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
        Schema::create(ConstantHelper::TABLE_STATIONS, function (Blueprint $table) {
            $table->id()->comment('Mã định danh quầy chế biến (màn hình KDS)');
            $table->foreignId('branch_id')->constrained(ConstantHelper::TABLE_BRANCHES)->restrictOnDelete()->comment('Chi nhánh trực thuộc');
            $table->enum('station_code', StationCode::values())->comment('Mã phân loại quầy (PHO, DRINK, SIDE)');
            $table->string('name', 100)->comment('Tên quầy chế biến');
            $table->foreignId('printer_id')->nullable()->constrained(ConstantHelper::TABLE_PRINTERS)->nullOnDelete()->comment('Máy in tương ứng của quầy');
            $table->boolean('is_active')->default(true)->comment('Trạng thái hoạt động');
            $table->dateTime('created_at')->nullable()->comment('Thời điểm tạo bản ghi');
            $table->dateTime('updated_at')->nullable()->comment('Thời điểm cập nhật bản ghi');

            $table->unique(['branch_id', 'station_code', 'name'], 'uq_stations_branch_code_name');
        });

        DB::statement(sprintf("ALTER TABLE `%s` COMMENT = 'Quầy chế biến tại chi nhánh (KDS)'", ConstantHelper::TABLE_STATIONS));
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists(ConstantHelper::TABLE_STATIONS);
    }
};
