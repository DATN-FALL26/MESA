<?php

declare(strict_types=1);

namespace App\Repositories\Eloquent;

use App\Models\SupplierDocument;
use App\Repositories\Contracts\SupplierDocumentRepositoryInterface;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class EloquentSupplierDocumentRepository implements SupplierDocumentRepositoryInterface
{
    /**
     * {@inheritDoc}
     */
    public function getBySupplier(int $supplierId): Collection
    {
        return SupplierDocument::query()
            ->where('supplier_id', $supplierId)
            ->orderBy('document_type')
            ->orderByDesc('created_at')
            ->get();
    }

    /**
     * {@inheritDoc}
     */
    public function findById(int $id): ?SupplierDocument
    {
        return SupplierDocument::query()->find($id);
    }

    /**
     * {@inheritDoc}
     */
    public function create(int $supplierId, array $data, ?UploadedFile $file = null): SupplierDocument
    {
        if ($file !== null) {
            $path = $file->store('supplier_documents', 'public');
            $data['file_path'] = $path;
            $data['file_name'] = $file->getClientOriginalName();
            $data['file_size'] = $file->getSize();
            $data['mime_type'] = $file->getClientMimeType();
        }

        $data['supplier_id'] = $supplierId;

        return SupplierDocument::query()->create($data);
    }

    /**
     * {@inheritDoc}
     */
    public function update(SupplierDocument $document, array $data, ?UploadedFile $file = null): SupplierDocument
    {
        if ($file !== null) {
            if (! empty($document->file_path) && Storage::disk('public')->exists($document->file_path)) {
                Storage::disk('public')->delete($document->file_path);
            }

            $path = $file->store('supplier_documents', 'public');
            $data['file_path'] = $path;
            $data['file_name'] = $file->getClientOriginalName();
            $data['file_size'] = $file->getSize();
            $data['mime_type'] = $file->getClientMimeType();
        }

        $document->update($data);

        return $document->fresh();
    }

    /**
     * {@inheritDoc}
     */
    public function delete(SupplierDocument $document): bool
    {
        if (! empty($document->file_path) && Storage::disk('public')->exists($document->file_path)) {
            Storage::disk('public')->delete($document->file_path);
        }

        return (bool) $document->delete();
    }

    /**
     * {@inheritDoc}
     */
    public function getExpiringOrExpiredDocuments(int $daysThreshold = 30): Collection
    {
        $thresholdDate = Carbon::now()->addDays($daysThreshold)->toDateString();

        return SupplierDocument::query()
            ->with('supplier')
            ->whereNotNull('expiry_date')
            ->where('expiry_date', '<=', $thresholdDate)
            ->orderBy('expiry_date')
            ->get();
    }
}
