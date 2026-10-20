<?php

declare(strict_types=1);

namespace App\Http\Controllers\Menu;

use App\Http\Controllers\Controller;
use App\Http\Requests\Menu\ItemVariantRequest;
use App\Models\ItemVariant;
use App\Models\MenuItem;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ItemVariantController extends Controller
{
    public function store(ItemVariantRequest $request, MenuItem $menuItem): RedirectResponse
    {
        $data = $request->payload();

        DB::transaction(function () use ($menuItem, $data): void {
            // Món chưa có biến thể mặc định thì biến thể đầu tiên tự là mặc định.
            $isDefault = $data['is_default'] || ! $menuItem->variants()->where('is_default', true)->exists();

            if ($isDefault) {
                $menuItem->variants()->update(['is_default' => false]);
            }

            $menuItem->variants()->create([
                'name' => $data['name'],
                'price_delta' => $data['price_delta'],
                'is_default' => $isDefault,
                'is_active' => $isDefault ? true : $data['is_active'],
            ]);
        });

        return redirect()->route('menu.items.edit', $menuItem)->with('success', 'Đã thêm biến thể.');
    }

    public function update(ItemVariantRequest $request, ItemVariant $variant): RedirectResponse
    {
        $data = $request->payload();

        if ($variant->is_default && (! $data['is_default'] || ! $data['is_active'])) {
            throw ValidationException::withMessages([
                'variant' => 'Biến thể mặc định phải đang hoạt động. Hãy đặt biến thể khác làm mặc định trước.',
            ]);
        }

        DB::transaction(function () use ($variant, $data): void {
            if ($data['is_default']) {
                $variant->menuItem->variants()->whereKeyNot($variant->getKey())->update(['is_default' => false]);
                $data['is_active'] = true;
            }

            $variant->update($data);
        });

        return redirect()->route('menu.items.edit', $variant->menu_item_id)->with('success', 'Đã cập nhật biến thể.');
    }

    public function destroy(ItemVariant $variant): RedirectResponse
    {
        // order_items.variant_id và recipes.variant_id tham chiếu RESTRICT; chặn trước để báo lỗi dễ hiểu.
        if ($variant->orderItems()->exists() || $variant->recipes()->exists()) {
            return back()->withErrors(['variant' => 'Biến thể đã được dùng trong đơn hàng hoặc công thức định lượng. Hãy tắt hoạt động thay vì xóa.']);
        }

        $menuItemId = (int) $variant->menu_item_id;
        $wasDefault = $variant->is_default;

        DB::transaction(function () use ($variant, $menuItemId, $wasDefault): void {
            $variant->delete();

            if ($wasDefault) {
                ItemVariant::query()
                    ->where('menu_item_id', $menuItemId)
                    ->where('is_active', true)
                    ->orderBy('id')
                    ->first()
                    ?->update(['is_default' => true]);
            }
        });

        return redirect()->route('menu.items.edit', $menuItemId)->with('success', 'Đã xóa biến thể.');
    }
}
