<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Đăng ký - MESA</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-light min-vh-100 d-flex align-items-center justify-content-center py-5">
    <main class="container">
        <div class="mx-auto card border-0 shadow-sm rounded-4" style="max-width: 30rem;">
            <div class="card-body p-4 p-md-5 text-center">
                <p class="small text-uppercase text-secondary fw-semibold mb-2">MESA</p>
                <h1 class="h3 fw-bold mb-3">Tính năng đăng ký đã bị tắt</h1>
                <p class="text-secondary mb-4">
                    Hệ thống hiện chỉ hỗ trợ đăng nhập tài khoản nội bộ. Nếu cần hỗ trợ, vui lòng liên hệ quản trị viên.
                </p>
                <a href="{{ route('login') }}" class="btn btn-dark fw-semibold px-4">Về trang đăng nhập</a>
            </div>
        </div>
    </main>
</body>
</html>
