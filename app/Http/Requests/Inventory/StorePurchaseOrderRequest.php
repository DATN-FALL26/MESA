<?php

declare(strict_types=1);

namespace App\Http\Requests\Inventory;

use App\Enums\PurchaseOrderSource;
use App\Helpers\ConstantHelper;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePurchaseOrderRequest extends FormRequest
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
            'branch_id' => ['required', 'integer', Rule::exists(ConstantHelper::TABLE_BRANCHES, 'id')],
            'warehouse_id' => ['required', 'integer', Rule::exists(ConstantHelper::TABLE_WAREHOUSES, 'id')],
            'supplier_id' => ['nullable', 'integer', Rule::exists(ConstantHelper::TABLE_SUPPLIERS, 'id')],
            'source' => ['nullable', Rule::enum(PurchaseOrderSource::class)],
            'note' => ['nullable', 'string', 'max:255'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.ingredient_id' => ['required', 'integer', Rule::exists(ConstantHelper::TABLE_INGREDIENTS, 'id')],
            'items.*.quantity' => ['required', 'numeric', 'min:0.001'],
            'items.*.unit_price' => ['nullable', 'numeric', 'min:0'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'branch_id.required' => 'Chi nhánh đặt hàng là bắt buộc.',
            'warehouse_id.required' => 'Kho nhận hàng là bắt buộc.',
            'items.required' => 'Danh sách mặt hàng đặt mua không được để trống.',
            'items.min' => 'Đơn hàng phải có ít nhất 1 mặt hàng.',
            'items.*.ingredient_id.required' => 'Vui lòng chọn nguyên liệu đặt mua.',
            'items.*.quantity.required' => 'Số lượng đặt mua là bắt buộc.',
            'items.*.quantity.min' => 'Số lượng đặt mua phải lớn hơn 0.',
        ];
    }
}
