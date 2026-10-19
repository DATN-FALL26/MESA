<?php

declare(strict_types=1);

namespace App\Http\Controllers\Menu;

use App\Http\Controllers\Controller;
use App\Http\Requests\Menu\CategoryRequest;
use App\Models\Category;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class CategoryController extends Controller
{
    public function index(): View
    {
        $categories = Category::query()
            ->whereNull('parent_id')
            ->withCount('menuItems')
            ->with(['children' => fn ($query) => $query
                ->withCount('menuItems')
                ->orderBy('sort_order')
                ->orderBy('name')])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return view('menu.categories.index', compact('categories'));
    }

    public function create(): View
    {
        return view('menu.categories.form', [
            'category' => new Category,
            'parents' => $this->parentOptions(),
        ]);
    }

    public function store(CategoryRequest $request): RedirectResponse
    {
        Category::query()->create($request->payload());

        return redirect()->route('menu.categories.index')->with('success', 'Đã tạo danh mục.');
    }

    public function edit(Category $category): View
    {
        return view('menu.categories.form', [
            'category' => $category,
            'parents' => $this->parentOptions($category),
        ]);
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

    /**
     * Danh mục cha hợp lệ: chỉ danh mục gốc, không gồm chính nó.
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, Category>
     */
    private function parentOptions(?Category $category = null)
    {
        return Category::query()
            ->whereNull('parent_id')
            ->when($category?->exists, fn ($query) => $query->whereKeyNot($category->getKey()))
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }
}
