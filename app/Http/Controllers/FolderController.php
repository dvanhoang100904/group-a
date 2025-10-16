<?php

namespace App\Http\Controllers;

use App\Models\Folder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class FolderController extends Controller
{
    /**
     * Hiển thị danh sách thư mục
     */
    public function index()
    {
        // Lấy tất cả folder gốc (không có parent)
        $folders = Folder::with(['childFolders', 'user'])
            ->whereNull('parent_folder_id')
            ->orderBy('created_at', 'desc')
            ->get();

        return view('folders.index', compact('folders'));
    }

    /**
     * Hiển thị form tạo thư mục
     */
    public function create()
    {
        // Không cần truyền parentFolders nữa
        return view('folders.create');
    }

    /**
     * Lưu thư mục mới - MẶC ĐỊNH LÀ THƯ MỤC GỐC
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:folders,name',
            'status' => 'required|in:public,private',
            // Không validate parent_folder_id nữa
        ]);

        try {
            Folder::create([
                'name' => $request->name,
                'status' => $request->status,
                'parent_folder_id' => null, // MẶC ĐỊNH NULL = THƯ MỤC GỐC
                'user_id' => Auth::id() ?? 1, // Fallback cho demo
            ]);

            return redirect()->route('folders.index')
                ->with('success', 'Thư mục đã được tạo thành công!');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Lỗi khi tạo thư mục: ' . $e->getMessage());
        }
    }
}
