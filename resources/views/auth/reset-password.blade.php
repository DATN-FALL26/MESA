<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Đặt lại mật khẩu - MESA</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="bg-light">
    <main class="container py-5">
        <div class="card mx-auto border-0 shadow-sm" style="max-width: 28rem;">
            <div class="card-body p-4">
                <h1 class="h4 fw-bold">Đặt mật khẩu mới</h1>

                @if ($errors->any())
                    <div class="alert alert-danger" role="alert">
                        @foreach ($errors->all() as $error)
                            <div>{{ $error }}</div>
                        @endforeach
                    </div>
                @endif

                <form method="POST" action="{{ route('password.reset') }}">
                    @csrf
                    @method('PUT')
                    <div class="mb-3">
                        <label for="password" class="form-label">Mật khẩu mới</label>
                        <input id="password" name="password" type="password" minlength="8"
                            autocomplete="new-password" required class="form-control">
                    </div>
                    <div class="mb-3">
                        <label for="password_confirmation" class="form-label">Xác nhận mật khẩu mới</label>
                        <input id="password_confirmation" name="password_confirmation" type="password" minlength="8"
                            autocomplete="new-password" required class="form-control">
                    </div>
                    <button type="submit" class="btn btn-dark w-100">Đổi mật khẩu</button>
                </form>
            </div>
        </div>
    </main>
</body>

</html>
