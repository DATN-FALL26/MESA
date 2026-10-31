<?php

declare(strict_types=1);

namespace App\Http\Requests\Inventory;

use App\Helpers\ConstantHelper;
use Illuminate\Foundation\Http\FormRequest;

class StoreRecipeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'menu_item_id' => [
                'required',
                'integer',
                "exists:" . ConstantHelper::TABLE_MENU_ITEMS . ",id",
            ],
            'variant_id' => [
                'nullable',
                'integer',
                "exists:" . ConstantHelper::TABLE_ITEM_VARIANTS . ",id",
            ],
            'activate_immediately' => ['nullable', 'boolean'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.ingredient_id' => [
                'required',
                'integer',
                'distinct',
                "exists:" . ConstantHelper::TABLE_INGREDIENTS . ",id",
            ],
            'items.*.quantity' => ['required', 'numeric', 'gt:0'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'menu_item_id.required' => 'Món ăn là bắt buộc.',
            'menu_item_id.exists' => 'Món ăn được chọn không tồn tại.',
            'variant_id.exists' => 'Biến thể món ăn không tồn tại.',
            'items.required' => 'Danh sách nguyên liệu trong công thức là bắt buộc.',
            'items.min' => 'Công thức phải có ít nhất 1 nguyên vật liệu.',
            'items.*.ingredient_id.required' => 'Nguyên liệu là bắt buộc.',
            'items.*.ingredient_id.exists' => 'Nguyên liệu không tồn tại trong hệ thống.',
            'items.*.ingredient_id.distinct' => 'Không được chọn trùng lặp cùng một nguyên liệu trong một công thức.',
            'items.*.quantity.required' => 'Định lượng nguyên liệu là bắt buộc.',
            'items.*.quantity.gt' => 'Định lượng nguyên liệu phải lớn hơn 0.',
        ];
    }
}
