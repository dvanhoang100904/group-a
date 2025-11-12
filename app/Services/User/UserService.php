<?php

namespace App\Services\User;

use Illuminate\Support\Facades\Auth;

class UserService
{
    /**
     * Xu ly dang nhap
     */
    public function login(array $credentials): bool
    {
        if (!Auth::attempt($credentials)) {
            return false;
        }

        Auth::user();
        return true;
    }

    /**
     * Xu ly dang xuat
     */
    public function logout(): void
    {
        // Dang xuat nguoi dung
        Auth::logout();
    }
}
