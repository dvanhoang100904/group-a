<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Document;
use App\Models\Subject;
use App\Models\Type;
use Illuminate\Support\Facades\DB;

class DocumentController extends Controller
{
    /**
     * Danh sách tài liệu
     */
    public function index()
    {
        $documents = Document::with(['type', 'subject', 'user'])
            ->orderByDesc('created_at')
            ->paginate(10);

        return view('documents.index', compact('documents'));
    }

    /**
     * Form tạo mới tài liệu
     */
    public function create()
    {
        $subjects = Subject::all();
        $types = Type::all();
        return view('documents.create', compact('subjects', 'types'));
    }

    /**
     * Lưu tài liệu mới vào DB
     */
    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:150',
            'description' => 'nullable|string|max:255',
            'status' => 'in:public,private,restricted',
            'type_id' => 'required|exists:types,type_id',
            'subject_id' => 'required|exists:subjects,subject_id',
        ]);

        DB::beginTransaction();
        try {
            Document::create([
                'title' => $request->title,
                'description' => $request->description,
                'status' => $request->status ?? 'private',
                'user_id' => auth()->id() ?? 1,
                'folder_id' => null,
                'type_id' => $request->type_id,
                'subject_id' => $request->subject_id,
            ]);

            DB::commit();
            return redirect()->route('documents.index')->with('success', 'Tạo tài liệu thành công!');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Không thể tạo tài liệu: ' . $e->getMessage());
        }
    }

    /**
     * Hiển thị chi tiết tài liệu
     */
    public function show($id)
    {
        $document = Document::with(['type', 'subject', 'user', 'versions'])->findOrFail($id);
        return view('documents.show', compact('document'));
    }

    /**
     * Cập nhật tài liệu
     */
    public function update(Request $request, $id)
    {
        $doc = Document::findOrFail($id);

        $request->validate([
            'title' => 'required|string|max:150',
            'description' => 'nullable|string|max:255',
            'status' => 'in:public,private,restricted',
        ]);

        $doc->update($request->only(['title', 'description', 'status']));

        return redirect()->back()->with('success', 'Cập nhật tài liệu thành công.');
    }

    /**
     * Xóa tài liệu
     */
    public function destroy($id)
    {
        $doc = Document::findOrFail($id);

        foreach ($doc->versions as $version) {
            if ($version->file_path && file_exists(storage_path('app/public/' . $version->file_path))) {
                unlink(storage_path('app/public/' . $version->file_path));
            }
            $version->delete();
        }

        $doc->delete();

        return redirect()->route('documents.index')->with('success', 'Đã xóa tài liệu thành công.');
    }
}
