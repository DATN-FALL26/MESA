<?php

declare(strict_types=1);

namespace App\Http\Requests\Menu;

use App\Models\ItemVariant;
use App\Models\MenuItem;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ItemVariantRequest extends FormRequest
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
        /** @var ItemVariant|null $variant */
        $variant = $this->route('variant');
        /** @var MenuItem $menuItem */
        $menuItem = $variant?->menuItem ?? $this->route('menuItem');
        $basePrice = (int) $menuItem->base_price;

        return [
            'name' => [
                'required',
                'string',
                'max:50',
                Rule::unique(ItemVariant::class, 'name')
                    ->where('menu_item_id', $menuItem->getKey())
                    ->ignore($variant?->getKey()),
            ],
            // price_delta = chênh lệch so với giá gốc; có thể âm nhưng giá cuối không được âm.
            'price_delta' => ['required', 'integer', 'min:'.(-$basePrice), 'max:999999999'],
            'is_default' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Tên biến thể không được để trống.',
            'name.unique' => 'Món này đã có biến thể cùng tên.',
            'price_delta.required' => 'Mức chênh lệch giá không được để trống (nhập 0 nếu không đổi).',
            'price_delta.integer' => 'Mức chênh lệch là số nguyên VND.',
            'price_delta.min' => 'Giá sau chênh lệch không được âm (không thấp hơn -giá gốc).',
        ];
    }

    /**
     * @return array{name: string, price_delta: int, is_default: bool, is_active: bool}
     */
    public function payload(): array
    {
        $data = $this->validated();

        return [
            'name' => trim($data['name']),
            'price_delta' => (int) $data['price_delta'],
            'is_default' => $this->boolean('is_default'),
            'is_active' => $this->boolean('is_active'),
        ];
    }
}
