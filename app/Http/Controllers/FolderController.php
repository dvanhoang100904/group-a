<?php

namespace App\Http\Controllers;

use App\Services\FolderService;
use App\Http\Requests\Folder\StoreFolderRequest;
use App\Http\Requests\Folder\UpdateFolderRequest;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;

class FolderController extends Controller
{
    protected $folderService;

    public function __construct(FolderService $folderService)
    {
        $this->folderService = $folderService;
    }

    /**
     * Hiển thị danh sách thư mục + documents (Home page)
     */
    public function index(Request $request): View
    {
        try {
            $validatedData = $request->validate([
                'name' => 'nullable|string|max:255',
                'date' => 'nullable|date',
                'status' => 'nullable|in:public,private',
                'parent_id' => 'nullable|exists:folders,folder_id',
                'per_page' => 'nullable|integer|min:1|max:100'
            ]);

            // ✅ GỌI SERVICE MỚI - Lấy cả folders + documents
            $result = $this->folderService->getFoldersAndDocuments($validatedData);

            return view('folders.index', [
                'items' => $result['items'],           // ✅ Thay đổi: trả về items (folders + documents)
                'currentFolder' => $result['currentFolder'],
                'breadcrumbs' => $result['breadcrumbs'],
                'searchParams' => $validatedData
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            $result = $this->folderService->getFoldersAndDocuments([]);

            return view('folders.index', [
                'items' => $result['items'],
                'currentFolder' => $result['currentFolder'],
                'breadcrumbs' => $result['breadcrumbs'],
                'searchParams' => [],
                'errors' => $e->errors()
            ]);
        } catch (\Exception $e) {
            $result = $this->folderService->getFoldersAndDocuments([]);

            return view('folders.index', [
                'items' => $result['items'],
                'currentFolder' => $result['currentFolder'],
                'breadcrumbs' => $result['breadcrumbs'],
                'searchParams' => [],
                'error' => 'Lỗi khi tải danh sách: ' . $e->getMessage()
            ]);
        }
    }

    // GIỮ NGUYÊN CÁC METHOD KHÁC: show, create, store, edit, update, destroy, search

    public function show(Request $request, $folder): View
    {
        try {
            $validatedData = $request->validate([
                'name' => 'nullable|string|max:255',
                'date' => 'nullable|date',
                'status' => 'nullable|in:public,private',
                'per_page' => 'nullable|integer|min:1|max:100'
            ]);

            $params = array_merge($validatedData, ['parent_id' => $folder]);
            $result = $this->folderService->getFoldersAndDocuments($params);

            return view('folders.index', [
                'items' => $result['items'],
                'currentFolder' => $result['currentFolder'],
                'breadcrumbs' => $result['breadcrumbs'],
                'searchParams' => $validatedData
            ]);
        } catch (\Exception $e) {
            return view('folders.index', [
                'items' => collect(),
                'currentFolder' => null,
                'breadcrumbs' => [],
                'searchParams' => [],
                'error' => 'Lỗi: ' . $e->getMessage()
            ]);
        }
    }

    public function create(Request $request): View
    {
        try {
            $parentFolderId = $request->get('parent_id');

            if ($parentFolderId === 'null' || $parentFolderId === '') {
                $parentFolderId = null;
            }

            $locationInfo = $this->folderService->getFolderLocationInfo($parentFolderId);

            return view('folders.create', [
                'parentFolderId' => $parentFolderId,
                'parentFolderName' => $locationInfo['name'],
                'breadcrumbs' => $locationInfo['breadcrumbs']
            ]);
        } catch (\Exception $e) {
            return view('folders.create', [
                'parentFolderId' => $request->get('parent_id'),
                'parentFolderName' => 'Thư mục gốc',
                'breadcrumbs' => [],
                'error' => 'Lỗi: ' . $e->getMessage()
            ]);
        }
    }

    public function store(StoreFolderRequest $request): RedirectResponse
    {
        try {
            Log::info('Store method called with data:', $request->all());

            $validatedData = $request->validated();
            Log::info('Validated data:', $validatedData);

            $folder = $this->folderService->createFolder($validatedData);

            Log::info('Folder created successfully:', [
                'id' => $folder->folder_id,
                'name' => $folder->name,
                'parent_id' => $folder->parent_folder_id
            ]);

            $redirectParams = [];
            if ($folder->parent_folder_id) {
                $redirectParams = ['parent_id' => $folder->parent_folder_id];
            }

            return redirect()->route('folders.index', $redirectParams)
                ->with('success', 'Thư mục "' . $folder->name . '" đã được tạo thành công!');
        } catch (\Exception $e) {
            Log::error('Error in store method: ' . $e->getMessage());
            Log::error('Stack trace:', ['trace' => $e->getTraceAsString()]);

            return redirect()->back()
                ->withInput()
                ->with('error', 'Lỗi khi tạo thư mục: ' . $e->getMessage());
        }
    }

    public function edit($folder): View
    {
        try {
            $folderData = $this->folderService->getFolderForEdit($folder);

            return view('folders.edit', [
                'folder' => $folderData['folder'],
                'parentFolders' => $folderData['parentFolders'],
                'descendantIds' => $folderData['descendantIds'],
                'breadcrumbs' => $folderData['breadcrumbs']
            ]);
        } catch (\Exception $e) {
            return view('folders.edit', [
                'folder' => null,
                'parentFolders' => [],
                'descendantIds' => [],
                'breadcrumbs' => [],
                'error' => 'Lỗi: ' . $e->getMessage()
            ]);
        }
    }

    public function update(UpdateFolderRequest $request, $folder): RedirectResponse|JsonResponse
    {
        try {
            $updatedFolder = $this->folderService->updateFolder($folder, $request->validated());

            $message = 'Thư mục "' . $updatedFolder->name . '" đã được cập nhật thành công!';

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => $message,
                    'folder' => $updatedFolder
                ]);
            }

            $redirectUrl = '/folders';
            if ($updatedFolder->parent_folder_id) {
                $redirectUrl .= '?parent_id=' . $updatedFolder->parent_folder_id;
            }

            return redirect($redirectUrl)->with('success', $message);
        } catch (\Exception $e) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Lỗi: ' . $e->getMessage()
                ], 500);
            }

            return redirect()->back()
                ->withInput()
                ->with('error', 'Lỗi: ' . $e->getMessage());
        }
    }

    public function destroy($folder): RedirectResponse
    {
        try {
            $folderName = $this->folderService->deleteFolder($folder);

            return redirect()->back()
                ->with('success', 'Thư mục "' . $folderName . '" đã được xóa thành công!');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', $e->getMessage());
        }
    }

    public function search(Request $request): JsonResponse|View
    {
        try {
            $validatedData = $request->validate([
                'name' => 'nullable|string|max:255',
                'date' => 'nullable|date',
                'status' => 'nullable|in:public,private',
                'parent_id' => 'nullable|exists:folders,folder_id',
                'per_page' => 'nullable|integer|min:1|max:100'
            ]);

            $result = $this->folderService->getFoldersAndDocuments($validatedData);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'data' => $result
                ]);
            }

            return view('folders.index', [
                'items' => $result['items'],
                'currentFolder' => $result['currentFolder'],
                'breadcrumbs' => $result['breadcrumbs'],
                'searchParams' => $validatedData
            ]);
        } catch (\Exception $e) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage()
                ], 500);
            }

            return view('folders.index', [
                'items' => collect(),
                'currentFolder' => null,
                'breadcrumbs' => [],
                'searchParams' => [],
                'error' => 'Lỗi: ' . $e->getMessage()
            ]);
        }
    }
}
