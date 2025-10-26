@extends('layouts.app')

@section('title', 'Hồ sơ cá nhân')

@section('content')
<div class="container py-4">
    <h4 class="mb-4"><i class="bi bi-person-circle"></i> Hồ sơ cá nhân</h4>
    <div class="row">
        <!-- Cột bên trái: Avatar -->
        <div class="col-md-4 mb-4">
            <div class="card shadow-sm p-3 text-center">
                <div class="mb-3">
                    <img src="{{ $user->avatar ?? 'https://via.placeholder.com/150' }}" 
                         class="rounded-circle img-fluid border" 
                         alt="Avatar" width="150" height="150">
                </div>
                <h5 class="fw-bold mb-0">{{ $user->full_name ?? 'Nguyễn Văn A' }}</h5>
                <p class="text-muted">Giảng viên - Khoa Công nghệ thông tin</p>
                <button class="btn btn-outline-secondary btn-sm mt-2">Đổi ảnh đại diện</button>
            </div>
        </div>

        <!-- Cột bên phải: Thông tin cá nhân -->
        <div class="col-md-8">
            <div class="card shadow-sm p-4">
                <form>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Họ và tên</label>
                            <input type="text" class="form-control" value="{{ $user->full_name ?? 'Nguyễn Văn A' }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control" value="{{ $user->email ?? 'nguyenvana@unidocs.edu.vn' }}" readonly>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Chức vụ</label>
                            <input type="text" class="form-control" value="{{ $user->position ?? 'Giảng viên' }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Khoa / Bộ môn</label>
                            <input type="text" class="form-control" value="{{ $user->department ?? 'Công nghệ thông tin' }}">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Giới thiệu bản thân</label>
                        <textarea class="form-control" rows="3">{{ $user->bio ?? 'Giảng viên có hơn 5 năm kinh nghiệm trong lĩnh vực lập trình và hệ thống thông tin.' }}</textarea>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Mật khẩu mới</label>
                            <input type="password" class="form-control" placeholder="Nhập mật khẩu mới">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Xác nhận mật khẩu</label>
                            <input type="password" class="form-control" placeholder="Nhập lại mật khẩu">
                        </div>
                    </div>

                    <div class="text-end">
                        <button class="btn btn-primary"><i class="bi bi-save"></i> Lưu thay đổi</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Hoạt động gần đây -->
    <div class="card shadow-sm mt-4 p-3">
        <h5 class="mb-3">Hoạt động gần đây</h5>
        <ul class="list-group list-group-flush">
            <li class="list-group-item">
                <i class="bi bi-upload text-success"></i> 
                Đã upload tài liệu: <strong>Bài giảng Lập trình Web</strong>
                <span class="text-muted float-end">2 giờ trước</span>
            </li>
            <li class="list-group-item">
                <i class="bi bi-chat-dots text-info"></i> 
                Bình luận vào tài liệu <strong>Slide Toán Cao cấp</strong>
                <span class="text-muted float-end">Hôm qua</span>
            </li>
            <li class="list-group-item">
                <i class="bi bi-download text-primary"></i> 
                Tải xuống tài liệu <strong>Đề thi C++</strong>
                <span class="text-muted float-end">3 ngày trước</span>
            </li>
        </ul>
    </div>
</div>
@endsection
