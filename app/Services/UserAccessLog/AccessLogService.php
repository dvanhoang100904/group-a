<?php

namespace App\Services\UserAccessLog;

use App\Models\AccessLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Log;

class AccessLogService
{
    /**
     * Ghi log hành động
     * 
     * @param string $action Loại hành động (login, logout, document_view, etc.)
     * @param int|null $targetId ID của đối tượng liên quan (document_id, user_id, etc.)
     * @param string|null $targetType Loại đối tượng ('document', 'system', 'user')
     * @param string|null $description Mô tả thêm
     * @return AccessLog|null
     */
    public static function log(
        string $action,
        ?int $targetId = null,
        ?string $targetType = null,
        ?string $description = null
    ): ?AccessLog {
        try {
            if ($targetId && !$targetType) {
                $targetType = self::guessTargetType($action);
            }

            return AccessLog::create([
                'user_id' => Auth::id(),
                'action' => $action,
                'target_id' => $targetId,
                'target_type' => $targetType,
                'ip_address' => Request::ip(),
                'user_agent' => Request::userAgent(),
                'url' => Request::fullUrl(),
                'description' => $description,
            ]);
        } catch (\Exception $e) {
            Log::error('AccessLogService Error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Tự động đoán target_type dựa trên action
     */
    private static function guessTargetType(string $action): string
    {
        if (str_starts_with($action, 'document_')) {
            return 'document';
        }

        if (in_array($action, ['login', 'logout', 'register'])) {
            return 'system';
        }

        return 'other';
    }

    /**
     * Ghi log đăng nhập
     */
    public static function logLogin(): void
    {
        self::log('login', null, 'system', 'Người dùng đăng nhập vào hệ thống');
    }

    /**
     * Ghi log đăng xuất
     */
    public static function logLogout(): void
    {
        self::log('logout', null, 'system', 'Người dùng đăng xuất khỏi hệ thống');
    }

    /**
     * Ghi log thao tác tài liệu
     */
    public static function logDocumentAction(string $action, int $documentId, ?string $documentTitle = null): void
    {
        $description = $documentTitle ? ucfirst(str_replace('_', ' ', $action)) . ": {$documentTitle}" : null;
        self::log($action, $documentId, 'document', $description);
    }

    public static function logDocumentView(int $documentId, ?string $documentTitle = null): void
    {
        self::logDocumentAction('document_view', $documentId, $documentTitle);
    }

    public static function logDocumentUpload(int $documentId, ?string $documentTitle = null): void
    {
        self::logDocumentAction('document_upload', $documentId, $documentTitle);
    }

    public static function logDocumentUpdate(int $documentId, ?string $documentTitle = null): void
    {
        self::logDocumentAction('document_update', $documentId, $documentTitle);
    }

    public static function logDocumentComment(int $documentId, ?string $documentTitle = null): void
    {
        self::logDocumentAction('document_comment', $documentId, $documentTitle);
    }

    public static function logDocumentReport(int $documentId, ?string $reason = null): void
    {
        self::log('document_report', $documentId, 'document', $reason ? "Báo cáo tài liệu: {$reason}" : null);
    }

    public static function logDocumentDownload(int $documentId, ?string $documentTitle = null): void
    {
        self::logDocumentAction('document_download', $documentId, $documentTitle);
    }

    public static function logDocumentDelete(int $documentId, ?string $documentTitle = null): void
    {
        self::logDocumentAction('document_delete', $documentId, $documentTitle);
    }

    /**
     * Lấy logs của một user
     *
     * @param int $userId
     * @param int $limit
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function getUserLogs(int $userId, int $limit = 50)
    {
        return AccessLog::with(['user'])
            ->where('user_id', $userId)
            ->latest('created_at')
            ->limit($limit)
            ->get();
    }

    /**
     * Lấy logs của một document
     *
     * @param int $documentId
     * @param int $limit
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function getDocumentLogs(int $documentId, int $limit = 50)
    {
        return AccessLog::with(['user'])
            ->where('target_id', $documentId)
            ->where('target_type', 'document')
            ->latest('created_at')
            ->limit($limit)
            ->get();
    }

    /**
     * Thống kê số lượng action
     *
     * @param string|null $startDate
     * @param string|null $endDate
     * @return \Illuminate\Support\Collection
     */
    public static function getActionStats(?string $startDate = null, ?string $endDate = null)
    {
        $query = AccessLog::selectRaw('action, COUNT(*) as count')->groupBy('action');

        if ($startDate && $endDate) {
            $query->whereBetween('created_at', [$startDate, $endDate]);
        }

        return $query->get();
    }
}
