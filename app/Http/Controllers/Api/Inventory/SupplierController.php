<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Inventory;

use App\Http\Controllers\Controller;
use App\Http\Requests\Inventory\StoreSupplierRequest;
use App\Http\Requests\Inventory\UpdateSupplierRequest;
use App\Http\Resources\Inventory\SupplierResource;
use App\Models\Supplier;
use App\Repositories\Contracts\SupplierRepositoryInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class SupplierController extends Controller
{
    public function __construct(
        protected SupplierRepositoryInterface $supplierRepository
    ) {}

    /**
     * Danh sách nhà cung cấp có phân trang và tìm kiếm.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $perPage = (int) $request->input('per_page', 15);
        $filters = $request->only(['search', 'is_active']);

        $suppliers = $this->supplierRepository->paginate($filters, $perPage);

        return SupplierResource::collection($suppliers);
    }

    /**
     * Danh sách toàn bộ nhà cung cấp đang hoạt động (dùng cho PO / Dropdown).
     */
    public function active(): AnonymousResourceCollection
    {
        $suppliers = $this->supplierRepository->getActiveSuppliers();

        return SupplierResource::collection($suppliers);
    }

    /**
     * Thêm mới nhà cung cấp.
     */
    public function store(StoreSupplierRequest $request): JsonResponse
    {
        $supplier = $this->supplierRepository->create($request->validated());

        return (new SupplierResource($supplier))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Chi tiết nhà cung cấp.
     */
    public function show(Supplier $supplier): SupplierResource
    {
        return new SupplierResource($supplier);
    }

    /**
     * Cập nhật thông tin nhà cung cấp.
     */
    public function update(UpdateSupplierRequest $request, Supplier $supplier): SupplierResource
    {
        $updated = $this->supplierRepository->update($supplier, $request->validated());

        return new SupplierResource($updated);
    }

    /**
     * Xóa nhà cung cấp (chặn xóa nếu đã có đơn mua hàng PO).
     */
    public function destroy(Supplier $supplier): JsonResponse
    {
        if ($this->supplierRepository->hasPurchaseOrders($supplier->id)) {
            return response()->json([
                'message' => 'Không thể xóa nhà cung cấp đã có đơn mua hàng (Purchase Order) liên quan.',
            ], 422);
        }

        $this->supplierRepository->delete($supplier);

        return response()->json([
            'message' => 'Đã xóa nhà cung cấp thành công.',
        ]);
    }
}
