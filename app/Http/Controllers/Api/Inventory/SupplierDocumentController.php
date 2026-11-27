<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Inventory;

use App\Enums\SupplierDocumentType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Inventory\StoreSupplierDocumentRequest;
use App\Http\Requests\Inventory\UpdateSupplierDocumentRequest;
use App\Http\Resources\Inventory\SupplierDocumentResource;
use App\Models\Supplier;
use App\Models\SupplierDocument;
use App\Repositories\Contracts\SupplierDocumentRepositoryInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class SupplierDocumentController extends Controller
{
    public function __construct(
        protected SupplierDocumentRepositoryInterface $documentRepository
    ) {}

    /**
     * Danh sách tài liệu của một nhà cung cấp.
     */
    public function index(Supplier $supplier): AnonymousResourceCollection
    {
        $documents = $this->documentRepository->getBySupplier($supplier->id);

        return SupplierDocumentResource::collection($documents);
    }

    /**
     * Thêm mới tài liệu / hồ sơ ATVSTP cho nhà cung cấp.
     */
    public function store(StoreSupplierDocumentRequest $request, Supplier $supplier): JsonResponse
    {
        $file = $request->file('file');
        $validated = $request->validated();
        unset($validated['file']);

        $document = $this->documentRepository->create($supplier->id, $validated, $file);

        return (new SupplierDocumentResource($document))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Xem chi tiết một tài liệu.
     */
    public function show(Supplier $supplier, SupplierDocument $document): SupplierDocumentResource
    {
        if ($document->supplier_id !== $supplier->id) {
            abort(404, 'Tài liệu không thuộc nhà cung cấp này.');
        }

        return new SupplierDocumentResource($document);
    }

    /**
     * Cập nhật thông tin tài liệu.
     */
    public function update(
        UpdateSupplierDocumentRequest $request,
        Supplier $supplier,
        SupplierDocument $document
    ): SupplierDocumentResource {
        if ($document->supplier_id !== $supplier->id) {
            abort(404, 'Tài liệu không thuộc nhà cung cấp này.');
        }

        $file = $request->file('file');
        $validated = $request->validated();
        unset($validated['file']);

        $updated = $this->documentRepository->update($document, $validated, $file);

        return new SupplierDocumentResource($updated);
    }

    /**
     * Xóa tài liệu.
     */
    public function destroy(Supplier $supplier, SupplierDocument $document): JsonResponse
    {
        if ($document->supplier_id !== $supplier->id) {
            abort(404, 'Tài liệu không thuộc nhà cung cấp này.');
        }

        $this->documentRepository->delete($document);

        return response()->json([
            'message' => 'Đã xóa tài liệu thành công.',
        ]);
    }

    /**
     * Danh sách tài liệu sắp hết hạn hoặc đã hết hạn trong hệ thống (cảnh báo ATVSTP).
     */
    public function expiring(Request $request): AnonymousResourceCollection
    {
        $daysThreshold = (int) $request->input('days', 30);
        $documents = $this->documentRepository->getExpiringOrExpiredDocuments($daysThreshold);

        return SupplierDocumentResource::collection($documents);
    }

    /**
     * Danh sách phân loại tài liệu ATVSTP (options dropdown).
     */
    public function types(): JsonResponse
    {
        return response()->json([
            'data' => SupplierDocumentType::options(),
        ]);
    }
}
