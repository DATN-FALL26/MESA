<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserStatus;
use App\Enums\BranchStatus;
use App\Helpers\ConstantHelper;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Branch;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Order;
use App\Models\User;
use App\Services\Auth\BranchAccessService;

class DashboardController extends Controller
{
    public function __construct(private BranchAccessService $branchAccess) {}

    /**
     * Trang Tổng quan Dashboard (Code gốc của team + Dữ liệu tĩnh dự phòng an toàn)
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $branchId = $this->resolveBranchId($request);
        if ($branchId === false) {
            return view('branches.select', [
                'branches' => $this->selectableBranches($user),
                'action' => route('admin.dashboard'),
            ]);
        }

        $currentRoles = $user->activeRoles()->orderBy('name')->get();
        $totalUsers = User::query()
            ->when($branchId !== null, fn ($query) => $query->whereHas('userRoles', fn ($roleQuery) => $roleQuery
                ->where('scope_type', 'BRANCH')
                ->where('scope_id', $branchId)
                ->where('valid_from', '<=', now())
                ->where(fn ($validityQuery) => $validityQuery
                    ->whereNull('valid_to')
                    ->orWhere('valid_to', '>=', now()))))
            ->distinct()
            ->count('users.id');
        $activeUsers = User::query()
            ->where('status', UserStatus::ACTIVE->value)
            ->when($branchId !== null, fn ($query) => $query->whereHas('userRoles', fn ($roleQuery) => $roleQuery
                ->where('scope_type', 'BRANCH')
                ->where('scope_id', $branchId)
                ->where('valid_from', '<=', now())
                ->where(fn ($validityQuery) => $validityQuery
                    ->whereNull('valid_to')
                    ->orWhere('valid_to', '>=', now()))))
            ->distinct()
            ->count();
        $totalRoles = Role::query()->count();
        $totalPermissions = Permission::query()->count();
        $recentActivities = AuditLog::query()
            ->when($branchId !== null, fn ($query) => $query->where('branch_id', $branchId))
            ->orderByDesc('created_at')
            ->limit(6)
            ->get();
        $activityUsers = User::query()
            ->whereIn('id', $recentActivities->pluck('user_id')->filter()->unique())
            ->pluck('full_name', 'id');
        $actionLabels = [
            'create' => 'Đã tạo',
            'update' => 'Đã cập nhật',
            'delete' => 'Đã xóa',
            'status_change' => 'Đã thay đổi trạng thái',
            'login' => 'Đã đăng nhập',
        ];
        $modules = [
            [
                'permission' => ConstantHelper::PERM_USERS_VIEW,
                'route' => 'users.index',
                'title' => 'Quản lý tài khoản',
                'description' => 'Theo dõi tài khoản, thông tin nhân sự và trạng thái hoạt động.',
                'icon' => 'users',
                'color' => 'indigo',
            ],
            [
                'permission' => ConstantHelper::PERM_ROLES_VIEW,
                'route' => 'roles.index',
                'title' => 'Quản lý vai trò',
                'description' => 'Tổ chức vai trò và kiểm soát quyền truy cập theo nhóm.',
                'icon' => 'roles',
                'color' => 'violet',
            ],
            [
                'permission' => ConstantHelper::PERM_PERMISSIONS_VIEW,
                'route' => 'permissions.index',
                'title' => 'Quản lý quyền',
                'description' => 'Tra cứu các quyền chức năng được cấu hình trong hệ thống.',
                'icon' => 'permissions',
                'color' => 'amber',
            ],
        ];

        $title = 'Tổng quan Dashboard';
        $today = today();

        $orderStats = DB::table('orders')
            ->whereDate('created_at', $today)
            ->when($branchId !== null, fn ($query) => $query->where('branch_id', $branchId))
            ->select([
                DB::raw('SUM(total_amount) as total_revenue'),
                DB::raw('COUNT(id) as total_orders'),
                DB::raw("SUM(CASE WHEN TIME(created_at) >= '06:00:00' AND TIME(created_at) < '11:00:00' THEN total_amount ELSE 0 END) as ca_sang"),
                DB::raw("SUM(CASE WHEN TIME(created_at) >= '11:00:00' AND TIME(created_at) < '17:00:00' THEN total_amount ELSE 0 END) as ca_trua"),
                DB::raw("SUM(CASE WHEN TIME(created_at) >= '17:00:00' AND TIME(created_at) < '22:00:00' THEN total_amount ELSE 0 END) as ca_toi"),
            ])
            ->first();

        $doanhThu = $orderStats->total_revenue ?? 0;
        $tongDon = $orderStats->total_orders ?? 0;
        $chartData = [
            (float) ($orderStats->ca_sang ?? 0),
            (float) ($orderStats->ca_trua ?? 0),
            (float) ($orderStats->ca_toi ?? 0),
        ];

        $danhSachBan = DB::table('dining_tables')
            ->when($branchId !== null, fn ($query) => $query->where('branch_id', $branchId))
            ->select('code as name', 'status')
            ->get();
        $donHangs = DB::table('orders')
            ->when($branchId !== null, fn ($query) => $query->where('branch_id', $branchId))
            ->latest()
            ->take(5)
            ->get();

        $thongKeMon = DB::table('order_items')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->join('menu_items', 'order_items.menu_item_id', '=', 'menu_items.id')
            ->whereDate('orders.created_at', $today)
            ->when($branchId !== null, fn ($query) => $query->where('orders.branch_id', $branchId))
            ->where('order_items.status', '!=', 'cancelled')
            ->select(
                'menu_items.id as dish_id',
                'menu_items.name as dish_name',
                'menu_items.base_price as dish_price',
                DB::raw('SUM(order_items.quantity) as total_qty'),
                DB::raw('SUM(order_items.line_total) as total_revenue')
            )
            ->groupBy('menu_items.id', 'menu_items.name', 'menu_items.base_price')
            ->orderByDesc('total_qty')
            ->get()
            ->map(function ($item) {
                $item->dish = (object)[
                    'name' => $item->dish_name,
                    'price' => $item->dish_price ?? 0
                ];
                return $item;
            });

        $monBanChay = $thongKeMon->first();
        $monItOrder = $thongKeMon->last();
        $branches = $this->selectableBranches($user);

        return view('admin.dashboard', compact(
            'user',
            'currentRoles',
            'totalUsers',
            'activeUsers',
            'totalRoles',
            'totalPermissions',
            'recentActivities',
            'activityUsers',
            'actionLabels',
            'modules',
            'doanhThu',
            'tongDon',
            'danhSachBan',
            'donHangs',
            'monBanChay',
            'monItOrder',
            'chartData',
            'branches',
            'branchId',
            'title'
        ));
    }

    /**
     * Trang Báo cáo chi tiết (Đã tích hợp sẵn dữ liệu bảo quản thực phẩm & thời gian nhập)
     */
    public function reports(Request $request)
    {
        $branchId = $this->resolveBranchId($request);
        if ($branchId === false) {
            return view('branches.select', [
                'branches' => $this->selectableBranches($request->user()),
                'action' => route('admin.reports'),
            ]);
        }

        $fromDate = $request->input('from_date', now()->subDays(30)->toDateString());
        $toDate = $request->input('to_date', now()->toDateString());
        $title = 'Báo cáo chi tiết';

        $tongDoanhThuKy = DB::table('orders')
            ->whereBetween(DB::raw('DATE(created_at)'), [$fromDate, $toDate])
            ->when($branchId !== null, fn ($query) => $query->where('branch_id', $branchId))
            ->sum('total_amount');

        $tongSoTo = DB::table('order_items')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->whereBetween(DB::raw('DATE(orders.created_at)'), [$fromDate, $toDate])
            ->when($branchId !== null, fn ($query) => $query->where('orders.branch_id', $branchId))
            ->sum('order_items.quantity');

        $topMonAn = DB::table('order_items')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->join('menu_items', 'order_items.menu_item_id', '=', 'menu_items.id')
            ->whereBetween(DB::raw('DATE(orders.created_at)'), [$fromDate, $toDate])
            ->when($branchId !== null, fn ($query) => $query->where('orders.branch_id', $branchId))
            ->where('order_items.status', '!=', 'cancelled')
            ->select(
                'menu_items.id as dish_id',
                'menu_items.name as dish_name',
                'menu_items.base_price as dish_price',
                DB::raw('SUM(order_items.quantity) as total_qty'),
                DB::raw('SUM(order_items.line_total) as total_revenue')
            )
            ->groupBy('menu_items.id', 'menu_items.name', 'menu_items.base_price')
            ->orderByDesc('total_qty')
            ->get()
            ->map(function ($item) {
                $item->dish = (object)[
                    'name' => $item->dish_name,
                    'price' => $item->dish_price ?? 0
                ];
                return $item;
            });

        $ingredients = $this->stockReportRows($branchId);

        return view('admin.reports', compact('fromDate', 'toDate', 'tongDoanhThuKy', 'tongSoTo', 'topMonAn', 'ingredients', 'title'));
    }

