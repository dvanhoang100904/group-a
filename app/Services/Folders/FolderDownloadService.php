<?php

namespace App\Services\Folders;

use App\Models\Folder;
use App\Models\Document;
use App\Models\DocumentVersion;
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
    }

    /**
     * Tạo ZIP cho folder và tất cả nội dung bên trong
     */
    public function createZipForFolder($folderId)
    {
        $userId = Auth::id();

        if (!$userId) {
            throw new \Exception('User not authenticated');
        }

        $folder = Folder::find($folderId);

        if (!$folder) {
            throw new \Exception('Folder không tồn tại');
        }

        // Kiểm tra quyền vào folder gốc
        $hasAccess = $this->checkFolderAccess($folder, $userId);
        if (!$hasAccess) {
            throw new \Exception('Bạn không có quyền truy cập folder này');
        }

        Log::info("=== START CREATING ZIP ===", [
            'folder_id' => $folderId,
            'folder_name' => $folder->name,
            'user_id' => $userId
        ]);

        // Tạo file ZIP tạm
        $this->tempPath = storage_path('app/temp/folder_' . uniqid('', true) . '.zip');

        // Tạo thư mục temp nếu chưa có
        $tempDir = dirname($this->tempPath);
        if (!file_exists($tempDir)) {
            mkdir($tempDir, 0777, true);
        }

        // Xóa file cũ nếu tồn tại
        if (file_exists($this->tempPath)) {
            unlink($this->tempPath);
        }

        // Mở file ZIP
        if ($this->zip->open($this->tempPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new \Exception("Không thể tạo file ZIP");
        }

        // Thêm tất cả nội dung vào ZIP
        $filesAdded = $this->addFolderContentToZip($folder, '');

        Log::info("Total files added to ZIP", ['count' => $filesAdded]);

        // Nếu không có file nào, thêm README
        if ($filesAdded === 0) {
            $this->zip->addFromString(
                'README.txt',
                "Folder '{$folder->name}' không có file nào hoặc chỉ chứa folder rỗng.\n\n" .
                    "Downloaded: " . date('Y-m-d H:i:s')
            );
        }

        // Đóng ZIP
        $this->zip->close();

        // Kiểm tra file
        if (!file_exists($this->tempPath)) {
            throw new \Exception('File ZIP không được tạo');
        }

        $fileSize = filesize($this->tempPath);
        Log::info("=== ZIP CREATED SUCCESSFULLY ===", [
            'path' => $this->tempPath,
            'size' => $fileSize,
            'files_added' => $filesAdded
        ]);

        return $this->tempPath;
    }

    /**
     * Kiểm tra quyền truy cập folder
     */
    private function checkFolderAccess(Folder $folder, $userId)
    {
        // Chủ sở hữu folder
        if ($folder->user_id === $userId) {
            return true;
        }

        // Kiểm tra share trực tiếp
        if ($folder->shares()->where('shared_with_id', $userId)->exists()) {
            return true;
        }

        // Kiểm tra share qua parent
        $parent = $folder;
        while ($parent->parent_folder_id) {
            $parent = Folder::find($parent->parent_folder_id);
            if ($parent && $parent->shares()->where('shared_with_id', $userId)->exists()) {
                return true;
            }
        }

        return false;
    }

    /**
     * Đệ quy thêm tất cả nội dung folder vào ZIP
     */
    private function addFolderContentToZip(Folder $folder, $currentZipPath)
    {
        $filesAdded = 0;

        // Tạo folder trong ZIP
        $folderNameInZip = $this->sanitizeFileName($folder->name);
        if ($currentZipPath !== '') {
            $folderNameInZip = $currentZipPath . $folderNameInZip . '/';
            $this->zip->addEmptyDir($folderNameInZip);
        } else {
            $folderNameInZip = $folderNameInZip . '/';
        }

        Log::info("Processing folder", [
            'folder_id' => $folder->folder_id,
            'folder_name' => $folder->name,
            'zip_path' => $folderNameInZip
        ]);

        // 1. THÊM TẤT CẢ FILE TRONG FOLDER HIỆN TẠI
        $documents = Document::where('folder_id', $folder->folder_id)->get();

        foreach ($documents as $document) {
            // Lấy version mới nhất
            $latestVersion = DocumentVersion::where('document_id', $document->document_id)
                ->orderByDesc('version_number')
                ->first();

            if ($latestVersion) {
                // Thử các trường có thể chứa tên file
                $fileName = $this->getFileNameFromVersion($latestVersion);

                if ($fileName) {
                    // Thử các đường dẫn có thể
                    $filePath = $this->findFilePath($fileName);

                    if ($filePath && file_exists($filePath) && is_readable($filePath)) {
                        // Tạo tên file an toàn cho ZIP
                        $safeFileName = $this->sanitizeFileName(
                            $document->title ?: pathinfo($fileName, PATHINFO_FILENAME)
                        );
                        $extension = pathinfo($fileName, PATHINFO_EXTENSION);

                        $zipFilePath = $folderNameInZip . $safeFileName;
                        if ($extension) {
                            $zipFilePath .= '.' . $extension;
                        }

                        // Tránh trùng tên
                        $counter = 1;
                        $originalZipPath = $zipFilePath;
                        while ($this->zip->locateName($zipFilePath) !== false) {
                            $zipFilePath = $folderNameInZip . $safeFileName . '_' . $counter;
                            if ($extension) {
                                $zipFilePath .= '.' . $extension;
                            }
                            $counter++;
                        }

                        // Thêm file vào ZIP
                        if ($this->zip->addFile($filePath, $zipFilePath)) {
                            $filesAdded++;
                            Log::info("✓ Added file", [
                                'document_id' => $document->document_id,
                                'file' => $zipFilePath
                            ]);
                        }
                    }
                }
            }
        }

        // 2. ĐỆ QUY VÀO TẤT CẢ SUBFOLDERS
        $subfolders = Folder::where('parent_folder_id', $folder->folder_id)->get();

        foreach ($subfolders as $subfolder) {
            Log::info("Recursing into subfolder", [
                'subfolder_id' => $subfolder->folder_id,
                'subfolder_name' => $subfolder->name
            ]);

            $subFilesAdded = $this->addFolderContentToZip($subfolder, $folderNameInZip);
            $filesAdded += $subFilesAdded;

            Log::info("Finished subfolder", [
                'subfolder_name' => $subfolder->name,
                'files_added' => $subFilesAdded
            ]);
        }

        return $filesAdded;
    }

    /**
     * Lấy tên file từ DocumentVersion (thử nhiều trường)
     */
    private function getFileNameFromVersion(DocumentVersion $version)
    {
        // Thử các trường có thể
        $possibleFields = [
            'file_name',
            'file_path',
            'original_file_name',
            'file',
            'filename',
            'path'
        ];

        foreach ($possibleFields as $field) {
            if (isset($version->$field) && !empty($version->$field)) {
                return $version->$field;
            }
        }

        return null;
    }

    /**
     * Tìm đường dẫn file vật lý
     */
    private function findFilePath($fileName)
    {
        // Các thư mục có thể chứa file
        $possibleDirs = [
            base_path('app/Public_UploadFile/'),
            base_path('public/uploads/'),
            base_path('storage/app/uploads/'),
            base_path('storage/app/public/'),
            public_path('uploads/'),
            storage_path('app/uploads/'),
            storage_path('app/public/'),
            // Thêm các đường dẫn phổ biến khác
            base_path('uploads/'),
            base_path('public/storage/'),
        ];

        // Các pattern đường dẫn
        $possiblePaths = [];

        foreach ($possibleDirs as $dir) {
            $possiblePaths[] = $dir . $fileName;
            $possiblePaths[] = $dir . basename($fileName); // Chỉ lấy tên file
            $possiblePaths[] = $dir . 'documents/' . $fileName;
            $possiblePaths[] = $dir . 'documents/' . basename($fileName);
            $possiblePaths[] = $dir . date('Y/m/d') . '/' . $fileName;
            $possiblePaths[] = $dir . date('Y/m') . '/' . $fileName;
        }

        // Kiểm tra từng đường dẫn
        foreach ($possiblePaths as $path) {
            $normalizedPath = $this->normalizePath($path);
            if (file_exists($normalizedPath) && is_readable($normalizedPath)) {
                return $normalizedPath;
            }
        }

        return null;
    }

    /**
     * Làm sạch tên file cho ZIP
     */
    private function sanitizeFileName($filename)
    {
        if (empty($filename)) {
            return 'file';
        }

        // Xóa ký tự không hợp lệ
        $filename = preg_replace('/[<>:"\/\\\|?*\x00-\x1F]/', '_', $filename);
        $filename = preg_replace('/\s+/', '_', $filename);
        $filename = trim($filename, ' ._-');

        // Giới hạn độ dài
        if (strlen($filename) > 200) {
            $filename = substr($filename, 0, 200);
        }

        return $filename;
    }

    /**
     * Chuẩn hóa đường dẫn cho Windows
     */
    private function normalizePath($path)
    {
        return str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path);
    }

    /**
     * Lấy thông tin folder (dùng cho hiển thị trước khi download)
     */
    public function getFolderInfo($folderId)
    {
        $userId = Auth::id();

        if (!$userId) {
            throw new \Exception('User not authenticated');
        }

        $folder = Folder::find($folderId);

        if (!$folder) {
            throw new \Exception('Folder không tồn tại');
        }

        // Kiểm tra quyền
        $hasAccess = $this->checkFolderAccess($folder, $userId);
        if (!$hasAccess) {
            throw new \Exception('Bạn không có quyền truy cập folder này');
        }

        // Tính thống kê
        $stats = $this->calculateFolderStats($folder);

        return [
            'name' => $folder->name,
            'total_size' => $stats['total_size'],
            'file_count' => $stats['file_count'],
            'folder_count' => $stats['folder_count'],
            'can_download' => true
        ];
    }

    /**
     * Tính thống kê folder
     */
    private function calculateFolderStats(Folder $folder)
    {
        $totalSize = 0;
        $fileCount = 0;
        $folderCount = 1; // Bắt đầu từ folder hiện tại

        // Đếm file trong folder hiện tại
        $documents = Document::where('folder_id', $folder->folder_id)->get();

        foreach ($documents as $document) {
            $latestVersion = DocumentVersion::where('document_id', $document->document_id)
                ->orderByDesc('version_number')
                ->first();

            if ($latestVersion) {
                $fileName = $this->getFileNameFromVersion($latestVersion);
                if ($fileName) {
                    $filePath = $this->findFilePath($fileName);
                    if ($filePath && file_exists($filePath)) {
                        $totalSize += filesize($filePath);
                        $fileCount++;
                    }
                }
            }
        }

        // Đệ quy subfolders
        $subfolders = Folder::where('parent_folder_id', $folder->folder_id)->get();

        foreach ($subfolders as $subfolder) {
            $subStats = $this->calculateFolderStats($subfolder);
            $totalSize += $subStats['total_size'];
            $fileCount += $subStats['file_count'];
            $folderCount += $subStats['folder_count'];
        }

        return [
            'total_size' => $totalSize,
            'file_count' => $fileCount,
            'folder_count' => $folderCount
        ];
    }

    /**
     * Cleanup temp file
     */
    public function cleanup()
    {
        if ($this->tempPath && file_exists($this->tempPath)) {
            unlink($this->tempPath);
            Log::info("Cleaned up temp ZIP: {$this->tempPath}");
        }
    }

    /**
     * Stream download response
     */
    public function streamDownload($folderId)
    {
        $userId = Auth::id();

        if (!$userId) {
            throw new \Exception('User not authenticated');
        }

        $folder = Folder::find($folderId);

        if (!$folder) {
            throw new \Exception('Folder không tồn tại');
        }

        // Kiểm tra quyền
        $hasAccess = $this->checkFolderAccess($folder, $userId);
        if (!$hasAccess) {
            throw new \Exception('Bạn không có quyền download folder này');
        }

        // Kiểm tra kích thước
        $stats = $this->calculateFolderStats($folder);
        $maxSize = config('filesystems.folder_download.max_size', 500 * 1024 * 1024);

        if ($stats['total_size'] > $maxSize) {
            throw new \Exception('Folder quá lớn (' . round($stats['total_size'] / 1024 / 1024, 2) . ' MB). Giới hạn: ' . round($maxSize / 1024 / 1024, 2) . ' MB');
        }

        Log::info("Starting folder download", [
            'folder_id' => $folderId,
            'folder_name' => $folder->name,
            'total_size' => $stats['total_size'],
            'file_count' => $stats['file_count'],
            'folder_count' => $stats['folder_count']
        ]);

        // Tạo ZIP
        $zipPath = $this->createZipForFolder($folderId);
        $zipName = $this->sanitizeFileName($folder->name) . '.zip';

        // Trả về response download
        return response()->download($zipPath, $zipName, [
            'Content-Type' => 'application/zip',
            'Content-Disposition' => 'attachment; filename="' . $zipName . '"',
        ])->deleteFileAfterSend(true);
    }
}
