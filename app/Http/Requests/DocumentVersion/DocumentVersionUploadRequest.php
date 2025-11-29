<?php

namespace App\Http\Requests\DocumentVersion;

use Illuminate\Foundation\Http\FormRequest;

class DocumentVersionUploadRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'file' => 'required|file|mimes:pdf,doc,docx|max:10240',
            'change_note' => 'nullable|string|max:500',
        ];
    }

    public function messages(): array
    {
        return [
            'file.required' => 'Vui lòng chọn file để tải lên.',
            'file.file' => 'File tải lên không hợp lệ.',
            'file.mimes' => 'Chỉ cho phép định dạng PDF, DOC, DOCX.',
            'file.max' => 'Dung lượng file không được vượt quá 10MB.',
            'change_note.string' => 'Ghi chú phải là chuỗi.',
            'change_note.max' => 'Ghi chú tối đa 500 ký tự.',
        ];
    }
}
