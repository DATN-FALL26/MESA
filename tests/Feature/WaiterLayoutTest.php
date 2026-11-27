<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Helpers\ConstantHelper;
use App\Models\Branch;
use Database\Seeders\ReferenceSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Concerns\BuildsKitchenOrders;
use Tests\TestCase;

/**
 * Tài khoản order (phục vụ): đăng nhập vào thẳng POS, không có menu trái, nút Đăng xuất cạnh Làm mới.
 */
class WaiterLayoutTest extends TestCase
{
    use BuildsKitchenOrders;
    use RefreshDatabase;

    private Branch $branch;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(ReferenceSeeder::class);
        $this->withoutVite();
        $this->branch = $this->createBranch('WL01');
    }

    public function test_waiter_logs_in_straight_to_pos_without_sidebar(): void
    {
        $waiter = $this->createUserWithRole(ConstantHelper::ROLE_WAITER, $this->branch);
        $waiter->forceFill(['password' => bcrypt('12345678')])->save();

        $this->post('/login', ['login' => $waiter->username, 'password' => '12345678'])
            ->assertRedirect(route('tables.index'));

        $this->get(route('tables.index'))
            ->assertOk()
            ->assertDontSee('MENU QUẢN LÝ')
            ->assertDontSee('POS & Sơ đồ bàn', false)
            ->assertDontSee('Đăng xuất hệ thống')
            ->assertSeeInOrder([$waiter->full_name, 'Đăng xuất', 'Làm mới']);
    }

    public function test_kitchen_logs_in_straight_to_kds_with_red_logout(): void
    {
        $kitchen = $this->createUserWithRole(ConstantHelper::ROLE_KITCHEN, $this->branch);
        $kitchen->forceFill(['password' => bcrypt('12345678')])->save();

        $this->post('/login', ['login' => $kitchen->username, 'password' => '12345678'])
            ->assertRedirect(route('kitchen.index'));

        $this->get(route('kitchen.index'))
            ->assertOk()
            ->assertSee('Đăng xuất')
            ->assertDontSee(route('admin.dashboard'), false);
    }

    public function test_other_accounts_keep_dashboard_and_sidebar(): void
    {
        $manager = $this->createUserWithRole(ConstantHelper::ROLE_BRANCH_MANAGER, $this->branch);
        $manager->forceFill(['password' => bcrypt('12345678')])->save();

        $this->post('/login', ['login' => $manager->username, 'password' => '12345678'])
            ->assertRedirect(route('admin.dashboard'));

        $this->get(route('tables.index'))
            ->assertOk()
            ->assertSee('POS & Sơ đồ bàn', false)
            ->assertSee('Đăng xuất hệ thống');
    }
}
