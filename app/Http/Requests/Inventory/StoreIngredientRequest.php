<?php

declare(strict_types=1);

namespace App\Http\Requests\Inventory;

use App\Enums\IngredientType;
use App\Helpers\ConstantHelper;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreIngredientRequest extends FormRequest
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
            'sku' => [
                'required',
                'string',
                'max:40',
                'regex:/^[A-Za-z0-9_]+$/',
                Rule::unique(ConstantHelper::TABLE_INGREDIENTS, 'sku')->whereNull('deleted_at'),
            ],
            'name' => ['required', 'string', 'max:150'],
            'ingredient_type' => ['required', Rule::enum(IngredientType::class)],
            'base_uom' => ['required', 'string', 'max:20', Rule::in(['g', 'ml', 'cai', 'qua', 'lon', 'hop', 'dia', 'to', 'kg', 'l'])],
            'min_stock' => ['nullable', 'numeric', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'sku.required' => 'Mã SKU nguyên liệu là bắt buộc.',
            'sku.unique' => 'Mã SKU này đã tồn tại trong hệ thống.',
            'sku.regex' => 'Mã SKU chỉ được chứa chữ cái, số và dấu gạch dưới (_).',
            'name.required' => 'Tên nguyên liệu là bắt buộc.',
            'ingredient_type.required' => 'Phân loại nguyên liệu là bắt buộc.',
            'base_uom.required' => 'Đơn vị tính cơ sở là bắt buộc.',
            'base_uom.in' => 'Đơn vị tính cơ sở không hợp lệ.',
            'min_stock.min' => 'Mức tồn kho tối thiểu không được âm.',
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('sku')) {
            $this->merge([
                'sku' => strtoupper(trim((string) $this->input('sku'))),
            ]);
        }
    }
}
