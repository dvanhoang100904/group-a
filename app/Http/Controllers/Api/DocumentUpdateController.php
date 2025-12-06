<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Document;
use Illuminate\Http\Request;

class DocumentUpdateController extends Controller
{
    public function update(Request $request, $id)
    {
        $request->validate([
            'title'       => 'required|string|max:255',
            'description' => 'nullable|string',
            'type_id'     => 'required|exists:types,type_id',
            'subject_id'  => 'nullable|exists:subjects,subject_id',
            'folder_id'   => 'nullable|exists:folders,folder_id',
        ]);

        $document = Document::findOrFail($id);

        $document->update([
            'title'       => strip_tags($request->title),
            'description' => strip_tags($request->description),
            'type_id'     => $request->type_id,
            'subject_id'  => $request->subject_id,
            'folder_id'   => $request->folder_id,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Cập nhật thành công!',
            'document' => $document,
        ]);
    }
}
