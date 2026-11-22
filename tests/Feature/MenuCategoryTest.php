<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Helpers\ConstantHelper;
use App\Models\Category;
use Database\Seeders\ReferenceSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Concerns\BuildsMenuFixtures;
use Tests\TestCase;

class MenuCategoryTest extends TestCase
{
    use BuildsMenuFixtures;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(ReferenceSeeder::class);
        $this->withoutVite();
    }

    public function test_manager_sees_category_tree_with_children_under_their_parent(): void
    {
        $manager = $this->createUserWithRole(ConstantHelper::ROLE_BRANCH_MANAGER);
        $drinks = $this->createCategory('Đồ uống');
        $this->createCategory('Nước ngọt', $drinks);
        $this->createCategory('Phở bò');

        $html = $this->actingAs($manager)->get(route('menu.categories.index'))
            ->assertOk()
            ->assertSee('Danh mục thực đơn')
            ->assertSee('Thêm danh mục')
            ->getContent();

        $this->assertLessThan(strpos($html, 'Nước ngọt'), strpos($html, 'Đồ uống'), 'Danh mục con phải nằm sau danh mục cha.');
    }

    public function test_newly_created_category_group_appears_first_without_separating_its_parent_and_child(): void
    {
        $manager = $this->createUserWithRole(ConstantHelper::ROLE_BRANCH_MANAGER);
        $this->createCategory('Danh mục cũ');
        $recentParent = $this->createCategory('Danh mục cha mới');
        $recentChild = $this->createCategory('Danh mục con mới', $recentParent);

        $categories = $this->actingAs($manager)
            ->get(route('menu.categories.index'))
            ->assertOk()
            ->viewData('categories');

        $this->assertSame(
            [$recentParent->id, $recentChild->id],
            $categories->take(2)->pluck('id')->all(),
        );
    }

    public function test_view_only_user_sees_the_list_without_management_buttons(): void
    {
        $waiter = $this->createUserWithRole(ConstantHelper::ROLE_WAITER);
        $this->createCategory('Phở bò');

        $this->actingAs($waiter)->get(route('menu.categories.index'))
            ->assertOk()
            ->assertSee('Phở bò')
            ->assertDontSee('Thêm danh mục')
            ->assertDontSee('id="category-modal"', false)
            ->assertDontSee('data-confirm="', false);

        $this->post(route('menu.categories.store'), ['name' => 'Không được tạo'])->assertForbidden();
        $this->assertDatabaseMissing('categories', ['name' => 'Không được tạo']);
    }

    public function test_user_without_menu_permission_cannot_open_the_page(): void
    {
        $warehouse = $this->createUserWithRole(ConstantHelper::ROLE_WAREHOUSE);

        $this->actingAs($warehouse)->get(route('menu.categories.index'))->assertForbidden();
    }

    public function test_manager_can_create_update_and_filter_categories(): void
    {
        $manager = $this->createUserWithRole(ConstantHelper::ROLE_BRANCH_MANAGER);
        $this->actingAs($manager);

        $this->post(route('menu.categories.store'), ['name' => 'Phở bò', 'sort_order' => 2, 'is_active' => 1])
            ->assertRedirect(route('menu.categories.index'));

        $category = Category::query()->where('name', 'Phở bò')->firstOrFail();
        $this->assertSame(2, $category->sort_order);
        $this->assertTrue($category->is_active);

        $this->put(route('menu.categories.update', $category), ['name' => 'Phở bò tái', 'sort_order' => 1, 'is_active' => 0])
            ->assertRedirect(route('menu.categories.index'));
        $this->assertFalse($category->fresh()->is_active);

        $names = fn (array $query) => $this->get(route('menu.categories.index', $query))->assertOk()->viewData('categories')->pluck('name')->all();

        $this->assertSame(['Phở bò tái'], $names(['status' => 'inactive']));
        $this->assertSame([], $names(['status' => 'active']));
        $this->assertSame(['Phở bò tái'], $names(['q' => 'tái']));
    }

    public function test_only_two_category_levels_and_no_self_parent(): void
    {
        $manager = $this->createUserWithRole(ConstantHelper::ROLE_BRANCH_MANAGER);
        $root = $this->createCategory('Gốc');
        $child = $this->createCategory('Con', $root);
        $this->actingAs($manager);

        $this->from(route('menu.categories.index'))
            ->post(route('menu.categories.store'), ['name' => 'Cháu', 'parent_id' => $child->id])
            ->assertRedirect(route('menu.categories.index'))
            ->assertSessionHasErrors('parent_id');

        $this->put(route('menu.categories.update', $root), ['name' => 'Gốc', 'parent_id' => $root->id])
            ->assertSessionHasErrors('parent_id');

        // Danh mục đang có con không thể trở thành danh mục con.
        $other = $this->createCategory('Khác');
        $this->put(route('menu.categories.update', $root), ['name' => 'Gốc', 'parent_id' => $other->id])
            ->assertSessionHasErrors('parent_id');
    }

    public function test_duplicate_name_under_same_parent_is_rejected_but_allowed_under_another_parent(): void
    {
        $manager = $this->createUserWithRole(ConstantHelper::ROLE_BRANCH_MANAGER);
        $a = $this->createCategory('Nhóm A');
        $b = $this->createCategory('Nhóm B');
        $this->createCategory('Đặc biệt', $a);
        $this->actingAs($manager);

        $this->post(route('menu.categories.store'), ['name' => 'Đặc biệt', 'parent_id' => $a->id])->assertSessionHasErrors('name');
        $this->post(route('menu.categories.store'), ['name' => 'Đặc biệt', 'parent_id' => $b->id])->assertSessionDoesntHaveErrors();
        $this->assertSame(2, Category::query()->where('name', 'Đặc biệt')->count());
    }

    public function test_failed_modal_submit_reopens_the_modal_with_the_entered_values(): void
    {
        $manager = $this->createUserWithRole(ConstantHelper::ROLE_BRANCH_MANAGER);
        $category = $this->createCategory('Phở bò');
        $this->actingAs($manager);

        $this->from(route('menu.categories.index'))
            ->put(route('menu.categories.update', $category), [
                'name' => '',
                '_modal' => 'category-modal',
                '_method' => 'PUT',
                '_action' => route('menu.categories.update', $category),
                '_title' => 'Chỉnh sửa danh mục',
            ])
            ->assertSessionHasErrors('name');

        $this->get(route('menu.categories.index'))
            ->assertOk()
            ->assertSee('MenuUI.reopen', false)
            ->assertSee(route('menu.categories.update', $category), false);
    }

    public function test_category_with_items_or_children_cannot_be_deleted_but_empty_one_can(): void
    {
        $manager = $this->createUserWithRole(ConstantHelper::ROLE_BRANCH_MANAGER);
        $root = $this->createCategory('Gốc');
        $this->createCategory('Con', $root);
        $withItem = $this->createCategory('Có món');
        $this->createMenuItem('Phở', 'PHO-1', $withItem);
        $empty = $this->createCategory('Trống');
        $this->actingAs($manager);

        $this->delete(route('menu.categories.destroy', $root))->assertSessionHasErrors('category');
        $this->delete(route('menu.categories.destroy', $withItem))->assertSessionHasErrors('category');
        $this->delete(route('menu.categories.destroy', $empty))->assertRedirect(route('menu.categories.index'));

        $this->assertDatabaseMissing('categories', ['id' => $empty->id]);
        $this->assertDatabaseHas('categories', ['id' => $root->id]);
        $this->assertDatabaseHas('categories', ['id' => $withItem->id]);
    }

    public function test_sidebar_shows_the_menu_section_only_with_menu_permission(): void
    {
        $manager = $this->createUserWithRole(ConstantHelper::ROLE_BRANCH_MANAGER);
        $warehouse = $this->createUserWithRole(ConstantHelper::ROLE_WAREHOUSE);

        $this->actingAs($manager)->get(route('profile'))->assertOk()->assertSee('Danh mục thực đơn');
        $this->actingAs($warehouse)->get(route('profile'))->assertOk()->assertDontSee('Danh mục thực đơn');
    }
}
