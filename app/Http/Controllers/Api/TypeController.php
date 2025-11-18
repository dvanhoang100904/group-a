<?php

namespace App\Http\Controllers;

use App\Models\Type;
use App\Models\Document;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\TypesExport;

class TypeController extends Controller
{
       //Đừng xóa của dân nha!!!
    public function getType()
    {
        return response()->json(Type::all());
    }
    // Hiển thị danh sách loại tài liệu
    public function index(Request $request)
    {
        $query = Type::query()->withCount('documents');

        // Filter theo tên
        if ($request->filled('name')) {
            $query->where('name', 'like', '%' . $request->name . '%');
        }

        // Filter theo trạng thái
        if ($request->filled('status')) {
            $query->where('status', (bool) $request->boolean('status'));
        }

        // Filter nâng cao: có/không có tài liệu
        if ($request->filled('has_documents')) {
            if ($request->has_documents == '1') {
                $query->has('documents');
            } elseif ($request->has_documents == '0') {
                $query->doesntHave('documents');
            }
        }

        // Sort
        if ($request->filled('sort_by')) {
            switch ($request->sort_by) {
                case 'name':
                    $query->orderBy('name');
                    break;
                case 'documents_count':
                    $query->orderBy('documents_count', 'desc');
                    break;
                case 'created_at':
                    $query->orderBy('created_at', 'desc');
                    break;
                default:
                    $query->orderBy('created_at', 'desc');
            }
        } else {
            $query->orderBy('created_at', 'desc');
        }

        $types = $query->paginate(15)->withQueryString();

        // Thống kê tổng số loại và tổng số tài liệu
        $totalTypes = Type::count();
        $totalDocuments = Document::count();

        return view('types.index', [
            'types' => $types,
            'totalTypes' => $totalTypes,
            'totalDocuments' => $totalDocuments
        ]);
    }

    // Form tạo mới
    public function create()
    {
        return view('types.create');
    }

    // Lưu loại tài liệu mới
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|unique:types,name',
            'description' => 'nullable|string',
        ]);

        $type = Type::create([
            'name' => $request->name,
            'description' => $request->description,
        ]);

        // Ghi nhật ký
        activity()->causedBy(auth()->user())
            ->performedOn($type)
            ->log('create_document_category');

        return redirect()->route('types.index')->with('success', 'Tạo loại tài liệu thành công.');
    }

    // Hiển thị chi tiết loại
    public function show(Type $type)
    {
        $documents = $type->documents()->with('user')->paginate(10);

        $total = $type->documents()->count();
        $public = $type->documents()->where('status', 'public')->count();
        $private = $type->documents()->where('status', 'private')->count();
        $publicPercent = $total ? round($public / $total * 100, 2) : 0;
        $privatePercent = $total ? round($private / $total * 100, 2) : 0;
        $violations = $type->documents()->whereHas('reports')->count();

        $activities = \App\Models\Activity::where('subject_type', 'App\Models\Type')
            ->where('subject_id', $type->type_id)
            ->latest()
            ->take(10)
            ->get();

        return view('types.show', compact('type', 'documents', 'publicPercent', 'privatePercent', 'violations', 'activities'));
    }

    // Form chỉnh sửa
    public function edit(Type $type)
    {
        return view('types.edit', compact('type'));
    }

    // Cập nhật
    public function update(Request $request, Type $type)
    {
        $request->validate([
            'name' => 'required|string|unique:types,name,' . $type->type_id . ',type_id',
            'description' => 'nullable|string',
        ]);

        $type->update([
            'name' => $request->name,
            'description' => $request->description,
        ]);

        activity()->causedBy(auth()->user())
            ->performedOn($type)
            ->log('edit_document_category');

        return redirect()->route('types.index')->with('success', 'Cập nhật loại tài liệu thành công.');
    }

    // Xóa
    public function destroy(Type $type)
    {
        if ($type->documents()->count() > 0) {
            return redirect()->route('types.index')->with('error', 'Loại này đang được tài liệu sử dụng, không thể xóa!');
        }

        $type->delete();

        activity()->causedBy(auth()->user())
            ->performedOn($type)
            ->log('delete_document_category');

        return redirect()->route('types.index')->with('success', 'Xóa loại tài liệu thành công.');
    }

    // Xuất Excel
    public function exportExcel()
    {
        return Excel::download(new TypesExport, 'types.xlsx');
    }
}
