<?php

namespace App\Http\Controllers;

use App\Models\Folder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

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
}
