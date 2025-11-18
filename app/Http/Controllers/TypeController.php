<?php

namespace App\Http\Controllers;

use App\Models\Type;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class TypeController extends Controller
{
    /**
     * Hiển thị danh sách (kèm lọc + tìm kiếm)
     */
    public function index(Request $request)
    {
        $query = Type::query();

        if ($request->filled('name')) {
            $query->where('name', 'like', '%' . $request->name . '%');
        }
        if ($request->filled('search')) {
            $keyword = $request->search;
            $query->where(function ($q) use ($keyword) {
				$q->where('name', 'like', "%{$keyword}%")
                    ->orWhere('description', 'like', "%{$keyword}%");
            });
        }

		// ⚡ Thêm đếm số lượng tài liệu
        $types = $query
            ->withCount('documents')
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return view('types.index', compact('types'));
    }

    /**
     * Form tạo mới
     */
    public function create()
    {
        return view('types.create');
    }
    
    /**
     * Lưu loại mới
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|max:255',
            'description' => 'nullable|string',
        ]);

		$type = new Type([
            'name' => $request->name,
            'description' => $request->description,
        ]);
        $type->save();

		$userName = optional(Auth::user())->name ?? 'System';
		Log::info("Tạo loại tài liệu mới: {$type->name} bởi {$userName}");

        return redirect()->route('types.index')->with('success', 'Thêm loại tài liệu thành công!');
    }

    /**
     * Form sửa
     */
    public function edit(Type $type)
    {
        return view('types.edit', compact('type'));
    }

    /**
     * Hiển thị chi tiết
     */
    public function show(Type $type)
    {
        $type->loadCount('documents');
        return view('types.show', compact('type'));
    }

    /**
     * Cập nhật loại tài liệu
     */
    public function update(Request $request, Type $type)
    {
        $request->validate([
            'name' => 'required|max:255',
            'description' => 'nullable|string',
        ]);

		$oldName = $type->name;

        $type->update([
            'name' => $request->name,
            'description' => $request->description,
        ]);

		$userName = optional(Auth::user())->name ?? 'System';
		Log::info("Cập nhật loại tài liệu: {$oldName} -> {$type->name} bởi {$userName}");

        return redirect()->route('types.index')->with('success', 'Cập nhật loại tài liệu thành công!');
    }

    /**
     * Xóa loại tài liệu
     */
    public function destroy(Type $type)
    {
        $typeName = $type->name ?? 'N/A';
        $userName = optional(Auth::user())->name ?? 'System';

		Log::warning("Xóa loại tài liệu: {$typeName} bởi {$userName}");

        $type->delete();

        return redirect()->route('types.index')->with('success', 'Đã xóa loại tài liệu!');
    }
}
