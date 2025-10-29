<?php

namespace App\Http\Requests\Folder;

use Illuminate\Foundation\Http\FormRequest;

class SearchFolderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'nullable|string|max:255',
            'date' => 'nullable|date',
            'status' => 'nullable|in:public,private',
            'parent_id' => 'nullable|exists:folders,folder_id',
            'per_page' => 'nullable|integer|min:1|max:100'
        ];
    }
}
