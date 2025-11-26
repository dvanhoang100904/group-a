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
use Illuminate\Support\Facades\Validator;

class FolderController extends Controller
{
    protected $folderService;

    public function __construct(FolderService $folderService)
    {
        $this->folderService = $folderService;
    }

    /**
     * Validate và sanitize input parameters
     */
    private function validateSearchParams(array $params): array
    {
        $validator = Validator::make($params, [
            'name' => 'nullable|string|max:255',
            'date' => 'nullable|date_format:Y-m-d',
            'file_type' => 'nullable|string|max:100',
            'parent_id' => 'nullable|integer|min:0',
            'per_page' => 'nullable|integer|min:1|max:100'
        ]);

        if ($validator->fails()) {
            throw new \Illuminate\Validation\ValidationException($validator);
        }

        $validated = $validator->validated();

        // Sanitize inputs để tránh XSS
        if (isset($validated['name'])) {
            $validated['name'] = $this->sanitizeInput($validated['name']);
        }

        if (isset($validated['file_type'])) {
            $validated['file_type'] = $this->sanitizeInput($validated['file_type']);
        }

        return $validated;
    }

    /**
     * Sanitize input để tránh XSS
     */
    private function sanitizeInput(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        return htmlspecialchars(trim($value), ENT_QUOTES, 'UTF-8');
    }

    /**
     * Escape output để tránh XSS
     */
    private function escapeOutput(?string $value): string
    {
        if ($value === null) {
            return '';
        }

        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }

    /**
     * Validate folder ID để tránh SQL Injection
     */
    private function validateFolderId($folderId): int
    {
        if (!is_numeric($folderId) || $folderId <= 0) {
            throw new \Exception('ID thư mục không hợp lệ');
        }

        return (int)$folderId;
    }

    /**
     * Escape output data để tránh XSS
     */
    private function escapeOutputData(array $data): array
    {
        // Escape breadcrumbs
        if (isset($data['breadcrumbs']) && is_array($data['breadcrumbs'])) {
            foreach ($data['breadcrumbs'] as &$breadcrumb) {
                if (isset($breadcrumb['name'])) {
                    $breadcrumb['name'] = $this->escapeOutput($breadcrumb['name']);
                }
            }
        }

        // Escape current folder name
        if (isset($data['currentFolder']) && $data['currentFolder'] !== null) {
            if (isset($data['currentFolder']->name)) {
                $data['currentFolder']->name = $this->escapeOutput($data['currentFolder']->name);
            }
        }

        return $data;
    }

