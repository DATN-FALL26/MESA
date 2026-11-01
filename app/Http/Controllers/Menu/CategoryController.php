<?php

declare(strict_types=1);

namespace App\Http\Controllers\Menu;

use App\Http\Controllers\Controller;
use App\Http\Requests\Menu\CategoryRequest;
use App\Models\Category;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CategoryController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q', ''));
        $status = (string) $request->query('status', '');

        $categories = Category::query()
            ->with('parent')
            ->withCount(['menuItems', 'children'])
            ->when($search !== '', fn ($query) => $query->where('name', 'like', "%{$search}%"))
            ->when($status === 'active', fn ($query) => $query->where('is_active', true))
            ->when($status === 'inactive', fn ($query) => $query->where('is_active', false))
            ->get()
            // Xếp theo cây: danh mục cha (theo thứ tự) rồi tới các danh mục con của nó.
            ->sortBy(fn (Category $category): array => [
                $category->parent_id === null ? $category->sort_order : ($category->parent?->sort_order ?? 0),
                $category->parent_id === null ? $category->name : ($category->parent?->name ?? ''),
                $category->parent_id === null ? 0 : 1,
                $category->sort_order,
                $category->name,
            ])
            ->values();

        return view('menu.categories.index', [
            'categories' => $categories,
            'parents' => Category::query()->whereNull('parent_id')->orderBy('sort_order')->orderBy('name')->get(),
            'filters' => compact('search', 'status'),
            'stats' => [
                'all' => Category::query()->count(),
                'active' => Category::query()->where('is_active', true)->count(),
            ],
        ]);
    }

    public function store(CategoryRequest $request): RedirectResponse
    {
        Category::query()->create($request->payload());

        return redirect()->route('menu.categories.index')->with('success', 'Đã tạo danh mục.');
    }

    public function update(CategoryRequest $request, Category $category): RedirectResponse
    {
        $category->update($request->payload());

        return redirect()->route('menu.categories.index')->with('success', 'Đã cập nhật danh mục.');
    }

    public function destroy(Category $category): RedirectResponse
    {
        if ($category->children()->exists()) {
            return back()->withErrors(['category' => 'Danh mục đang có danh mục con. Hãy xóa hoặc chuyển danh mục con trước.']);
        }

        // withTrashed: món đã xóa mềm vẫn giữ FK RESTRICT tới danh mục.
        if ($category->menuItems()->withTrashed()->exists()) {
            return back()->withErrors(['category' => 'Danh mục đang có món (kể cả món đã xóa). Hãy tắt hoạt động thay vì xóa.']);
        }

        $category->delete();

        return redirect()->route('menu.categories.index')->with('success', 'Đã xóa danh mục.');
    }
}
