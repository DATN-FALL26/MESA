<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\UserStatus;
use App\Helpers\ConstantHelper;
use App\Models\Role;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AuthDemoSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            throw new \RuntimeException('Auth demo accounts can only be seeded in local or testing environments.');
        }

        $this->call(ReferenceSeeder::class);

        $departmentIds = DB::table(ConstantHelper::TABLE_DEPARTMENTS)->pluck('id', 'code');
        $roleIds = Role::query()->pluck('id', 'code');
        $password = Hash::make('Password123!');

        $accounts = [
            [
                'username' => 'admin',
                'employee_code' => 'AUTH001',
                'full_name' => 'Quản trị viên demo',
                'email' => 'admin.auth@mesa.test',
                'department_code' => ConstantHelper::DEPT_OFFICE,
                'role_code' => ConstantHelper::ROLE_ADMIN,
            ],
            [
                'username' => 'manager',
                'employee_code' => 'AUTH002',
                'full_name' => 'Quản lý demo',
                'email' => 'manager.auth@mesa.test',
                'department_code' => ConstantHelper::DEPT_OFFICE,
                'role_code' => ConstantHelper::ROLE_BRANCH_MANAGER,
            ],
            [
                'username' => 'cashier1',
                'employee_code' => 'AUTH003',
                'full_name' => 'Thu ngân demo 1',
                'email' => 'cashier1.auth@mesa.test',
                'department_code' => ConstantHelper::DEPT_CASHIER,
                'role_code' => ConstantHelper::ROLE_CASHIER,
            ],
            [
                'username' => 'waiter1',
                'employee_code' => 'AUTH004',
                'full_name' => 'Nhân viên phục vụ demo',
                'email' => 'waiter1.auth@mesa.test',
                'department_code' => ConstantHelper::DEPT_SERVICE,
                'role_code' => ConstantHelper::ROLE_WAITER,
            ],
            [
                'username' => 'kitchen1',
                'employee_code' => 'AUTH005',
                'full_name' => 'Nhân viên bếp demo',
                'email' => 'kitchen1.auth@mesa.test',
                'department_code' => ConstantHelper::DEPT_KITCHEN,
                'role_code' => ConstantHelper::ROLE_KITCHEN,
            ],
            [
                'username' => 'warehouse1',
                'employee_code' => 'AUTH006',
                'full_name' => 'Nhân viên kho demo',
                'email' => 'warehouse1.auth@mesa.test',
                'department_code' => ConstantHelper::DEPT_WAREHOUSE,
                'role_code' => ConstantHelper::ROLE_WAREHOUSE,
            ],
            [
                'username' => 'cashier2',
                'employee_code' => 'AUTH007',
                'full_name' => 'Thu ngân demo 2',
                'email' => 'cashier2.auth@mesa.test',
                'department_code' => ConstantHelper::DEPT_CASHIER,
                'role_code' => ConstantHelper::ROLE_CASHIER,
            ],
        ];

        DB::transaction(function () use ($accounts, $departmentIds, $roleIds, $password): void {
            foreach ($accounts as $account) {
                $user = User::withTrashed()->updateOrCreate(
                    ['username' => $account['username']],
                    [
                        'employee_code' => $account['employee_code'],
                        'full_name' => $account['full_name'],
                        'email' => $account['email'],
                        'department_id' => $departmentIds[$account['department_code']],
                        'password' => $password,
                        'status' => UserStatus::ACTIVE,
                        'deleted_at' => null,
                    ]
                );

                $this->assignRole(
                    $user,
                    (int) $roleIds[$account['role_code']],
                );
            }
        });
    }

    private function assignRole(User $user, int $roleId): void
    {
        $now = now();
        $revokedAt = $now->copy()->subSecond();

        $user->userRoles()
            ->where('valid_from', '<=', $now)
            ->where(fn ($query) => $query
                ->whereNull('valid_to')
                ->orWhere('valid_to', '>=', $now))
            ->where('role_id', '!=', $roleId)
            ->update(['valid_to' => $revokedAt, 'updated_at' => $now]);

        $assignment = UserRole::query()
            ->where('user_id', $user->getKey())
            ->where('role_id', $roleId)
            ->where('scope_type', 'ALL')
            ->whereNull('scope_id')
            ->first();

        if ($assignment === null) {
            UserRole::query()->create([
                'user_id' => $user->getKey(),
                'role_id' => $roleId,
                'scope_type' => 'ALL',
                'scope_id' => null,
                'valid_from' => $now,
                'granted_by' => null,
            ]);

            return;
        }

        $assignment->forceFill([
            'valid_from' => $now,
            'valid_to' => null,
            'granted_by' => null,
        ])->save();
    }
}
