<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Inventory;

use App\Enums\BranchStatus;
use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Warehouse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WarehouseController extends Controller
{
    /**
     * Danh sách kho hàng kèm chi nhánh tương ứng.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Warehouse::query()
            ->with('branch:id,code,name')
            ->where('is_active', true);

        $allowedBranchIds = $request->attributes->get('authorized_branch_ids');
        $selectedBranchId = $request->attributes->get('selected_branch_id');
        $query
            ->when($selectedBranchId !== null, fn ($builder) => $builder->where('branch_id', $selectedBranchId))
            ->when(is_array($allowedBranchIds), fn ($builder) => $builder->whereIn('branch_id', $allowedBranchIds));

        $warehouses = $query->orderBy('branch_id')->orderBy('name')->get();

        return response()->json([
            'data' => $warehouses->map(fn ($w) => [
                'id' => $w->id,
                'name' => $w->name,
                'type' => $w->type->value,
                'branch_id' => $w->branch_id,
                'branch_name' => $w->branch?->name,
                'branch_code' => $w->branch?->code,
            ]),
        ]);
    }

    /**
     * Danh sách các chi nhánh / cơ sở đang hoạt động.
     */
    public function branches(Request $request): JsonResponse
    {
        $allowedBranchIds = $request->attributes->get('authorized_branch_ids');
        $selectedBranchId = $request->attributes->get('selected_branch_id');

        $branches = Branch::query()
            ->with(['warehouses' => fn ($q) => $q->where('is_active', true)])
            ->where('status', BranchStatus::ACTIVE)
            ->when($selectedBranchId !== null, fn ($query) => $query->whereKey($selectedBranchId))
            ->when(is_array($allowedBranchIds), fn ($query) => $query->whereIn('id', $allowedBranchIds))
            ->orderBy('id')
            ->get();

        return response()->json([
            'data' => $branches->map(fn ($b) => [
                'id' => $b->id,
                'code' => $b->code,
                'name' => $b->name,
                'warehouses' => $b->warehouses->map(fn ($w) => [
                    'id' => $w->id,
                    'name' => $w->name,
                    'type' => $w->type->value,
                ]),
            ]),
        ]);
    }
}
