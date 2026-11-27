<?php

declare(strict_types=1);

namespace App\Http\Requests\Menu;

use Illuminate\Foundation\Http\FormRequest;

class ModifierGroupRequest extends FormRequest
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
            'min_select' => ['required', 'integer', 'min:0', 'max:20'],
            'max_select' => ['required', 'integer', 'min:1', 'max:20', 'gte:min_select'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Tên nhóm topping không được để trống.',
            'min_select.required' => 'Số lượng chọn tối thiểu không được để trống.',
            'max_select.required' => 'Số lượng chọn tối đa không được để trống.',
            'max_select.min' => 'Số lượng chọn tối đa phải từ 1 trở lên.',
            'max_select.gte' => 'Số lượng chọn tối đa phải lớn hơn hoặc bằng tối thiểu.',
        ];
    }

    /**
     * @return array{name: string, min_select: int, max_select: int, is_active: bool}
     */
    public function payload(): array
    {
        $data = $this->validated();

        return [
            'name' => trim($data['name']),
            'min_select' => (int) $data['min_select'],
            'max_select' => (int) $data['max_select'],
            'is_active' => $this->boolean('is_active'),
        ];
    }
}
