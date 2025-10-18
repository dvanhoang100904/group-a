<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Document;
use App\Models\User;
use App\Models\Subject;

class DocumentController extends Controller
{
    public function index(Request $request)
    {
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');

        $documents = Document::with(['user', 'subject'])
            ->orderBy($sortBy == 'user_name' ? 'user_id' : $sortBy, $sortOrder)
            ->paginate(10);

        $subjects = Subject::all();

        return view('documents.uploads.my_documents', compact('documents', 'subjects', 'sortBy', 'sortOrder'));
    }
    public function show($id)
    {
        // Lấy thông tin tài liệu cùng các quan hệ liên quan
        $document = Document::with([
            'user',
            'folder',
            'subject',
            'versions',
            'previews',
            'accesses'
        ])->findOrFail($id);

        // Trả về view hiển thị chi tiết tài liệu
      return view('documents.documents_detail.document_detail', compact('document'));
    }


    public function edit($id) {}

    public function update(Request $request, $id) {}

    public function destroy($id) {}
}
