<?php

namespace App\Http\Controllers;

use App\Models\Department;
use Illuminate\Http\Request;

class KhoaController extends Controller
{
    /**
     * Hiển thị danh sách Khoa / Bộ môn
     */
    public function index(Request $request)
    {
        $query = Department::query()->withCount('subjects');

        // 🔍 Tìm kiếm
        if ($request->filled('keyword')) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->keyword . '%')
                  ->orWhere('code', 'like', '%' . $request->keyword . '%');
            });
        }

        $khoas = $query->orderBy('department_id', 'asc')->paginate(10);
        return view('khoa.index', compact('khoas'));
    }

    /**
     * Hiển thị form thêm mới
     */
    public function create()
    {
        return view('khoa.create');
    }

    /**
     * Lưu dữ liệu Khoa / Bộ môn mới
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|max:150|unique:departments,name',
            'description' => 'nullable|max:255',
        ]);

        // 🔹 Tự động sinh mã khoa
        $last = Department::orderBy('department_id', 'desc')->first();
        $nextCode = 'KHOA' . str_pad(($last ? $last->department_id + 1 : 1), 3, '0', STR_PAD_LEFT);

        Department::create([
            'code' => $nextCode,
            'name' => $request->name,
            'description' => $request->description,
        ]);

        return redirect()->route('khoa.index')->with('success', 'Thêm Khoa / Bộ môn thành công!');
    }

    /**
     * Xem chi tiết Khoa / Bộ môn
     */
    public function show($id)
    {
        $khoa = Department::withCount('subjects')
            ->with('subjects')
            ->findOrFail($id);

        return view('khoa.show', compact('khoa'));
    }

    /**
     * Hiển thị form chỉnh sửa
     */
    public function edit($id)
    {
        $khoa = Department::findOrFail($id);
        return view('khoa.edit', compact('khoa'));
    }

    /**
     * Cập nhật Khoa / Bộ môn
     */
    public function update(Request $request, $id)
    {
        $khoa = Department::findOrFail($id);

        $request->validate([
            'name' => 'required|max:150|unique:departments,name,' . $id . ',department_id',
            'description' => 'nullable|max:255',
        ]);

        $khoa->update([
            'name' => $request->name,
            'description' => $request->description,
        ]);

        return redirect()->route('khoa.index')->with('success', 'Cập nhật Khoa / Bộ môn thành công!');
    }

    /**
     * Xóa Khoa / Bộ môn
     */
    public function destroy($id)
    {
        Department::findOrFail($id)->delete();
        return redirect()->route('khoa.index')->with('success', 'Xóa Khoa / Bộ môn thành công!');
    }
}
