    <nav class="navbar navbar-expand-lg fixed-top" style="background: #f8fafd; border: none; box-shadow: none">
        <div class="container-fluid">
            <button class="btn btn-sm btn-outline-primary me-2 d-lg-none" id="mobileSidebarToggle">
                <i class="bi bi-list"></i>
            </button>
            <a class="navbar-brand" href="#!">
                <i class="bi bi-journal-bookmark-fill me-2"></i>Nhóm A
            </a>
            <div class="d-flex align-items-center ">
                <div class="dropdown ">
                    <a class="d-flex align-items-center text-decoration-none" href="#!" data-bs-toggle="dropdown">
                        <div class="user-avatar me-2">
                        </div>
                        <strong class="d-none d-md-block"></strong>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end shadow">
                        <li>
                            <a class="dropdown-item" href="{{ route('profile.view') }}"><i
                                    class="bi bi-person me-2"></i>Xem hồ sơ</a>
                        </li>
                        <li>
                            <hr class="dropdown-divider">
                        </li>
                        <li>
                            <a class="dropdown-item" href="#!"><i class="bi bi-box-arrow-right me-1"></i>
                                Đăng xuất</a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </nav>
