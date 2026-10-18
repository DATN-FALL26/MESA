<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Helpers\ConstantHelper;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * ReferenceSeeder – dữ liệu danh mục hệ thống (idempotent).
 *
 * Chạy lại nhiều lần không nhân đôi dữ liệu.
 * Thứ tự: departments → permissions → roles → role_permissions.
 */
class ReferenceSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedDepartments();
        $this->seedPermissions();
        $this->seedRoles();
        $this->seedRolePermissions();
        $this->assignCustomerRoleToUsersWithoutActiveRole();
    }

    // --------------------------------------------------------
    // 1. Phòng ban
    // --------------------------------------------------------
    private function seedDepartments(): void
    {
        $rows = [
            ['code' => ConstantHelper::DEPT_SERVICE,   'name' => 'Phục vụ',      'sort_order' => 1],
            ['code' => ConstantHelper::DEPT_KITCHEN,   'name' => 'Bếp',          'sort_order' => 2],
            ['code' => ConstantHelper::DEPT_CASHIER,   'name' => 'Thu ngân',     'sort_order' => 3],
            ['code' => ConstantHelper::DEPT_WAREHOUSE, 'name' => 'Kho',          'sort_order' => 4],
            ['code' => ConstantHelper::DEPT_OFFICE,    'name' => 'Văn phòng',    'sort_order' => 5],
        ];

        foreach ($rows as $row) {
            DB::table(ConstantHelper::TABLE_DEPARTMENTS)->upsert(
                array_merge($row, ['is_active' => true]),
                ['code'],
                ['name', 'sort_order', 'is_active']
            );
        }
    }

    // --------------------------------------------------------
    // 2. Quyền hạn
    // --------------------------------------------------------
    private function seedPermissions(): void
    {
        $rows = [];
        foreach (ConstantHelper::allPermissions() as $code => [$module, $name]) {
            $rows[] = [
                'code' => $code,
                'module' => $module,
                'name' => $name,
            ];
        }

        DB::table(ConstantHelper::TABLE_PERMISSIONS)->upsert(
            $rows,
            ['code'],
            ['module', 'name']
        );
    }

    // --------------------------------------------------------
    // 3. Chức vụ (roles)
    // --------------------------------------------------------
    private function seedRoles(): void
    {
        $rows = [
            [
                'code' => ConstantHelper::ROLE_ADMIN,
                'name' => 'Quản trị viên hệ thống',
                'description' => 'Toàn quyền hệ thống',
                'is_system' => true,
            ],
            [
                'code' => ConstantHelper::ROLE_BRANCH_MANAGER,
                'name' => 'Quản lý chi nhánh',
                'description' => 'Quản lý toàn bộ nghiệp vụ chi nhánh',
                'is_system' => true,
            ],
            [
                'code' => ConstantHelper::ROLE_CASHIER,
                'name' => 'Thu ngân',
                'description' => 'Thanh toán, xuất hóa đơn, tạo order',
                'is_system' => true,
            ],
            [
                'code' => ConstantHelper::ROLE_WAITER,
                'name' => 'Nhân viên phục vụ',
                'description' => 'Gọi món, phục vụ, yêu cầu thanh toán',
                'is_system' => true,
            ],
            [
                'code' => ConstantHelper::ROLE_KITCHEN,
                'name' => 'Nhân viên bếp',
                'description' => 'Xem và cập nhật trạng thái chế biến',
                'is_system' => true,
            ],
            [
                'code' => ConstantHelper::ROLE_WAREHOUSE,
                'name' => 'Nhân viên kho',
                'description' => 'Nhập xuất kho, kiểm kê, quản lý công thức',
                'is_system' => true,
            ],
            [
                'code' => ConstantHelper::ROLE_CUSTOMER,
                'name' => 'Khách hàng',
                'description' => 'Tài khoản khách hàng tự đăng ký',
                'is_system' => true,
            ],
        ];

        DB::table(ConstantHelper::TABLE_ROLES)->upsert(
            $rows,
            ['code'],
            ['name', 'description', 'is_system']
        );
    }

    // --------------------------------------------------------
    // 4. Ma trận role → permission
    // --------------------------------------------------------
    private function seedRolePermissions(): void
    {
        // Lấy id của roles và permissions từ DB (tránh hardcode)
        $roleIds = DB::table(ConstantHelper::TABLE_ROLES)
            ->pluck('id', 'code');

        $permIds = DB::table(ConstantHelper::TABLE_PERMISSIONS)
            ->pluck('id', 'code');

        $matrix = $this->buildPermissionMatrix();

        // Xóa và chèn lại theo từng role để idempotent
        foreach ($matrix as $roleCode => $permCodes) {
            $roleId = $roleIds[$roleCode] ?? null;
            if ($roleId === null) {
                continue;
            }

            $rows = [];
            foreach ($permCodes as $permCode) {
                $permId = $permIds[$permCode] ?? null;
                if ($permId !== null) {
                    $rows[] = ['role_id' => $roleId, 'permission_id' => $permId];
                }
            }

            // Xóa quyền cũ của role này rồi chèn lại (pivot bảng no-ts)
            DB::table(ConstantHelper::TABLE_ROLE_PERMISSIONS)
                ->where('role_id', $roleId)
                ->delete();

            if (! empty($rows)) {
                DB::table(ConstantHelper::TABLE_ROLE_PERMISSIONS)->insert($rows);
            }
        }
    }

    /**
     * Ma trận role → danh sách permission code theo task2.txt.
     *
     * @return array<string, list<string>>
     */
    private function buildPermissionMatrix(): array
    {
        $all = array_keys(ConstantHelper::allPermissions());

        return [
            // ADMIN: toàn bộ
            ConstantHelper::ROLE_ADMIN => $all,

            // BRANCH_MANAGER: toàn bộ nghiệp vụ chi nhánh, trừ branch.manage / role.manage / department.manage
            ConstantHelper::ROLE_BRANCH_MANAGER => array_values(array_filter($all, static function (string $code): bool {
                return ! in_array($code, [
                    ConstantHelper::PERM_BRANCH_MANAGE,
                    ConstantHelper::PERM_ROLE_MANAGE,
                    ConstantHelper::PERM_DEPARTMENT_MANAGE,
                    // user.manage không nêu trong BRANCH_MANAGER
                    ConstantHelper::PERM_USER_MANAGE,
                    ConstantHelper::PERM_PERMISSION_VIEW,
                    ConstantHelper::PERM_ROLES_VIEW,
                    ConstantHelper::PERM_PERMISSIONS_VIEW,
                    ConstantHelper::PERM_USERS_CREATE,
                    ConstantHelper::PERM_USERS_UPDATE,
                    ConstantHelper::PERM_USERS_DELETE,
                    ConstantHelper::PERM_USERS_ASSIGN_ROLES,
                    ConstantHelper::PERM_ROLES_CREATE,
                    ConstantHelper::PERM_ROLES_UPDATE,
                    ConstantHelper::PERM_ROLES_DELETE,
                    ConstantHelper::PERM_ROLES_ASSIGN_PERMISSIONS,
                    ConstantHelper::PERM_PERMISSIONS_CREATE,
                    ConstantHelper::PERM_PERMISSIONS_UPDATE,
                    ConstantHelper::PERM_PERMISSIONS_DELETE,
                ], true);
            })),

            // CASHIER
            ConstantHelper::ROLE_CASHIER => [
                ConstantHelper::PERM_DASHBOARD_VIEW,
                ConstantHelper::PERM_PROFILE_VIEW,
                ConstantHelper::PERM_PROFILE_UPDATE,
                ConstantHelper::PERM_TABLE_VIEW,
                ConstantHelper::PERM_SESSION_MANAGE,
                ConstantHelper::PERM_ORDER_VIEW,
                ConstantHelper::PERM_ORDER_CREATE,
                ConstantHelper::PERM_ORDER_UPDATE,
                ConstantHelper::PERM_ORDER_SERVE,
                ConstantHelper::PERM_PAYMENT_REQUEST,
                ConstantHelper::PERM_PAYMENT_CONFIRM,
                ConstantHelper::PERM_INVOICE_PRINT,
                ConstantHelper::PERM_KITCHEN_PRINT,
                ConstantHelper::PERM_MENU_VIEW,
            ],

            // WAITER
            ConstantHelper::ROLE_WAITER => [
                ConstantHelper::PERM_DASHBOARD_VIEW,
                ConstantHelper::PERM_PROFILE_VIEW,
                ConstantHelper::PERM_PROFILE_UPDATE,
                ConstantHelper::PERM_TABLE_VIEW,
                ConstantHelper::PERM_SESSION_MANAGE,
                ConstantHelper::PERM_ORDER_VIEW,
                ConstantHelper::PERM_ORDER_CREATE,
                ConstantHelper::PERM_ORDER_UPDATE,
                ConstantHelper::PERM_ORDER_CANCEL,
                ConstantHelper::PERM_ORDER_SERVE,
                ConstantHelper::PERM_PAYMENT_REQUEST,
                ConstantHelper::PERM_MENU_VIEW,
            ],

            // KITCHEN
            ConstantHelper::ROLE_KITCHEN => [
                ConstantHelper::PERM_DASHBOARD_VIEW,
                ConstantHelper::PERM_PROFILE_VIEW,
                ConstantHelper::PERM_PROFILE_UPDATE,
                ConstantHelper::PERM_KITCHEN_VIEW,
                ConstantHelper::PERM_KITCHEN_UPDATE_STATUS,
                ConstantHelper::PERM_KITCHEN_PRINT,
                ConstantHelper::PERM_ORDER_VIEW,
                ConstantHelper::PERM_MENU_VIEW,
                ConstantHelper::PERM_MENU_AVAILABILITY_UPDATE,
                ConstantHelper::PERM_INVENTORY_VIEW,
            ],

            // WAREHOUSE
            ConstantHelper::ROLE_WAREHOUSE => [
                ConstantHelper::PERM_DASHBOARD_VIEW,
                ConstantHelper::PERM_PROFILE_VIEW,
                ConstantHelper::PERM_PROFILE_UPDATE,
                ConstantHelper::PERM_INVENTORY_VIEW,
                ConstantHelper::PERM_INVENTORY_RECEIVE_ISSUE,
                ConstantHelper::PERM_INVENTORY_ADJUST,
                ConstantHelper::PERM_RECIPE_MANAGE,
                ConstantHelper::PERM_SUPPLIER_MANAGE,
                ConstantHelper::PERM_PURCHASE_CREATE,
                ConstantHelper::PERM_REPORT_VIEW,
            ],

            // CUSTOMER: quyền cơ bản cho người dùng tự đăng ký.
            ConstantHelper::ROLE_CUSTOMER => [
                ConstantHelper::PERM_PROFILE_VIEW,
                ConstantHelper::PERM_PROFILE_UPDATE,
                ConstantHelper::PERM_DASHBOARD_VIEW,
            ],
        ];
    }

    private function assignCustomerRoleToUsersWithoutActiveRole(): void
    {
        $roleId = DB::table(ConstantHelper::TABLE_ROLES)
            ->where('code', ConstantHelper::ROLE_CUSTOMER)
            ->value('id');

        if ($roleId === null) {
            throw new \RuntimeException('The default customer role was not seeded.');
        }

        $usersWithoutActiveRole = DB::table(ConstantHelper::TABLE_USERS)
            ->whereNull('deleted_at')
            ->whereNotExists(function ($query): void {
                $query->selectRaw('1')
                    ->from(ConstantHelper::TABLE_USER_ROLES)
                    ->whereColumn('user_roles.user_id', 'users.id')
                    ->where('user_roles.valid_from', '<=', now())
                    ->where(function ($validityQuery): void {
                        $validityQuery
                            ->whereNull('user_roles.valid_to')
                            ->orWhere('user_roles.valid_to', '>=', now());
                    });
            })
            ->pluck('id');

        foreach ($usersWithoutActiveRole as $userId) {
            $existingAssignment = DB::table(ConstantHelper::TABLE_USER_ROLES)
                ->where('user_id', $userId)
                ->where('role_id', $roleId)
                ->where('scope_type', 'ALL')
                ->whereNull('scope_id')
                ->first();

            if ($existingAssignment !== null) {
                DB::table(ConstantHelper::TABLE_USER_ROLES)
                    ->where('id', $existingAssignment->id)
                    ->update([
                        'valid_from' => now(),
                        'valid_to' => null,
                        'updated_at' => now(),
                    ]);

                continue;
            }

            DB::table(ConstantHelper::TABLE_USER_ROLES)->insert([
                'user_id' => $userId,
                'role_id' => $roleId,
                'scope_type' => 'ALL',
                'scope_id' => null,
                'valid_from' => now(),
                'valid_to' => null,
                'granted_by' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
