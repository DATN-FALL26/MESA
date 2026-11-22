<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Helpers\ConstantHelper;
use App\Models\BranchMenuItem;
use Database\Seeders\ReferenceSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Concerns\BuildsMenuFixtures;
use Tests\TestCase;

class MenuAvailabilityTest extends TestCase
{
    use BuildsMenuFixtures;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(ReferenceSeeder::class);
        $this->withoutVite();
    }

    public function test_page_shows_the_state_of_each_item_at_the_selected_branch(): void
    {
        $branch = $this->createBranch('B01');
        $manager = $this->createUserWithRole(ConstantHelper::ROLE_BRANCH_MANAGER, $branch);
        $category = $this->createCategory('Phở');
        $available = $this->createMenuItem('Phở tái', 'PHO-TAI', $category);
        $soldOut = $this->createMenuItem('Phở chín', 'PHO-CHIN', $category);
        $this->createMenuItem('Phở gà', 'PHO-GA', $category);
        BranchMenuItem::query()->create(['branch_id' => $branch->id, 'menu_item_id' => $available->id, 'is_available' => true]);
        BranchMenuItem::query()->create(['branch_id' => $branch->id, 'menu_item_id' => $soldOut->id, 'is_available' => false, 'price' => 60000]);

        $this->actingAs($manager)->get(route('menu.availability.index'))
            ->assertOk()
            ->assertSee('Còn hàng & giá theo chi nhánh')
            ->assertSee('Phở tái')
            ->assertSee('Báo hết')
            ->assertSee('Mở bán lại')
            ->assertSee('Áp dụng & mở bán')
            ->assertSee('Mở bán lại tất cả (1)')
            ->assertSee('+10.000đ');
    }

    public function test_inactive_items_are_not_listed(): void
    {
        $manager = $this->createUserWithRole(ConstantHelper::ROLE_BRANCH_MANAGER);
        $this->createBranch('B01');
        $item = $this->createMenuItem('Phở tái', 'PHO-TAI');
        $item->update(['is_active' => false]);

        $this->actingAs($manager)->get(route('menu.availability.index'))
            ->assertOk()
            ->assertViewHas('items', fn ($items) => $items->isEmpty());
    }

    public function test_kitchen_can_mark_an_item_sold_out_and_back_but_cannot_change_price(): void
    {
        $branch = $this->createBranch('B01');
        $kitchen = $this->createUserWithRole(ConstantHelper::ROLE_KITCHEN, $branch);
        $item = $this->createMenuItem();
        BranchMenuItem::query()->create(['branch_id' => $branch->id, 'menu_item_id' => $item->id, 'is_available' => true]);
        $this->actingAs($kitchen);

        $this->get(route('menu.availability.index'))->assertOk()->assertSee('name="is_available"', false)->assertDontSee('aria-label="Giá riêng của', false);

        $this->patch(route('menu.availability.update', [$branch, $item]), ['is_available' => 0])->assertRedirect();
        $this->assertDatabaseHas('branch_menu_items', ['branch_id' => $branch->id, 'menu_item_id' => $item->id, 'is_available' => 0]);

        $this->patch(route('menu.availability.update', [$branch, $item]), ['is_available' => 1])->assertRedirect();
        $this->assertDatabaseHas('branch_menu_items', ['branch_id' => $branch->id, 'menu_item_id' => $item->id, 'is_available' => 1]);

        $this->put(route('menu.availability.price', [$branch, $item]), ['price' => 70000])->assertForbidden();
    }

    public function test_applying_an_item_to_a_branch_creates_the_branch_row(): void
    {
        $branch = $this->createBranch('B01');
        $manager = $this->createUserWithRole(ConstantHelper::ROLE_BRANCH_MANAGER);
        $item = $this->createMenuItem();
        $this->actingAs($manager);

        $this->assertDatabaseMissing('branch_menu_items', ['menu_item_id' => $item->id]);
        $this->patch(route('menu.availability.update', [$branch, $item]), ['is_available' => 1])->assertRedirect();
        $this->assertDatabaseHas('branch_menu_items', ['branch_id' => $branch->id, 'menu_item_id' => $item->id, 'is_available' => 1]);
    }

    public function test_manager_sets_and_clears_a_branch_price_override_without_touching_availability(): void
    {
        $branch = $this->createBranch('B01');
        $manager = $this->createUserWithRole(ConstantHelper::ROLE_BRANCH_MANAGER);
        $item = $this->createMenuItem('Phở tái', 'PHO-TAI', null, 50000);
        $this->actingAs($manager);

        $this->put(route('menu.availability.price', [$branch, $item]), ['price' => 60000])->assertRedirect();
        $row = BranchMenuItem::query()->where('branch_id', $branch->id)->firstOrFail();
        $this->assertSame(60000, (int) $row->price);

        $this->patch(route('menu.availability.update', [$branch, $item]), ['is_available' => 0]);
        $this->assertSame(60000, (int) $row->fresh()->price, 'Báo hết không được xóa giá riêng.');

        $this->put(route('menu.availability.price', [$branch, $item]), ['price' => ''])->assertRedirect();
        $this->assertNull($row->fresh()->price);
        $this->assertFalse($row->fresh()->is_available, 'Xóa giá riêng không được mở bán lại món.');

        $this->put(route('menu.availability.price', [$branch, $item]), ['price' => '1000.5'])->assertSessionHasErrors('price');
        $this->put(route('menu.availability.price', [$branch, $item]), ['price' => -5])->assertSessionHasErrors('price');
    }

    public function test_branch_scoped_users_only_see_and_touch_their_own_branch(): void
    {
        $own = $this->createBranch('OWN');
        $other = $this->createBranch('OTH');
        $manager = $this->createUserWithRole(ConstantHelper::ROLE_BRANCH_MANAGER, $own);
        $item = $this->createMenuItem();
        $this->actingAs($manager);

        $this->get(route('menu.availability.index'))
            ->assertOk()
            ->assertViewHas('branches', fn ($branches) => $branches->pluck('id')->all() === [$own->id]);
        $this->get(route('menu.availability.index', ['branch_id' => $other->id]))
            ->assertOk()
            ->assertViewHas('branch', fn ($branch) => $branch->id === $own->id);

        $this->patch(route('menu.availability.update', [$own, $item]), ['is_available' => 1])->assertRedirect();
        $this->patch(route('menu.availability.update', [$other, $item]), ['is_available' => 1])->assertForbidden();
        $this->put(route('menu.availability.price', [$other, $item]), ['price' => 1000])->assertForbidden();
        $this->post(route('menu.availability.reset', $other))->assertForbidden();
        $this->assertDatabaseMissing('branch_menu_items', ['branch_id' => $other->id]);
    }

    public function test_reset_reopens_only_sold_out_rows_of_that_branch(): void
    {
        $branch = $this->createBranch('B01');
        $other = $this->createBranch('B02');
        $manager = $this->createUserWithRole(ConstantHelper::ROLE_BRANCH_MANAGER);
        $a = $this->createMenuItem('Món A', 'MON-A');
        $b = $this->createMenuItem('Món B', 'MON-B');
        BranchMenuItem::query()->create(['branch_id' => $branch->id, 'menu_item_id' => $a->id, 'is_available' => false]);
        BranchMenuItem::query()->create(['branch_id' => $branch->id, 'menu_item_id' => $b->id, 'is_available' => false]);
        BranchMenuItem::query()->create(['branch_id' => $other->id, 'menu_item_id' => $a->id, 'is_available' => false]);
        $this->actingAs($manager);

        $this->post(route('menu.availability.reset', $branch))->assertRedirect();

        $this->assertSame(0, BranchMenuItem::query()->where('branch_id', $branch->id)->where('is_available', false)->count());
        $this->assertDatabaseHas('branch_menu_items', ['branch_id' => $other->id, 'menu_item_id' => $a->id, 'is_available' => 0]);
    }

    public function test_view_only_user_sees_states_without_action_buttons(): void
    {
        $branch = $this->createBranch('B01');
        $waiter = $this->createUserWithRole(ConstantHelper::ROLE_WAITER, $branch);
        $item = $this->createMenuItem();
        BranchMenuItem::query()->create(['branch_id' => $branch->id, 'menu_item_id' => $item->id, 'is_available' => true]);
        $this->actingAs($waiter);

        $this->get(route('menu.availability.index'))
            ->assertOk()
            ->assertSee('Còn hàng')
            ->assertDontSee('name="is_available"', false)
            ->assertDontSee('data-confirm="', false);

        $this->patch(route('menu.availability.update', [$branch, $item]), ['is_available' => 0])->assertForbidden();
    }
}
