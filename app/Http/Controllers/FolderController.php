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
     * Hiển thị danh sách thư mục
     */
    public function index(Request $request): View
    {
        try {
            // Validate thủ công các tham số tìm kiếm
            $validatedData = $request->validate([
                'name' => 'nullable|string|max:255',
                'date' => 'nullable|date',
                'status' => 'nullable|in:public,private',
                'parent_id' => 'nullable|exists:folders,folder_id',
                'per_page' => 'nullable|integer|min:1|max:100'
            ]);

            $result = $this->folderService->getFoldersWithFilters($validatedData);

            return view('folders.index', [
                'folders' => $result['folders'],
                'currentFolder' => $result['currentFolder'],
                'breadcrumbs' => $result['breadcrumbs'],
                'searchParams' => $validatedData
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            // FIX: Không redirect mà hiển thị lỗi trực tiếp
            $result = $this->folderService->getFoldersWithFilters([]);

            return view('folders.index', [
                'folders' => $result['folders'],
                'currentFolder' => $result['currentFolder'],
                'breadcrumbs' => $result['breadcrumbs'],
                'searchParams' => [],
                'errors' => $e->errors()
            ]);
        } catch (\Exception $e) {
            // FIX: Không redirect mà hiển thị lỗi trực tiếp
            $result = $this->folderService->getFoldersWithFilters([]);

            return view('folders.index', [
                'folders' => $result['folders'],
                'currentFolder' => $result['currentFolder'],
                'breadcrumbs' => $result['breadcrumbs'],
                'searchParams' => [],
                'error' => 'Lỗi khi tải danh sách thư mục: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Hiển thị chi tiết thư mục
     */
    public function show(Request $request, $folder): View
    {
        try {
            // Validate thủ công các tham số tìm kiếm
            $validatedData = $request->validate([
                'name' => 'nullable|string|max:255',
                'date' => 'nullable|date',
                'status' => 'nullable|in:public,private',
                'per_page' => 'nullable|integer|min:1|max:100'
            ]);

            $params = array_merge($validatedData, ['parent_id' => $folder]);
            $result = $this->folderService->getFoldersWithFilters($params);

            return view('folders.index', [
                'folders' => $result['folders'],
                'currentFolder' => $result['currentFolder'],
                'breadcrumbs' => $result['breadcrumbs'],
                'searchParams' => $validatedData
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            // FIX: Xử lý lỗi validation bằng cách hiển thị trang với lỗi
            return view('folders.index', [
                'folders' => collect(),
                'currentFolder' => null,
                'breadcrumbs' => [],
                'searchParams' => [],
                'errors' => $e->errors()
            ]);
        } catch (\Exception $e) {
            // FIX: Xử lý lỗi bằng cách hiển thị trang với thông báo lỗi
            return view('folders.index', [
                'folders' => collect(),
                'currentFolder' => null,
                'breadcrumbs' => [],
                'searchParams' => [],
                'error' => 'Lỗi khi tải thư mục: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Hiển thị form tạo thư mục
     */
    public function create(Request $request): View
    {
        try {
            $parentFolderId = $request->get('parent_id');

            // FIX: Kiểm tra nếu parent_id là 'null' string thì set thành null
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
            // FIX: Xử lý lỗi bằng cách hiển thị form với thông báo lỗi
            return view('folders.create', [
                'parentFolderId' => $request->get('parent_id'),
                'parentFolderName' => 'Thư mục gốc',
                'breadcrumbs' => [],
                'error' => 'Lỗi khi tải form tạo thư mục: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Lưu thư mục mới
     */
    public function store(StoreFolderRequest $request): RedirectResponse
    {
        try {
            Log::info('Store method called with data:', $request->all());

            // Kiểm tra dữ liệu trước khi tạo
            $validatedData = $request->validated();
            Log::info('Validated data:', $validatedData);

            $folder = $this->folderService->createFolder($validatedData);

            Log::info('Folder created successfully:', [
                'id' => $folder->folder_id,
                'name' => $folder->name,
                'parent_id' => $folder->parent_folder_id
            ]);

            // Build redirect URL
            $redirectParams = [];
            if ($folder->parent_folder_id) {
                $redirectParams = ['parent_id' => $folder->parent_folder_id];
            }

            return redirect()->route('folders.index', $redirectParams)
                ->with('success', 'Thư mục "' . $folder->name . '" đã được tạo thành công!');
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('Validation error in store:', $e->errors());
            return redirect()->back()
                ->withErrors($e->errors())
                ->withInput();
        } catch (\Exception $e) {
            Log::error('Error in store method: ' . $e->getMessage());
            Log::error('Stack trace:', ['trace' => $e->getTraceAsString()]);

            return redirect()->back()
                ->withInput()
                ->with('error', 'Lỗi khi tạo thư mục: ' . $e->getMessage());
        }
    }

    /**
     * Hiển thị form chỉnh sửa thư mục
     */
    public function edit($folder): View
    {
        try {
            $folderData = $this->folderService->getFolderForEdit($folder);

            return view('folders.edit', [
                'folder' => $folderData['folder'],
                'parentFolders' => $folderData['parentFolders'],
                'descendantIds' => $folderData['descendantIds'],  // FIX: Pass new data for cycle prevention
                'breadcrumbs' => $folderData['breadcrumbs']
            ]);
        } catch (\Exception $e) {
            // FIX: Xử lý lỗi bằng cách hiển thị form với thông báo lỗi
            return view('folders.edit', [
                'folder' => null,
                'parentFolders' => [],
                'descendantIds' => [],
                'breadcrumbs' => [],
                'error' => 'Lỗi khi tải form chỉnh sửa: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Cập nhật thư mục
     */
    public function update(UpdateFolderRequest $request, $folder): RedirectResponse|JsonResponse
    {
        try {
            $updatedFolder = $this->folderService->updateFolder($folder, $request->validated());

            $message = 'Thư mục "' . $updatedFolder->name . '" đã được cập nhật thành công!';

            if ($request->expectsJson()) {
                // FIX: Trả JSON cho AJAX request (Vue)
                return response()->json([
                    'success' => true,
                    'message' => $message,
                    'folder' => $updatedFolder  // Pass updated data for redirect in Vue
                ]);
            }

            // Sync request: Build redirect URL
            $redirectUrl = '/folders';
            if ($updatedFolder->parent_folder_id) {
                $redirectUrl .= '?parent_id=' . $updatedFolder->parent_folder_id;
            }

            return redirect($redirectUrl)->with('success', $message);
        } catch (\Exception $e) {
            if ($request->expectsJson()) {
                // FIX: Trả error JSON cho AJAX
                return response()->json([
                    'success' => false,
                    'message' => 'Lỗi khi cập nhật thư mục: ' . $e->getMessage()
                ], 500);
            }

            return redirect()->back()
                ->withInput()
                ->with('error', 'Lỗi khi cập nhật thư mục: ' . $e->getMessage());
        }
    }

    /**
     * Xóa thư mục
     */
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

    /**
     * Tìm kiếm thư mục
     */
    public function search(Request $request): JsonResponse|View
    {
        try {
            // Validate thủ công các tham số tìm kiếm
            $validatedData = $request->validate([
                'name' => 'nullable|string|max:255',
                'date' => 'nullable|date',
                'status' => 'nullable|in:public,private',
                'parent_id' => 'nullable|exists:folders,folder_id',
                'per_page' => 'nullable|integer|min:1|max:100'
            ]);

            $result = $this->folderService->getFoldersWithFilters($validatedData);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'data' => $result
                ]);
            }

            return view('folders.index', [
                'folders' => $result['folders'],
                'currentFolder' => $result['currentFolder'],
                'breadcrumbs' => $result['breadcrumbs'],
                'searchParams' => $validatedData
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            // Xử lý lỗi validation
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'errors' => $e->errors()
                ], 422);
            }

            // FIX: Xử lý lỗi bằng cách hiển thị trang với lỗi
            return view('folders.index', [
                'folders' => collect(),
                'currentFolder' => null,
                'breadcrumbs' => [],
                'searchParams' => [],
                'errors' => $e->errors()
            ]);
        } catch (\Exception $e) {
            // Xử lý lỗi chung
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage()
                ], 500);
            }

            // FIX: Xử lý lỗi bằng cách hiển thị trang với thông báo lỗi
            return view('folders.index', [
                'folders' => collect(),
                'currentFolder' => null,
                'breadcrumbs' => [],
                'searchParams' => [],
                'error' => 'Lỗi khi tìm kiếm: ' . $e->getMessage()
            ]);
        }
    }
}
