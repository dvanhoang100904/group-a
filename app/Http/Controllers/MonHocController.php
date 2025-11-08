<?php

namespace App\Http\Controllers;

use App\Models\Subject;
use App\Models\Department;
use Illuminate\Http\Request;

class MonHocController extends Controller
{
    /**
     * 📚 Hiển thị danh sách môn học
     */
    public function index(Request $request)
    {
        $query = Subject::query()
            ->with('department')
            ->withCount('documents');

        // 🔍 Tìm kiếm theo tên hoặc mã
        if ($request->filled('keyword')) {
            $keyword = $request->keyword;
            $query->where(function($q) use ($keyword) {
                $q->where('name', 'like', "%$keyword%")
                  ->orWhere('code', 'like', "%$keyword%");
            });
        }

        // 🏫 Lọc theo khoa / bộ môn
        if ($request->filled('department_id')) {
            $query->where('department_id', $request->department_id);
        }

        $monhocs = $query->orderBy('subject_id')->paginate(10);
        $departments = Department::orderBy('name')->get();

        return view('monhoc.index', compact('monhocs', 'departments'));
    }

    /**
     * ➕ Trang thêm mới môn học
     */
    public function create()
    {
        $departments = Department::orderBy('name')->get();
        return view('monhoc.create', compact('departments'));
    }

    /**
     * 💾 Lưu môn học mới
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|max:150|unique:subjects,name',
            'credits' => 'required|integer|min:1|max:10',
            'department_id' => 'required|exists:departments,department_id',
            'description' => 'nullable|max:255',
        ]);

        // 🔹 Sinh mã môn học tự động (VD: MH001)
        $lastSubject = Subject::orderBy('subject_id', 'desc')->first();
        $nextId = $lastSubject ? $lastSubject->subject_id + 1 : 1;
        $code = 'MH' . str_pad($nextId, 3, '0', STR_PAD_LEFT);

        Subject::create([
            'code' => $code,
            'name' => $request->name,
            'credits' => $request->credits,
            'department_id' => $request->department_id,
            'description' => $request->description,
        ]);

        return redirect()->route('monhoc.index')->with('success', 'Thêm môn học thành công!');
    }

    /**
     * 👁 Xem chi tiết môn học
     */
    public function show($id)
    {
        $monhoc = Subject::with(['department', 'documents'])->findOrFail($id);
        return view('monhoc.show', compact('monhoc'));
    }

    /**
     * ✏️ Trang sửa môn học
     */
    public function edit($id)
    {
        $monhoc = Subject::findOrFail($id);
        $departments = Department::orderBy('name')->get();
        return view('monhoc.edit', compact('monhoc', 'departments'));
    }

    /**
     * 🔄 Cập nhật môn học
     */
    public function update(Request $request, $id)
    {
        $monhoc = Subject::findOrFail($id);

        $request->validate([
            'name' => 'required|max:150|unique:subjects,name,' . $id . ',subject_id',
            'credits' => 'required|integer|min:1|max:10',
            'department_id' => 'required|exists:departments,department_id',
            'description' => 'nullable|max:255',
        ]);

        $monhoc->update($request->only('name', 'credits', 'department_id', 'description'));

        return redirect()->route('monhoc.index')->with('success', 'Cập nhật môn học thành công!');
    }

    /**
     * 🗑 Xóa môn học
     */
    public function destroy($id)
    {
        Subject::findOrFail($id)->delete();
        return redirect()->route('monhoc.index')->with('success', 'Xóa môn học thành công!');
    }
}
