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
        Schema::create(ConstantHelper::TABLE_SUPPLIER_DOCUMENTS, function (Blueprint $table) {
            $table->id()->comment('Mã định danh hồ sơ tài liệu');
            $table->foreignId('supplier_id')->constrained(ConstantHelper::TABLE_SUPPLIERS)->cascadeOnDelete()->comment('Mã nhà cung cấp');
            $table->string('document_type', 50)->comment('Loại tài liệu ATVSTP (food_safety_cert, business_license, test_report, v.v.)');
            $table->string('document_number', 100)->nullable()->comment('Số hiệu văn bản/giấy chứng nhận');
            $table->string('document_name', 255)->comment('Tên hiển thị của tài liệu');
            $table->string('issuing_authority', 255)->nullable()->comment('Cơ quan cấp chứng nhận');
            $table->date('issue_date')->nullable()->comment('Ngày cấp');
            $table->date('expiry_date')->nullable()->comment('Ngày hết hạn');
            $table->string('file_path', 500)->comment('Đường dẫn file lưu trữ trên storage');
            $table->string('file_name', 255)->comment('Tên file gốc');
            $table->unsignedBigInteger('file_size')->nullable()->comment('Kích thước file (bytes)');
            $table->string('mime_type', 100)->nullable()->comment('Loại file (MIME type)');
            $table->text('notes')->nullable()->comment('Ghi chú bổ sung');
            $table->dateTime('created_at')->nullable()->comment('Thời điểm tạo');
            $table->dateTime('updated_at')->nullable()->comment('Thời điểm cập nhật');

            $table->index(['supplier_id', 'document_type'], 'idx_supplier_docs_type');
            $table->index('expiry_date', 'idx_supplier_docs_expiry');
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement(sprintf("ALTER TABLE `%s` COMMENT = 'Hồ sơ, giấy tờ ATVSTP và pháp lý của nhà cung cấp'", ConstantHelper::TABLE_SUPPLIER_DOCUMENTS));
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists(ConstantHelper::TABLE_SUPPLIER_DOCUMENTS);
    }
};
