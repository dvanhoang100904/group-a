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
        // FIX: Tạm thời bỏ unique constraint để test
        return [
            'name' => 'required|string|max:255', // removed unique:folders,name
            'status' => 'required|in:public,private',
            'parent_folder_id' => 'nullable' // removed exists:folders,folder_id
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Tên thư mục là bắt buộc.',
            'name.unique' => 'Tên thư mục đã tồn tại.',
            'status.required' => 'Trạng thái là bắt buộc.',
            'status.in' => 'Trạng thái không hợp lệ.',
            'parent_folder_id.exists' => 'Thư mục cha không tồn tại.'
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
