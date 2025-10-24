<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class UserApiController extends Controller
{
    /**
     * Danh sach nguoi dung
     */
    public function index()
    {
        $users = User::select('user_id', 'name')->orderBy('name')->get();

        return response()->json([
            'success' => true,
            'data' => $users,
            'message' => 'Danh sách người dùng'
        ]);
    }
}
