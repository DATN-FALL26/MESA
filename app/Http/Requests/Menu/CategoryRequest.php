<?php

declare(strict_types=1);

namespace App\Http\Requests\Menu;

use App\Models\Category;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var Category|null $category */
        $category = $this->route('category');

        return [
            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique(Category::class, 'name')
                    ->where(fn ($query) => $query->where('parent_id', $this->input('parent_id') ?: null))
                    ->ignore($category?->getKey()),
            ],
            'parent_id' => [
                'nullable',
                'integer',
                'exists:categories,id',
                function (string $attribute, mixed $value, \Closure $fail) use ($category): void {
                    if ($value === null || $value === '') {
                        return;
                    }

                    if ($category !== null && (int) $value === (int) $category->getKey()) {
                        $fail('Danh mục không thể là cha của chính nó.');

                        return;
                    }

                    if (Category::query()->whereKey($value)->whereNotNull('parent_id')->exists()) {
                        $fail('Hệ thống chỉ hỗ trợ 2 cấp danh mục (cha - con).');

                        return;
                    }

                    if ($category !== null && $category->children()->exists()) {
                        $fail('Danh mục đang có danh mục con nên không thể chuyển thành danh mục con.');
                    }
                },
            ],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Tên danh mục không được để trống.',
            'name.unique' => 'Đã có danh mục cùng tên trong cùng nhóm cha.',
            'name.max' => 'Tên danh mục tối đa 100 ký tự.',
            'parent_id.exists' => 'Danh mục cha không tồn tại.',
            'sort_order.integer' => 'Thứ tự hiển thị phải là số nguyên.',
        ];
    }

    /**
     * @return array{name: string, parent_id: int|null, sort_order: int, is_active: bool}
     */
    public function payload(): array
    {
        $data = $this->validated();

        return [
            'name' => trim($data['name']),
            'parent_id' => ! empty($data['parent_id']) ? (int) $data['parent_id'] : null,
            'sort_order' => (int) ($data['sort_order'] ?? 0),
            'is_active' => $this->boolean('is_active'),
        ];
    }
}
