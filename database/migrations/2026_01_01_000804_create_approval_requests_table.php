<?php

declare(strict_types=1);

use App\Enums\ApprovalStatus;
use App\Enums\ApprovalType;
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
        Schema::create(ConstantHelper::TABLE_APPROVAL_REQUESTS, function (Blueprint $table) {
            $table->id()->comment('Mã định danh yêu cầu phê duyệt');
            $table->foreignId('branch_id')->constrained(ConstantHelper::TABLE_BRANCHES)->restrictOnDelete()->comment('Chi nhánh phát sinh yêu cầu');
            $table->enum('request_type', ApprovalType::values())->comment('Loại phê duyệt (purchase_order, stock_adjustment, ai_suggestion)');
            $table->unsignedBigInteger('ref_id')->comment('Mã định danh đối tượng cần duyệt (tham chiếu đa hình, không FK)');
            $table->foreignId('requested_by')->nullable()->constrained(ConstantHelper::TABLE_USERS)->restrictOnDelete()->comment('Người gửi yêu cầu (NULL nếu AI hoặc hệ thống tạo)');
            $table->enum('status', ApprovalStatus::values())->default(ApprovalStatus::PENDING->value)->comment('Trạng thái phê duyệt (pending, approved, rejected)');
            $table->foreignId('decided_by')->nullable()->constrained(ConstantHelper::TABLE_USERS)->restrictOnDelete()->comment('Người ra quyết định phê duyệt');
            $table->dateTime('decided_at')->nullable()->comment('Thời điểm ra quyết định');
            $table->string('note', 255)->nullable()->comment('Ghi chú lý do phê duyệt hoặc từ chối');
            $table->dateTime('created_at')->nullable()->comment('Thời điểm tạo bản ghi');
            $table->dateTime('updated_at')->nullable()->comment('Thời điểm cập nhật bản ghi');

            $table->index(['branch_id', 'status'], 'idx_approval_requests_branch_status');
            $table->index(['request_type', 'ref_id'], 'idx_approval_requests_type_ref');
        });

        DB::statement(sprintf("ALTER TABLE `%s` COMMENT = 'Yêu cầu phê duyệt nghiệp vụ và đề xuất AI'", ConstantHelper::TABLE_APPROVAL_REQUESTS));
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists(ConstantHelper::TABLE_APPROVAL_REQUESTS);
    }
};
