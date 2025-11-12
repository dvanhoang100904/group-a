<div class="sidebar pt-4" style="background: #f8fafd; border: none; box-shadow: none" id="sidebar">
    <button class="sidebar-toggle d-none d-lg-block" style="background: #f8fafd; " id="sidebarToggle">
        <i class="bi bi-chevron-left"></i>
    </button>

    <div class="sidebar-content">
        <ul class="nav nav-pills flex-column">
            <!-- Tổng quan -->
            <li class="nav-item">
                <span class="sidebar-section-title">Tổng quan</span>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ Request::routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}">
                    <i class="bi bi-speedometer2"></i>
                    <span class="link-text">Dashboard</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link " href="#!">
                    <i class="bi bi-clock-history"></i>
                    <span class="link-text">Hoạt động gần đây</span>
                </a>
            </li>

            <!-- Tài liệu -->
            <li class="nav-item">
                <span class="sidebar-section-title">Tài liệu</span>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ Request::routeIs('documents.*') ? 'active' : '' }}"
                    href="{{ route('documents.index') }}">
                    <i class="bi bi-files"></i>
                    <span class="link-text">Danh sách tài liệu</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="{{ route('upload.index') }}">
                    <i class="bi bi-upload"></i>
                    <span class="link-text">Tải lên tài liệu</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="#">
                    <i class="bi bi-person-lines-fill"></i>
                    <span class="link-text">Tài liệu của tôi</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ Request::routeIs('shared.*') ? 'active' : '' }}"
                    href="{{ route('shared.index') }}">
                    <i class="bi bi-share"></i>
                    <span class="link-text">Chia sẻ với tôi</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="{{ route('folders.index') }}">
                    <i class="bi bi-folder2-open"></i>
                    <span class="link-text">Quản lý thư mục</span>
                </a>
            </li>

            <!-- Danh mục & Phân loại -->
            <li class="nav-item">
                <span class="sidebar-section-title">Danh mục</span>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ Request::routeIs('types.*') ? 'active' : '' }}" href="{{ route('types.index') }}">
                    <i class="bi bi-collection"></i>
                    <span class="link-text">Loại tài liệu</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ Request::routeIs('monhoc.*') ? 'active' : '' }}"
                    href="{{ route('monhoc.index') }}">
                    <i class="bi bi-journal-bookmark"></i>
                    <span class="link-text">Môn học</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ Request::routeIs('khoa.*') ? 'active' : '' }}" href="{{ route('khoa.index') }}">
                    <i class="bi bi-building"></i>
                    <span class="link-text">Khoa / Bộ môn</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ Request::routeIs('tags.*') ? 'active' : '' }}" href="{{ route('tags.index') }}">
                    <i class="bi bi-tags"></i>
                    <span class="link-text">Thẻ</span>
                </a>
            </li>

            <!-- Quản trị -->
            <li class="nav-item">
                <span class="sidebar-section-title">Quản trị</span>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="#">
                    <i class="bi bi-journal-text"></i>
                    <span class="link-text">Nhật ký truy cập</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="#">
                    <i class="bi bi-people"></i>
                    <span class="link-text">Quản lý người dùng</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="#">
                    <i class="bi bi-person-badge"></i>
                    <span class="link-text">Vai trò & quyền hạn</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ request()->is('reports') ? 'active' : '' }}"
                    href="{{ route('reports.index') }}">
                    <i class="bi bi-flag"></i>
                    <span class="link-text">Báo cáo vi phạm</span>
                </a>
            </li>
        </ul>
    </div>
</div>
