<?php

namespace App\Listeners;

use Illuminate\Auth\Events\Login;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Log;

class LogSuccessfulLogin
{
    private static $logged = false;

    public function handle(Login $event)
    {
        if (self::$logged) {
            Log::warning('LOGIN EVENT BỊ GỌI LẦN 2 – ĐÃ BỎ QUA', [
                'user_id' => $event->user->user_id ?? null,
                'time' => now(),
            ]);
            return;
        }

        $user = $event->user;

        $fullName = $user->full_name ?? $user->name ?? 'Unknown';
        $username = $user->username ?? $user->email ?? 'unknown';

        \App\Models\AccessLog::create([
            'user_id'     => $user->user_id ?? $user->id,
            'action'      => 'login',
            'target_id'   => null,
            'target_type' => 'system',
            'ip_address'  => Request::ip() ?? '127.0.0.1 ', //dữ liệu ip ảo
            'user_agent'  => Request::userAgent() ?? 'Unknown',
            'url'         => Request::fullUrl(),
            'description' => "Người dùng {$fullName} ({$username}) đã đăng nhập ",
        ]);

        Log::info('LOGIN LOGGED SUCCESSFULLY', [
            'user_id' => $user->user_id,
            'name' => $fullName,
            'username' => $username,
        ]);

        self::$logged = true;
    }
}