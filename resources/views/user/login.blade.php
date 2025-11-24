<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>@yield('title', 'Đăng nhập')</title>

    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="{{ asset('assets/css/bootstrap.min.css') }}">

    <!-- Bootstrap Icons -->
    <link rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.13.1/font/bootstrap-icons.min.css"
        integrity="sha512-t7Few9xlddEmgd3oKZQahkNI4dS6l80+eGEzFQiqtyVYdvcSG2D3Iub77R20BdotfRPA9caaRkg1tyaJiPmO0g=="
        crossorigin="anonymous" referrerpolicy="no-referrer" />

    <style>
        body {
            min-height: 100vh;
            display: flex;
            align-items: center;
            background-color: #f8f9fa;
        }

        .login-container {
            max-width: 1200px;
        }

        .hero-section {
            background: linear-gradient(135deg, #0d6efd, #0dcaf0);
            border-radius: 15px 0 0 15px;
        }

        .feature-icon {
            width: 60px;
            height: 60px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: transform 0.3s ease;
        }

        .feature-icon:hover {
            transform: scale(1.05);
        }

        .login-section {
            border-radius: 0 15px 15px 0;
            background: white;
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.1);
        }

        @media (max-width: 992px) {
            .hero-section {
                border-radius: 15px 15px 0 0;
            }

            .login-section {
                border-radius: 0 0 15px 15px;
            }
        }

        .form-control:focus {
            border-color: #0d6efd;
            box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
        }

        .btn-primary {
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(13, 110, 253, 0.3);
        }
    </style>

</head>

<body class="bg-white">

    <div class="container login-container">
        <div class="row shadow rounded-3 overflow-hidden bg-white">
            <!-- Info -->
            <div class="col-lg-6 hero-section text-white p-5">
                <div class="d-flex flex-column h-100 justify-content-center">
                    <div class="text-center mb-5">
                        <h3 class="fw-bold mb-3">Nơi lưu trữ tài liệu</h3>
                        <p class="lead mb-0">Kết nối và chia sẻ tri thức với mọi người.</p>
                    </div>
                    <div class="row g-4">
                        <div class="col-md-4 text-center">
                            <div class="feature-icon mx-auto mb-3">
                                <i class="bi bi-cloud-upload fs-2"></i>
                            </div>
                            <h6>Tải lên đa dạng</h6>
                            <small class="opacity-75">PDF, Word</small>
                        </div>

                        <div class="col-md-4 text-center">
                            <div class="feature-icon mx-auto mb-3">
                                <i class="bi bi-folder-symlink fs-2"></i>
                            </div>
                            <h6>Lưu trữ thông minh</h6>
                            <small class="opacity-75">Phân loại theo danh mục</small>
                        </div>

                        <div class="col-md-4 text-center">
                            <div class="feature-icon mx-auto mb-3">
                                <i class="bi bi-search fs-2"></i>
                            </div>
                            <h6>Tìm kiếm nhanh</h6>
                            <small class="opacity-75">Theo từ khóa</small>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Login -->
            <div class="col-lg-6 login-section p-5">
                <div class="d-flex flex-column h-100 justify-content-center">
                    <div class="text-center mb-4">
                        <h3 class="fw-bold text-primary">Đăng nhập</h3>
                        <p class="text-muted">Vui lòng đăng nhập để tiếp tục</p>
                    </div>

                    @if (session('error'))
                        <div class="alert alert-danger">{{ session('error') }}</div>
                    @endif
                    @if (session('success'))
                        <div class="alert alert-success">{{ session('success') }}</div>
                    @endif

                    <form method="POST" role="form" action="{{ route('auth.login') }}">
                        @csrf
                        <div class="form-floating mb-3">
                            <input class="form-control" id="email" type="email" name="email" placeholder="Email"
                                value="{{ old('email') }}" autofocus />
                            <label for="email">Email</label>
                            @error('email')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="form-floating mb-3">
                            <input class="form-control" id="password" type="password" name="password"
                                placeholder="Password" />
                            <label for="password">Mật khẩu</label>
                            @error('password')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="d-flex align-items-center justify-content-between mt-4 mb-0">
                            <a class="small text-muted text-decoration-none" href="#!">Quên mật khẩu?</a>
                            <button id="loginBtn" type="submit" class="btn btn-primary px-4">Đăng nhập</button>
                        </div>
                    </form>
                    
                    {{-- <div class="text-center mt-4 pt-3 border-top">
                        <p class="mb-0">Bạn chưa có tài khoản?
                            <a class="text-primary text-decoration-none" href="#!">Tạo tài khoản mới</a>
                        </p>
                    </div> --}}
                </div>
            </div>
        </div>
    </div>

    {{-- Bootstrap JS --}}
    <script src="{{ asset('assets/js/bootstrap.bundle.min.js') }}"></script>

    <script>
        const loginForm = document.querySelector('form[role="form"]');
        const loginBtn = document.getElementById('loginBtn');

        if (loginForm && loginBtn) {
            loginForm.addEventListener('submit', function() {
                loginBtn.disabled = true;
                loginBtn.innerText = 'Đang đăng nhập...';
            });
        }
    </script>

</body>

</html>
