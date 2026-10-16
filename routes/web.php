use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Trang Dashboard tổng quan của Thuận
Route::get('/dashboard', function () {
    return view('admin.dashboard');
});

// Trang Báo cáo chi tiết của Thuận
Route::get('/admin/reports', function () {
    return view('admin.reports');
});
