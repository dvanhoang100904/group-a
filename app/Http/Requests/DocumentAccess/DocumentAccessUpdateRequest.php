<?php

namespace App\Http\Requests\DocumentAccess;

use Illuminate\Foundation\Http\FormRequest;

class DocumentAccessUpdateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * boolean tu checkbox hoac string "true"/"false".
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'no_expiry' => filter_var($this->no_expiry, FILTER_VALIDATE_BOOLEAN),
            'can_view' => filter_var($this->can_view, FILTER_VALIDATE_BOOLEAN),
            'can_edit' => filter_var($this->can_edit, FILTER_VALIDATE_BOOLEAN),
            'can_delete' => filter_var($this->can_delete, FILTER_VALIDATE_BOOLEAN),
            'can_upload' => filter_var($this->can_upload, FILTER_VALIDATE_BOOLEAN),
            'can_download' => filter_var($this->can_download, FILTER_VALIDATE_BOOLEAN),
            'can_share' => filter_var($this->can_share, FILTER_VALIDATE_BOOLEAN),
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'no_expiry' => 'boolean',
            'can_view' => 'boolean',
            'can_edit' => 'boolean',
            'can_delete' => 'boolean',
            'can_upload' => 'boolean',
            'can_download' => 'boolean',
            'can_share' => 'boolean',
            'expiration_date' => 'nullable|date|after_or_equal:today',
        ];
    }

    public function messages(): array
    {
        return [
            'no_expiry.boolean' => 'Trường "Không giới hạn" phải là giá trị đúng/sai.',
            'can_view.boolean' => 'Trường "Xem" phải là giá trị đúng/sai.',
            'can_edit.boolean' => 'Trường "Chỉnh sửa" phải là giá trị đúng/sai.',
            'can_delete.boolean' => 'Trường "Xóa" phải là giá trị đúng/sai.',
            'can_upload.boolean' => 'Trường "Tải lên" phải là giá trị đúng/sai.',
            'can_download.boolean' => 'Trường "Tải xuống" phải là giá trị đúng/sai.',
            'can_share.boolean' => 'Trường "Chia sẻ tiếp" phải là giá trị đúng/sai.',
            'expiration_date.date' => 'Ngày hết hạn không hợp lệ.',
            'expiration_date.after_or_equal' => 'Ngày hết hạn phải bằng hoặc sau hôm nay.',
        ];
    }
}
