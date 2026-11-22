<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\ItemType;
use App\Enums\StationCode;
use App\Helpers\ConstantHelper;
use App\Models\MenuItem;
use Database\Seeders\ReferenceSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Feature\Concerns\BuildsMenuFixtures;
use Tests\TestCase;

class MenuItemTest extends TestCase
{
    use BuildsMenuFixtures;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(ReferenceSeeder::class);
        $this->withoutVite();
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(array $override = []): array
    {
        return array_merge([
            'category_id' => $this->createCategory('Phở')->id,
            'sku' => 'pho-bo',
            'name' => 'Phở bò',
            'item_type' => ItemType::DISH->value,
            'station_code' => StationCode::PHO->value,
            'base_price' => 55000,
            'tax_rate' => 8,
            'prep_time_seconds' => 180,
            'is_active' => 1,
        ], $override);
    }

    public function test_list_shows_items_and_can_be_filtered(): void
    {
        $manager = $this->createUserWithRole(ConstantHelper::ROLE_BRANCH_MANAGER);
        $pho = $this->createCategory('Phở');
        $drinks = $this->createCategory('Đồ uống');
        $this->createMenuItem('Phở tái', 'PHO-TAI', $pho);
        $inactive = $this->createMenuItem('Phở chín', 'PHO-CHIN', $pho);
        $inactive->update(['is_active' => false]);
        $this->createMenuItem('Trà đá', 'TRA-DA', $drinks);
        $this->actingAs($manager);

        $this->get(route('menu.items.index'))
            ->assertOk()
            ->assertSee('Quản lý món ăn')
            ->assertSee('Phở tái')
            ->assertSee('Trà đá')
            ->assertSee('Thêm món');

        $names = fn (array $query) => $this->get(route('menu.items.index', $query))->assertOk()->viewData('items')->pluck('name')->all();

        $this->assertEqualsCanonicalizing(['Phở tái', 'Phở chín'], $names(['category_id' => $pho->id]));
        $this->assertSame(['Phở chín'], $names(['status' => 'inactive']));
        $this->assertSame(['Trà đá'], $names(['q' => 'tra-da']));
        $this->assertSame(['Phở tái'], $names(['q' => 'tái']));
    }

    public function test_view_only_user_can_list_but_not_manage_items(): void
    {
        $waiter = $this->createUserWithRole(ConstantHelper::ROLE_WAITER);
        $this->createMenuItem();
        $this->actingAs($waiter);

        $this->get(route('menu.items.index'))
            ->assertOk()
            ->assertSee('Phở tái')
            ->assertDontSee('Thêm món')
            ->assertDontSee('data-confirm="', false);

        $this->get(route('menu.items.create'))->assertForbidden();
        $this->post(route('menu.items.store'), $this->payload())->assertForbidden();
        $this->assertDatabaseMissing('menu_items', ['sku' => 'PHO-BO']);
    }

    public function test_manager_creates_an_item_and_it_is_applied_only_to_selected_branches(): void
    {
        $manager = $this->createUserWithRole(ConstantHelper::ROLE_BRANCH_MANAGER);
        $branchA = $this->createBranch('A01');
        $branchB = $this->createBranch('B01');
        $this->actingAs($manager);

        $this->get(route('menu.items.create'))->assertOk()->assertSee('Thêm món mới')->assertSee($branchA->name);

        $this->post(route('menu.items.store'), $this->payload(['branch_ids' => [$branchA->id]]))
            ->assertRedirect();

        $item = MenuItem::query()->where('sku', 'PHO-BO')->firstOrFail();
        $this->assertSame('Phở bò', $item->name);
        $this->assertSame(55000, (int) $item->base_price);
        $this->assertDatabaseHas('branch_menu_items', ['branch_id' => $branchA->id, 'menu_item_id' => $item->id, 'is_available' => 1]);
        $this->assertDatabaseMissing('branch_menu_items', ['branch_id' => $branchB->id, 'menu_item_id' => $item->id]);
    }

    public function test_branch_scoped_manager_cannot_apply_an_item_to_another_branch(): void
    {
        $own = $this->createBranch('OWN');
        $other = $this->createBranch('OTH');
        $manager = $this->createUserWithRole(ConstantHelper::ROLE_BRANCH_MANAGER, $own);
        $this->actingAs($manager);

        $this->post(route('menu.items.store'), $this->payload(['branch_ids' => [$own->id, $other->id]]))->assertRedirect();

        $item = MenuItem::query()->where('sku', 'PHO-BO')->firstOrFail();
        $this->assertDatabaseHas('branch_menu_items', ['branch_id' => $own->id, 'menu_item_id' => $item->id]);
        $this->assertDatabaseMissing('branch_menu_items', ['branch_id' => $other->id, 'menu_item_id' => $item->id]);
    }

