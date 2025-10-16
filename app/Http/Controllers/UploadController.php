<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Folder;
use App\Models\Subject;
use App\Models\Document;
use Illuminate\Support\Facades\Storage;

class UploadController extends Controller
{
    public function index()
    {
        $folders = Folder::select('folder_id', 'name')->get();
        $subjects = Subject::select('subject_id', 'name')->get();
        $types = ['PDF', 'DOCX', 'PPTX', 'TXT'];

        // Lấy danh sách tài liệu
        $documents = Document::latest()->limit(10)->get();

       return view('dashboard.upload', compact('folders', 'subjects', 'types', 'documents'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'files.*' => 'required|file|max:20480|mimes:pdf,docx,pptx,txt',
            'subject_id' => 'required|integer',
            'folder_id' => 'nullable|integer',
        ]);

        $user_id = 1; // Tạm thời giả định user ID = 1 (nếu bạn chưa có auth)

        foreach ($request->file('files') as $file) {
            $originalName = $file->getClientOriginalName();
            $path = $file->store("public/uploads/{$user_id}");

            Document::create([
                'title' => pathinfo($originalName, PATHINFO_FILENAME),
                'description' => null,
                'status' => 'private',
                'user_id' => $user_id,
                'folder_id' => $request->folder_id,
                'type_id' => 1, // Nếu bạn có bảng types thì thay bằng dynamic
                'subject_id' => $request->subject_id,
            ]);
        }

        return redirect()->route('upload.index')->with('success', 'Tải lên thành công!');
    }
}
