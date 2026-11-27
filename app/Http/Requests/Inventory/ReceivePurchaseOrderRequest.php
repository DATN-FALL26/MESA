<?php

declare(strict_types=1);

namespace App\Http\Requests\Inventory;

use App\Helpers\ConstantHelper;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReceivePurchaseOrderRequest extends FormRequest
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
            'items' => ['nullable', 'array'],
            'items.*.ingredient_id' => ['required_with:items', 'integer', Rule::exists(ConstantHelper::TABLE_INGREDIENTS, 'id')],
            'items.*.received_qty' => ['required_with:items', 'numeric', 'min:0'],
            'items.*.unit_price' => ['nullable', 'numeric', 'min:0'],
        ];
    }
}
