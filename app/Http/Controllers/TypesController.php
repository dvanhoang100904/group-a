<?php

namespace App\Http\Controllers;

use App\Models\Type;
use App\Models\Document;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\TypesExport;
use App\Imports\TypesImport;

class TypesController extends Controller
{
    /**
     * Hiển thị danh sách loại tài liệu với filter, sort, search, tổng số.
     */
    public function index(Request $request)
    {
        $query = Type::query()->withCount('documents');

        // Filter tên
        if ($request->filled('name')) {
            $query->where('name', 'like', '%' . trim($request->name) . '%');
        }

        // Tìm kiếm chung
        if ($request->filled('search')) {
            $keyword = trim($request->search);
            $query->where(function ($q) use ($keyword) {
                $q->where('name', 'like', "%{$keyword}%")
                  ->orWhere('description', 'like', "%{$keyword}%");
            });
        }

        // Filter có/không có tài liệu
        if ($request->filled('has_documents')) {
            if ($request->has_documents == '1') $query->has('documents');
            elseif ($request->has_documents == '0') $query->doesntHave('documents');
        }

        // Sort
        $sortFields = ['name', 'documents_count', 'created_at'];
        $sort = $request->get('sort_by', 'created_at');
        $direction = $request->get('sort_dir', 'desc');
        if (in_array($sort, $sortFields)) {
            $query->orderBy($sort, $direction);
        }

        $types = $query->paginate(10)->withQueryString();

        $totalTypes = Type::count();
        $totalDocuments = Document::count();

        return view('types.index', compact('types', 'totalTypes', 'totalDocuments'));
    }

    /**
     * Trang tạo loại mới
     */
    public function create()
    {
        return view('types.create');
    }

    /**
     * Lưu loại tài liệu mới
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'max:255', Rule::unique('types', 'name')],
            'description' => 'nullable|string|max:1000',
            'status' => 'boolean',
        ]);

        // Normalize
        $validated['name'] = ucfirst(trim($validated['name']));
        $validated['description'] = trim($validated['description'] ?? '');
        $validated['status'] = $validated['status'] ?? 0;

        $userName = optional(Auth::user())->name ?? 'System';

        try {
            DB::transaction(function () use ($validated, $userName) {
                Type::create(array_merge($validated, [
                    'created_by' => $userName,
                    'updated_by' => $userName,
                ]));
            });
            Log::info("Tạo loại tài liệu: {$validated['name']} bởi {$userName}");
            return redirect()->route('types.index')->with('success', 'Thêm loại tài liệu thành công!');
        } catch (\Exception $e) {
            Log::error("Lỗi tạo loại tài liệu: {$e->getMessage()} bởi {$userName}");
            return back()->withInput()->with('error', 'Đã xảy ra lỗi, vui lòng thử lại.');
        }
    }

    /**
     * Trang sửa loại tài liệu
     */
    public function edit(Type $type)
    {
        return view('types.edit', compact('type'));
    }

    /**
     * Cập nhật loại tài liệu
     */
    public function update(Request $request, Type $type)
    {
        $validated = $request->validate([
            'name' => [
                'required', 'max:255',
                Rule::unique('types', 'name')->ignore($type->type_id, 'type_id')
            ],
            'description' => 'nullable|string|max:1000',
            'status' => 'boolean',
        ]);

        // Normalize
        $validated['name'] = ucfirst(trim($validated['name']));
        $validated['description'] = trim($validated['description'] ?? '');
        $validated['status'] = $validated['status'] ?? 0;

        $userName = optional(Auth::user())->name ?? 'System';

        try {
            DB::transaction(function () use ($type, $validated, $userName) {
                $type->update(array_merge($validated, ['updated_by' => $userName]));
            });
            Log::info("Cập nhật loại tài liệu: {$type->name} bởi {$userName}");
            return redirect()->route('types.index')->with('success', 'Cập nhật thành công!');
        } catch (\Exception $e) {
            Log::error("Lỗi cập nhật loại tài liệu: {$e->getMessage()} bởi {$userName}");
            return back()->withInput()->with('error', 'Đã xảy ra lỗi, vui lòng thử lại.');
        }
    }

    /**
     * Hiển thị chi tiết loại tài liệu
     */
    public function show(Type $type)
    {
        $type->loadCount('documents');
        $documents = $type->documents()->orderBy('created_at', 'desc')->get();
        $logs = collect(); // placeholder, có thể dùng activity log

        return view('types.show', compact('type', 'documents', 'logs'));
    }

    /**
     * Xóa loại tài liệu (nếu không có tài liệu liên quan)
     */
    public function destroy(Type $type)
    {
        $userName = optional(Auth::user())->name ?? 'System';
        $documentsCount = $type->documents()->count();

        if ($documentsCount > 0) {
            Log::warning("Không thể xóa loại tài liệu: {$type->name} đang có {$documentsCount} tài liệu. Bởi {$userName}");
            return redirect()->route('types.index')
                ->with('error', "Loại này đang có {$documentsCount} tài liệu. Không thể xóa!");
        }

        try {
            DB::transaction(function () use ($type) {
                $type->delete();
            });
            Log::info("Xóa loại tài liệu: {$type->name} bởi {$userName}");
            return redirect()->route('types.index')->with('success', 'Đã xóa loại tài liệu!');
        } catch (\Exception $e) {
            Log::error("Lỗi xóa loại tài liệu: {$e->getMessage()} bởi {$userName}");
            return redirect()->route('types.index')->with('error', 'Đã xảy ra lỗi, không thể xóa.');
        }
    }

    /**
     * Xuất Excel danh sách loại tài liệu
     */
    public function exportExcel()
    {
        return Excel::download(new TypesExport, 'types.xlsx');
    }

    /**
     * Import Excel
     */
    public function importExcel(Request $request)
    {
        $request->validate(['file' => 'required|file|mimes:xlsx,xls,csv']);

        try {
            Excel::import(new TypesImport, $request->file('file'));
            return redirect()->route('types.index')->with('success', 'Import thành công!');
        } catch (\Exception $e) {
            Log::error("Lỗi import loại tài liệu: {$e->getMessage()}");
            return redirect()->route('types.index')->with('error', 'Import thất bại, vui lòng kiểm tra file.');
        }
    }

    /**
     * Bulk delete
     */
    public function bulkDelete(Request $request)
    {
        $request->validate(['ids' => 'required|array']);
        $userName = optional(Auth::user())->name ?? 'System';

        try {
            DB::transaction(function () use ($request, $userName) {
                $types = Type::whereIn('type_id', $request->ids)->get();
                foreach ($types as $type) {
                    if ($type->documents()->count() === 0) {
                        $type->delete();
                        Log::info("Xóa loại tài liệu: {$type->name} bởi {$userName}");
                    }
                }
            });
            return redirect()->route('types.index')->with('success', 'Xóa nhiều loại tài liệu thành công!');
        } catch (\Exception $e) {
            Log::error("Lỗi bulk delete loại tài liệu: {$e->getMessage()} bởi {$userName}");
            return redirect()->route('types.index')->with('error', 'Xảy ra lỗi khi xóa nhiều loại.');
        }
    }
}
