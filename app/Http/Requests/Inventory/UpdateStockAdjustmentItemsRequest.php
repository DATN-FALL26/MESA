<?php

declare(strict_types=1);

namespace App\Http\Requests\Inventory;

use App\Helpers\ConstantHelper;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateStockAdjustmentItemsRequest extends FormRequest
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
            'items' => ['required', 'array', 'min:1'],
            'items.*.id' => ['nullable', 'integer'],
            'items.*.ingredient_id' => ['nullable', 'integer', Rule::exists(ConstantHelper::TABLE_INGREDIENTS, 'id')],
            'items.*.actual_qty' => ['required', 'numeric', 'min:0'],
            'items.*.note' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'items.required' => 'Danh sách mặt hàng kiểm kê không được để trống.',
            'items.*.actual_qty.required' => 'Số lượng thực tế kiểm kê là bắt buộc.',
            'items.*.actual_qty.min' => 'Số lượng thực tế không được âm.',
        ];
    }
}
