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
            'name' => 'required|string|max:255|unique:folders,name,' . $folderId . ',folder_id',  // FIX: Ignore current for unique
            'status' => 'required|in:public,private',
            'parent_folder_id' => 'nullable|exists:folders,folder_id'
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
