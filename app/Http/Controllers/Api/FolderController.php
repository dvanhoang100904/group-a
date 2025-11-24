<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\FolderService;
use App\Http\Requests\Folder\StoreFolderRequest;
use App\Http\Requests\Folder\UpdateFolderRequest;
use App\Models\Folder;
use App\Models\Document;
use App\Models\DocumentVersion;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

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
    // Trong FolderController
    public function index(Request $request): JsonResponse
    {
        try {
            if (!Auth::check()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized'
                ], 401);
            }

            $validator = \Validator::make($request->all(), [
                'name' => 'nullable|string|max:255',
                'date' => 'nullable|date',
                'status' => 'nullable|in:public,private',
                'file_type' => 'nullable|string|max:255',
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

            \Log::info('📦 API Request Data:', $validatedData);

            // Gọi service với tất cả params
            $result = $this->folderService->getFoldersAndDocuments($validatedData);

            $items = $result['items'] ?? [];
            if ($items && method_exists($items, 'getCollection')) {
                $filteredCollection = $items->getCollection()->filter(function ($item) {
                    return $item !== null;
                });
                $items->setCollection($filteredCollection);
            }

            $responseData = [
                'success' => true,
                'data' => [
                    'items' => $result['items'] ?? [],
                    'currentFolder' => $result['currentFolder'] ?? null,
                    'breadcrumbs' => $result['breadcrumbs'] ?? [],
                    'isSearchMode' => $result['isSearchMode'] ?? false,
                    'current_page' => $request->input('page', 1),
                    'last_page' => $result['items']->lastPage() ?? 1,
                    'from' => $result['items']->firstItem() ?? 0,
                    'to' => $result['items']->lastItem() ?? 0,
                    'total' => $result['items']->total() ?? 0
                ]
            ];

            \Log::info('📤 API Response Summary:', [
                'items_count' => count($responseData['data']['items']->items() ?? []),
                'isSearchMode' => $responseData['data']['isSearchMode'],
                'total' => $responseData['data']['total']
            ]);

            return response()->json($responseData);
        } catch (\Exception $e) {
            \Log::error('API Folder Index Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error loading folders: ' . $e->getMessage()
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
    /**
     * API: Xóa document
     */
    /**
     * API: Xóa document - ĐÃ SỬA VỚI DEBUG
     */
    public function deleteDocument($id)
    {
        DB::beginTransaction();

        try {
            Log::info('=== API DELETE DOCUMENT START ===');
            Log::info('Document ID:', ['id' => $id]);
            Log::info('User ID:', ['user_id' => Auth::id()]);

            // Kiểm tra user đăng nhập
            if (!Auth::check()) {
                Log::warning('User not authenticated');
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized'
                ], 401);
            }

            $document = Document::find($id);

            if (!$document) {
                Log::warning('Document not found:', ['document_id' => $id]);
                return response()->json([
                    'success' => false,
                    'message' => 'Tài liệu không tồn tại'
                ], 404);
            }

            Log::info('Document found:', [
                'id' => $document->document_id,
                'title' => $document->title,
                'user_id' => $document->user_id,
                'owner' => $document->user_id
            ]);

            // Kiểm tra quyền
            if ($document->user_id != Auth::id()) {
                Log::warning('Permission denied:', [
                    'current_user' => Auth::id(),
                    'document_owner' => $document->user_id
                ]);
                return response()->json([
                    'success' => false,
                    'message' => 'Bạn không có quyền xóa tài liệu này'
                ], 403);
            }

            // Xóa các versions
            $versions = DocumentVersion::where('document_id', $id)->get();
            Log::info('Found versions:', ['count' => $versions->count()]);

            foreach ($versions as $version) {
                Log::info('Processing version:', [
                    'version_id' => $version->id,
                    'file_name' => $version->file_name
                ]);

                if ($version->file_name) {
                    $filePath = base_path('app/Public_UploadFile/' . $version->file_name);
                    Log::info('File path:', ['path' => $filePath]);

                    if (file_exists($filePath)) {
                        if (unlink($filePath)) {
                            Log::info('File deleted successfully:', ['file' => $version->file_name]);
                        } else {
                            Log::warning('Failed to delete file:', ['file' => $version->file_name]);
                        }
                    } else {
                        Log::warning('File not found:', ['file' => $version->file_name]);
                    }
                }

                $version->delete();
                Log::info('Version deleted:', ['version_id' => $version->id]);
            }

            $documentName = $document->title;
            $document->delete();

            Log::info('Document deleted successfully:', [
                'document_id' => $id,
                'document_name' => $documentName
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Xóa tài liệu "' . $documentName . '" thành công!'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('=== API DELETE DOCUMENT ERROR ===');
            Log::error('Error message: ' . $e->getMessage());
            Log::error('Error trace: ' . $e->getTraceAsString());
            Log::error('Error in file: ' . $e->getFile());
            Log::error('Error on line: ' . $e->getLine());
            Log::error('=== END ERROR ===');

            return response()->json([
                'success' => false,
                'message' => 'Lỗi hệ thống khi xóa tài liệu: ' . $e->getMessage()
            ], 500);
        }
    }
}
