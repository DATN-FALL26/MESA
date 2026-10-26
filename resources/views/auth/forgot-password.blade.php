<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Quên mật khẩu - MESA</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="bg-light">
    <main class="container py-5">
        <div class="card mx-auto border-0 shadow-sm" style="max-width: 28rem;">
            <div class="card-body p-4">
                <h1 class="h4 fw-bold">Quên mật khẩu</h1>
                <p class="text-muted">Nhập email nhân viên đã đăng ký để nhận mã khôi phục.</p>

                @if (session('status'))
                    <div class="alert alert-info" role="status">{{ session('status') }}</div>
                @endif

                @if ($errors->any())
                    <div class="alert alert-danger" role="alert">
                        @foreach ($errors->all() as $error)
                            <div>{{ $error }}</div>
                        @endforeach
                    </div>
                @endif

                <form method="POST" action="{{ route('password.email') }}">
                    @csrf
                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input id="email" name="email" type="email" value="{{ old('email') }}" maxlength="150"
                            autocomplete="email" required class="form-control">
                    </div>
                    <button type="submit" class="btn btn-dark w-100">Gửi mã OTP</button>
                </form>

                <p class="small text-muted mt-3 mb-0">
                    Tài khoản chưa có email? Vui lòng liên hệ Admin để được hỗ trợ.
                </p>
                <a href="{{ route('login') }}" class="d-inline-block mt-3">Quay lại đăng nhập</a>
            </div>
        </div>
    </main>
</body>

</html>