    /**
     * Thống kê phương thức thanh toán (Tiền mặt vs Chuyển khoản) cho Dashboard
     */
    public function getPaymentMethodStats(Request $request)
    {
        $branchId = $this->resolveBranchId($request);
        abort_if($branchId === false, 422, 'Vui lòng chọn chi nhánh trước khi xem thống kê.');

        $stats = Order::query()
            ->when($branchId !== null, fn ($query) => $query->where('branch_id', $branchId))
            ->select('payment_method', DB::raw('count(*) as total'))
            ->groupBy('payment_method')
            ->pluck('total', 'payment_method');

        return response()->json([
            'status' => 'success',
            'data' => [
                'cash' => (int) ($stats['cash'] ?? 0),
                'transfer' => (int) ($stats['transfer'] ?? 0),
            ],
        ]);
    }

   
    /**
     * Báo cáo tình trạng bảo quản thực phẩm dự phòng riêng nếu cần gọi API
     */
    public function getFoodStorageReport(Request $request)
    {
        $branchId = $this->resolveBranchId($request);
        abort_if($branchId === false, 422, 'Vui lòng chọn chi nhánh trước khi xem báo cáo.');

        $ingredients = $this->stockReportRows($branchId);

        return view('admin.reports', compact('ingredients'));
    }

