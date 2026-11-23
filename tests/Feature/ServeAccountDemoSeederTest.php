<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\ScopeType;
use App\Models\Branch;
use App\Models\Role;
use App\Models\User;
use App\Models\UserRole;
use Database\Seeders\ReferenceSeeder;
use Database\Seeders\SampleDataSeeder;
use Database\Seeders\ServeAccountDemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ServeAccountDemoSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_serve_account_seeder_is_idempotent_and_scoped_to_pho01(): void
    {
        $this->seed([
            ReferenceSeeder::class,
            SampleDataSeeder::class,
        ]);
        $this->seed(ServeAccountDemoSeeder::class);
        $this->seed(ServeAccountDemoSeeder::class);

        $user = User::where('username', 'nhanvientrangmon')->firstOrFail();
        $branch = Branch::where('code', 'PHO01')->firstOrFail();
        $role = Role::where('code', 'QUAN_LY_RA_MON')->firstOrFail();

        $this->assertSame('Nhanvientrangmon@gmail.com', $user->email);
        $this->assertTrue(Hash::check('Password123!', $user->password));

        $assignments = UserRole::where('user_id', $user->id)
            ->whereNull('valid_to')
            ->get();

        $this->assertCount(1, $assignments);
        $this->assertSame($role->id, $assignments->first()->role_id);
        $this->assertSame(ScopeType::BRANCH, $assignments->first()->scope_type);
        $this->assertSame($branch->id, $assignments->first()->scope_id);
        $this->assertTrue($role->permissions()->whereIn('code', [
            'dashboard.view',
            'profile.view',
            'profile.update',
            'order.serve',
        ])->count() === 4);
    }
}
