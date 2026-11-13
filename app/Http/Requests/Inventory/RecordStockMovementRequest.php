<?php

declare(strict_types=1);

namespace App\Http\Requests\Inventory;

use App\Enums\MovementType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RecordStockMovementRequest extends FormRequest
{
    /**
     * Xác thực quyền gửi yêu cầu ghi sổ kho.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Quy tắc kiểm thực dữ liệu đầu vào biến động kho.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'warehouse_id' => ['required', 'integer', 'exists:warehouses,id'],
            'ingredient_id' => ['required', 'integer', 'exists:ingredients,id'],
            'movement_type' => ['required', 'string', Rule::enum(MovementType::class)],
            'quantity' => ['required', 'numeric', 'not_in:0'],
            'unit_cost' => ['nullable', 'numeric', 'min:0'],
            'ref_type' => ['nullable', 'string', 'max:50'],
            'ref_id' => ['nullable', 'integer'],
            'idempotency_key' => ['nullable', 'string', 'max:100'],
            'note' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'warehouse_id.required' => 'Mã kho hàng không được để trống.',
            'warehouse_id.exists' => 'Kho hàng được chọn không tồn tại.',
            'ingredient_id.required' => 'Mã nguyên liệu không được để trống.',
            'ingredient_id.exists' => 'Nguyên liệu được chọn không tồn tại.',
            'movement_type.required' => 'Loại biến động kho không được để trống.',
            'quantity.required' => 'Số lượng biến động kho là bắt buộc.',
            'quantity.not_in' => 'Số lượng biến động phải khác 0.',
        ];
    }
}
