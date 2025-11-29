<?php

namespace App\Http\Requests\Folder;

use Illuminate\Foundation\Http\FormRequest;

class UpdateFolderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $folderId = $this->route('folder');  // Get {folder} from route

        return [
            'name' => 'required|string|max:255',
            'parent_folder_id' => 'nullable|integer|min:1'
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Tên thư mục là bắt buộc',
            'name.string' => 'Tên thư mục phải là chuỗi ký tự',
            'name.max' => 'Tên thư mục không được vượt quá 255 ký tự',
            'parent_folder_id.integer' => 'ID thư mục cha phải là số nguyên',
            'parent_folder_id.min' => 'ID thư mục cha không hợp lệ'
        ];
    }

    // FIX: Chuẩn bị data trước khi validation (tương tự Store)
    public function prepareForValidation()
    {
        if ($this->has('parent_folder_id') && ($this->parent_folder_id === '' || $this->parent_folder_id === 'null')) {
            $this->merge([
                'parent_folder_id' => null
            ]);
        }
    }
}
