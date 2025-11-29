<?php

namespace App\Http\Requests\Folder;

use Illuminate\Foundation\Http\FormRequest;

class StoreFolderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'parent_folder_id' => 'nullable'
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

    public function prepareForValidation()
    {
        // FIX: Xử lý parent_folder_id
        if ($this->has('parent_folder_id')) {
            $parentId = $this->parent_folder_id;

            if ($parentId === '' || $parentId === 'null' || $parentId === null) {
                $this->merge([
                    'parent_folder_id' => null
                ]);
            }
        }
    }
}
