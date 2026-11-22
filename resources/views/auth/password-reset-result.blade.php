<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $success ? 'Cập nhật thành công' : 'Cập nhật thất bại' }} - MESA</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="bg-light">
    <main class="container py-5">
        <div class="card mx-auto border-0 shadow-sm" style="max-width: 28rem;">
            <div class="card-body p-4 text-center">
                <div class="alert {{ $success ? 'alert-success' : 'alert-danger' }}" role="alert">
                    <h1 class="h5 fw-bold">{{ $success ? 'Thành công' : 'Thất bại' }}</h1>
                    <p class="mb-0">{{ $message }}</p>
                </div>

                <p class="text-muted">Bạn sẽ được chuyển đến {{ $destination }} sau <span id="redirect-countdown">3</span> giây.</p>
                <a id="redirect-link" href="{{ $redirectUrl }}" class="btn btn-dark w-100">
                    {{ $success ? 'Đăng nhập ngay' : 'Thử lại' }}
                </a>
            </div>
        </div>
    </main>

    <script>
        (() => {
            const countdown = document.getElementById('redirect-countdown');
            const redirectLink = document.getElementById('redirect-link');
            let remaining = 3;

            const timer = setInterval(() => {
                remaining -= 1;
                countdown.textContent = String(remaining);

                if (remaining <= 0) {
                    clearInterval(timer);
                    window.location.assign(redirectLink.href);
                }
            }, 1000);
        })();
    </script>
</body>

</html>
