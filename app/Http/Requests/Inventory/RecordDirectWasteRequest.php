<?php

declare(strict_types=1);

namespace App\Http\Requests\Inventory;

use App\Helpers\ConstantHelper;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RecordDirectWasteRequest extends FormRequest
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
            'warehouse_id' => ['required', 'integer', Rule::exists(ConstantHelper::TABLE_WAREHOUSES, 'id')],
            'ingredient_id' => ['required', 'integer', Rule::exists(ConstantHelper::TABLE_INGREDIENTS, 'id')],
            'quantity' => ['required', 'numeric', 'min:0.001'],
            'note' => ['required', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'warehouse_id.required' => 'Kho phát sinh hao hụt là bắt buộc.',
            'ingredient_id.required' => 'Nguyên vật liệu xuất hủy là bắt buộc.',
            'quantity.required' => 'Số lượng xuất hủy là bắt buộc.',
            'quantity.min' => 'Số lượng xuất hủy phải lớn hơn 0.',
            'note.required' => 'Vui lòng ghi rõ lý do xuất hủy / hao hụt.',
        ];
    }
}
