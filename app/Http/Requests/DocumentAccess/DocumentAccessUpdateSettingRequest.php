<?php

namespace App\Http\Requests\DocumentAccess;

use Illuminate\Foundation\Http\FormRequest;

class   DocumentAccessUpdateSettingRequest extends FormRequest
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
            'share_mode' => 'required|in:public,private,custom',
            'share_link' => 'nullable|string|max:255',
            'expiration_date' => 'nullable|date',
            'no_expiry' => 'nullable|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'share_mode.required' => 'Bạn phải chọn chế độ chia sẻ.',
            'share_mode.in' => 'Chế độ chia sẻ không hợp lệ.',
            'share_link.string' => 'Liên kết chia sẻ phải là chuỗi.',
            'share_link.max' => 'Liên kết chia sẻ không được quá 255 ký tự.',
            'expiration_date.date' => 'Ngày hết hạn không hợp lệ.',
            'no_expiry.boolean' => 'Giá trị Không giới hạn phải đúng định dạng.',
        ];
    }
}
