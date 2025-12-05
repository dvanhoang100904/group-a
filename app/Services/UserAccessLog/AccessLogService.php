<?php

namespace App\Services\UserAccessLog;

use App\Models\AccessLog;
use Illuminate\Support\Facades\Request;

class AccessLogService
{
    public static function logLogin($user)
    {
        AccessLog::create([
            'user_id' => $user->user_id,
            'action' => 'login',
            'target_id' => null,
            'target_type' => 'system',
            'ip_address' => Request::ip() ?? '127.0.0.1',
            'user_agent' => Request::userAgent() ?? 'Unknown',
            'url' => Request::fullUrl() ?? url()->current(),
            'description' => "Người dùng {$user->full_name} ({$user->username}) đăng nhập vào hệ thống",
        ]);
    }

    public static function logLogout($user)
    {
        AccessLog::create([
            'user_id' => $user->user_id,
            'action' => 'logout',
            'target_id' => null,
            'target_type' => 'system',
            'ip_address' => Request::ip() ?? '127.0.0.1',
            'user_agent' => Request::userAgent() ?? 'Unknown',
            'url' => Request::fullUrl() ?? url()->current(),
            'description' => "Người dùng {$user->full_name} ({$user->username}) đăng xuất khỏi hệ thống",
        ]);
    }

    public static function logDocumentView($documentId, $title)
    {
        AccessLog::create([
            'user_id' => auth()->id() ?? null,
            'action' => 'document_view',
            'target_id' => $documentId,
            'target_type' => 'document',
            'ip_address' => Request::ip() ?? '127.0.0.1',
            'user_agent' => Request::userAgent() ?? 'Unknown',
            'url' => Request::fullUrl() ?? url()->current(),
            'description' => "Xem tài liệu: {$title}",
        ]);
    }
}
