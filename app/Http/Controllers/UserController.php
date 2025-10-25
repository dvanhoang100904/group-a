<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http; // dùng để gọi API
use App\Models\User;

class UserController extends Controller
{
    /**
     * Hiển thị trang hồ sơ cá nhân
     */
    public function showProfile()
    {
        // Giả lập dữ liệu từ API (sau này bạn thay bằng gọi thật)
        $apiResponse = [
            'user_id' => 1,
            'full_name' => 'Nguyễn Văn A',
            'email' => 'nguyenvana@unidocs.edu.vn',
            'position' => 'Giảng viên',
            'department' => 'Công nghệ thông tin',
            'bio' => 'Giảng viên có hơn 5 năm kinh nghiệm trong lĩnh vực lập trình và hệ thống thông tin.',
            'avatar' => null,
        ];

        // Chuyển dữ liệu sang view
        $user = (object) $apiResponse;

        return view('profile.view_profile.profile', compact('user'));
    }

    /**
     * Cập nhật thông tin hồ sơ cá nhân
     */
    public function updateProfile(Request $request)
    {
        $validated = $request->validate([
            'full_name' => 'required|string|max:255',
            'position' => 'nullable|string|max:255',
            'department' => 'nullable|string|max:255',
            'bio' => 'nullable|string|max:500',
            'password' => 'nullable|min:6|confirmed',
        ]);

        // Gửi dữ liệu cập nhật lên API (giả lập hoặc thật)
        // Ví dụ giả lập PUT API:
        /*
        $response = Http::put('https://api.example.com/users/' . $request->user_id, $validated);
        if ($response->failed()) {
            return back()->withErrors(['error' => 'Không thể cập nhật thông tin người dùng']);
        }
        */

        // Tạm thời log ra dữ liệu cập nhật
        // Log::info('User update data', $validated);

        return back()->with('success', 'Cập nhật hồ sơ thành công!');
    }
}
