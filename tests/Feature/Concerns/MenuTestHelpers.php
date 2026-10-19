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
use Database\Seeders\ReferenceSeeder;
use Illuminate\Support\Facades\Hash;

trait MenuTestHelpers
{
    protected function setUpMenuFixtures(): void
    {
        $this->seed(ReferenceSeeder::class);
        $this->withoutVite();
    }

    protected function userWithRole(string $username, string $roleCode, string $scopeType = 'ALL', ?int $scopeId = null): User
    {
        $user = User::query()->create([
            'username' => $username,
            'full_name' => ucfirst($username),
            'password' => Hash::make('Password123!'),
            'status' => UserStatus::ACTIVE,
        ]);

        UserRole::query()->create([
            'user_id' => $user->getKey(),
            'role_id' => Role::query()->where('code', $roleCode)->firstOrFail()->getKey(),
            'scope_type' => $scopeType,
            'scope_id' => $scopeId,
            'valid_from' => now()->subMinute(),
            'granted_by' => null,
        ]);

        return $user;
    }

    protected function makeBranch(string $code): Branch
    {
        return Branch::query()->create([
            'code' => $code,
            'name' => "Chi nhánh {$code}",
            'timezone' => 'Asia/Ho_Chi_Minh',
            'status' => BranchStatus::ACTIVE,
        ]);
    }

    protected function makeCategory(string $name): Category
    {
        return Category::query()->create(['name' => $name, 'is_active' => true]);
    }

    protected function makeItem(): MenuItem
    {
        return MenuItem::query()->create([
            'category_id' => $this->makeCategory('Phở')->id,
            'sku' => 'PHO-TAI',
            'name' => 'Phở tái',
            'item_type' => ItemType::DISH,
            'station_code' => StationCode::PHO,
            'base_price' => 50000,
            'tax_rate' => 8,
            'is_active' => true,
        ]);
    }
}
