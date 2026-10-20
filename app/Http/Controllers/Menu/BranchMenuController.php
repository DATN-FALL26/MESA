<?php

declare(strict_types=1);

namespace App\Http\Controllers\Menu;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Menu\Concerns\ResolvesAccessibleBranches;
use App\Models\Branch;
use App\Models\BranchMenuItem;
use App\Models\Category;
use App\Models\MenuItem;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BranchMenuController extends Controller
{
    use ResolvesAccessibleBranches;

    public function index(Request $request): View
    {
        $branches = $this->accessibleBranches($request->user());
        abort_if($branches->isEmpty(), 403, 'Bạn chưa được gán chi nhánh nào để quản lý thực đơn.');

        $branch = $branches->firstWhere('id', (int) $request->query('branch_id')) ?? $branches->first();
        $search = trim((string) $request->query('q', ''));
        $categoryId = $request->integer('category_id') ?: null;

        $items = MenuItem::query()
            ->with('category')
            ->where('is_active', true)
            ->when($search !== '', fn ($query) => $query->where(
                fn ($inner) => $inner->where('name', 'like', "%{$search}%")->orWhere('sku', 'like', "%{$search}%")
            ))
            ->when($categoryId, fn ($query) => $query->where('category_id', $categoryId))
            ->orderBy('category_id')
            ->orderBy('name')
            ->get();

        $rows = BranchMenuItem::query()
            ->where('branch_id', $branch->getKey())
            ->whereIn('menu_item_id', $items->pluck('id'))
            ->get()
            ->keyBy('menu_item_id');

        return view('menu.availability.index', [
            'branches' => $branches,
            'branch' => $branch,
            'items' => $items,
            'rows' => $rows,
            'categories' => Category::query()->orderBy('sort_order')->orderBy('name')->get(),
            'filters' => compact('search', 'categoryId'),
        ]);
    }

    /**
     * Bật/tắt "còn hàng / hết hàng trong ngày" của một món tại chi nhánh.
     */
    public function updateAvailability(Request $request, Branch $branch, MenuItem $menuItem): RedirectResponse
    {
        $this->authorizeBranch($request->user(), $branch);

        $available = $request->boolean('is_available');

        BranchMenuItem::query()->updateOrCreate(
            ['branch_id' => $branch->getKey(), 'menu_item_id' => $menuItem->getKey()],
            ['is_available' => $available]
        );

        return back()->with('success', $available
            ? "Đã mở bán lại \"{$menuItem->name}\" tại {$branch->name}."
            : "Đã báo hết \"{$menuItem->name}\" tại {$branch->name}.");
    }

    /**
     * Ghi đè giá bán theo chi nhánh. Để trống = dùng giá gốc (base_price).
     * Đơn đã tạo giữ nguyên giá nhờ cột snapshot trong order_items.
     */
    public function updatePrice(Request $request, Branch $branch, MenuItem $menuItem): RedirectResponse
    {
        $this->authorizeBranch($request->user(), $branch);

        $validated = $request->validate([
            'price' => ['nullable', 'integer', 'min:0', 'max:999999999'],
        ], [
            'price.integer' => 'Giá bán là số nguyên VND.',
            'price.min' => 'Giá bán không được âm.',
        ]);

        $price = isset($validated['price']) ? (int) $validated['price'] : null;

        BranchMenuItem::query()->updateOrCreate(
            ['branch_id' => $branch->getKey(), 'menu_item_id' => $menuItem->getKey()],
            ['price' => $price]
        );

        return back()->with('success', $price === null
            ? "Đã đưa \"{$menuItem->name}\" về giá gốc tại {$branch->name}."
            : "Đã đặt giá riêng cho \"{$menuItem->name}\" tại {$branch->name}.");
    }

    /**
     * Đầu ngày: mở bán lại toàn bộ món đang bị báo hết tại chi nhánh.
     */
    public function resetAvailability(Request $request, Branch $branch): RedirectResponse
    {
        $this->authorizeBranch($request->user(), $branch);

        $count = BranchMenuItem::query()
            ->where('branch_id', $branch->getKey())
            ->where('is_available', false)
            ->update(['is_available' => true]);

        return back()->with('success', "Đã mở bán lại {$count} món tại {$branch->name}.");
    }
}
