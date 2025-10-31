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

        if ($request->filled('code')) {
            $query->where('code', 'like', '%' . $request->code . '%');
        }
        if ($request->filled('name')) {
            $query->where('name', 'like', '%' . $request->name . '%');
        }
        if ($request->filled('search')) {
            $keyword = $request->search;
            $query->where(function ($q) use ($keyword) {
                $q->where('code', 'like', "%{$keyword}%")
                    ->orWhere('name', 'like', "%{$keyword}%")
                    ->orWhere('description', 'like', "%{$keyword}%");
            });
        }

        $types = $query->with('creator')->orderBy('created_at', 'desc')->paginate(10);

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
        $type = new Type($request->only(['code', 'name', 'description']));
        $type->created_by = Auth::id();
        $type->updated_by = Auth::id();
        $type->save();

        $request->validate([
            'code' => 'required|max:50|unique:types,code',
            'name' => 'required|max:255',
            'description' => 'nullable|string',
        ]);

        // nếu có user đăng nhập thì dùng id, nếu không thì đặt null hoặc 1 (tùy bạn)
        $createdBy = Auth::id() ?? null;

        $type = Type::create([
            'code' => $request->code,
            'name' => $request->name,
            'description' => $request->description,
            'created_by' => $createdBy,
        ]);

        // an toàn lấy tên user (nếu không có -> "System")
        $userName = optional(Auth::user())->name ?? 'System';

        Log::info("Tạo loại tài liệu mới: {$type->name} (Mã: {$type->code}) bởi {$userName}");

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
     * show
     */
    public function show(Type $type)
    {
        return view('types.show', compact('type'));
    }


    /**
     * Cập nhật
     */
    public function update(Request $request, Type $type)
    {
        $type->update($request->only(['code', 'name', 'description']));
        $type->updated_by = Auth::id();
        $type->save();

        $request->validate([
            'code' => 'required|max:50|unique:types,code,' . $type->id,
            'name' => 'required|max:255',
            'description' => 'nullable|string',
        ]);

        $oldName = $type->name;
        $oldCode = $type->code;

        $type->update($request->only(['code', 'name', 'description']));

        $userName = optional(Auth::user())->name ?? 'System';
        Log::info("Cập nhật loại tài liệu: {$oldName} ({$oldCode}) -> {$type->name} ({$type->code}) bởi {$userName}");

        return redirect()->route('types.index')->with('success', 'Cập nhật loại tài liệu thành công!');
    }

    /**
     * Xóa
     */
    public function destroy(Type $type)
    {
        // chuỗi thông tin trước khi xóa
        $typeName = $type->name ?? 'N/A';
        $typeCode = $type->code ?? 'N/A';
        $userName = optional(Auth::user())->name ?? 'System';

        Log::warning("Xóa loại tài liệu: {$typeName} (Mã: {$typeCode}) bởi {$userName}");

        $type->delete();

        return redirect()->route('types.index')->with('success', 'Đã xóa loại tài liệu!');
    }
}


