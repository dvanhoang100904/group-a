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
                        @auth
                            @if (Auth::user())
                                <li>
                                    <a class="dropdown-item" href="#!">
                                        <i class="fas fa-user-circle me-2"></i>{{ Auth::user()->name }}
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item" href="{{ route('profile.view') }}"><i
                                            class="bi bi-person me-2"></i>Xem hồ sơ</a>
                                </li>
                                <li>
                                    <hr class="dropdown-divider">
                                </li>
                                <li>
                                    {{-- logout --}}
                                    <form id="logout-form" action="{{ route('logout') }}" method="POST">
                                        @csrf
                                        <button id="logout-button" type="button" class="dropdown-item">
                                            <i class="bi bi-box-arrow-right me-1"></i>
                                            Đăng xuất
                                        </button>
                                    </form>
                                </li>
                            @endif
                        @else
                            <li>
                                <a class="dropdown-item" href="{{ route('login') }}">
                                    <i class="fas fa-sign-in-alt me-2"></i>Đăng Nhập
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="#!">
                                    <i class="fas fa-user-plus me-2"></i>Đăng Ký
                                </a>
                            </li>
                        @endauth
                    </ul>
                </div>
            </div>
        </div>
    </nav>
    <script>
        // ---------------- Logout ----------------
        const logoutBtn = document.getElementById("logout-button");
        if (logoutBtn) {
            logoutBtn.addEventListener("click", function(event) {
                Swal.fire({
                    title: "Xác nhận đăng xuất",
                    text: "Bạn sẽ bị đăng xuất khỏi tài khoản. Bạn có chắc chắn muốn tiếp tục?",
                    icon: "warning",
                    showCancelButton: true,
                    confirmButtonColor: "#d33",
                    cancelButtonColor: "#3085d6",
                    confirmButtonText: "Đăng xuất",
                    cancelButtonText: "Hủy",
                }).then((result) => {
                    if (result.isConfirmed) {
                        Swal.fire({
                            title: "Đang đăng xuất...",
                            text: "Vui lòng chờ",
                            allowOutsideClick: false,
                            didOpen: () => {
                                Swal.showLoading();
                                const logoutForm =
                                    document.getElementById("logout-form");
                                if (logoutForm) logoutForm.submit();
                            },
                        });
                    }
                });
            });
        }
    </script>
