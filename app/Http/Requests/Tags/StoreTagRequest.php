<?php

namespace App\Http\Requests\Tags;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTagRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // điều chỉnh nếu cần permission
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'image' => ['nullable', 'file', 'max:2048', 'mimes:jpeg,jpg,png,gif'], // 2MB
        ];
    }

    public function messages()
    {
        return [
            'name.required' => 'Tên thẻ là bắt buộc.',
            'name.max' => 'Tên thẻ không quá 255 ký tự.',
            'description.max' => 'Mô tả không quá 1000 ký tự.',
            'image.mimes' => 'Ảnh phải là jpeg|jpg|png|gif.',
            'image.max' => 'Kích thước ảnh tối đa 2MB.',
        ];
    }
}
