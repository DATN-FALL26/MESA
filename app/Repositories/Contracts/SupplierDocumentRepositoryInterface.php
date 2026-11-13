<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Models\SupplierDocument;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\UploadedFile;

interface SupplierDocumentRepositoryInterface
{
    /**
     * Lấy toàn bộ tài liệu của một nhà cung cấp.
     *
     * @return Collection<int, SupplierDocument>
     */
    public function getBySupplier(int $supplierId): Collection;

    /**
     * Tìm tài liệu theo ID.
     */
    public function findById(int $id): ?SupplierDocument;

    /**
     * Thêm mới tài liệu kèm file đính kèm.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(int $supplierId, array $data, ?UploadedFile $file = null): SupplierDocument;

    /**
     * Cập nhật thông tin tài liệu (có thể cập nhật kèm file mới).
     *
     * @param  array<string, mixed>  $data
     */
    public function update(SupplierDocument $document, array $data, ?UploadedFile $file = null): SupplierDocument;

    /**
     * Xóa tài liệu và file đính kèm.
     */
    public function delete(SupplierDocument $document): bool;

    /**
     * Danh sách tài liệu sắp hết hạn hoặc đã hết hạn.
     *
     * @return Collection<int, SupplierDocument>
     */
    public function getExpiringOrExpiredDocuments(int $daysThreshold = 30): Collection;
}
