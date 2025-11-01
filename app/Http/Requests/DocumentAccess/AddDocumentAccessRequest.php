<?php

namespace App\Http\Requests\DocumentAccess;

use Illuminate\Foundation\Http\FormRequest;

class AddDocumentAccessRequest extends FormRequest
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
            'granted_to_type' => 'required|in:user,role',
            'granted_to_user_id' => 'required_if:granted_to_type,user|nullable|integer|exists:users,user_id',
            'granted_to_role_id' => 'required_if:granted_to_type,role|nullable|integer|exists:roles,role_id',
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
            'granted_to_type.required' => 'Vui lòng chọn loại cấp quyền (Người dùng hoặc Vai trò).',
            'granted_to_type.in' => 'Loại cấp quyền không hợp lệ, chỉ chấp nhận "user" hoặc "role".',
            'granted_to_user_id.required_if' => 'Vui lòng chọn người dùng.',
            'granted_to_user_id.exists' => 'Người dùng được chọn không tồn tại.',
            'granted_to_role_id.required_if' => 'Vui lòng chọn vai trò.',
            'granted_to_role_id.exists' => 'Vai trò được chọn không tồn tại.',
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
