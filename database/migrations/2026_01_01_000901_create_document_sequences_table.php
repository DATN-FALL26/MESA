<?php

declare(strict_types=1);

use App\Enums\SequenceResetPolicy;
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
        Schema::create(ConstantHelper::TABLE_DOCUMENT_SEQUENCES, function (Blueprint $table) {
            $table->id()->comment('Mã định danh chuỗi số chứng từ');
            $table->foreignId('branch_id')->constrained(ConstantHelper::TABLE_BRANCHES)->restrictOnDelete()->comment('Chi nhánh cấp số chứng từ');
            $table->string('doc_type', 30)->comment('Loại chứng từ (ORDER, INVOICE, PO...)');
            $table->string('period_key', 10)->default('')->comment('Khóa chu kỳ thời gian (ví dụ: 20261003)');
            $table->string('prefix', 20)->default('')->comment('Tiền tố mã chứng từ');
            $table->unsignedBigInteger('current_no')->default(0)->comment('Số thứ tự hiện tại của chuỗi');
            $table->unsignedTinyInteger('padding')->default(5)->comment('Độ dài đệm số (số chữ số 0 phía trước)');
            $table->enum('reset_policy', SequenceResetPolicy::values())->default(SequenceResetPolicy::DAILY->value)->comment('Quy tắc đặt lại chuỗi số');
            $table->dateTime('created_at')->nullable()->comment('Thời điểm tạo bản ghi');
            $table->dateTime('updated_at')->nullable()->comment('Thời điểm cập nhật bản ghi');

            $table->unique(['branch_id', 'doc_type', 'period_key'], 'uq_doc_sequences_branch_type_period');
        });

        DB::statement(sprintf("ALTER TABLE `%s` COMMENT = 'Quản lý sinh số chứng từ không trùng lặp'", ConstantHelper::TABLE_DOCUMENT_SEQUENCES));
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists(ConstantHelper::TABLE_DOCUMENT_SEQUENCES);
    }
};
