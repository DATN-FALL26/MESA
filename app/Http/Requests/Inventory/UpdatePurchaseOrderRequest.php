<?php

declare(strict_types=1);

namespace App\Http\Requests\Inventory;

use App\Helpers\ConstantHelper;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePurchaseOrderRequest extends FormRequest
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
            'warehouse_id' => ['sometimes', 'integer', Rule::exists(ConstantHelper::TABLE_WAREHOUSES, 'id')],
            'supplier_id' => ['nullable', 'integer', Rule::exists(ConstantHelper::TABLE_SUPPLIERS, 'id')],
            'note' => ['nullable', 'string', 'max:255'],
            'items' => ['sometimes', 'array', 'min:1'],
            'items.*.ingredient_id' => ['required_with:items', 'integer', Rule::exists(ConstantHelper::TABLE_INGREDIENTS, 'id')],
            'items.*.quantity' => ['required_with:items', 'numeric', 'min:0.001'],
            'items.*.unit_price' => ['nullable', 'numeric', 'min:0'],
        ];
    }
}
