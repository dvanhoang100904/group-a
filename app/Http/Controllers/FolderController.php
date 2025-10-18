<?php

namespace App\Http\Controllers;

use App\Models\Folder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class FolderController extends Controller
{
    /**
     * Hiển thị danh sách thư mục (gốc hoặc theo parent)
     */
    public function index(Request $request)
    {
        $parentFolderId = $request->get('parent_id');

        if ($parentFolderId) {
            $currentFolder = Folder::with(['parentFolder'])->findOrFail($parentFolderId);
            $folders = Folder::with(['childFolders', 'user', 'documents'])
                ->where('parent_folder_id', $parentFolderId)
                ->orderBy('created_at', 'desc')
                ->get();
        } else {
            $currentFolder = null;
            $folders = Folder::with(['childFolders', 'user', 'documents'])
                ->whereNull('parent_folder_id')
                ->orderBy('created_at', 'desc')
                ->get();
        }

        $breadcrumbs = $this->getBreadcrumbs($currentFolder);

        return view('folders.index', compact('folders', 'currentFolder', 'breadcrumbs'));
    }

    /**
     * Hiển thị chi tiết thư mục và các thư mục con
     */
    public function show($folderId)
    {
        $currentFolder = Folder::with(['parentFolder'])->findOrFail($folderId);

        $folders = Folder::with(['childFolders', 'user', 'documents'])
            ->where('parent_folder_id', $folderId)
            ->orderBy('created_at', 'desc')
            ->get();

        $breadcrumbs = $this->getBreadcrumbs($currentFolder);

        return view('folders.index', compact('folders', 'currentFolder', 'breadcrumbs'));
    }

    /**
     * Tạo breadcrumbs
     */
    private function getBreadcrumbs($currentFolder)
    {
        $breadcrumbs = [];

        if ($currentFolder) {
            $folder = $currentFolder;
            while ($folder) {
                $breadcrumbs[] = $folder;
                $folder = $folder->parentFolder;
            }
            $breadcrumbs = array_reverse($breadcrumbs);
        }

        return $breadcrumbs;
    }

    /**
     * Hiển thị form tạo thư mục
     */
    public function create(Request $request)
    {
        $parentFolderId = $request->get('parent_id');
        return view('folders.create', compact('parentFolderId'));
    }

    /**
     * Lưu thư mục mới
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:folders,name',
            'status' => 'required|in:public,private',
        ]);

        try {
            Folder::create([
                'name' => $request->name,
                'status' => $request->status,
                'parent_folder_id' => $request->parent_folder_id,
                'user_id' => Auth::id() ?? 1,
            ]);

            // Redirect về đúng thư mục hiện tại
            $redirectParams = $request->parent_folder_id ? ['parent_id' => $request->parent_folder_id] : [];

            return redirect()->route('folders.index', $redirectParams)
                ->with('success', 'Thư mục đã được tạo thành công!');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Lỗi khi tạo thư mục: ' . $e->getMessage());
        }
    }



    public function edit($folderId)
    {
        $folder = Folder::findOrFail($folderId);
        $parentFolders = Folder::whereNull('parent_folder_id')
            ->where('folder_id', '!=', $folderId)
            ->get();

        return view('folders.edit', compact('folder', 'parentFolders'));
    }

    /**
     * Cập nhật thư mục
     */
    public function update(Request $request, $folderId)
    {
        $folder = Folder::findOrFail($folderId);

        $request->validate([
            'name' => 'required|string|max:255|unique:folders,name,' . $folderId . ',folder_id',
            'status' => 'required|in:public,private',
            'parent_folder_id' => 'nullable|exists:folders,folder_id'
        ]);

        try {
            // Kiểm tra không cho phép chọn chính nó làm parent
            if ($request->parent_folder_id == $folderId) {
                return redirect()->back()
                    ->with('error', 'Không thể chọn chính thư mục này làm thư mục cha!');
            }

            $folder->update([
                'name' => $request->name,
                'status' => $request->status,
                'parent_folder_id' => $request->parent_folder_id,
            ]);

            return redirect()->route('folders.index', ['parent_id' => $folder->parent_folder_id])
                ->with('success', 'Thư mục đã được cập nhật thành công!');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Lỗi khi cập nhật thư mục: ' . $e->getMessage());
        }
    }

    /**
     * Xóa thư mục
     */
    public function destroy($folderId)
    {
        $folder = Folder::with(['childFolders', 'documents'])->findOrFail($folderId);

        try {
            DB::transaction(function () use ($folder) {
                // Kiểm tra nếu thư mục có thư mục con
                if ($folder->childFolders->count() > 0) {
                    throw new \Exception('Không thể xóa thư mục có chứa thư mục con!');
                }

                // Kiểm tra nếu thư mục có tài liệu
                if ($folder->documents->count() > 0) {
                    throw new \Exception('Không thể xóa thư mục có chứa tài liệu!');
                }

                $folder->delete();
            });

            return redirect()->back()
                ->with('success', 'Thư mục đã được xóa thành công!');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', $e->getMessage());
        }
    }
}