    public function test_validation_rejects_decimal_price_bad_sku_and_duplicate_sku_even_for_soft_deleted_items(): void
    {
        $manager = $this->createUserWithRole(ConstantHelper::ROLE_BRANCH_MANAGER);
        $existing = $this->createMenuItem('Phở tái', 'PHO-TAI');
        $this->actingAs($manager);

        $this->post(route('menu.items.store'), $this->payload(['sku' => 'pho-tai']))->assertSessionHasErrors('sku');

        $existing->delete();
        $this->post(route('menu.items.store'), $this->payload(['sku' => 'PHO-TAI']))->assertSessionHasErrors('sku');

        $this->post(route('menu.items.store'), $this->payload(['sku' => 'khong hop le!']))->assertSessionHasErrors('sku');
        $this->post(route('menu.items.store'), $this->payload(['base_price' => '1000.5']))->assertSessionHasErrors('base_price');
        $this->post(route('menu.items.store'), $this->payload(['base_price' => -1]))->assertSessionHasErrors('base_price');
        $this->post(route('menu.items.store'), $this->payload(['item_type' => 'khac']))->assertSessionHasErrors('item_type');
        $this->post(route('menu.items.store'), $this->payload(['station_code' => 'XYZ']))->assertSessionHasErrors('station_code');
    }

    public function test_failed_validation_shows_field_errors_on_the_form(): void
    {
        $manager = $this->createUserWithRole(ConstantHelper::ROLE_BRANCH_MANAGER);
        $this->actingAs($manager);

        $this->from(route('menu.items.create'))
            ->post(route('menu.items.store'), $this->payload(['name' => '', 'base_price' => 55000]))
            ->assertRedirect(route('menu.items.create'));

        $this->get(route('menu.items.create'))
            ->assertOk()
            ->assertSee('Tên món không được để trống.')
            ->assertSee('value="55000"', false);
    }

    public function test_manager_updates_an_item_and_edit_page_shows_current_values(): void
    {
        $manager = $this->createUserWithRole(ConstantHelper::ROLE_BRANCH_MANAGER);
        $item = $this->createMenuItem('Phở tái', 'PHO-TAI', null, 50000);
        $this->actingAs($manager);

        $this->get(route('menu.items.edit', $item))
            ->assertOk()
            ->assertSee('Chỉnh sửa món ăn')
            ->assertSee('value="PHO-TAI"', false)
            ->assertSee('value="50000"', false);

        $this->put(route('menu.items.update', $item), $this->payload([
            'category_id' => $item->category_id,
            'sku' => 'PHO-TAI',
            'name' => 'Phở tái nạm',
            'base_price' => 60000,
            'is_active' => 0,
        ]))->assertRedirect(route('menu.items.edit', $item));

        $item->refresh();
        $this->assertSame('Phở tái nạm', $item->name);
        $this->assertSame(60000, (int) $item->base_price);
        $this->assertFalse($item->is_active);
    }

    public function test_image_is_stored_replaced_and_removed(): void
    {
        Storage::fake('public');
        $manager = $this->createUserWithRole(ConstantHelper::ROLE_BRANCH_MANAGER);
        $this->actingAs($manager);

        $this->post(route('menu.items.store'), $this->payload(['image' => UploadedFile::fake()->image('pho.jpg', 200, 200)]))->assertRedirect();
        $item = MenuItem::query()->where('sku', 'PHO-BO')->firstOrFail();
        $this->assertNotNull($item->image_path);
        Storage::disk('public')->assertExists($item->image_path);
        $firstImage = $item->image_path;

        $this->put(route('menu.items.update', $item), $this->payload([
            'category_id' => $item->category_id,
            'image' => UploadedFile::fake()->image('moi.png', 200, 200),
        ]))->assertRedirect();
        $item->refresh();
        $this->assertNotSame($firstImage, $item->image_path);
        Storage::disk('public')->assertMissing($firstImage);
        Storage::disk('public')->assertExists($item->image_path);

        $current = $item->image_path;
        $this->put(route('menu.items.update', $item), $this->payload(['category_id' => $item->category_id, 'remove_image' => 1]))->assertRedirect();
        $this->assertNull($item->fresh()->image_path);
        Storage::disk('public')->assertMissing($current);

        $this->post(route('menu.items.store'), $this->payload(['sku' => 'FILE-LOI', 'image' => UploadedFile::fake()->create('x.pdf', 10, 'application/pdf')]))
            ->assertSessionHasErrors('image');
    }

    public function test_toggle_and_soft_delete(): void
    {
        $manager = $this->createUserWithRole(ConstantHelper::ROLE_BRANCH_MANAGER);
        $item = $this->createMenuItem();
        $this->actingAs($manager);

        $this->patch(route('menu.items.toggle', $item))->assertRedirect();
        $this->assertFalse($item->fresh()->is_active);
        $this->patch(route('menu.items.toggle', $item))->assertRedirect();
        $this->assertTrue($item->fresh()->is_active);

        $this->delete(route('menu.items.destroy', $item))->assertRedirect(route('menu.items.index'));
        $this->assertSoftDeleted('menu_items', ['id' => $item->id]);
        $this->get(route('menu.items.index'))->assertOk()->assertDontSee('PHO-TAI');
        $this->get(route('menu.items.edit', $item))->assertNotFound();
    }
}