    private function resolveBranchId(Request $request): int|false|null
    {
        $user = $request->user();
        abort_unless($user instanceof User, 401);
        $branchIds = $this->branchAccess->accessibleBranchIds($user, [ConstantHelper::PERM_DASHBOARD_VIEW]);

        if (is_array($branchIds) && $branchIds === []) {
            abort(403, 'Bạn chưa được cấp quyền xem chi nhánh nào.');
        }

        if ($request->filled('branch_id')) {
            $branchId = $request->integer('branch_id');
            $this->branchAccess->authorizeBranch($user, $branchId, [ConstantHelper::PERM_DASHBOARD_VIEW]);
            $request->session()->put('branch_id', $branchId);

            return $branchId;
        }

        if (! is_array($branchIds)) {
            return null;
        }

        $sessionBranchId = $request->session()->get('branch_id');
        if (is_numeric($sessionBranchId)) {
            $sessionBranchId = (int) $sessionBranchId;
            if ($this->branchAccess->canAccessBranch($user, $sessionBranchId, [ConstantHelper::PERM_DASHBOARD_VIEW])) {
                return $sessionBranchId;
            }

            $request->session()->forget('branch_id');
        }

        if (count($branchIds) === 1) {
            $request->session()->put('branch_id', $branchIds[0]);

            return $branchIds[0];
        }

        return false;
    }

    private function selectableBranches(User $user): \Illuminate\Database\Eloquent\Collection
    {
        $branchIds = $this->branchAccess->accessibleBranchIds($user, [ConstantHelper::PERM_DASHBOARD_VIEW]);

        return Branch::query()
            ->where('status', BranchStatus::ACTIVE)
            ->when(is_array($branchIds), fn ($query) => $query->whereIn('id', $branchIds))
            ->orderBy('name')
            ->get();
    }

    private function stockReportRows(?int $branchId)
    {
        return DB::table('stock_levels')
            ->join('warehouses', 'stock_levels.warehouse_id', '=', 'warehouses.id')
            ->join('ingredients', 'stock_levels.ingredient_id', '=', 'ingredients.id')
            ->when($branchId !== null, fn ($query) => $query->where('warehouses.branch_id', $branchId))
            ->select(
                'ingredients.name',
                'stock_levels.quantity as stock_quantity',
                'ingredients.base_uom as unit',
                DB::raw('NULL as import_date'),
                DB::raw('NULL as expiry_date'),
                DB::raw('NULL as quality_status')
            )
            ->orderBy('ingredients.name')
            ->get();
    }
}
