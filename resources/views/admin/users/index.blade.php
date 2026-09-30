<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Quản lý Phân quyền Tài khoản</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light py-5">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="fw-bold">Quản lý Phân quyền Tài khoản</h2>
            <a href="{{ route('dashboard') }}" class="btn btn-secondary">Quay lại Dashboard</a>
        </div>

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        <div class="card shadow-sm border-0 rounded-4">
            <div class="card-body p-4">
                <table class="table table-hover align-middle">
                    <thead class="table-dark">
                        <tr>
                            <th>ID</th>
                            <th>Họ tên</th>
                            <th>Email</th>
                            <th>Vai trò hiện tại (Role)</th>
                            <th>Thao tác đổi quyền</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($users as $u)
                        <tr>
                            <td>{{ $u->id }}</td>
                            <td class="fw-semibold">{{ $u->name }}</td>
                            <td>{{ $u->email }}</td>
                            <td>
                                <span class="badge 
                                    @if($u->role == 'ceo') bg-danger 
                                    @elseif($u->role == 'manager') bg-primary 
                                    @elseif($u->role == 'cashier') bg-success 
                                    @elseif($u->role == 'chef') bg-warning text-dark 
                                    @elseif($u->role == 'staff') bg-info text-dark 
                                    @else bg-secondary @endif">
                                    {{ strtoupper($u->role) }}
                                </span>
                            </td>
                            <td>
                                <!-- Form đổi quyền ngay trên giao diện -->
                                <form action="{{ route('admin.users.updateRole', $u->id) }}" method="POST" class="d-flex gap-2">
                                    @csrf
                                    @method('PUT')
                                    <select name="role" class="form-select form-select-sm w-auto">
                                        <option value="customer" {{ $u->role == 'customer' ? 'selected' : '' }}>Khách hàng (customer)</option>
                                        <option value="staff" {{ $u->role == 'staff' ? 'selected' : '' }}>Nhân viên (staff)</option>
                                        <option value="chef" {{ $u->role == 'chef' ? 'selected' : '' }}>Đầu bếp (chef)</option>
                                        <option value="cashier" {{ $u->role == 'cashier' ? 'selected' : '' }}>Thu ngân (cashier)</option>
                                        <option value="manager" {{ $u->role == 'manager' ? 'selected' : '' }}>Quản lý (manager)</option>
                                        <option value="ceo" {{ $u->role == 'ceo' ? 'selected' : '' }}>CEO (ceo)</option>
                                    </select>
                                    <button type="submit" class="btn btn-sm btn-dark">Lưu</button>
                                </form>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
                {{ $users->links() }}
            </div>
        </div>
    </div>
</body>
</html>