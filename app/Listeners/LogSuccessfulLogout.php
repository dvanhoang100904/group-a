<?php

namespace App\Listeners;

use Illuminate\Auth\Events\Logout;
use Illuminate\Support\Facades\Request;

class LogSuccessfulLogout
{
    // Cờ static để chỉ log 1 lần duy nhất trong cùng request
    private static $logged = false;

    public function handle(Logout $event)
    {
        // Nếu đã log rồi trong request này → bỏ qua
        if (self::$logged) {
            return;
        }

        $user = $event->user;

        if (!$user) {
            return;
        }

        // Ghi log
        \App\Models\AccessLog::create([
            'user_id'     => $user->user_id,
            'action'      => 'logout',
            'target_id'   => null,
            'target_type' => 'system',
            'ip_address'  => Request::ip() ?? '127.0.0.1',
            'user_agent'  => Request::userAgent() ?? 'Unknown',
            'url'         => Request::fullUrl(),
            'description' => "Đăng xuất: {$user->full_name} ({$user->email})",
        ]);

        // Đánh dấu đã log
        self::$logged = true;
    }
}