<?php

declare(strict_types=1);

namespace App\Http\Requests\Menu;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ModifierRequest extends FormRequest
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
        return [
            'name' => ['required', 'string', 'max:100'],
            'price' => ['required', 'integer', 'min:0', 'max:999999999'],
            // Chỉ nhận nguyên liệu chưa bị xóa mềm.
            'ingredient_id' => [
                'nullable',
                'integer',
                Rule::exists('ingredients', 'id')->whereNull('deleted_at'),
            ],
            // ingredient_qty là DECIMAL(15,3), bắt buộc khi có nguyên liệu trừ kho.
            'ingredient_qty' => ['nullable', 'required_with:ingredient_id', 'numeric', 'gt:0', 'max:999999999999'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Tên topping không được để trống.',
            'price.required' => 'Giá phụ thu không được để trống (nhập 0 nếu miễn phí).',
            'price.integer' => 'Giá phụ thu là số nguyên VND.',
            'ingredient_id.exists' => 'Nguyên liệu không tồn tại hoặc đã bị xóa.',
            'ingredient_qty.required_with' => 'Hãy nhập định lượng khi chọn nguyên liệu trừ kho.',
            'ingredient_qty.gt' => 'Định lượng phải lớn hơn 0.',
        ];
    }

    /**
     * @return array{name: string, price: int, ingredient_id: int|null, ingredient_qty: string|null, is_active: bool}
     */
    public function payload(): array
    {
        $data = $this->validated();
        $ingredientId = ! empty($data['ingredient_id']) ? (int) $data['ingredient_id'] : null;

        return [
            'name' => trim($data['name']),
            'price' => (int) $data['price'],
            'ingredient_id' => $ingredientId,
            'ingredient_qty' => $ingredientId !== null ? (string) $data['ingredient_qty'] : null,
            'is_active' => $this->boolean('is_active'),
        ];
    }
}
