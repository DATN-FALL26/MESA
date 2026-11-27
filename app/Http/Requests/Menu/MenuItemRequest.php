<?php

declare(strict_types=1);

namespace App\Http\Requests\Menu;

use App\Enums\ItemType;
use App\Enums\StationCode;
use App\Models\MenuItem;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MenuItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->input('sku'))) {
            $this->merge(['sku' => strtoupper(trim($this->input('sku')))]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var MenuItem|null $menuItem */
        $menuItem = $this->route('menuItem');

        return [
            'category_id' => ['required', 'integer', 'exists:categories,id'],
            // Cột sku UNIQUE trên toàn bảng (kể cả món đã xóa mềm) nên rule không lọc deleted_at.
            'sku' => [
                'required',
                'string',
                'max:40',
                'regex:/^[A-Z0-9][A-Z0-9._-]*$/',
                Rule::unique(MenuItem::class, 'sku')->ignore($menuItem?->getKey()),
            ],
            'name' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:255'],
            'item_type' => ['required', Rule::enum(ItemType::class)],
            'station_code' => ['required', Rule::enum(StationCode::class)],
            // base_price là DECIMAL(15,0): tiền VND nguyên, không số lẻ.
            'base_price' => ['required', 'integer', 'min:0', 'max:999999999'],
            // tax_rate là DECIMAL(5,2).
            'tax_rate' => ['required', 'numeric', 'min:0', 'max:100'],
            'prep_time_seconds' => ['required', 'integer', 'min:0', 'max:86400'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'remove_image' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
            'branch_ids' => ['nullable', 'array'],
            'branch_ids.*' => ['integer', 'exists:branches,id'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'category_id.required' => 'Vui lòng chọn danh mục.',
            'sku.required' => 'Mã món (SKU) không được để trống.',
            'sku.regex' => 'SKU chỉ gồm chữ in hoa, số và các ký tự . _ - (ví dụ PHO-TAI).',
            'sku.unique' => 'SKU đã tồn tại (kể cả món đã xóa). Hãy dùng mã khác.',
            'name.required' => 'Tên món không được để trống.',
            'item_type.required' => 'Vui lòng chọn loại món.',
            'station_code.required' => 'Vui lòng chọn quầy chế biến.',
            'base_price.required' => 'Giá bán không được để trống.',
            'base_price.integer' => 'Giá bán là số nguyên VND, không có phần lẻ.',
            'base_price.min' => 'Giá bán không được âm.',
            'tax_rate.numeric' => 'Thuế suất phải là số.',
            'tax_rate.max' => 'Thuế suất tối đa 100%.',
            'prep_time_seconds.integer' => 'Thời gian chế biến tính bằng giây, phải là số nguyên.',
            'image.image' => 'Ảnh món phải là tệp hình ảnh hợp lệ.',
            'image.max' => 'Ảnh món tối đa 2MB.',
        ];
    }

    /**
     * Dữ liệu cột của menu_items (không gồm ảnh và chi nhánh).
     *
     * @return array<string, mixed>
     */
    public function itemData(): array
    {
        $data = $this->validated();

        return [
            'category_id' => (int) $data['category_id'],
            'sku' => $data['sku'],
            'name' => trim($data['name']),
            'description' => isset($data['description']) ? trim($data['description']) : null,
            'item_type' => $data['item_type'],
            'station_code' => $data['station_code'],
            'base_price' => (int) $data['base_price'],
            'tax_rate' => $data['tax_rate'],
            'prep_time_seconds' => (int) $data['prep_time_seconds'],
            'is_active' => $this->boolean('is_active'),
        ];
    }
}