    /**
     * Hiển thị danh sách thư mục + documents (Home page) - ĐÃ BẢO MẬT
     */
    public function index(Request $request): View
    {
        try {
            // ✅ BẢO MẬT: Validate và sanitize input
            $validatedData = $this->validateSearchParams($request->all());

            // ✅ GỌI SERVICE MỚI - Lấy cả folders + documents
            $result = $this->folderService->getFoldersAndDocuments($validatedData);

            // ✅ BẢO MẬT: Escape output để tránh XSS
            $escapedResult = $this->escapeOutputData($result);

            return view('folders.index', [
                'items' => $escapedResult['items'],
                'currentFolder' => $escapedResult['currentFolder'],
                'breadcrumbs' => $escapedResult['breadcrumbs'],
                'searchParams' => $this->escapeSearchParams($validatedData)
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            // ✅ BẢO MẬT: Xử lý validation error an toàn
            $result = $this->folderService->getFoldersAndDocuments([]);
            $escapedResult = $this->escapeOutputData($result);

            return view('folders.index', [
                'items' => $escapedResult['items'],
                'currentFolder' => $escapedResult['currentFolder'],
                'breadcrumbs' => $escapedResult['breadcrumbs'],
                'searchParams' => [],
                'errors' => $e->errors()
            ]);
        } catch (\Exception $e) {
            // ✅ BẢO MẬT: Log lỗi và escape error message
            Log::error('FolderController index error: ' . $e->getMessage());

            $result = $this->folderService->getFoldersAndDocuments([]);
            $escapedResult = $this->escapeOutputData($result);

            return view('folders.index', [
                'items' => $escapedResult['items'],
                'currentFolder' => $escapedResult['currentFolder'],
                'breadcrumbs' => $escapedResult['breadcrumbs'],
                'searchParams' => [],
                'error' => 'Lỗi khi tải danh sách: ' . $this->escapeOutput($e->getMessage())
            ]);
        }
    }

    /**
     * Escape search parameters
     */
    private function escapeSearchParams(array $params): array
    {
        foreach ($params as $key => $value) {
            if (is_string($value)) {
                $params[$key] = $this->escapeOutput($value);
            }
        }
        return $params;
    }

    public function show(Request $request, $folder): View
    {
        try {
            // ✅ BẢO MẬT: Validate folder ID
            $folderId = $this->validateFolderId($folder);

            $validatedData = $this->validateSearchParams($request->all());
            $params = array_merge($validatedData, ['parent_id' => $folderId]);

            $result = $this->folderService->getFoldersAndDocuments($params);

            // ✅ BẢO MẬT: Escape output
            $escapedResult = $this->escapeOutputData($result);

            return view('folders.index', [
                'items' => $escapedResult['items'],
                'currentFolder' => $escapedResult['currentFolder'],
                'breadcrumbs' => $escapedResult['breadcrumbs'],
                'searchParams' => $this->escapeSearchParams($validatedData)
            ]);
        } catch (\Exception $e) {
            // ✅ BẢO MẬT: Log và escape error
            Log::error('FolderController show error: ' . $e->getMessage());

            return view('folders.index', [
                'items' => collect(),
                'currentFolder' => null,
                'breadcrumbs' => [],
                'searchParams' => [],
                'error' => 'Lỗi: ' . $this->escapeOutput($e->getMessage())
            ]);
        }
    }

    public function create(Request $request): View
    {
        try {
            $parentFolderId = $request->get('parent_id');

            // ✅ BẢO MẬT: Validate parent_id
            if ($parentFolderId && (!is_numeric($parentFolderId) || $parentFolderId <= 0)) {
                throw new \Exception('ID thư mục cha không hợp lệ');
            }

            if ($parentFolderId === 'null' || $parentFolderId === '') {
                $parentFolderId = null;
            }

            $locationInfo = $this->folderService->getFolderLocationInfo($parentFolderId);

            // ✅ BẢO MẬT: Escape output
            $locationInfo['name'] = $this->escapeOutput($locationInfo['name']);
            foreach ($locationInfo['breadcrumbs'] as &$breadcrumb) {
                $breadcrumb['name'] = $this->escapeOutput($breadcrumb['name']);
            }

            return view('folders.create', [
                'parentFolderId' => $parentFolderId,
                'parentFolderName' => $locationInfo['name'],
                'breadcrumbs' => $locationInfo['breadcrumbs']
            ]);
        } catch (\Exception $e) {
            // ✅ BẢO MẬT: Log và escape error
            Log::error('FolderController create error: ' . $e->getMessage());

            return view('folders.create', [
                'parentFolderId' => $request->get('parent_id'),
                'parentFolderName' => 'Thư mục gốc',
                'breadcrumbs' => [],
                'error' => 'Lỗi: ' . $this->escapeOutput($e->getMessage())
            ]);
        }
    }

    public function store(StoreFolderRequest $request): RedirectResponse
    {
        try {
            // ✅ BẢO MẬT: Log an toàn (không log sensitive data)
            Log::info('Store method called');

            $validatedData = $request->validated();

            $folder = $this->folderService->createFolder($validatedData);

            Log::info('Folder created successfully:', [
                'id' => $folder->folder_id,
                'parent_id' => $folder->parent_folder_id
            ]);

            $redirectParams = [];
            if ($folder->parent_folder_id) {
                $redirectParams = ['parent_id' => $folder->parent_folder_id];
            }

            // ✅ BẢO MẬT: Escape output trong success message
            return redirect()->route('folders.index', $redirectParams)
                ->with('success', 'Thư mục "' . $this->escapeOutput($folder->name) . '" đã được tạo thành công!');
        } catch (\Exception $e) {
            // ✅ BẢO MẬT: Log chi tiết nhưng không expose sensitive info
            Log::error('Error in store method: ' . $e->getMessage());

            return redirect()->back()
                ->withInput()
                ->with('error', 'Lỗi khi tạo thư mục: ' . $this->escapeOutput($e->getMessage()));
        }
    }

    public function edit($folder): View
    {
        try {
            // ✅ BẢO MẬT: Validate folder ID
            $folderId = $this->validateFolderId($folder);

            $folderData = $this->folderService->getFolderForEdit($folderId);

            // ✅ BẢO MẬT: Escape output
            $folderData['folder']->name = $this->escapeOutput($folderData['folder']->name);
            foreach ($folderData['parentFolders'] as &$parentFolder) {
                $parentFolder->name = $this->escapeOutput($parentFolder->name);
                if (isset($parentFolder->indented_name)) {
                    $parentFolder->indented_name = $this->escapeOutput($parentFolder->indented_name);
                }
            }
            foreach ($folderData['breadcrumbs'] as &$breadcrumb) {
                $breadcrumb['name'] = $this->escapeOutput($breadcrumb['name']);
            }

            return view('folders.edit', [
                'folder' => $folderData['folder'],
                'parentFolders' => $folderData['parentFolders'],
                'descendantIds' => $folderData['descendantIds'],
                'breadcrumbs' => $folderData['breadcrumbs']
            ]);
        } catch (\Exception $e) {
            // ✅ BẢO MẬT: Log và escape error
            Log::error('FolderController edit error: ' . $e->getMessage());

            return view('folders.edit', [
                'folder' => null,
                'parentFolders' => [],
                'descendantIds' => [],
                'breadcrumbs' => [],
                'error' => 'Lỗi: ' . $this->escapeOutput($e->getMessage())
            ]);
        }
    }

    public function update(UpdateFolderRequest $request, $folder): RedirectResponse|JsonResponse
    {
        try {
            // ✅ BẢO MẬT: Validate folder ID
            $folderId = $this->validateFolderId($folder);

            $updatedFolder = $this->folderService->updateFolder($folderId, $request->validated());

            // ✅ BẢO MẬT: Escape output trong message
            $message = 'Thư mục "' . $this->escapeOutput($updatedFolder->name) . '" đã được cập nhật thành công!';

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => $message,
                    'folder' => [
                        'folder_id' => $updatedFolder->folder_id,
                        'name' => $this->escapeOutput($updatedFolder->name),
                        'status' => $updatedFolder->status,
                        'parent_folder_id' => $updatedFolder->parent_folder_id
                    ]
                ]);
            }

            $redirectUrl = '/folders';
            if ($updatedFolder->parent_folder_id) {
                $redirectUrl .= '?parent_id=' . $updatedFolder->parent_folder_id;
            }

            return redirect($redirectUrl)->with('success', $message);
        } catch (\Exception $e) {
            // ✅ BẢO MẬT: Log và escape error
            Log::error('FolderController update error: ' . $e->getMessage());

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Lỗi: ' . $this->escapeOutput($e->getMessage())
                ], 500);
            }

            return redirect()->back()
                ->withInput()
                ->with('error', 'Lỗi: ' . $this->escapeOutput($e->getMessage()));
        }
    }

    public function destroy($folder): RedirectResponse
    {
        try {
            // ✅ BẢO MẬT: Validate folder ID
            $folderId = $this->validateFolderId($folder);

            $folderName = $this->folderService->deleteFolder($folderId);

            // ✅ BẢO MẬT: Escape output
            return redirect()->back()
                ->with('success', 'Thư mục "' . $this->escapeOutput($folderName) . '" đã được xóa thành công!');
        } catch (\Exception $e) {
            // ✅ BẢO MẬT: Log và escape error
            Log::error('FolderController destroy error: ' . $e->getMessage());

            return redirect()->back()
                ->with('error', $this->escapeOutput($e->getMessage()));
        }
    }

    public function search(Request $request): JsonResponse|View
    {
        try {
            // ✅ BẢO MẬT: Validate và sanitize input
            $validatedData = $this->validateSearchParams($request->all());

            $result = $this->folderService->getFoldersAndDocuments($validatedData);

            // ✅ BẢO MẬT: Escape output
            $escapedResult = $this->escapeOutputData($result);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'data' => $escapedResult
                ]);
            }

            return view('folders.index', [
                'items' => $escapedResult['items'],
                'currentFolder' => $escapedResult['currentFolder'],
                'breadcrumbs' => $escapedResult['breadcrumbs'],
                'searchParams' => $this->escapeSearchParams($validatedData)
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'errors' => $e->errors()
                ], 422);
            }

            return view('folders.index', [
                'items' => collect(),
                'currentFolder' => null,
                'breadcrumbs' => [],
                'searchParams' => [],
                'errors' => $e->errors()
            ]);
        } catch (\Exception $e) {
            // ✅ BẢO MẬT: Log và escape error
            Log::error('FolderController search error: ' . $e->getMessage());

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $this->escapeOutput($e->getMessage())
                ], 500);
            }

            return view('folders.index', [
                'items' => collect(),
                'currentFolder' => null,
                'breadcrumbs' => [],
                'searchParams' => [],
                'error' => 'Lỗi: ' . $this->escapeOutput($e->getMessage())
            ]);
        }
    }
}
