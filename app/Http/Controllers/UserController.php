<?php

namespace App\Http\Controllers;

use App\Http\Requests\User\LoginRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http; // dùng để gọi API
use App\Models\User;
use App\Services\User\UserService;

class UserController extends Controller
{
    protected UserService $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    /**
     * Hien thi form dang nhap
     */
    public function login()
    {
        return view('user.login');
    }

    /**
     * Xu lu dang nhap
     */
    public function authLogin(LoginRequest $request)
    {
        // Lay email và password tu form
        $credentials = $request->only([
            'email',
            'password'
        ]);

        // Kiem tra thong tin dang nhap
        $login = $this->userService->login($credentials);

        if (!$login) {
            return redirect()->route('login')
                ->with('error', 'Đăng nhập thất bại. Vui lòng thử lại.');
        }

        return redirect()->route('folders.index')
            ->with('success', 'Đăng nhập thành công.');
    }

    /**
     * Xu ly dang xuat
     */
    public function logout(Request $request)
    {
        // Huy session hien tai
        $this->userService->logout($request);

        // Huy session hien tai
        $request->session()->invalidate();

        // Tao lai token moi de tranh CSRF
        $request->session()->regenerateToken();

        return redirect()->route('login')
            ->with('success', 'Đăng xuất thành công');
    }

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
