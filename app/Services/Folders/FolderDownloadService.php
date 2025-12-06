<?php

namespace App\Services\Folders;

use App\Models\Folder;
use App\Models\Document;
use App\Models\DocumentVersion;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use ZipArchive;

class FolderDownloadService
{
    private $zip;
    private $tempPath;

    public function __construct()
    {
        $this->zip = new ZipArchive();
        $this->tempPath = storage_path('app/temp/' . uniqid('folder_', true) . '.zip');
    }

    /**
     * Tạo ZIP cho folder
     */
    public function createZipForFolder($folderId)
    {
        $userId = Auth::id();
        $folder = Folder::accessibleBy($userId)->findOrFail($folderId);

        // Tạo thư mục temp nếu chưa tồn tại
        if (!file_exists(dirname($this->tempPath))) {
            mkdir(dirname($this->tempPath), 0755, true);
        }

        if ($this->zip->open($this->tempPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new \Exception('Không thể tạo file ZIP');
        }

        // Thêm tất cả file và folder vào ZIP
        $this->addFolderToZip($folder, '', $userId);

        $this->zip->close();

        return $this->tempPath;
    }

    /**
     * Đệ quy thêm folder và file vào ZIP
     */
    private function addFolderToZip(Folder $folder, $basePath, $userId)
    {
        // Thêm folder hiện tại
        $folderPath = $basePath . $this->sanitizeFileName($folder->name) . '/';

        // Thêm các file trong folder
        $documents = Document::where('folder_id', $folder->folder_id)
            ->where(function ($query) use ($userId) {
                $query->where('user_id', $userId)
                    ->orWhereHas('folder', function ($q) use ($userId) {
                        $q->accessibleBy($userId);
                    });
            })
            ->get();

        foreach ($documents as $document) {
            $latestVersion = DocumentVersion::where('document_id', $document->document_id)
                ->orderByDesc('version_number')
                ->first();

            if ($latestVersion && $latestVersion->file_name) {
                $filePath = base_path('app/Public_UploadFile/' . $latestVersion->file_name);
                if (file_exists($filePath)) {
                    $safeFileName = $this->sanitizeFileName($document->title ?: $latestVersion->file_name);
                    $extension = pathinfo($latestVersion->file_name, PATHINFO_EXTENSION);
                    $zipPath = $folderPath . $safeFileName . '.' . $extension;

                    $this->zip->addFile($filePath, $zipPath);
                }
            }
        }

        // Đệ quy thêm subfolders
        $subfolders = Folder::accessibleBy($userId)
            ->where('parent_folder_id', $folder->folder_id)
            ->get();

        foreach ($subfolders as $subfolder) {
            $this->addFolderToZip($subfolder, $folderPath, $userId);
        }
    }

    /**
     * Sanitize tên file để an toàn
     */
    private function sanitizeFileName($filename)
    {
        $filename = preg_replace('/[<>:"\/\\\|?*]/', '_', $filename);
        $filename = trim($filename);
        return $filename ?: 'file';
    }

    /**
     * Lấy thông tin folder để hiển thị
     */
    public function getFolderInfo($folderId)
    {
        $userId = Auth::id();
        $folder = Folder::accessibleBy($userId)->findOrFail($folderId);

        $totalSize = 0;
        $fileCount = 0;
        $folderCount = 0;

        // Đếm số lượng và kích thước
        $this->calculateFolderStats($folder, $userId, $totalSize, $fileCount, $folderCount);

        return [
            'name' => $folder->name,
            'total_size' => $totalSize,
            'file_count' => $fileCount,
            'folder_count' => $folderCount,
            'can_download' => $this->canDownloadFolder($folder, $userId)
        ];
    }

    /**
     * Tính toán thống kê folder
     */
    private function calculateFolderStats(Folder $folder, $userId, &$totalSize, &$fileCount, &$folderCount)
    {
        $folderCount++;

        // Tính file trong folder hiện tại
        $documents = Document::where('folder_id', $folder->folder_id)
            ->where(function ($query) use ($userId) {
                $query->where('user_id', $userId)
                    ->orWhereHas('folder', function ($q) use ($userId) {
                        $q->accessibleBy($userId);
                    });
            })
            ->get();

        foreach ($documents as $document) {
            $latestVersion = DocumentVersion::where('document_id', $document->document_id)
                ->orderByDesc('version_number')
                ->first();

            if ($latestVersion && $latestVersion->file_name) {
                $filePath = base_path('app/Public_UploadFile/' . $latestVersion->file_name);
                if (file_exists($filePath)) {
                    $totalSize += filesize($filePath);
                    $fileCount++;
                }
            }
        }

        // Đệ quy tính subfolders
        $subfolders = Folder::accessibleBy($userId)
            ->where('parent_folder_id', $folder->folder_id)
            ->get();

        foreach ($subfolders as $subfolder) {
            $this->calculateFolderStats($subfolder, $userId, $totalSize, $fileCount, $folderCount);
        }
    }

    /**
     * Kiểm tra quyền download folder
     */
    private function canDownloadFolder(Folder $folder, $userId): bool
    {
        // Kiểm tra quyền view folder
        if (!$folder->canUserView($userId)) {
            return false;
        }

        // Kiểm tra giới hạn kích thước (tùy chọn)
        $maxSize = config('folder.max_download_size', 500 * 1024 * 1024); // 500MB mặc định

        $totalSize = 0;
        $fileCount = 0;
        $folderCount = 0;
        $this->calculateFolderStats($folder, $userId, $totalSize, $fileCount, $folderCount);

        if ($totalSize > $maxSize) {
            return false;
        }

        return true;
    }

    /**
     * Xóa file temp sau khi download
     */
    public function cleanup()
    {
        if (file_exists($this->tempPath)) {
            unlink($this->tempPath);
        }
    }

    /**
     * Stream download để xử lý folder lớn
     */
    public function streamDownload($folderId)
    {
        $userId = Auth::id();
        $folder = Folder::accessibleBy($userId)->findOrFail($folderId);

        if (!$this->canDownloadFolder($folder, $userId)) {
            abort(403, 'Bạn không có quyền download folder này hoặc folder quá lớn');
        }

        $zipName = $this->sanitizeFileName($folder->name) . '.zip';

        return response()->streamDownload(function () use ($folderId, $userId) {
            $this->streamZipCreation($folderId, $userId);
        }, $zipName, [
            'Content-Type' => 'application/zip',
            'Content-Disposition' => 'attachment; filename="' . $zipName . '"'
        ]);
    }

    /**
     * Stream ZIP creation để xử lý folder lớn
     */
    private function streamZipCreation($folderId, $userId)
    {
        // Implementation for streaming large folders
        // (Có thể sử dụng ZipStream-PHP library)
    }
}
