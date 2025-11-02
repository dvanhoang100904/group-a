<?php

namespace App\Http\Requests\DocumentAccess;

use Illuminate\Foundation\Http\FormRequest;

class UpdateDocumentAccessRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'can_view' => 'boolean',
            'can_edit' => 'boolean',
            'can_delete' => 'boolean',
            'can_upload' => 'boolean',
            'can_download' => 'boolean',
            'can_share' => 'boolean',
            'expiration_date' => 'nullable|date|after_or_equal:today',
            'no_expiry' => 'boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'can_view.boolean' => 'Trường "Xem" phải là giá trị đúng/sai.',
            'can_edit.boolean' => 'Trường "Chỉnh sửa" phải là giá trị đúng/sai.',
            'can_delete.boolean' => 'Trường "Xóa" phải là giá trị đúng/sai.',
            'can_upload.boolean' => 'Trường "Tải lên" phải là giá trị đúng/sai.',
            'can_download.boolean' => 'Trường "Tải xuống" phải là giá trị đúng/sai.',
            'can_share.boolean' => 'Trường "Chia sẻ tiếp" phải là giá trị đúng/sai.',
            'no_expiry.boolean' => 'Trường "Không hết hạn" phải là giá trị đúng/sai.',
            'expiration_date.date' => 'Ngày hết hạn không hợp lệ.',
            'expiration_date.after_or_equal' => 'Ngày hết hạn phải bằng hoặc sau hôm nay.',
        ];
    }
}
