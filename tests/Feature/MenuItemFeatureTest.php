<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\ItemType;
use App\Enums\StationCode;
use App\Helpers\ConstantHelper;
use App\Models\MenuItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Concerns\MenuTestHelpers;
use Tests\TestCase;

class MenuItemFeatureTest extends TestCase
{
    use MenuTestHelpers;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpMenuFixtures();
    }

    public function test_user_with_view_only_permission_can_list_but_not_manage_menu(): void
    {
        $waiter = $this->userWithRole('waiter_menu', ConstantHelper::ROLE_WAITER);
        $this->makeItem();

        $this->actingAs($waiter)->get('/menu/items')->assertOk()->assertSee('Phở tái');
        $this->get('/menu/items/create')->assertForbidden();
        $this->post('/menu/categories', ['name' => 'Không được tạo'])->assertForbidden();
    }

    public function test_manager_creates_item_and_it_is_applied_to_selected_branches(): void
    {
        $manager = $this->userWithRole('manager_menu', ConstantHelper::ROLE_BRANCH_MANAGER);
        $branch = $this->makeBranch('B01');
        $category = $this->makeCategory('Phở');

        $this->actingAs($manager)->post('/menu/items', [
            'category_id' => $category->id,
            'sku' => ' pho-bo ',
            'name' => 'Phở bò',
            'item_type' => ItemType::DISH->value,
            'station_code' => StationCode::PHO->value,
            'base_price' => 55000,
            'tax_rate' => 8,
            'prep_time_seconds' => 180,
            'is_active' => 1,
            'branch_ids' => [$branch->id],
        ])->assertRedirect();

        $item = MenuItem::query()->where('sku', 'PHO-BO')->firstOrFail();
        $this->assertSame('Phở bò', $item->name);
        $this->assertDatabaseHas('branch_menu_items', [
            'branch_id' => $branch->id,
            'menu_item_id' => $item->id,
            'is_available' => 1,
        ]);
    }

    public function test_item_validation_rejects_decimal_price_and_duplicate_sku_even_when_soft_deleted(): void
    {
        $manager = $this->userWithRole('manager_menu', ConstantHelper::ROLE_BRANCH_MANAGER);
        $item = $this->makeItem();
        $payload = [
            'category_id' => $item->category_id,
            'sku' => 'PHO-TAI',
            'name' => 'Phở trùng mã',
            'item_type' => ItemType::DISH->value,
            'station_code' => StationCode::PHO->value,
            'base_price' => 50000,
            'tax_rate' => 8,
            'prep_time_seconds' => 180,
        ];

        $this->actingAs($manager)->post('/menu/items', $payload)->assertSessionHasErrors('sku');

        $item->delete();
        $this->post('/menu/items', $payload)->assertSessionHasErrors('sku');

        $this->post('/menu/items', array_merge($payload, ['sku' => 'PHO-MOI', 'base_price' => '1000.5']))
            ->assertSessionHasErrors('base_price');
    }

    public function test_deleting_an_item_is_a_soft_delete(): void
    {
        $manager = $this->userWithRole('manager_menu', ConstantHelper::ROLE_BRANCH_MANAGER);
        $item = $this->makeItem();

        $this->actingAs($manager)->delete("/menu/items/{$item->id}")->assertRedirect('/menu/items');

        $this->assertSoftDeleted('menu_items', ['id' => $item->id]);
    }
}
