<?php

namespace App\Http\Controllers;

use App\Services\FolderService;
use App\Http\Requests\Folder\StoreFolderRequest;
use App\Http\Requests\Folder\UpdateFolderRequest;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

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
            // FIX: Redirect về root thay vì current folder
            return redirect('/folders')
                ->withErrors($e->errors())
                ->withInput();
        } catch (\Exception $e) {
            // FIX: Redirect về root thay vì current folder
            return redirect('/folders')
                ->with('error', 'Lỗi khi tải thư mục: ' . $e->getMessage());
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
            // KHÔNG redirect trong method create() vì nó phải trả về View
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
            $folder = $this->folderService->createFolder($request->validated());

            // Build redirect URL
            $redirectUrl = '/folders';
            if ($folder->parent_folder_id) {
                $redirectUrl .= '?parent_id=' . $folder->parent_folder_id;
            }

            return redirect($redirectUrl)
                ->with('success', 'Thư mục "' . $folder->name . '" đã được tạo thành công!');
        } catch (\Exception $e) {
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
                'breadcrumbs' => $folderData['breadcrumbs']
            ]);
        } catch (\Exception $e) {
            // FIX: Redirect về root với thông báo lỗi
            return redirect('/folders')
                ->with('error', 'Lỗi khi tải form chỉnh sửa: ' . $e->getMessage());
        }
    }

    /**
     * Cập nhật thư mục
     */
    public function update(UpdateFolderRequest $request, $folder): RedirectResponse
    {
        try {
            $folder = $this->folderService->updateFolder($folder, $request->validated());

            // Build redirect URL
            $redirectUrl = '/folders';
            if ($folder->parent_folder_id) {
                $redirectUrl .= '?parent_id=' . $folder->parent_folder_id;
            }

            return redirect($redirectUrl)
                ->with('success', 'Thư mục "' . $folder->name . '" đã được cập nhật thành công!');
        } catch (\Exception $e) {
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

            // FIX: Redirect về root với lỗi validation
            return redirect('/folders')
                ->withErrors($e->errors())
                ->withInput();
        } catch (\Exception $e) {
            // Xử lý lỗi chung
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage()
                ], 500);
            }

            // FIX: Redirect về root với lỗi chung
            return redirect('/folders')
                ->with('error', 'Lỗi khi tìm kiếm: ' . $e->getMessage());
        }
    }
}
