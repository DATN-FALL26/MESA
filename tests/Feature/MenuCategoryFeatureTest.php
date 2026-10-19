<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Helpers\ConstantHelper;
use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Concerns\MenuTestHelpers;
use Tests\TestCase;

class MenuCategoryFeatureTest extends TestCase
{
    use MenuTestHelpers;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpMenuFixtures();
    }

    public function test_view_only_user_can_list_categories_but_not_manage_them(): void
    {
        $waiter = $this->userWithRole('waiter_menu', ConstantHelper::ROLE_WAITER);
        $this->makeCategory('Phở');

        $this->actingAs($waiter)->get('/menu/categories')->assertOk()->assertSee('Phở');
        $this->get('/menu/categories/create')->assertForbidden();
        $this->post('/menu/categories', ['name' => 'Không được tạo'])->assertForbidden();
    }

    public function test_category_rules_for_levels_and_deletion(): void
    {
        $manager = $this->userWithRole('manager_menu', ConstantHelper::ROLE_BRANCH_MANAGER);
        $item = $this->makeItem();
        $root = $item->category;

        $this->actingAs($manager)->post('/menu/categories', ['name' => 'Con', 'parent_id' => $root->id, 'is_active' => 1])
            ->assertRedirect('/menu/categories');
        $child = Category::query()->where('name', 'Con')->firstOrFail();

        $this->post('/menu/categories', ['name' => 'Cháu', 'parent_id' => $child->id])
            ->assertSessionHasErrors('parent_id');

        $this->delete("/menu/categories/{$root->id}")->assertSessionHasErrors('category');
        $this->delete("/menu/categories/{$child->id}")->assertRedirect('/menu/categories');
        $this->assertDatabaseMissing('categories', ['id' => $child->id]);
    }
}
