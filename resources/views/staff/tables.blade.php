<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Quản lý Bàn / Đặt món</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light py-5">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="text-success">🛎️ Khu vực Nhân viên Phục vụ</h2>
            <form action="{{ route('logout') }}" method="POST">
                @csrf
                <button type="submit" class="btn btn-outline-danger btn-sm">Đăng xuất</button>
            </form>
        </div>
        <div class="p-5 bg-white rounded-4 shadow-sm">
            <h4>Khu vực gọi món, quản lý bàn ăn cho khách...</h4>
            <p class="text-muted">Xin chào: <strong>{{ Auth::user()->name }}</strong> (Vai trò: Nhân viên phục vụ)</p>
        </div>
    </div>
</body>
</html>