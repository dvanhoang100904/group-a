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
use Illuminate\Support\Facades\Validator;
use App\Models\User;
use App\Models\FolderShare;

class FolderController extends Controller
{
    protected $folderService;

    public function __construct(FolderService $folderService)
    {
        $this->folderService = $folderService;
    }

    /**
     * Validate và sanitize API parameters
     */
    private function validateApiParams(array $params): array
    {
        $validator = Validator::make($params, [
            'name' => 'nullable|string|max:255',
            'date' => 'nullable|date_format:Y-m-d',
            'file_type' => 'nullable|string|max:100',
            'parent_id' => 'nullable|integer|min:0',
            'per_page' => 'nullable|integer|min:1|max:100',
            'page' => 'nullable|integer|min:1'
        ]);

        if ($validator->fails()) {
            throw new \Illuminate\Validation\ValidationException($validator);
        }

        $validated = $validator->validated();

        // Sanitize inputs
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
     * Lấy danh sách folders của user đăng nhập - ĐÃ BẢO MẬT
     */
    public function index(Request $request): JsonResponse
    {
        try {
            if (!Auth::check()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized'
                ], 401);
            }

            // Validate và sanitize input
            $validatedData = $this->validateApiParams($request->all());

            // Gọi service với tất cả params
            $result = $this->folderService->getFoldersAndDocuments($validatedData);

            $items = $result['items'] ?? [];
            if ($items && method_exists($items, 'getCollection')) {
                $filteredCollection = $items->getCollection()->filter(function ($item) {
                    return $item !== null;
                });
                $items->setCollection($filteredCollection);
            }

            // Escape output data
            $escapedData = $this->escapeApiOutputData($result);

            $responseData = [
                'success' => true,
                'data' => [
                    'items' => $escapedData['items'] ?? [],
                    'currentFolder' => $escapedData['currentFolder'] ?? null,
                    'breadcrumbs' => $escapedData['breadcrumbs'] ?? [],
                    'isSearchMode' => $escapedData['isSearchMode'] ?? false,
                    'current_page' => $request->input('page', 1),
                    'last_page' => $escapedData['items']->lastPage() ?? 1,
                    'from' => $escapedData['items']->firstItem() ?? 0,
                    'to' => $escapedData['items']->lastItem() ?? 0,
                    'total' => $escapedData['items']->total() ?? 0
                ]
            ];

            \Log::info('📤 API Response Summary:', [
                'items_count' => count($responseData['data']['items']->items() ?? []),
                'isSearchMode' => $responseData['data']['isSearchMode'],
                'total' => $responseData['data']['total']
            ]);

            return response()->json($responseData);
        } catch (\Illuminate\Validation\ValidationException $e) {
            \Log::error('API Validation Error: ' . json_encode($e->errors()));

            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            \Log::error('API Folder Index Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error loading folders: ' . $this->escapeOutput($e->getMessage())
            ], 500);
        }
    }

    /**
     * Escape API output data để tránh XSS
     */
    private function escapeApiOutputData(array $data): array
    {
        // Escape items data
        if (isset($data['items']) && method_exists($data['items'], 'getCollection')) {
            $collection = $data['items']->getCollection()->map(function ($item) {
                if (is_array($item)) {
                    return $this->escapeItemData($item);
                }
                return $item;
            });
            $data['items']->setCollection($collection);
        }

        // Escape breadcrumbs
        if (isset($data['breadcrumbs']) && is_array($data['breadcrumbs'])) {
            foreach ($data['breadcrumbs'] as &$breadcrumb) {
                if (isset($breadcrumb['name'])) {
                    $breadcrumb['name'] = $this->escapeOutput($breadcrumb['name']);
                }
            }
        }

        // Escape current folder
        if (isset($data['currentFolder']) && $data['currentFolder'] !== null) {
            if (isset($data['currentFolder']->name)) {
                $data['currentFolder']->name = $this->escapeOutput($data['currentFolder']->name);
            }
        }

        return $data;
    }

    /**
     * Escape individual item data
     */
    private function escapeItemData(array $item): array
    {
        $escapeFields = ['name', 'type_name', 'file_name', 'description', 'folder_path'];

        foreach ($escapeFields as $field) {
            if (isset($item[$field])) {
                $item[$field] = $this->escapeOutput($item[$field]);
            }
        }

        return $item;
    }

    public function getFolder()
    {
        try {
            if (!Auth::check()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized'
                ], 401);
            }

            $folders = Folder::where('user_id', Auth::id())->get();

            // Escape output
            $folders->transform(function ($folder) {
                $folder->name = $this->escapeOutput($folder->name);
                return $folder;
            });

            return response()->json([
                'success' => true,
                'data' => $folders
            ]);
        } catch (\Exception $e) {
            \Log::error('API Get Folder Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error loading folders'
            ], 500);
        }
    }

    /**
     * Lấy chi tiết folder (API) - ĐÃ BẢO MẬT
     */
    public function show($folder): JsonResponse
    {
        try {
            // Validate folder ID
            if (!is_numeric($folder) || $folder <= 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'ID thư mục không hợp lệ'
                ], 400);
            }

            $folderData = $this->folderService->getFolderForEdit($folder);

            // Escape output
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

            return response()->json([
                'success' => true,
                'data' => $folderData
            ]);
        } catch (\Exception $e) {
            \Log::error('API Folder Show Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error loading folder: ' . $this->escapeOutput($e->getMessage())
            ], 500);
        }
    }

    /**
     * Tạo folder mới (API) - ĐÃ BẢO MẬT
     */
    public function store(StoreFolderRequest $request): JsonResponse
    {
        try {
            $folder = $this->folderService->createFolder($request->validated());

            return response()->json([
                'success' => true,
                'message' => 'Thư mục "' . $this->escapeOutput($folder->name) . '" đã được tạo thành công!',
                'data' => [
                    'folder_id' => $folder->folder_id,
                    'name' => $this->escapeOutput($folder->name),
                    'parent_folder_id' => $folder->parent_folder_id
                ]
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
                'message' => 'Error creating folder: ' . $this->escapeOutput($e->getMessage())
            ], 500);
        }
    }

    /**
     * Cập nhật folder (API)
     */
    public function update(UpdateFolderRequest $request, $folder): JsonResponse
    {
        try {
            // Validate folder ID
            if (!is_numeric($folder) || $folder <= 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'ID thư mục không hợp lệ'
                ], 400);
            }

            $updatedFolder = $this->folderService->updateFolder($folder, $request->validated());

            return response()->json([
                'success' => true,
                'message' => 'Thư mục "' . $this->escapeOutput($updatedFolder->name) . '" đã được cập nhật thành công!',
                'data' => [
                    'folder_id' => $updatedFolder->folder_id,
                    'name' => $this->escapeOutput($updatedFolder->name),
                    // ✅ ĐÃ SỬA: BỎ status
                    'parent_folder_id' => $updatedFolder->parent_folder_id
                ]
            ]);
        } catch (\Exception $e) {
            \Log::error('API Folder Update Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error updating folder: ' . $this->escapeOutput($e->getMessage())
            ], 500);
        }
    }

    /**
     * Xóa folder (API) - ĐÃ BẢO MẬT
     */
    public function destroy(Request $request, $folder): JsonResponse
    {
        try {
            Log::info('API Delete Folder Request:', [
                'folder_id' => $folder,
                'user_id' => auth()->id(),
                'time' => now()
            ]);

            // Validate folder ID
            if (!is_numeric($folder) || $folder <= 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'ID thư mục không hợp lệ'
                ], 400);
            }

            $folderName = $this->folderService->deleteFolder($folder);

            Log::info('API Delete Folder Success:', ['folder_name' => $folderName]);

            return response()->json([
                'success' => true,
                'message' => 'Thư mục "' . $this->escapeOutput($folderName) . '" đã được xóa thành công!'
            ]);
        } catch (\Exception $e) {
            Log::error('API Delete Folder Error:', [
                'error' => $e->getMessage(),
                'folder_id' => $folder
            ]);

            $statusCode = $e->getMessage() === 'Thư mục không tồn tại' ? 404 : 500;

            return response()->json([
                'success' => false,
                'message' => $this->escapeOutput($e->getMessage())
            ], $statusCode);
        }
    }

    /**
     * API: Xóa document - ĐÃ BẢO MẬT
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

            // Validate document ID
            if (!is_numeric($id) || $id <= 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'ID tài liệu không hợp lệ'
                ], 400);
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
                'message' => 'Xóa tài liệu "' . $this->escapeOutput($documentName) . '" thành công!'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('=== API DELETE DOCUMENT ERROR ===');
            Log::error('Error message: ' . $e->getMessage());
            Log::error('Error trace: ' . $e->getTraceAsString());

            return response()->json([
                'success' => false,
                'message' => 'Lỗi hệ thống khi xóa tài liệu: ' . $this->escapeOutput($e->getMessage())
            ], 500);
        }
    }

    /**
     * Tìm kiếm folders (API) - ĐÃ BẢO MẬT
     */
    public function search(Request $request): JsonResponse
    {
        try {
            $validatedData = $this->validateApiParams($request->all());

            $result = $this->folderService->getFoldersWithFilters($validatedData);
            $escapedResult = $this->escapeApiOutputData($result);

            return response()->json([
                'success' => true,
                'data' => $escapedResult
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
                'message' => 'Error searching folders: ' . $this->escapeOutput($e->getMessage())
            ], 500);
        }
    }


    /**
     * Chia sẻ folder với nhiều user
     */
    public function shareFolder(Request $request, $folderId): JsonResponse
    {
        try {
            $user = Auth::user();
            $folder = Folder::findOrFail($folderId);

            // Kiểm tra quyền sở hữu
            if ($folder->user_id !== $user->user_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Bạn không có quyền chia sẻ folder này'
                ], 403);
            }

            $validated = $request->validate([
                'emails' => 'required|array|min:1',
                'emails.*' => 'required|email|exists:users,email',
                'permission' => 'required|in:view,edit'
            ]);

            $sharedUsers = [];
            $alreadyShared = [];

            foreach ($validated['emails'] as $email) {
                $sharedUser = User::where('email', $email)->first();

                // Không cho phép chia sẻ cho chính mình
                if ($sharedUser->user_id === $user->user_id) {
                    continue;
                }

                // Kiểm tra đã chia sẻ chưa
                $existingShare = FolderShare::where('folder_id', $folderId)
                    ->where('shared_with_id', $sharedUser->user_id)
                    ->first();

                if ($existingShare) {
                    $alreadyShared[] = $email;
                    continue;
                }

                // Tạo chia sẻ mới
                $share = FolderShare::create([
                    'folder_id' => $folderId,
                    'owner_id' => $user->user_id,
                    'shared_with_id' => $sharedUser->user_id,
                    'permission' => $validated['permission']
                ]);

                $sharedUsers[] = [
                    'user_id' => $sharedUser->user_id,
                    'name' => $sharedUser->name,
                    'email' => $sharedUser->email,
                    'permission' => $validated['permission']
                ];
            }

            $message = 'Chia sẻ folder thành công';
            if (!empty($alreadyShared)) {
                $message .= '. Một số email đã được chia sẻ trước đó: ' . implode(', ', $alreadyShared);
            }

            return response()->json([
                'success' => true,
                'message' => $message,
                'data' => [
                    'shared_users' => $sharedUsers,
                    'already_shared' => $alreadyShared
                ]
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Dữ liệu không hợp lệ',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Share folder error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi chia sẻ folder: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Hủy chia sẻ folder
     */
    public function unshareFolder(Request $request, $folderId): JsonResponse
    {
        try {
            $user = Auth::user();
            $folder = Folder::findOrFail($folderId);

            // Kiểm tra quyền sở hữu
            if ($folder->user_id !== $user->user_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Bạn không có quyền hủy chia sẻ folder này'
                ], 403);
            }

            $validated = $request->validate([
                'user_ids' => 'required|array|min:1',
                'user_ids.*' => 'required|integer|exists:users,user_id'
            ]);

            $deletedCount = FolderShare::where('folder_id', $folderId)
                ->whereIn('shared_with_id', $validated['user_ids'])
                ->delete();

            return response()->json([
                'success' => true,
                'message' => 'Đã hủy chia sẻ folder với ' . $deletedCount . ' người dùng'
            ]);
        } catch (\Exception $e) {
            Log::error('Unshare folder error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi hủy chia sẻ folder'
            ], 500);
        }
    }

    /**
     * Lấy danh sách người được chia sẻ folder
     */
    public function getSharedUsers($folderId): JsonResponse
    {
        try {
            $user = Auth::user();
            $folder = Folder::findOrFail($folderId);

            // Kiểm tra quyền truy cập
            if (!$folder->canAccess($user)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Bạn không có quyền truy cập folder này'
                ], 403);
            }

            $sharedUsers = FolderShare::where('folder_id', $folderId)
                ->with('sharedWith')
                ->get()
                ->map(function ($share) {
                    return [
                        'share_id' => $share->share_id,
                        'user_id' => $share->sharedWith->user_id,
                        'name' => $share->sharedWith->name,
                        'email' => $share->sharedWith->email,
                        'permission' => $share->permission,
                        'shared_at' => $share->created_at
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => $sharedUsers
            ]);
        } catch (\Exception $e) {
            Log::error('Get shared users error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi tải danh sách chia sẻ'
            ], 500);
        }
    }

    /**
     * Tìm kiếm user theo email để chia sẻ
     */
    public function searchUsers(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'email' => 'required|string|min:2'
            ]);

            $users = User::where('email', 'like', '%' . $validated['email'] . '%')
                ->where('user_id', '!=', Auth::id()) // Loại trừ chính mình
                ->limit(10)
                ->get(['user_id', 'name', 'email']);

            return response()->json([
                'success' => true,
                'data' => $users
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi tìm kiếm user'
            ], 500);
        }
    }
}
