<?php

declare(strict_types=1);

namespace App\Http\Requests\Inventory;

use App\Enums\SupplierDocumentType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSupplierDocumentRequest extends FormRequest
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
            'document_type' => ['required', 'string', Rule::in(SupplierDocumentType::values())],
            'document_name' => ['required', 'string', 'max:255'],
            'document_number' => ['nullable', 'string', 'max:100'],
            'issuing_authority' => ['nullable', 'string', 'max:255'],
            'issue_date' => ['nullable', 'date'],
            'expiry_date' => ['nullable', 'date', 'after_or_equal:issue_date'],
            'file' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png,doc,docx', 'max:10240'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'document_type.required' => 'Vui lòng chọn loại giấy tờ / chứng nhận.',
            'document_type.in' => 'Loại giấy tờ không hợp lệ.',
            'document_name.required' => 'Vui lòng nhập tên tài liệu.',
            'document_name.max' => 'Tên tài liệu không được vượt quá 255 ký tự.',
            'expiry_date.after_or_equal' => 'Ngày hết hạn phải sau hoặc bằng ngày cấp.',
            'file.required' => 'Vui lòng tải lên tệp hồ sơ chứng nhận.',
            'file.file' => 'Tệp tải lên không hợp lệ.',
            'file.mimes' => 'Định dạng tệp cho phép: PDF, JPG, PNG, DOC, DOCX.',
            'file.max' => 'Dung lượng tệp không được vượt quá 10MB.',
        ];
    }
}
