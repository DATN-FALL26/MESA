<?php

declare(strict_types=1);

namespace App\Http\Controllers\Menu;

use App\Http\Controllers\Controller;
use App\Http\Requests\Menu\ModifierGroupRequest;
use App\Models\Ingredient;
use App\Models\ModifierGroup;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ModifierGroupController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q', ''));
        $status = (string) $request->query('status', '');

        $groups = ModifierGroup::query()
            ->withCount(['modifiers', 'menuItems'])
            ->when($search !== '', fn ($query) => $query->where('name', 'like', "%{$search}%"))
            ->when($status === 'active', fn ($query) => $query->where('is_active', true))
            ->when($status === 'inactive', fn ($query) => $query->where('is_active', false))
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return view('menu.modifier-groups.index', [
            'groups' => $groups,
            'filters' => compact('search', 'status'),
        ]);
    }

    public function store(ModifierGroupRequest $request): RedirectResponse
    {
        $group = ModifierGroup::query()->create($request->payload());

        return redirect()
            ->route('menu.modifier-groups.edit', $group)
            ->with('success', 'Đã tạo nhóm topping. Hãy thêm các topping vào nhóm.');
    }

    public function edit(ModifierGroup $modifierGroup): View
    {
        $modifierGroup->load(['modifiers' => fn ($query) => $query->orderBy('name'), 'modifiers.ingredient']);

        return view('menu.modifier-groups.edit', [
            'group' => $modifierGroup,
            'ingredients' => Ingredient::query()->where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function update(ModifierGroupRequest $request, ModifierGroup $modifierGroup): RedirectResponse
    {
        $modifierGroup->update($request->payload());

        return redirect()->route('menu.modifier-groups.edit', $modifierGroup)->with('success', 'Đã cập nhật nhóm topping.');
    }

    public function destroy(ModifierGroup $modifierGroup): RedirectResponse
    {
        if ($modifierGroup->modifiers()->exists()) {
            return back()->withErrors(['group' => 'Nhóm còn topping bên trong. Hãy xóa hoặc tắt các topping trước.']);
        }

        // Bảng gán món - nhóm xóa theo CASCADE.
        $modifierGroup->delete();

        return redirect()->route('menu.modifier-groups.index')->with('success', 'Đã xóa nhóm topping.');
    }
}
