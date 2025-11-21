<?php

namespace App\Http\Controllers;

use App\Models\Type;
use App\Models\Document; // Nếu cần truy vấn tài liệu
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class TypeController extends Controller
{
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

        if ($request->filled('has_documents')) {
            if ($request->has_documents == '1') {
                $query->has('documents');
            } elseif ($request->has_documents == '0') {
                $query->doesntHave('documents');
            }
        }

        if ($request->filled('sort_by')) {
            $sort = $request->sort_by;
            if (in_array($sort, ['name', 'documents_count', 'created_at'])) {
                $query->withCount('documents')->orderBy($sort);
            }
        } else {
            $query->withCount('documents')->orderBy('created_at', 'desc');
        }

        $types = $query->paginate(10);

        return view('types.index', compact('types'));
    }

    public function create()
    {
        return view('types.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => ['required', 'max:255', Rule::unique('types', 'name')],
            'description' => 'nullable|string',
            'status' => 'boolean',
        ]);

        $userName = optional(Auth::user())->name ?? 'System';

        $type = Type::create([
            'name' => $request->name,
            'description' => $request->description,
            'status' => $request->status ?? 0,
            'created_by' => $userName,
            'updated_by' => $userName,
        ]);

        Log::info("Tạo loại tài liệu mới: {$type->name} bởi {$userName}");

        return redirect()->route('types.index')->with('success', 'Thêm loại tài liệu thành công!');
    }

    public function edit(Type $type)
    {
        return view('types.edit', compact('type'));
    }

    public function show(Type $type)
    {
        $type->loadCount('documents');

        // Lấy tất cả tài liệu thuộc loại này
        $documents = $type->documents()->orderBy('created_at', 'desc')->get();

        // Nếu chưa có ActivityLog, tạo collection rỗng
        $logs = collect();

        return view('types.show', compact('type', 'documents', 'logs'));
    }

    public function update(Request $request, Type $type)
    {
        $request->validate([
            'name' => [
                'required',
                'max:255',
                Rule::unique('types', 'name')->ignore($type->type_id, 'type_id'),
            ],
            'description' => 'nullable|string',
            'status' => 'boolean',
        ]);

        $oldName = $type->name;
        $userName = optional(Auth::user())->name ?? 'System';

        $type->update([
            'name' => $request->name,
            'description' => $request->description,
            'status' => $request->status ?? 0,
            'updated_by' => $userName,
        ]);

        Log::info("Cập nhật loại tài liệu: {$oldName} -> {$type->name} bởi {$userName}");

        return redirect()->route('types.index')->with('success', 'Cập nhật loại tài liệu thành công!');
    }

    public function destroy(Type $type)
    {
        $typeName = $type->name ?? 'N/A';
        $userName = optional(Auth::user())->name ?? 'System';

        $documentsCount = $type->documents()->count();

        if ($documentsCount > 0) {
            Log::warning("Không thể xóa loại tài liệu: {$typeName} đang được {$documentsCount} tài liệu sử dụng. Thao tác bởi {$userName}");
            return redirect()->route('types.index')
                ->with('error', "Loại này đang được {$documentsCount} tài liệu sử dụng. Không thể xóa!");
        }

        $type->delete();
        Log::info("Xóa loại tài liệu: {$typeName} bởi {$userName}");

        return redirect()->route('types.index')->with('success', 'Đã xóa loại tài liệu!');
    }
}
