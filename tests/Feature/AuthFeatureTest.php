<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\BranchStatus;
use App\Enums\ItemType;
use App\Enums\PaymentStatus;
use App\Enums\SessionStatus;
use App\Enums\StationCode;
use App\Enums\TableShape;
use App\Enums\TableStatus;
use App\Enums\UserStatus;
use App\Helpers\ConstantHelper;
use App\Models\Branch;
use App\Models\BranchMenuItem;
use App\Models\Category;
use App\Models\DiningSession;
use App\Models\DiningTable;
use App\Models\MenuItem;
use App\Models\Permission;
use App\Models\Role;
use App\Models\SessionTable;
use App\Models\Station;
use App\Models\User;
use App\Models\UserRole;
use Database\Seeders\ReferenceSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class AuthFeatureTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(ReferenceSeeder::class);
    }

    public function test_guest_can_open_the_customer_dashboard_without_signing_in(): void
    {
        $this->withoutVite()
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('Quét mã QR tại bàn');

        $this->assertGuest();
    }

    public function test_guest_can_place_a_signed_qr_order_for_an_open_table(): void
    {
        $this->withoutVite();

        $manager = $this->createUser([
            'username' => 'tablemanager',
            'full_name' => 'Table Manager',
            'password' => Hash::make('Password123!'),
            'status' => UserStatus::ACTIVE,
        ]);
        $branch = Branch::query()->create([
            'code' => 'TEST01',
            'name' => 'MESA Test Branch',
            'timezone' => 'Asia/Ho_Chi_Minh',
            'status' => BranchStatus::ACTIVE,
        ]);
        $table = DiningTable::query()->create([
            'branch_id' => $branch->id,
            'code' => 'A01',
            'shape' => TableShape::SQUARE,
            'status' => TableStatus::OCCUPIED,
            'is_active' => true,
        ]);
        $session = DiningSession::query()->create([
            'branch_id' => $branch->id,
            'opened_by' => $manager->id,
            'opened_at' => now(),
            'status' => SessionStatus::OPEN,
        ]);
        SessionTable::query()->create([
            'session_id' => $session->id,
            'table_id' => $table->id,
            'joined_at' => now(),
        ]);
        $category = Category::query()->create([
            'name' => 'Món thử',
            'is_active' => true,
        ]);
        $menuItem = MenuItem::query()->create([
            'category_id' => $category->id,
            'sku' => 'TEST-PHO',
            'name' => 'Phở thử nghiệm',
            'item_type' => ItemType::DISH,
            'station_code' => StationCode::PHO,
            'base_price' => 10000,
            'tax_rate' => 0,
            'is_active' => true,
        ]);
        BranchMenuItem::query()->create([
            'branch_id' => $branch->id,
            'menu_item_id' => $menuItem->id,
            'price' => 12000,
            'is_available' => true,
        ]);
        Station::query()->create([
            'branch_id' => $branch->id,
            'station_code' => StationCode::PHO,
            'name' => 'Quầy phở',
            'is_active' => true,
        ]);

        $signedOrderUrl = URL::signedRoute('customer.orders.store', ['diningTable' => $table->id]);

        $this->post($signedOrderUrl, [
            'customer_name' => 'Guest Customer',
            'customer_phone' => '0909000000',
            'items' => [
                [
                    'menu_item_id' => $menuItem->id,
                    'variant_id' => '',
                    'quantity' => 2,
                    'note' => 'Ít hành',
                ],
            ],
        ])->assertRedirect();

        $this->assertDatabaseHas('orders', [
            'branch_id' => $branch->id,
            'session_id' => $session->id,
            'created_by' => null,
            'customer_name' => 'Guest Customer',
            'subtotal' => 24000,
            'status' => 'sent',
        ]);
        $this->assertDatabaseHas('order_items', [
            'menu_item_id' => $menuItem->id,
            'unit_price_snapshot' => 12000,
            'quantity' => 2,
            'line_total' => 24000,
        ]);
        $this->assertDatabaseHas('payments', [
            'branch_id' => $branch->id,
            'method' => 'cash',
            'status' => PaymentStatus::PENDING->value,
            'amount' => 24000,
        ]);
        $this->assertDatabaseHas('order_batches', [
            'batch_no' => 1,
            'serve_mode' => 'together',
            'status' => 'sent',
        ]);
        $this->assertDatabaseHas('kitchen_tickets', [
            'branch_id' => $branch->id,
            'order_id' => DB::table('orders')->where('branch_id', $branch->id)->value('id'),
            'station_id' => Station::query()->where('branch_id', $branch->id)->value('id'),
            'status' => 'new',
        ]);
        $this->assertDatabaseHas('kitchen_ticket_items', [
            'order_item_id' => DB::table('order_items')->where('menu_item_id', $menuItem->id)->value('id'),
            'status' => 'new',
        ]);
        $this->assertDatabaseHas('order_status_history', [
            'to_status' => 'sent',
            'changed_by' => null,
            'note' => 'Đặt món qua QR tại bàn A01',
        ]);
        $this->assertGuest();

        $session->update(['status' => SessionStatus::CLOSED]);

        $this->post($signedOrderUrl, [
            'customer_name' => 'Guest Customer',
            'customer_phone' => '0909000000',
            'items' => [
                [
                    'menu_item_id' => $menuItem->id,
                    'quantity' => 1,
                ],
            ],
        ])->assertSessionHasErrors('table');

        $this->assertDatabaseCount('orders', 1);
    }

    public function test_guest_cannot_open_a_table_with_an_unsigned_qr_url(): void
    {
        $this->get(route('customer.table', ['diningTable' => 1]))
            ->assertForbidden();

        $this->assertGuest();
    }

    public function test_guest_can_register_with_default_customer_role_and_permissions(): void
    {
        $this->withoutVite();

        $this->get('/register')->assertOk();

        $this->post('/register', [
            'username' => 'newcustomer',
            'full_name' => 'New Customer',
            'email' => 'newcustomer@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ])->assertRedirect('/dashboard');

        $user = User::query()->where('username', 'newcustomer')->firstOrFail();

        $this->assertAuthenticatedAs($user);
        $this->assertTrue(Hash::check('Password123!', $user->password));
        $customerRole = Role::query()->where('code', ConstantHelper::ROLE_CUSTOMER)->firstOrFail();
        $this->assertDatabaseHas('user_roles', [
            'user_id' => $user->id,
            'role_id' => $customerRole->id,
            'scope_type' => 'ALL',
        ]);

        foreach (['profile.view', 'profile.update', 'dashboard.view'] as $permissionCode) {
            $this->assertTrue($user->hasPermission($permissionCode));
        }
    }

    public function test_reference_seeder_assigns_customer_role_to_legacy_users_without_an_active_role(): void
    {
        $user = User::query()->create([
            'username' => 'legacyuser',
            'full_name' => 'Legacy User',
            'password' => Hash::make('Password123!'),
            'status' => UserStatus::ACTIVE,
        ]);

        $this->seed(ReferenceSeeder::class);

        $this->assertTrue($user->fresh()->hasRole(ConstantHelper::ROLE_CUSTOMER));
    }

    public function test_registration_rejects_duplicate_username(): void
    {
        $this->createUser([
            'username' => 'existingcustomer',
            'full_name' => 'Existing Customer',
            'password' => Hash::make('Password123!'),
            'status' => UserStatus::ACTIVE,
        ]);

        $this->post('/register', [
            'username' => 'existingcustomer',
            'full_name' => 'Another Customer',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ])->assertSessionHasErrors('username');

        $this->assertGuest();
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

        $role = Role::query()->create([
            'code' => 'TEST_STAFF',
            'name' => 'Test Staff',
            'is_system' => false,
        ]);

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
        $customerRole = Role::query()->where('code', ConstantHelper::ROLE_CUSTOMER)->firstOrFail();

        UserRole::query()->create([
            'user_id' => $user->getKey(),
            'role_id' => $customerRole->getKey(),
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
