<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Xác thực OTP - MESA</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="bg-light">
    <main class="container py-5">
        <div class="card mx-auto border-0 shadow-sm" style="max-width: 28rem;">
            <div class="card-body p-4">
                <h1 class="h4 fw-bold">Xác thực OTP</h1>
                <p class="text-muted">Nếu email hợp lệ, mã xác thực sẽ được gửi đến địa chỉ đã đăng ký.</p>

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

                <form method="POST" action="{{ route('password.otp.verify') }}">
                    @csrf
                    <div class="mb-3">
                        <label for="otp" class="form-label">Mã OTP 6 chữ số</label>
                        <input id="otp" name="otp" type="text" inputmode="numeric" autocomplete="one-time-code"
                            pattern="[0-9]{6}" maxlength="6" required class="form-control">
                    </div>
                    <button type="submit" class="btn btn-dark w-100">Xác nhận mã</button>
                </form>

                <form method="POST" action="{{ route('password.otp.resend') }}" class="mt-3">
                    @csrf
                    <input type="hidden" name="email" value="{{ $email }}">
                    <button type="submit" class="btn btn-outline-secondary w-100">Gửi lại OTP</button>
                </form>

                <a href="{{ route('password.request') }}" class="d-inline-block mt-3">Nhập email khác</a>
            </div>
        </div>
    </main>
</body>

</html>
