<?php

declare(strict_types=1);

namespace App\Http\Requests\Inventory;

use App\Enums\AdjustmentReason;
use App\Helpers\ConstantHelper;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreStockAdjustmentRequest extends FormRequest
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
            'reason' => ['required', Rule::enum(AdjustmentReason::class)],
            'note' => ['nullable', 'string', 'max:255'],
            'ingredient_ids' => ['nullable', 'array'],
            'ingredient_ids.*' => ['integer', Rule::exists(ConstantHelper::TABLE_INGREDIENTS, 'id')],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'warehouse_id.required' => 'Kho kiểm kê là bắt buộc.',
            'reason.required' => 'Lý do kiểm kê / điều chỉnh là bắt buộc.',
        ];
    }
}
