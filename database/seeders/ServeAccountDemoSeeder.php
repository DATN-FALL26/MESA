<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\ScopeType;
use App\Enums\UserStatus;
use App\Helpers\ConstantHelper;
use App\Models\Branch;
use App\Models\Department;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

class ServeAccountDemoSeeder extends Seeder
{
    private const USERNAME = 'nhanvientrangmon';

    private const EMAIL = 'Nhanvientrangmon@gmail.com';

    private const PASSWORD = 'Password123!';

    private const ROLE_CODE = 'QUAN_LY_RA_MON';

    private const BRANCH_CODE = 'PHO01';

    private const EMPLOYEE_CODE = 'SERVE001';

    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            throw new RuntimeException('Serve demo account can only be seeded in local or testing environments.');
        }

        $this->call(ReferenceSeeder::class);

        $branch = Branch::query()
            ->where('code', self::BRANCH_CODE)
            ->where('status', 'active')
            ->first();
        $department = Department::query()
            ->where('code', ConstantHelper::DEPT_SERVICE)
            ->first();

        if ($branch === null || $department === null) {
            throw new RuntimeException('Thiếu chi nhánh PHO01 hoặc phòng ban Phục vụ để tạo tài khoản ra món.');
        }

        $permissions = Permission::query()
            ->whereIn('code', [
                'dashboard.view',
                'profile.view',
                'profile.update',
                'order.serve',
            ])
            ->get();

        if ($permissions->count() !== 4) {
            throw new RuntimeException('Thiếu quyền cần thiết để tạo tài khoản ra món.');
        }

        $role = Role::query()->updateOrCreate(
            ['code' => self::ROLE_CODE],
            [
                'name' => 'Quản lý ra món',
                'description' => 'Chỉ xem bảng điều khiển, hồ sơ cá nhân và xác nhận phục vụ món tại chi nhánh được gán.',
                'is_system' => false,
            ]
        );
        $role->permissions()->sync($permissions->pluck('id')->all());

        $emailOwner = User::withTrashed()
            ->whereRaw('LOWER(email) = ?', [strtolower(self::EMAIL)])
            ->where('username', '!=', self::USERNAME)
            ->exists();
        if ($emailOwner) {
            throw new RuntimeException('Email tài khoản ra món đang thuộc về tài khoản khác.');
        }

        $user = User::withTrashed()->where('username', self::USERNAME)->first();
        if ($user === null) {
            $user = User::query()->create([
                'username' => self::USERNAME,
                'employee_code' => self::EMPLOYEE_CODE,
                'full_name' => 'Nhân viên quản lý món',
                'email' => self::EMAIL,
                'department_id' => $department->id,
                'password' => Hash::make(self::PASSWORD),
                'status' => UserStatus::ACTIVE,
            ]);
        } else {
            $user->forceFill([
                'full_name' => 'Nhân viên quản lý món',
                'email' => self::EMAIL,
                'department_id' => $department->id,
                'password' => Hash::make(self::PASSWORD),
                'status' => UserStatus::ACTIVE,
                'deleted_at' => null,
            ])->save();
        }

        $now = now();
        $revokedAt = $now->copy()->subSecond();
        $user->userRoles()
            ->where('valid_from', '<=', $now)
            ->where(fn ($query) => $query
                ->whereNull('valid_to')
                ->orWhere('valid_to', '>=', $now))
            ->where(function ($query) use ($role, $branch): void {
                $query
                    ->where('role_id', '!=', $role->id)
                    ->orWhere('scope_type', '!=', ScopeType::BRANCH->value)
                    ->orWhere('scope_id', '!=', $branch->id);
            })
            ->update([
                'valid_to' => $revokedAt,
                'updated_at' => $now,
            ]);

        $assignment = UserRole::query()
            ->where('user_id', $user->id)
            ->where('role_id', $role->id)
            ->where('scope_type', ScopeType::BRANCH)
            ->where('scope_id', $branch->id)
            ->first();

        if ($assignment === null) {
            UserRole::query()->create([
                'user_id' => $user->id,
                'role_id' => $role->id,
                'scope_type' => ScopeType::BRANCH,
                'scope_id' => $branch->id,
                'valid_from' => $now,
                'valid_to' => null,
                'granted_by' => null,
            ]);
        } else {
            $assignment->forceFill([
                'valid_from' => $now,
                'valid_to' => null,
                'granted_by' => null,
                'updated_at' => $now,
            ])->save();
        }

        $this->command?->info(
            'Đã tạo tài khoản ra món '.$user->username.' cho PHO01. Mật khẩu demo: '.self::PASSWORD
        );
    }
}
