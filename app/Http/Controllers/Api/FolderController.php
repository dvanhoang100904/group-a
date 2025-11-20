<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\FolderService;
use App\Http\Requests\Folder\StoreFolderRequest;
use App\Http\Requests\Folder\UpdateFolderRequest;
use App\Models\Folder;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class FolderController extends Controller
{
    protected $folderService;

    public function __construct(FolderService $folderService)
    {
        $this->folderService = $folderService;
    }

    /**
     * Lấy danh sách folders của user đăng nhập
     */
    public function index(Request $request): JsonResponse
    {
        try {
            // Kiểm tra user đã đăng nhập chưa
            if (!Auth::check()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized'
                ], 401);
            }

            // Validate parameters
            $validator = \Validator::make($request->all(), [
                'name' => 'nullable|string|max:255',
                'date' => 'nullable|date',
                'status' => 'nullable|in:public,private',
                'parent_id' => 'nullable|integer',
                'per_page' => 'nullable|integer|min:1|max:100',
                'page' => 'nullable|integer|min:1'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $validatedData = $validator->validated();

            $result = $this->folderService->getFoldersWithFilters($validatedData);

            return response()->json([
                'success' => true,
                'data' => $result
            ]);
        } catch (\Exception $e) {
            \Log::error('API Folder Index Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error loading folders'
            ], 500);
        }
    }
    public function getFolder()
    {
        return response()->json(Folder::all());
    }
    /**
     * Lấy chi tiết folder (API)
     */
    public function show($folder): JsonResponse
    {
        try {
            $folderData = $this->folderService->getFolderForEdit($folder);

            return response()->json([
                'success' => true,
                'data' => $folderData
            ]);
        } catch (\Exception $e) {
            \Log::error('API Folder Show Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error loading folder'
            ], 500);
        }
    }

    /**
     * Tạo folder mới (API)
     */
    public function store(StoreFolderRequest $request): JsonResponse
    {
        try {
            $folder = $this->folderService->createFolder($request->validated());

            return response()->json([
                'success' => true,
                'message' => 'Thư mục "' . $folder->name . '" đã được tạo thành công!',
                'data' => $folder
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            \Log::error('API Folder Store Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error creating folder: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Cập nhật folder (API)
     */
    public function update(UpdateFolderRequest $request, $folder): JsonResponse
    {
        try {
            $updatedFolder = $this->folderService->updateFolder($folder, $request->validated());

            return response()->json([
                'success' => true,
                'message' => 'Thư mục "' . $updatedFolder->name . '" đã được cập nhật thành công!',
                'data' => $updatedFolder
            ]);
        } catch (\Exception $e) {
            \Log::error('API Folder Update Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error updating folder: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Xóa folder (API)
     */
    public function destroy(Request $request, $folder): JsonResponse
    {
        try {
            Log::info('API Delete Folder Request:', [
                'folder_id' => $folder,
                'user_id' => auth()->id(),
                'time' => now()
            ]);

            $folderName = $this->folderService->deleteFolder($folder);

            Log::info('API Delete Folder Success:', ['folder_name' => $folderName]);

            return response()->json([
                'success' => true,
                'message' => 'Thư mục "' . $folderName . '" đã được xóa thành công!'
            ]);
        } catch (\Exception $e) {
            Log::error('API Delete Folder Error:', [
                'error' => $e->getMessage(),
                'folder_id' => $folder
            ]);

            $statusCode = $e->getMessage() === 'Thư mục không tồn tại' ? 404 : 500;

            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], $statusCode);
        }
    }

    /**
     * Tìm kiếm folders (API)
     */
    public function search(Request $request): JsonResponse
    {
        try {
            $validatedData = $request->validate([
                'name' => 'nullable|string|max:255',
                'date' => 'nullable|date',
                'status' => 'nullable|in:public,private',
                'parent_id' => 'nullable|exists:folders,folder_id',
                'per_page' => 'nullable|integer|min:1|max:100'
            ]);

            $result = $this->folderService->getFoldersWithFilters($validatedData);

            return response()->json([
                'success' => true,
                'data' => $result
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            \Log::error('API Folder Search Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error searching folders'
            ], 500);
        }
    }
}
