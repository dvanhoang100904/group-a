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
            $info = $this->downloadService->getFolderInfo($folderId);

            return response()->json([
                'success' => true,
                'data' => $info
            ]);
        } catch (\Exception $e) {
            Log::error('Get folder download info error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi lấy thông tin folder'
            ], 500);
        }
    }

    /**
     * API: Download folder (trả về ZIP)
     */
    public function download($folderId)
    {
        try {
            return $this->downloadService->streamDownload($folderId);
        } catch (\Exception $e) {
            Log::error('Download folder error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], $e->getCode() ?: 500);
        } finally {
            // Cleanup temp files
            $this->downloadService->cleanup();
        }
    }

    /**
     * API: Download folder (tạo ZIP trước, sau đó download)
     */
    public function prepareDownload($folderId): JsonResponse
    {
        try {
            $info = $this->downloadService->getFolderInfo($folderId);

            if (!$info['can_download']) {
                return response()->json([
                    'success' => false,
                    'message' => 'Bạn không có quyền download folder này hoặc folder quá lớn'
                ], 403);
            }

            // Tạo job xử lý background cho folder lớn
            if ($info['total_size'] > 50 * 1024 * 1024) { // > 50MB
                // Queue job để xử lý background
                // dispatch(new PrepareFolderDownload($folderId, Auth::id()));

                return response()->json([
                    'success' => true,
                    'data' => [
                        'requires_preparation' => true,
                        'estimated_time' => ceil($info['total_size'] / (5 * 1024 * 1024)), // Giây
                        'message' => 'Folder lớn, đang chuẩn bị download...'
                    ]
                ]);
            }

            // Folder nhỏ, có thể download ngay
            $zipPath = $this->downloadService->createZipForFolder($folderId);
            $zipName = basename($zipPath);

            return response()->json([
                'success' => true,
                'data' => [
                    'requires_preparation' => false,
                    'download_url' => route('api.folders.download.direct', ['folder' => $folderId]),
                    'zip_name' => $zipName
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Prepare download error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi chuẩn bị download'
            ], 500);
        }
    }
}
