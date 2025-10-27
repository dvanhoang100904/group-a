<?php

namespace App\Http\Requests\Folder;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\Folder;

class UpdateFolderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $folderId = $this->route('folder');

        return [
            'name' => 'required|string|max:255|unique:folders,name,' . $folderId . ',folder_id',
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

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $folderId = $this->route('folder');
            $parentFolderId = $this->input('parent_folder_id');

            if ($parentFolderId == $folderId) {
                $validator->errors()->add(
                    'parent_folder_id',
                    'Không thể chọn chính thư mục này làm thư mục cha!'
                );
            }
        });
    }
}
