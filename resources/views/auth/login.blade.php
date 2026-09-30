<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng nhập Hệ thống</title>
    <!-- Bootstrap 5 CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Google Fonts: Inter -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #0b0f19;
            color: #f8fafc;
            overflow-x: hidden;
            position: relative;
            min-height: 100vh;
        }

        /* Hiệu ứng ánh sáng nền mờ ảo cực ngầu */
        .bg-glow-1 {
            position: absolute;
            top: -10%;
            left: -10%;
            width: 500px;
            height: 500px;
            background: radial-gradient(circle, rgba(43, 46, 189, 0.25) 0%, rgba(0, 0, 0, 0) 70%);
            z-index: -1;
            filter: blur(60px);
        }

        .bg-glow-2 {
            position: absolute;
            bottom: -10%;
            right: -10%;
            width: 500px;
            height: 500px;
            background: radial-gradient(circle, rgba(168, 85, 247, 0.25) 0%, rgba(0, 0, 0, 0) 70%);
            z-index: -1;
            filter: blur(60px);
        }

        /* Card dạng kính mờ (Glassmorphism) */
        .glass-card {
            background: rgba(17, 24, 39, 0.7);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid rgba(255, 255, 255, 0.08);
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.7);
        }

        /* Tùy chỉnh input form */
        .form-control {
            background-color: rgba(30, 41, 59, 0.6) !important;
            border: 1px solid rgba(255, 255, 255, 0.1) !important;
            color: #fff !important;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            background-color: rgba(30, 41, 59, 0.9) !important;
            border-color: #6366f1 !important;
            box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.25) !important;
        }

        .form-control::placeholder {
            color: #64748b;
        }

        /* Nút bấm hiệu ứng gradient */
        .btn-gradient {
            background: linear-gradient(135deg, #6366f1 0%, #a855f7 100%);
            border: none;
            transition: all 0.3s ease;
        }

        .btn-gradient:hover {
            background: linear-gradient(135deg, #4f46e5 0%, #9333ea 100%);
            transform: translateY(-2px);
            box-shadow: 0 10px 25px -5px rgba(99, 102, 241, 0.5);
        }

        .form-check-input {
            background-color: rgba(30, 41, 59, 0.8);
            border-color: rgba(255, 255, 255, 0.2);
        }

        .form-check-input:checked {
            background-color: #6366f1;
            border-color: #6366f1;
        }
    </style>
</head>
<body>

    <!-- Các đốm sáng trang trí nền -->
    <div class="bg-glow-1"></div>
    <div class="bg-glow-2"></div>

    <div class="container d-flex align-items-center justify-content-center min-vh-100 py-5">
        <div class="col-md-5 col-lg-4">
            
            <!-- Trạng thái Session (nếu có) -->
            @if (session('status'))
                <div class="alert alert-success border-0 bg-success bg-opacity-25 text-success mb-4 rounded-4 shadow-sm" role="alert">
                    {{ session('status') }}
                </div>
            @endif

            <div class="glass-card rounded-4 p-4 p-sm-5">
                
                <!-- Logo & Tiêu đề -->
                <div class="text-center mb-4">
                    <div class="d-inline-flex align-items-center justify-content-center bg-indigo bg-gradient text-white fw-bold rounded-3 mb-3 shadow-lg" style="width: 100px; height: 50px; font-size: 22px; background: linear-gradient(135deg, #6366f1, #a855f7);">
                        MESA
                    </div>
                    <h3 class="fw-bold text-white tracking-tight">Đăng nhập hệ thống</h3>
                    <p class="text-secondary small">Nhập thông tin xác thực để tiếp tục</p>
                </div>

                <form method="POST" action="{{ route('login') }}">
                    @csrf

                    <!-- Email Address -->
                    <div class="mb-3">
                        <label for="email" class="form-label text-secondary small fw-semibold">Email truy cập</label>
                        <input id="email" class="form-control form-control-lg fs-6 rounded-3 @error('email') is-invalid @enderror" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username" placeholder="name@example.com">
                        @error('email')
                            <div class="invalid-feedback text-danger">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Password -->
                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <label for="password" class="form-label text-secondary small fw-semibold">Mật khẩu</label>
                            @if (Route::has('password.request'))
                                <a class="text-decoration-none small text-indigo fw-medium" style="color: #818cf8;" href="{{ route('password.request') }}">
                                    Quên mật khẩu?
                                </a>
                            @endif
                        </div>
                        <input id="password" class="form-control form-control-lg fs-6 rounded-3 @error('password') is-invalid @enderror" type="password" name="password" required autocomplete="current-password" placeholder="••••••••">
                        @error('password')
                            <div class="invalid-feedback text-danger">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Remember Me -->
                    <div class="mb-4 form-check">
                        <input id="remember_me" type="checkbox" class="form-check-input" name="remember">
                        <label for="remember_me" class="form-check-label text-secondary small user-select-none">
                            Ghi nhớ phiên đăng nhập
                        </label>
                    </div>

                    <!-- Nút Submit -->
                    <div class="d-grid">
                        <button type="submit" class="btn btn-gradient btn-lg fs-6 fw-semibold rounded-3 py-2 text-white shadow">
                            Truy cập hệ thống
                        </button>
                    </div>
                </form>
            </div>
            
        </div>
    </div>

    <!-- Bootstrap JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>