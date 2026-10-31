<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\BranchStatus;
use App\Enums\UserStatus;
use App\Helpers\ConstantHelper;
use App\Models\Branch;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Models\UserRole;
use Database\Seeders\ReferenceSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AuthFeatureTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(ReferenceSeeder::class);
    }

    public function test_guest_can_open_the_public_dashboard_without_signing_in(): void
    {
        Branch::query()->create([
            'code' => 'PUBLIC01',
            'name' => 'MESA Public Branch',
            'timezone' => 'Asia/Ho_Chi_Minh',
            'status' => BranchStatus::ACTIVE,
        ]);

        $this->withoutVite()
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('MESA Public Branch')
            ->assertSee('Đăng nhập nhân viên')
            ->assertDontSee('Quản lý tài khoản');

        $this->assertGuest();
    }

    public function test_guest_menu_and_table_qr_routes_are_removed(): void
    {
        $this->get('/menu/1')->assertNotFound();
        $this->get('/customer/table/1')->assertNotFound();
        $this->get('/customer/table/1/qr-link')->assertNotFound();

        $this->assertGuest();
    }

    public function test_customer_registration_endpoints_are_not_available(): void
    {
        $this->get('/register')->assertNotFound();
        $this->post('/register')->assertNotFound();
        $this->assertGuest();
    }

    public function test_reference_seeder_only_creates_internal_roles(): void
    {
        $this->assertEqualsCanonicalizing([
            ConstantHelper::ROLE_ADMIN,
            ConstantHelper::ROLE_BRANCH_MANAGER,
            ConstantHelper::ROLE_CASHIER,
            ConstantHelper::ROLE_WAITER,
            ConstantHelper::ROLE_KITCHEN,
            ConstantHelper::ROLE_WAREHOUSE,
        ], Role::query()->pluck('code')->all());
    }

    public function test_reference_seeder_removes_legacy_customer_role_and_disables_customer_only_accounts(): void
    {
        $customerRole = Role::query()->create([
            'code' => 'CUSTOMER',
            'name' => 'Khách hàng',
            'is_system' => true,
        ]);
        $customerPermission = Permission::query()->create([
            'code' => 'customer.profile.view',
            'module' => 'customer',
            'name' => 'Customer profile view',
        ]);
        DB::table('role_permissions')->insert([
            'role_id' => $customerRole->getKey(),
            'permission_id' => $customerPermission->getKey(),
        ]);
        $user = User::query()->create([
            'username' => 'legacyuser',
            'full_name' => 'Legacy User',
            'password' => Hash::make('Password123!'),
            'status' => UserStatus::ACTIVE,
        ]);
        UserRole::query()->create([
            'user_id' => $user->getKey(),
            'role_id' => $customerRole->getKey(),
            'scope_type' => 'ALL',
            'scope_id' => null,
            'valid_from' => now()->subMinute(),
        ]);

        $this->seed(ReferenceSeeder::class);

        $legacyUser = User::withTrashed()->findOrFail($user->getKey());
        $this->assertSame(UserStatus::INACTIVE, $legacyUser->status);
        $this->assertNotNull($legacyUser->deleted_at);
        $this->assertDatabaseMissing('roles', ['code' => 'CUSTOMER']);
        $this->assertDatabaseMissing('permissions', ['code' => 'customer.profile.view']);
        $this->assertDatabaseMissing('user_roles', ['user_id' => $user->getKey()]);
    }

    public function test_user_can_login_with_username(): void
    {
        $this->createUser([
            'username' => 'bob',
            'full_name' => 'Bob Tran',
            'email' => 'bob@example.com',
            'password' => Hash::make('Password123!'),
            'status' => UserStatus::ACTIVE,
        ]);

        $response = $this->post('/login', [
            'login' => 'bob',
            'password' => 'Password123!',
        ]);

        $response->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs(User::first());
    }

    public function test_inactive_user_cannot_login(): void
    {
        $this->createUser([
            'username' => 'inactive',
            'full_name' => 'Inactive User',
            'password' => Hash::make('Password123!'),
            'status' => UserStatus::INACTIVE,
        ]);

        $this->post('/login', [
            'login' => 'inactive',
            'password' => 'Password123!',
        ])->assertSessionHasErrors('login');

        $this->assertGuest();
    }

    public function test_user_without_an_active_internal_role_cannot_login(): void
    {
        User::query()->create([
            'username' => 'unassigned',
            'full_name' => 'Unassigned User',
            'password' => Hash::make('Password123!'),
            'status' => UserStatus::ACTIVE,
        ]);

        $this->post('/login', [
            'login' => 'unassigned',
            'password' => 'Password123!',
        ])->assertSessionHasErrors('login');

        $this->assertGuest();
    }

    public function test_user_can_logout(): void
    {
        $user = $this->createUser([
            'username' => 'charlie',
            'full_name' => 'Charlie Nguyen',
            'email' => 'charlie@example.com',
            'password' => Hash::make('Password123!'),
            'status' => UserStatus::ACTIVE,
        ]);

        $this->actingAs($user);

        $response = $this->post('/logout');

        $response->assertRedirect('/login');
        $this->assertGuest();
    }

    public function test_authenticated_user_can_access_the_shared_dashboard(): void
    {
        $user = $this->createUser([
            'username' => 'diana',
            'full_name' => 'Diana Tran',
            'email' => 'diana@example.com',
            'password' => Hash::make('Password123!'),
            'status' => UserStatus::ACTIVE,
        ]);

        $this->actingAs($user);
        $this->withoutVite();

        $this->get('/dashboard')->assertOk();
    }

    public function test_user_without_permission_cannot_manage_users(): void
    {
        $user = $this->createUser([
            'username' => 'frank',
            'full_name' => 'Frank Tran',
            'email' => 'frank@example.com',
            'password' => Hash::make('Password123!'),
            'status' => UserStatus::ACTIVE,
        ]);

        $this->actingAs($user);
        $this->withoutVite();

        $this->get('/users')->assertForbidden();
    }

    public function test_permission_granted_through_a_role_controls_menu_and_route_access(): void
    {
        $user = $this->createUser([
            'username' => 'hasusersview',
            'full_name' => 'Has Users View',
            'password' => Hash::make('Password123!'),
            'status' => UserStatus::ACTIVE,
        ]);
        $this->grantPermission($user, 'users.view');

        $this->withoutVite();
        $this->actingAs($user)
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('Quản lý tài khoản');

        $this->get('/users')->assertOk();
    }

    public function test_user_list_can_be_searched_filtered_and_keeps_filters_in_pagination(): void
    {
        $viewer = $this->createUser([
            'username' => 'userfilterviewer',
            'full_name' => 'User Filter Viewer',
            'password' => Hash::make('Password123!'),
            'status' => UserStatus::ACTIVE,
        ]);
        $this->grantPermission($viewer, 'users.view');

        $role = Role::query()->create([
            'code' => 'FILTER_STAFF',
            'name' => 'Filter Staff',
            'is_system' => false,
        ]);

        for ($index = 1; $index <= 16; $index++) {
            $user = $this->createUser([
                'username' => sprintf('filterstaff%02d', $index),
                'full_name' => sprintf('Staff Person %02d', $index),
                'email' => sprintf('staff.person.%02d@example.com', $index),
                'password' => Hash::make('Password123!'),
                'status' => UserStatus::ACTIVE,
            ]);

            UserRole::query()->create([
                'user_id' => $user->id,
                'role_id' => $role->id,
                'scope_type' => 'ALL',
                'scope_id' => null,
                'valid_from' => now()->subMinute(),
                'granted_by' => null,
            ]);
        }

        $this->withoutVite();
        $this->actingAs($viewer)
            ->get('/users?q=staff.person.01%40example.com&role_id='.$role->id.'&status=active')
            ->assertOk()
            ->assertSee('filterstaff01')
            ->assertDontSee('filterstaff02')
            ->assertSee('Đặt lại');

        $this->get('/users?q=Staff&role_id='.$role->id.'&status=active')
            ->assertOk()
            ->assertSee('q=Staff')
            ->assertSee('role_id='.$role->id)
            ->assertSee('status=active');
    }

    public function test_user_can_create_account_and_assign_a_role_with_permissions(): void
    {
        $manager = $this->createUser([
            'username' => 'usercreator',
            'full_name' => 'User Creator',
            'password' => Hash::make('Password123!'),
            'status' => UserStatus::ACTIVE,
        ]);
        $this->grantPermission($manager, 'users.create');
        $this->grantPermission($manager, 'users.assign_roles');
        $this->grantPermission($manager, 'users.delete');

        $role = Role::query()->create([
            'code' => 'TEST_STAFF',
            'name' => 'Test Staff',
            'is_system' => false,
        ]);

        $this->withoutVite();
        $this->actingAs($manager);
        $this->get('/users/create')->assertOk();

        $this->actingAs($manager)->post('/users', [
            'username' => 'newstaff',
            'full_name' => 'New Staff',
            'email' => 'newstaff@example.com',
            'status' => UserStatus::ACTIVE->value,
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'assign_roles' => '1',
            'role_ids' => [$role->id],
        ])->assertRedirect('/users');

        $createdUser = User::query()->where('username', 'newstaff')->firstOrFail();
        $this->assertDatabaseHas('user_roles', [
            'user_id' => $createdUser->id,
            'role_id' => $role->id,
            'scope_type' => 'ALL',
            'scope_id' => null,
        ]);

        $this->delete('/users/'.$createdUser->id)->assertMethodNotAllowed();
        $this->assertDatabaseHas('users', ['id' => $createdUser->id]);
    }

    public function test_admin_can_create_an_account_and_assign_a_role(): void
    {
        $adminRole = Role::query()->where('code', ConstantHelper::ROLE_ADMIN)->firstOrFail();
        $admin = $this->createUser([
            'username' => 'accountadmin',
            'full_name' => 'Account Admin',
            'password' => Hash::make('Password123!'),
            'status' => UserStatus::ACTIVE,
        ]);
        UserRole::query()->create([
            'user_id' => $admin->id,
            'role_id' => $adminRole->id,
            'scope_type' => 'ALL',
            'scope_id' => null,
            'valid_from' => now()->subMinute(),
            'granted_by' => null,
        ]);

        $role = Role::query()->create([
            'code' => 'ADMIN_ASSIGNED_STAFF',
            'name' => 'Admin Assigned Staff',
            'is_system' => false,
        ]);

        $this->withoutVite();
        $this->actingAs($admin)
            ->get('/users')
            ->assertOk()
            ->assertSee('Thêm tài khoản mới');
        $this->get('/users/create')->assertOk();

        $this->post('/users', [
            'username' => 'admincreated',
            'full_name' => 'Admin Created',
            'status' => UserStatus::ACTIVE->value,
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'assign_roles' => '1',
            'role_ids' => [$role->id],
        ])->assertRedirect('/users');

        $createdUser = User::query()->where('username', 'admincreated')->firstOrFail();
        $this->assertDatabaseHas('user_roles', [
            'user_id' => $createdUser->id,
            'role_id' => $role->id,
            'scope_type' => 'ALL',
            'scope_id' => null,
        ]);
    }

    public function test_user_creation_is_forbidden_without_role_assignment_permission(): void
    {
        $manager = $this->createUser([
            'username' => 'limitedcreator',
            'full_name' => 'Limited Creator',
            'password' => Hash::make('Password123!'),
            'status' => UserStatus::ACTIVE,
        ]);
        $this->grantPermission($manager, 'users.create');

        $this->actingAs($manager)->post('/users', [
            'username' => 'roleless',
            'full_name' => 'Roleless User',
            'status' => UserStatus::ACTIVE->value,
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ])->assertForbidden();

        $this->assertDatabaseMissing('users', ['username' => 'roleless']);
    }

    public function test_role_can_group_permissions_and_permissions_can_be_created(): void
    {
        $manager = $this->createUser([
            'username' => 'rolemanager',
            'full_name' => 'Role Manager',
            'password' => Hash::make('Password123!'),
            'status' => UserStatus::ACTIVE,
        ]);
        $this->grantPermission($manager, 'roles.create');
        $this->grantPermission($manager, 'roles.assign_permissions');
        $this->grantPermission($manager, 'permissions.create');

        $firstPermission = Permission::query()->create([
            'code' => 'orders.view',
            'module' => 'order',
            'name' => 'View orders',
        ]);
        $secondPermission = Permission::query()->create([
            'code' => 'orders.update',
            'module' => 'order',
            'name' => 'Update orders',
        ]);

        $this->actingAs($manager)->post('/roles', [
            'code' => 'ORDER_VIEWER',
            'name' => 'Order Viewer',
            'assign_permissions' => '1',
            'permission_ids' => [$firstPermission->id, $secondPermission->id],
        ])->assertRedirect('/roles');

        $this->post('/roles', [
            'code' => 'customer',
            'name' => 'Customer',
        ])->assertSessionHasErrors('code');

        $role = Role::query()->where('code', 'ORDER_VIEWER')->firstOrFail();
        $this->assertDatabaseHas('role_permissions', [
            'role_id' => $role->id,
            'permission_id' => $firstPermission->id,
        ]);
        $this->assertDatabaseHas('role_permissions', [
            'role_id' => $role->id,
            'permission_id' => $secondPermission->id,
        ]);

        $this->post('/permissions', [
            'code' => 'orders.cancel',
            'module' => 'order',
            'name' => 'Cancel orders',
        ])->assertRedirect('/permissions');

        $this->assertDatabaseHas('permissions', ['code' => 'orders.cancel']);
    }

    public function test_user_can_update_profile_with_avatar(): void
    {
        $user = $this->createUser([
            'username' => 'erin',
            'full_name' => 'Erin Nguyen',
            'email' => 'erin@example.com',
            'phone' => '0909000001',
            'password' => Hash::make('Password123!'),
            'status' => UserStatus::ACTIVE,
        ]);

        $this->actingAs($user);
        Storage::fake('public');

        $response = $this->put('/profile', [
            'full_name' => 'Erin Nguyen Updated',
            'email' => 'erin.updated@example.com',
            'phone' => '0909000002',
            'avatar' => UploadedFile::fake()->image('avatar.jpg', 200, 200),
        ]);

        $response->assertRedirect('/profile');
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'full_name' => 'Erin Nguyen Updated',
            'email' => 'erin.updated@example.com',
            'phone' => '0909000002',
        ]);
    }

    public function test_user_can_change_password(): void
    {
        $user = $this->createUser([
            'username' => 'grace',
            'full_name' => 'Grace Nguyen',
            'password' => Hash::make('Password123!'),
            'status' => UserStatus::ACTIVE,
        ]);

        $this->actingAs($user);

        $this->put('/profile/password', [
            'current_password' => 'Password123!',
            'password' => 'NewPassword123!',
            'password_confirmation' => 'NewPassword123!',
        ])->assertRedirect('/profile');

        $this->assertTrue(Hash::check('NewPassword123!', $user->fresh()->password));
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function createUser(array $attributes): User
    {
        $user = User::query()->create($attributes);
        $staffRole = Role::query()->where('code', ConstantHelper::ROLE_WAITER)->firstOrFail();

        UserRole::query()->create([
            'user_id' => $user->getKey(),
            'role_id' => $staffRole->getKey(),
            'scope_type' => 'ALL',
            'scope_id' => null,
            'valid_from' => now()->subMinute(),
            'granted_by' => null,
        ]);

        return $user;
    }

    private function grantPermission(User $user, string $permissionCode): void
    {
        $permission = Permission::query()->create([
            'code' => $permissionCode,
            'module' => 'auth_user',
            'name' => $permissionCode,
        ]);

        $role = Role::query()->create([
            'code' => 'TEST_'.strtoupper(str_replace('.', '_', $permissionCode)),
            'name' => $permissionCode,
            'is_system' => false,
        ]);

        DB::table(ConstantHelper::TABLE_ROLE_PERMISSIONS)->insert([
            'role_id' => $role->id,
            'permission_id' => $permission->id,
        ]);

        UserRole::query()->create([
            'user_id' => $user->id,
            'role_id' => $role->id,
            'scope_type' => 'ALL',
            'scope_id' => null,
            'valid_from' => now()->subMinute(),
            'granted_by' => null,
        ]);
    }
}
