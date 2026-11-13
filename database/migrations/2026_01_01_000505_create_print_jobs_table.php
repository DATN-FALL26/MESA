<?php

declare(strict_types=1);

use App\Enums\PrintDocType;
use App\Enums\PrintJobStatus;
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
        Schema::create(ConstantHelper::TABLE_PRINT_JOBS, function (Blueprint $table) {
            $table->id()->comment('Mã định danh lệnh in');
            $table->foreignId('printer_id')->constrained(ConstantHelper::TABLE_PRINTERS)->restrictOnDelete()->comment('Máy in thực hiện');
            $table->enum('doc_type', PrintDocType::values())->comment('Loại chứng từ in (kitchen_ticket, invoice, payment_request)');
            $table->unsignedBigInteger('ref_id')->comment('Mã định danh đối tượng cần in');
            $table->json('payload')->nullable()->comment('Dữ liệu in ấn dạng JSON');
            $table->enum('status', PrintJobStatus::values())->default(PrintJobStatus::QUEUED->value)->comment('Trạng thái lệnh in');
            $table->unsignedTinyInteger('retry_count')->default(0)->comment('Số lần thử in lại khi thất bại');
            $table->dateTime('printed_at')->nullable()->comment('Thời điểm in thành công');
            $table->dateTime('created_at')->nullable()->comment('Thời điểm tạo bản ghi');
            $table->dateTime('updated_at')->nullable()->comment('Thời điểm cập nhật bản ghi');

            $table->index(['printer_id', 'status'], 'idx_print_jobs_printer_status');
        });

        DB::statement(sprintf("ALTER TABLE `%s` COMMENT = 'Hàng đợi lệnh in ấn'", ConstantHelper::TABLE_PRINT_JOBS));
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists(ConstantHelper::TABLE_PRINT_JOBS);
    }
};
