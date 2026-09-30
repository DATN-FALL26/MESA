<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Branch;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Facades\Hash;

class RoleAndUserSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // 1. Tạo các Cơ sở (Branches) mẫu
        $branch1 = Branch::create([
            'name' => 'I-Pho Cơ sở 1 - Cầu Giấy',
            'address' => '123 Xuân Thủy, Cầu Giấy, Hà Nội',
            'phone' => '0987654321',
            'status' => true,
        ]);

        $branch2 = Branch::create([
            'name' => 'I-Pho Cơ sở 2 - Đống Đa',
            'address' => '456 Chùa Bộc, Đống Đa, Hà Nội',
            'phone' => '0912345678',
            'status' => true,
        ]);

        // 2. Tạo các Roles (Vai trò hệ thống)
        $roleCEO = Role::create(['name' => 'ceo']);
        $roleManager = Role::create(['name' => 'branch-manager']);
        $roleWaiter = Role::create(['name' => 'waiter']);
        $roleKitchen = Role::create(['name' => 'kitchen']);
        $roleWarehouse = Role::create(['name' => 'warehouse']);
        $roleCustomer = Role::create(['name' => 'customer']);

        // 3. Tạo tài khoản mẫu tương ứng cho từng vai trò
        // Tài khoản CEO (Quản lý toàn hệ thống, không gò bó 1 chi nhánh cụ thể)
        $ceo = User::create([
            'name' => 'Nguyễn Văn Hoàng (CEO)',
            'email' => 'ceo@mesa.com',
            'password' => Hash::make('12345678'),
            'branch_id' => null,
            'phone' => '0900000001',
        ]);
        $ceo->assignRole($roleCEO);

        // Tài khoản Quản lý cơ sở 1
        $manager = User::create([
            'name' => 'Trần Văn Quản Lý',
            'email' => 'manager1@mesa.com',
            'password' => Hash::make('12345678'),
            'branch_id' => $branch1->id,
            'phone' => '0900000002',
        ]);
        $manager->assignRole($roleManager);

        // Tài khoản Nhân viên Phục vụ cơ sở 1
        $waiter = User::create([
            'name' => 'Lê Thị Phục Vụ',
            'email' => 'waiter1@mesa.com',
            'password' => Hash::make('12345678'),
            'branch_id' => $branch1->id,
            'phone' => '0900000003',
        ]);
        $waiter->assignRole($roleWaiter);

        // Tài khoản Nhân viên Bếp cơ sở 1
        $kitchen = User::create([
            'name' => 'Đầu Bếp Chính',
            'email' => 'kitchen1@mesa.com',
            'password' => Hash::make('12345678'),
            'branch_id' => $branch1->id,
            'phone' => '0900000004',
        ]);
        $kitchen->assignRole($roleKitchen);

        // Tài khoản Nhân viên Kho cơ sở 1
        $warehouse = User::create([
            'name' => 'Phạm Văn Thủ Kho',
            'email' => 'warehouse1@mesa.com',
            'password' => Hash::make('12345678'),
            'branch_id' => $branch1->id,
            'phone' => '0900000005',
        ]);
        $warehouse->assignRole($roleWarehouse);
    }
}
