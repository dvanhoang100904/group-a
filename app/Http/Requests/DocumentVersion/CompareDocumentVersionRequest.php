<?php

namespace App\Http\Requests\DocumentVersion;

use Illuminate\Foundation\Http\FormRequest;

class CompareDocumentVersionRequest extends FormRequest
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
            'version_a' => 'required|integer|exists:document_versions,version_id',
            'version_b' => 'required|integer|exists:document_versions,version_id|different:version_a',

        ];
    }
    public function messages(): array
    {
        return [
            'version_a.required' => 'Vui lòng chọn phiên bản A',
            'version_b.required' => 'Vui lòng chọn phiên bản B',
            'version_b.different' => 'Hai phiên bản phải khác nhau để so sánh',
            'version_a.exists' => 'Phiên bản A không tồn tại',
            'version_b.exists' => 'Phiên bản B không tồn tại',
        ];
    }
}
