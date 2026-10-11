<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Đăng ký tài khoản khách hàng - MESA</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-light min-vh-100 d-flex align-items-center py-5">
    <main class="container">
        <div class="mx-auto card border-0 shadow-sm rounded-4" style="max-width: 34rem;">
            <div class="card-body p-4 p-md-5">
                <p class="small text-uppercase text-secondary fw-semibold mb-2">MESA</p>
                <h1 class="h3 fw-bold mb-2">Tạo tài khoản khách hàng</h1>
                <p class="text-secondary mb-4">
                    Tài khoản mới không được cấp quyền quản trị. Quản trị viên có thể gán vai trò sau.
                </p>

                @if ($errors->any())
                    <div class="alert alert-danger" role="alert">
                        <ul class="mb-0 ps-3">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form method="POST" action="{{ route('register') }}" class="vstack gap-3">
                    @csrf
                    <div>
                        <label for="full_name" class="form-label">Họ và tên</label>
                        <input id="full_name" name="full_name" value="{{ old('full_name') }}" required maxlength="150" autocomplete="name" class="form-control">
                    </div>
                    <div>
                        <label for="username" class="form-label">Tên đăng nhập</label>
                        <input id="username" name="username" value="{{ old('username') }}" required minlength="3" maxlength="50" autocomplete="username" class="form-control">
                    </div>
                    <div>
                        <label for="email" class="form-label">Email <span class="text-secondary">(không bắt buộc)</span></label>
                        <input id="email" name="email" type="email" value="{{ old('email') }}" maxlength="150" autocomplete="email" class="form-control">
                    </div>
                    <div>
                        <label for="phone" class="form-label">Số điện thoại <span class="text-secondary">(không bắt buộc)</span></label>
                        <input id="phone" name="phone" value="{{ old('phone') }}" maxlength="20" autocomplete="tel" class="form-control">
                    </div>
                    <div>
                        <label for="password" class="form-label">Mật khẩu</label>
                        <input id="password" name="password" type="password" required minlength="8" autocomplete="new-password" class="form-control">
                    </div>
                    <div>
                        <label for="password_confirmation" class="form-label">Xác nhận mật khẩu</label>
                        <input id="password_confirmation" name="password_confirmation" type="password" required minlength="8" autocomplete="new-password" class="form-control">
                    </div>
                    <button type="submit" class="btn btn-dark fw-semibold py-2 mt-2">Tạo tài khoản</button>
                </form>

                <p class="text-center text-secondary small mt-4 mb-0">
                    Đã có tài khoản?
                    <a href="{{ route('login') }}" class="link-dark fw-semibold">Đăng nhập</a>
                </p>
            </div>
        </div>
    </main>
</body>
</html>
