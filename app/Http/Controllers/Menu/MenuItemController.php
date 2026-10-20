<?php

declare(strict_types=1);

namespace App\Http\Controllers\Menu;

use App\Enums\ItemType;
use App\Enums\StationCode;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Menu\Concerns\ResolvesAccessibleBranches;
use App\Http\Requests\Menu\MenuItemRequest;
use App\Models\BranchMenuItem;
use App\Models\Category;
use App\Models\MenuItem;
use App\Models\ModifierGroup;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Throwable;

class MenuItemController extends Controller
{
    use ResolvesAccessibleBranches;

    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q', ''));
        $categoryId = $request->integer('category_id') ?: null;
        $itemType = (string) $request->query('item_type', '');
        $status = (string) $request->query('status', '');

        $items = MenuItem::query()
            ->with('category')
            ->withCount('variants')
            ->when($search !== '', fn ($query) => $query->where(
                fn ($inner) => $inner->where('name', 'like', "%{$search}%")->orWhere('sku', 'like', "%{$search}%")
            ))
            ->when($categoryId, fn ($query) => $query->where('category_id', $categoryId))
            ->when(ItemType::tryFrom($itemType) !== null, fn ($query) => $query->where('item_type', $itemType))
            ->when($status === 'active', fn ($query) => $query->where('is_active', true))
            ->when($status === 'inactive', fn ($query) => $query->where('is_active', false))
            ->orderBy('category_id')
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return view('menu.items.index', [
            'items' => $items,
            'categories' => Category::query()->orderBy('sort_order')->orderBy('name')->get(),
            'itemTypes' => ItemType::cases(),
            'filters' => compact('search', 'categoryId', 'itemType', 'status'),
            'stats' => [
                'all' => MenuItem::query()->count(),
                'active' => MenuItem::query()->where('is_active', true)->count(),
                'inactive' => MenuItem::query()->where('is_active', false)->count(),
            ],
        ]);
    }

    public function create(Request $request): View
    {
        return view('menu.items.create', [
            'menuItem' => new MenuItem,
            'categories' => $this->categoryOptions(),
            'itemTypes' => ItemType::cases(),
            'stations' => StationCode::cases(),
            'branches' => $this->accessibleBranches($request->user()),
        ]);
    }

    public function store(MenuItemRequest $request): RedirectResponse
    {
        $data = $request->itemData();
        $imagePath = $request->hasFile('image')
            ? $request->file('image')->store('menu-items', 'public')
            : null;

        try {
            $menuItem = DB::transaction(function () use ($request, $data, $imagePath): MenuItem {
                $menuItem = MenuItem::query()->create($data + ['image_path' => $imagePath]);

                // Khách đặt qua QR chỉ thấy món có dòng branch_menu_items đang còn hàng,
                // nên tạo sẵn cấu hình cho các chi nhánh được chọn (chỉ trong phạm vi quyền).
                $allowedIds = $this->accessibleBranches($request->user())->pluck('id')->all();
                $branchIds = array_values(array_intersect(
                    array_map('intval', (array) $request->input('branch_ids', [])),
                    $allowedIds
                ));

                foreach ($branchIds as $branchId) {
                    BranchMenuItem::query()->create([
                        'branch_id' => $branchId,
                        'menu_item_id' => $menuItem->getKey(),
                        'price' => null,
                        'is_available' => true,
                    ]);
                }

                return $menuItem;
            });
        } catch (Throwable $exception) {
            if ($imagePath !== null) {
                Storage::disk('public')->delete($imagePath);
            }

            throw $exception;
        }

        return redirect()
            ->route('menu.items.edit', $menuItem)
            ->with('success', 'Đã tạo món. Bạn có thể thêm biến thể (size) và topping bên dưới.');
    }

    public function edit(MenuItem $menuItem): View
    {
        $menuItem->load([
            'variants' => fn ($query) => $query->orderByDesc('is_default')->orderBy('name'),
            'menuItemModifierGroups',
        ]);

        $assignedIds = $menuItem->menuItemModifierGroups->sortBy('sort_order')->pluck('group_id')->all();

        return view('menu.items.edit', [
            'menuItem' => $menuItem,
            'categories' => $this->categoryOptions($menuItem->category_id),
            'itemTypes' => ItemType::cases(),
            'stations' => StationCode::cases(),
            'groups' => ModifierGroup::query()->withCount('modifiers')->orderBy('name')->get(),
            'assignedIds' => $assignedIds,
        ]);
    }

    public function update(MenuItemRequest $request, MenuItem $menuItem): RedirectResponse
    {
        $data = $request->itemData();
        $oldImage = $menuItem->image_path;
        $newImage = null;

        if ($request->hasFile('image')) {
            $newImage = $request->file('image')->store('menu-items', 'public');
            $data['image_path'] = $newImage;
        } elseif ($request->boolean('remove_image')) {
            $data['image_path'] = null;
        }

        try {
            $menuItem->update($data);
        } catch (Throwable $exception) {
            if ($newImage !== null) {
                Storage::disk('public')->delete($newImage);
            }

            throw $exception;
        }

        if (array_key_exists('image_path', $data) && $oldImage !== null && $oldImage !== $data['image_path']) {
            Storage::disk('public')->delete($oldImage);
        }

        return redirect()->route('menu.items.edit', $menuItem)->with('success', 'Đã cập nhật món.');
    }

    public function toggle(MenuItem $menuItem): RedirectResponse
    {
        $menuItem->update(['is_active' => ! $menuItem->is_active]);

        return back()->with('success', $menuItem->is_active ? 'Đã bật kinh doanh món.' : 'Đã tạm ngừng kinh doanh món.');
    }

    /**
     * Xóa mềm: giữ nguyên lịch sử đơn hàng, báo cáo và công thức tham chiếu tới món.
     */
    public function destroy(MenuItem $menuItem): RedirectResponse
    {
        $menuItem->delete();

        return redirect()->route('menu.items.index')->with('success', 'Đã xóa món (xóa mềm, lịch sử đơn hàng vẫn được giữ).');
    }

    public function syncModifierGroups(Request $request, MenuItem $menuItem): RedirectResponse
    {
        $validated = $request->validate([
            'group_ids' => ['nullable', 'array'],
            'group_ids.*' => ['integer', 'distinct', 'exists:modifier_groups,id'],
        ]);

        $sync = [];
        foreach (array_values($validated['group_ids'] ?? []) as $position => $groupId) {
            $sync[(int) $groupId] = ['sort_order' => $position];
        }

        $menuItem->modifierGroups()->sync($sync);

        return redirect()->route('menu.items.edit', $menuItem)->with('success', 'Đã cập nhật nhóm topping của món.');
    }

    /**
     * Danh mục đang hoạt động, giữ thêm danh mục hiện tại của món (có thể đã bị tắt).
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, Category>
     */
    private function categoryOptions(?int $currentCategoryId = null)
    {
        return Category::query()
            ->where(fn ($query) => $query->where('is_active', true)
                ->when($currentCategoryId, fn ($inner) => $inner->orWhere('id', $currentCategoryId)))
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }
}
