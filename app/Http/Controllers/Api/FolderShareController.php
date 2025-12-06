<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Folder;
use App\Models\FolderShare;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class FolderShareController extends Controller
{
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
                'emails.*' => [
                    'required',
                    'email',
                    'exists:users,email',
                    function ($attribute, $value, $fail) use ($user) {
                        // Không cho phép chia sẻ với chính mình
                        if (User::where('email', $value)->first()?->user_id === $user->user_id) {
                            $fail('Không thể chia sẻ với chính mình');
                        }
                    }
                ],
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
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi tải danh sách chia sẻ'
            ], 500);
        }
    }

    /**
     * Cập nhật quyền chia sẻ
     */
    public function updateSharePermission(Request $request, $folderId, $shareId): JsonResponse
    {
        try {
            $user = Auth::user();
            $folder = Folder::findOrFail($folderId);

            // Kiểm tra quyền sở hữu
            if ($folder->user_id !== $user->user_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Bạn không có quyền cập nhật chia sẻ folder này'
                ], 403);
            }

            $validated = $request->validate([
                'permission' => 'required|in:view,edit'
            ]);

            $share = FolderShare::where('folder_id', $folderId)
                ->where('share_id', $shareId)
                ->firstOrFail();

            $share->permission = $validated['permission'];
            $share->save();

            return response()->json([
                'success' => true,
                'message' => 'Đã cập nhật quyền chia sẻ',
                'data' => [
                    'share_id' => $share->share_id,
                    'permission' => $share->permission
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi cập nhật quyền chia sẻ'
            ], 500);
        }
    }

    /**
     * Lấy danh sách folder được chia sẻ với user hiện tại
     */
    public function getSharedWithMe(): JsonResponse
    {
        try {
            $userId = Auth::id();

            $sharedFolders = FolderShare::where('shared_with_id', $userId)
                ->with(['folder', 'owner'])
                ->get()
                ->map(function ($share) {
                    return [
                        'folder_id' => $share->folder->folder_id,
                        'folder_name' => htmlspecialchars($share->folder->name, ENT_QUOTES, 'UTF-8'),
                        'owner_name' => htmlspecialchars($share->owner->name, ENT_QUOTES, 'UTF-8'),
                        'owner_email' => $share->owner->email,
                        'permission' => $share->permission,
                        'shared_at' => $share->created_at
                    ];
                });

            return response()->json([
                'success' => true,

                'data' => $sharedFolders
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi tải danh sách folder được chia sẻ'
            ], 500);
        }
    }
}
