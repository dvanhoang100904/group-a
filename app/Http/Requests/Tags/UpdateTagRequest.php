<?php

namespace App\Http\Requests\Tags;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTagRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'image' => ['nullable', 'file', 'max:2048', 'mimes:jpeg,jpg,png,gif'],
            'client_updated_at' => ['nullable', 'string'], // ISO8601 từ form để optimistic lock
        ];
    }

    public function messages()
    {
        return [
            'name.required' => 'Tên thẻ là bắt buộc.',
        ];
    }
}
