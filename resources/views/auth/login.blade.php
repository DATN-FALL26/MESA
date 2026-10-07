<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Đăng nhập - MESA</title>

    <!-- Bootstrap 5 CDN CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Bootstrap Icons (dùng cho icon mắt ẩn/hiện mật khẩu) -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="bg-white text-dark min-vh-100">

    <div class="container-fluid min-vh-100 p-0">
        <div class="row g-0 min-vh-100">

            <!-- ================================= -->
            <!-- BÊN TRÁI: GIỚI THIỆU MESA (Ẩn trên mobile, hiện từ lg trở lên) -->
            <!-- ================================= -->
            <div class="col-lg-6 d-none d-lg-flex bg-light align-items-center justify-content-center p-5">
                <div class="max-w-lg px-4" style="max-width: 32rem;">
                    <div
                        style="height: 300px; background-image:url('{{ asset('storage/images/banner1.jpg') }}'); background-size: cover; background-position: center;">
                    </div>
                    <span class="d-block text-uppercase text-secondary fw-bold fs-7 tracking-widest mb-3"
                        style="letter-spacing: 0.3em;">
                        MESA Ecosystem
                    </span>
                    <p class="text-secondary lh-base mb-5">
                        Hệ thống quản trị tập trung giúp doanh nghiệp quản lý công việc, dữ liệu và tài khoản một cách
                        hiệu quả và hiện đại.
                    </p>

                    <!-- Đường trang trí -->
                    <div class="bg-dark" style="width: 5rem; height: 0.25rem;"></div>

                </div>
            </div>


            <!-- ================================= -->
            <!-- BÊN PHẢI: FORM ĐĂNG NHẬP -->
            <!-- ================================= -->
            <div class="col-12 col-lg-6 d-flex align-items-center justify-content-center px-4 py-5 bg-white">
                <div class="w-100" style="max-width: 26rem;">

                    <!-- Header -->
                    <div class="mb-4">
                        <h2 class="fw-bold text-dark fs-2">
                            Đăng nhập hệ thống
                        </h2>
                        <p class="text-muted small mt-1">
                            Nhập thông tin tài khoản của bạn để tiếp tục.
                        </p>
                    </div>


                    <!-- ================= -->
                    <!-- HIỂN THỊ LỖI -->
                    <!-- ================= -->
                    @if ($errors->any())
                        <div class="alert alert-danger border-0 bg-danger-subtle text-danger rounded-4 fs-6 mb-4 p-3"
                            role="alert">
                            <ul class="mb-0 ps-3">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif


                    <!-- ================= -->
                    <!-- FORM -->
                    <!-- ================= -->
                    <form method="POST" action="{{ route('login') }}" class="needs-validation" novalidate>
                        @csrf

                        <!-- Username / Email -->
                        <div class="mb-3">
                            <label for="login" class="form-label text-secondary fw-semibold small">
                                Tên đăng nhập hoặc Email
                            </label>
                            <input id="login" name="login" type="text" value="{{ old('login') }}" required
                                autofocus placeholder="name@example.com"
                                class="form-control bg-white border-secondary-subtle rounded-3 py-3 px-3 shadow-none focus-ring-dark">
                        </div>


                        <!-- Password -->
                        <div class="mb-3">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <label for="password" class="form-label text-secondary fw-semibold small mb-0">
                                    Mật khẩu
                                </label>
                                @if (Route::has('password.request'))
                                    <a href="{{ route('password.request') }}"
                                        class="text-decoration-none text-muted small hover-dark">
                                        Quên mật khẩu?
                                    </a>
                                @endif
                            </div>

                            <div class="input-group">
                                <input id="password" name="password" type="password" required placeholder="••••••••"
                                    class="form-control bg-white border-secondary-subtle rounded-start-3 py-3 px-3 shadow-none focus-ring-dark">
                                <!-- Nút ẩn/hiện mật khẩu bằng Javascript thuần -->
                                <button
                                    class="btn border border-secondary-subtle bg-white text-muted rounded-end-3 px-3 shadow-none"
                                    type="button" id="togglePassword">
                                    <i class="bi bi-eye" id="eyeIcon"></i>
                                </button>
                            </div>
                        </div>


                        <!-- Remember Me -->
                        <div class="mb-4 d-flex justify-content-between align-items-center">

                            <div class="form-check">
                                <input class="form-check-input shadow-none border-secondary-subtle" type="checkbox"
                                    name="remember" value="1" id="remember">

                                <label class="form-check-label text-muted small" for="remember">
                                    Ghi nhớ đăng nhập
                                </label>
                            </div>

                            @if (Route::has('register'))
                                <a href="{{ route('register') }}"
                                    class="text-decoration-none text-muted small hover-dark">
                                    Đăng ký ngay
                                </a>
                            @endif

                        </div>


                        <!-- Button Submit -->
                        <button type="submit" class="btn btn-dark w-100 rounded-3 fw-bold py-3">
                            ĐĂNG NHẬP
                        </button>

                    </form>


                    <!-- Footer -->
                    <div class="text-center mt-5 text-muted small">
                        © {{ date('Y') }} MESA SYSTEM
                    </div>

                </div>
            </div>

        </div>
    </div>

    <!-- Bootstrap 5 JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Script thuần xử lý ẩn/hiện mật khẩu đơn giản, nhẹ nhàng -->
    <script>
        const togglePassword = document.getElementById('togglePassword');
        const password = document.getElementById('password');
        const eyeIcon = document.getElementById('eyeIcon');

        togglePassword.addEventListener('click', function() {
            const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
            password.setAttribute('type', type);

            // Đổi icon mắt
            if (type === 'text') {
                eyeIcon.classList.remove('bi-eye');
                eyeIcon.classList.add('bi-eye-slash');
            } else {
                eyeIcon.classList.remove('bi-eye-slash');
                eyeIcon.classList.add('bi-eye');
            }
        });
    </script>
</body>

</html>
