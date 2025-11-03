<?php

namespace App\Http\Requests\DocumentVersion;

use Illuminate\Foundation\Http\FormRequest;

class DocumentVersionFilterRequest extends FormRequest
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
            'keyword' => 'nullable|string|max:255',
            'user_id' => 'nullable|integer|exists:users,user_id',
            'status' => 'nullable|in:draft,published,archived',
            'from_date' => 'nullable|date',
            'to_date' => 'nullable|date|after_or_equal:from_date',
        ];
    }

    public function messages(): array
    {
        return [
            'keyword.string' => 'Từ khóa phải là chuỗi ký tự.',
            'keyword.max' => 'Từ khóa không được vượt quá 255 ký tự.',
            'user_id.integer' => 'Người dùng không hợp lệ.',
            'user_id.exists' => 'Người dùng không tồn tại.',
            'status.in' => 'Trạng thái không hợp lệ.',
            'from_date.date' => 'Ngày bắt đầu không hợp lệ.',
            'to_date.date' => 'Ngày kết thúc không hợp lệ.',
            'to_date.after_or_equal' => 'Ngày kết thúc phải bằng hoặc sau ngày bắt đầu.',
        ];
    }
}
