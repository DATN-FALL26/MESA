<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Màn hình Bếp (Kitchen Display)</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-dark text-white py-5">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>🍳 Màn hình Đầu bếp / Pha chế</h2>
            <form action="{{ route('logout') }}" method="POST">
                @csrf
                <button type="submit" class="btn btn-outline-light btn-sm">Đăng xuất</button>
            </form>
        </div>
        <div class="p-5 bg-secondary rounded-4 shadow">
            <h4>Khu vực hiển thị danh sách món ăn khách gọi đang chờ chế biến...</h4>
            <p class="text-light">Xin chào: <strong>{{ Auth::user()->name }}</strong> (Vai trò: Đầu bếp)</p>
        </div>
    </div>
</body>
</html>
