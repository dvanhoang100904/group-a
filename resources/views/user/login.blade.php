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

</head>

<body class="bg-light">
    <main>

        <div class="container py-5">
            <div class="row justify-content-center">
                @if (session('error'))
                    <div class="alert alert-danger">{{ session('error') }}</div>
                @endif
                @if (session('success'))
                    <div class="alert alert-success">{{ session('success') }}</div>
                @endif
                <div class="col-lg-5">
                    <div class="card shadow-lg border-0 rounded-lg mt-5">
                        <div class="card-header">
                            <h3 class="text-center fw-bold mb-3 text-primary py-2">Đăng nhập</h3>
                        </div>
                        <div class="card-body">
                            {{-- form login --}}
                            <form method="POST" role="form" action="{{ route('auth.login') }}">
                                @csrf
                                {{-- email --}}
                                <div class="form-floating mb-3">
                                    <input class="form-control mb-1" id="email" type="email" name="email"
                                        placeholder="Email" value="{{ old('email') }}" autofocus />
                                    <label for="email">Email </label>
                                    @error('email')
                                        <span class="text-danger">{{ $message }}</span>
                                    @enderror
                                </div>

                                {{-- password --}}
                                <div class="form-floating mb-3">
                                    <input class="form-control mb-1" id="password" type="password" name="password"
                                        placeholder="Password" />
                                    <label for="password">Mật khẩu</label>
                                    @error('password')
                                        <span class="text-danger">{{ $message }}</span>
                                    @enderror
                                </div>

                                {{-- action --}}
                                <div class="d-flex align-items-center justify-content-end mt-4 mb-0">
                                    <a class="small text-dark text-decoration-none me-3" href="#!">Quên mật
                                        khẩu?</a>
                                    <button type="submit" class="btn btn-primary">Đăng
                                        nhập</button>
                                </div>
                            </form>
                        </div>
                        <div class="card-footer text-center py-3">
                            <div class="small">Bạn chưa có tài khoản?
                                <a class="text-primary text-decoration-none" href="#!">Tạo mới tài khoản</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    {{-- Bootstrap JS --}}
    <script src="{{ asset('assets/js/bootstrap.bundle.min.js') }}"></script>

</body>

</html>
