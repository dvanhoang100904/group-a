<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Folders\FolderDownloadService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class FolderDownloadController extends Controller
{
    protected $downloadService;

    public function __construct(FolderDownloadService $downloadService)
    {
        $this->downloadService = $downloadService;
    }

    /**
     * API: Lấy thông tin folder để download
     */
    public function getInfo($folderId): JsonResponse
    {
        try {
            Log::info("Get folder download info", [
                'folder_id' => $folderId,
                'user_id' => Auth::id()
            ]);

            $info = $this->downloadService->getFolderInfo($folderId);

            return response()->json([
                'success' => true,
                'data' => $info
            ]);
        } catch (\Exception $e) {
            Log::error('Get folder download info error: ' . $e->getMessage(), [
                'folder_id' => $folderId,
                'user_id' => Auth::id(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi lấy thông tin folder: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * API: Download folder (trả về ZIP)
     * ✅ FIX: Không gọi cleanup() trong finally
     */
    public function download($folderId)
    {
        try {
            Log::info("Download folder request", [
                'folder_id' => $folderId,
                'user_id' => Auth::id()
            ]);

            // Tạo ZIP và trả về response download
            // deleteFileAfterSend(true) sẽ tự động xóa file sau khi gửi xong
            return $this->downloadService->streamDownload($folderId);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            Log::error('Folder not found', [
                'folder_id' => $folderId,
                'user_id' => Auth::id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Folder không tồn tại hoặc bạn không có quyền truy cập'
            ], 404);
        } catch (\Exception $e) {
            Log::error('Download folder error: ' . $e->getMessage(), [
                'folder_id' => $folderId,
                'user_id' => Auth::id(),
                'trace' => $e->getTraceAsString()
            ]);

            // ✅ Chỉ cleanup khi có lỗi
            $this->downloadService->cleanup();

            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], $e->getCode() ?: 500);
        }
        // ✅ KHÔNG có finally block gọi cleanup()
    }

    /**
     * API: Download folder (tạo ZIP trước, sau đó download)
     */
    public function prepareDownload($folderId): JsonResponse
    {
        try {
            Log::info("Prepare folder download", [
                'folder_id' => $folderId,
                'user_id' => Auth::id()
            ]);

            $info = $this->downloadService->getFolderInfo($folderId);

            if (!$info['can_download']) {
                return response()->json([
                    'success' => false,
                    'message' => 'Bạn không có quyền download folder này hoặc folder quá lớn'
                ], 403);
            }

            // Folder lớn (> 50MB), thông báo cần thời gian
            if ($info['total_size'] > 50 * 1024 * 1024) {
                return response()->json([
                    'success' => true,
                    'data' => [
                        'requires_preparation' => true,
                        'estimated_time' => ceil($info['total_size'] / (5 * 1024 * 1024)), // Giây
                        'message' => 'Folder lớn, đang chuẩn bị download...',
                        'folder_info' => $info
                    ]
                ]);
            }

            // Folder nhỏ, có thể download ngay
            return response()->json([
                'success' => true,
                'data' => [
                    'requires_preparation' => false,
                    'download_url' => route('api.folders.download.direct', ['folder' => $folderId]),
                    'folder_info' => $info
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Prepare download error: ' . $e->getMessage(), [
                'folder_id' => $folderId,
                'user_id' => Auth::id(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi chuẩn bị download: ' . $e->getMessage()
            ], 500);
        }
    }
}
