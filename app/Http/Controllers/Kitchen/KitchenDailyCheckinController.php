<?php

declare(strict_types=1);

namespace App\Http\Controllers\Kitchen;

use App\Enums\WarehouseType;
use App\Helpers\ConstantHelper;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Kitchen\Concerns\ResolvesBranchScope;
use App\Models\Warehouse;
use App\Services\Kitchen\KitchenDailyCheckinService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class KitchenDailyCheckinController extends Controller
{
    use ResolvesBranchScope;

    public function __construct(
        protected KitchenDailyCheckinService $checkinService,
    ) {}

    /**
     * Tiếp nhận xác nhận nguyên liệu đầu ca hoặc nhập bổ sung từ Bếp.
     */
    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'branch_id' => ['required', 'integer', 'exists:'.ConstantHelper::TABLE_BRANCHES.',id'],
            'type' => ['nullable', 'string', 'in:start_shift,in_shift_addition'],
            'note' => ['nullable', 'string', 'max:255'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.ingredient_id' => ['required', 'integer', 'exists:'.ConstantHelper::TABLE_INGREDIENTS.',id'],
            'items.*.action' => ['required', 'string', 'in:inward,use_existing,out_of_stock'],
            'items.*.quantity' => ['nullable', 'numeric', 'min:0'],
            'items.*.unit_price' => ['nullable', 'numeric', 'min:0'],
            'items.*.note' => ['nullable', 'string', 'max:255'],
        ], [
            'branch_id.required' => 'Chi nhánh không hợp lệ.',
            'items.required' => 'Danh sách nguyên liệu không được để trống.',
            'items.min' => 'Vui lòng chọn ít nhất một nguyên liệu.',
            'items.*.action.required' => 'Vui lòng chọn trạng thái nguyên liệu.',
        ]);

        $branchId = (int) $validated['branch_id'];
        $this->abortUnlessBranchAccessible($this->accessibleBranchIds($request->user(), ConstantHelper::PERM_KITCHEN_VIEW), $branchId);

        $warehouse = Warehouse::query()
            ->where('branch_id', $branchId)
            ->where('is_active', true)
            ->first();

        if (! $warehouse) {
            $branch = Branch::query()->findOrFail($branchId);
            $warehouse = Warehouse::query()->create([
                'branch_id' => $branchId,
                'name' => 'Kho '.$branch->name,
                'type' => WarehouseType::BRANCH,
                'is_active' => true,
            ]);
        }

        $type = $validated['type'] ?? 'start_shift';

        $this->checkinService->performCheckin(
            branchId: $branchId,
            warehouseId: (int) $warehouse->id,
            userId: (int) $request->user()->id,
            itemsData: $validated['items'],
            type: $type,
            note: $validated['note'] ?? null,
        );

        $msg = $type === 'start_shift'
            ? 'Đã xác nhận nguyên liệu đầu ngày thành công. Bếp sẵn sàng làm việc!'
            : 'Đã nhập bổ sung nguyên liệu vào kho thành công.';

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => $msg,
            ]);
        }

        return back()->with('success', $msg);
    }
}
