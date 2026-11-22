<?php

declare(strict_types=1);

namespace App\Http\Controllers\Menu;

use App\Http\Controllers\Controller;
use App\Http\Requests\Menu\ModifierRequest;
use App\Models\Modifier;
use App\Models\ModifierGroup;
use Illuminate\Http\RedirectResponse;

class ModifierController extends Controller
{
    public function store(ModifierRequest $request, ModifierGroup $modifierGroup): RedirectResponse
    {
        $modifierGroup->modifiers()->create($request->payload());

        return redirect()->route('menu.modifier-groups.edit', $modifierGroup)->with('success', 'Đã thêm topping.');
    }

    public function update(ModifierRequest $request, Modifier $modifier): RedirectResponse
    {
        $modifier->update($request->payload());

        return redirect()->route('menu.modifier-groups.edit', $modifier->group_id)->with('success', 'Đã cập nhật topping.');
    }

    public function destroy(Modifier $modifier): RedirectResponse
    {
        // order_item_modifiers.modifier_id tham chiếu RESTRICT: topping đã bán thì chỉ được tắt.
        if ($modifier->orderItemModifiers()->exists()) {
            return back()->withErrors(['modifier' => 'Topping đã được dùng trong đơn hàng. Hãy tắt hoạt động thay vì xóa.']);
        }

        $groupId = (int) $modifier->group_id;
        $modifier->delete();

        return redirect()->route('menu.modifier-groups.edit', $groupId)->with('success', 'Đã xóa topping.');
    }
}
