<?php

declare(strict_types=1);

namespace Tests\Feature\Concerns;

use App\Enums\BranchStatus;
use App\Enums\ItemType;
use App\Enums\StationCode;
use App\Enums\UserStatus;
use App\Models\Branch;
use App\Models\Category;
use App\Models\MenuItem;
use App\Models\Role;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Dữ liệu dùng chung cho test module Thực đơn: chi nhánh, người dùng theo vai trò, danh mục và món mẫu.
 */
trait BuildsMenuFixtures
{
    private function createBranch(string $code): Branch
    {
        return Branch::query()->create([
            'code' => $code,
            'name' => 'Chi nhánh '.$code,
            'timezone' => 'Asia/Ho_Chi_Minh',
            'status' => BranchStatus::ACTIVE,
        ]);
    }

    private function createUserWithRole(string $roleCode, ?Branch $branch = null): User
    {
        $user = User::query()->create([
            'username' => Str::lower(Str::random(10)),
            'full_name' => 'Người dùng '.$roleCode,
            'password' => Hash::make('Password123!'),
            'status' => UserStatus::ACTIVE,
        ]);

        UserRole::query()->create([
            'user_id' => $user->id,
            'role_id' => Role::query()->where('code', $roleCode)->value('id'),
            'scope_type' => $branch === null ? 'ALL' : 'BRANCH',
            'scope_id' => $branch?->id,
            'valid_from' => now()->subMinute(),
            'granted_by' => null,
        ]);

        return $user;
    }

    private function createCategory(string $name, ?Category $parent = null, bool $active = true): Category
    {
        return Category::query()->create([
            'name' => $name,
            'parent_id' => $parent?->id,
            'sort_order' => 0,
            'is_active' => $active,
        ]);
    }

    private function createMenuItem(string $name = 'Phở tái', string $sku = 'PHO-TAI', ?Category $category = null, int $price = 50000): MenuItem
    {
        return MenuItem::query()->create([
            'category_id' => ($category ?? $this->createCategory('Phở '.Str::random(4)))->id,
            'sku' => $sku,
            'name' => $name,
            'item_type' => ItemType::DISH,
            'station_code' => StationCode::PHO,
            'base_price' => $price,
            'tax_rate' => 8,
            'prep_time_seconds' => 180,
            'is_active' => true,
        ]);
    }
}
