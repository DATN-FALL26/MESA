<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Helpers\ConstantHelper;
use App\Models\Ingredient;
use App\Models\ItemVariant;
use App\Models\Modifier;
use App\Models\ModifierGroup;
use Database\Seeders\ReferenceSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Concerns\BuildsMenuFixtures;
use Tests\TestCase;

class MenuVariantAndModifierTest extends TestCase
{
    use BuildsMenuFixtures;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(ReferenceSeeder::class);
        $this->withoutVite();
    }

    private function createGroup(string $name = 'Thêm thịt', int $min = 0, int $max = 2): ModifierGroup
    {
        return ModifierGroup::query()->create(['name' => $name, 'min_select' => $min, 'max_select' => $max, 'is_active' => true]);
    }

    private function createIngredient(string $name = 'Thịt tái', string $sku = 'NL-TAI'): Ingredient
    {
        return Ingredient::query()->create(['sku' => $sku, 'name' => $name, 'base_uom' => 'g', 'is_active' => true]);
    }

    public function test_edit_page_lists_variants_with_final_price(): void
    {
        $manager = $this->createUserWithRole(ConstantHelper::ROLE_BRANCH_MANAGER);
        $item = $this->createMenuItem('Phở tái', 'PHO-TAI', null, 50000);
        $item->variants()->create(['name' => 'Lớn', 'price_delta' => 10000, 'is_default' => true, 'is_active' => true]);
        $this->actingAs($manager);

        $this->get(route('menu.items.edit', $item))
            ->assertOk()
            ->assertSee('Biến thể (size)')
            ->assertSee('Lớn')
            ->assertSee('60.000đ')
            ->assertSee('id="variant-modal"', false);
    }

    public function test_first_variant_becomes_default_and_a_new_default_replaces_the_old_one(): void
    {
        $manager = $this->createUserWithRole(ConstantHelper::ROLE_BRANCH_MANAGER);
        $item = $this->createMenuItem();
        $this->actingAs($manager);

        $this->post(route('menu.variants.store', $item), ['name' => 'Nhỏ', 'price_delta' => 0, 'is_default' => 0, 'is_active' => 1])
            ->assertRedirect(route('menu.items.edit', $item).'#variants');
        $small = ItemVariant::query()->where('name', 'Nhỏ')->firstOrFail();
        $this->assertTrue($small->is_default);

        $this->post(route('menu.variants.store', $item), ['name' => 'Lớn', 'price_delta' => 10000, 'is_default' => 1, 'is_active' => 1]);
        $large = ItemVariant::query()->where('name', 'Lớn')->firstOrFail();

        $this->assertTrue($large->fresh()->is_default);
        $this->assertFalse($small->fresh()->is_default);
        $this->assertSame(1, $item->variants()->where('is_default', true)->count());
    }

    public function test_default_variant_cannot_be_turned_off_and_variant_rules_are_enforced(): void
    {
        $manager = $this->createUserWithRole(ConstantHelper::ROLE_BRANCH_MANAGER);
        $item = $this->createMenuItem('Phở tái', 'PHO-TAI', null, 50000);
        $default = $item->variants()->create(['name' => 'Lớn', 'price_delta' => 10000, 'is_default' => true, 'is_active' => true]);
        $this->actingAs($manager);

        $this->put(route('menu.variants.update', $default), ['name' => 'Lớn', 'price_delta' => 10000, 'is_default' => 1, 'is_active' => 0])
            ->assertSessionHasErrors('variant');
        $this->put(route('menu.variants.update', $default), ['name' => 'Lớn', 'price_delta' => 10000, 'is_default' => 0, 'is_active' => 1])
            ->assertSessionHasErrors('variant');

        $this->post(route('menu.variants.store', $item), ['name' => 'Lớn', 'price_delta' => 5000])->assertSessionHasErrors('name');
        $this->post(route('menu.variants.store', $item), ['name' => 'Quá rẻ', 'price_delta' => -50001])->assertSessionHasErrors('price_delta');
        $this->post(route('menu.variants.store', $item), ['name' => 'Rẻ vừa', 'price_delta' => -50000])->assertSessionDoesntHaveErrors();
    }

    public function test_deleting_the_default_variant_promotes_another_active_one(): void
    {
        $manager = $this->createUserWithRole(ConstantHelper::ROLE_BRANCH_MANAGER);
        $item = $this->createMenuItem();
        $first = $item->variants()->create(['name' => 'Nhỏ', 'price_delta' => 0, 'is_default' => true, 'is_active' => true]);
        $second = $item->variants()->create(['name' => 'Lớn', 'price_delta' => 10000, 'is_default' => false, 'is_active' => true]);
        $this->actingAs($manager);

        $this->delete(route('menu.variants.destroy', $first))->assertRedirect();

        $this->assertDatabaseMissing('item_variants', ['id' => $first->id]);
        $this->assertTrue($second->fresh()->is_default);
    }

    public function test_view_only_user_does_not_see_variant_management(): void
    {
        $waiter = $this->createUserWithRole(ConstantHelper::ROLE_WAITER);
        $item = $this->createMenuItem();

        $this->actingAs($waiter)->get(route('menu.items.edit', $item))->assertForbidden();
        $this->post(route('menu.variants.store', $item), ['name' => 'Lớn', 'price_delta' => 0])->assertForbidden();
    }

    public function test_group_list_create_update_and_delete_rules(): void
    {
        $manager = $this->createUserWithRole(ConstantHelper::ROLE_BRANCH_MANAGER);
        $this->actingAs($manager);

        $this->post(route('menu.modifier-groups.store'), ['name' => 'Sai luật', 'min_select' => 3, 'max_select' => 1])
            ->assertSessionHasErrors('max_select');

        $this->post(route('menu.modifier-groups.store'), ['name' => 'Thêm thịt', 'min_select' => 0, 'max_select' => 2, 'is_active' => 1])
            ->assertRedirect();
        $group = ModifierGroup::query()->where('name', 'Thêm thịt')->firstOrFail();

        $this->get(route('menu.modifier-groups.index'))->assertOk()->assertSee('Thêm thịt')->assertSee('id="group-modal"', false);
        $this->assertSame([], $this->get(route('menu.modifier-groups.index', ['q' => 'xyz']))->assertOk()->viewData('groups')->pluck('name')->all());

        $this->put(route('menu.modifier-groups.update', $group), ['name' => 'Thêm thịt tái', 'min_select' => 1, 'max_select' => 3, 'is_active' => 0])
            ->assertRedirect(route('menu.modifier-groups.edit', $group));
        $this->assertSame('Thêm thịt tái', $group->fresh()->name);

        $group->modifiers()->create(['name' => 'Tái', 'price' => 10000, 'is_active' => true]);
        $this->delete(route('menu.modifier-groups.destroy', $group))->assertSessionHasErrors('group');

        $empty = $this->createGroup('Nhóm trống');
        $this->delete(route('menu.modifier-groups.destroy', $empty))->assertRedirect(route('menu.modifier-groups.index'));
        $this->assertDatabaseMissing('modifier_groups', ['id' => $empty->id]);
    }

    public function test_modifier_can_be_linked_to_an_ingredient_only_with_a_quantity(): void
    {
        $manager = $this->createUserWithRole(ConstantHelper::ROLE_BRANCH_MANAGER);
        $group = $this->createGroup();
        $ingredient = $this->createIngredient();
        $this->actingAs($manager);

        $this->get(route('menu.modifier-groups.edit', $group))->assertOk()->assertSee('Thêm topping')->assertSee('Thịt tái (g)');

        $this->post(route('menu.modifiers.store', $group), ['name' => 'Thêm tái', 'price' => 10000, 'ingredient_id' => $ingredient->id, 'is_active' => 1])
            ->assertSessionHasErrors('ingredient_qty');
        $this->post(route('menu.modifiers.store', $group), ['name' => 'Thêm tái', 'price' => 10000, 'ingredient_id' => $ingredient->id, 'ingredient_qty' => 0, 'is_active' => 1])
            ->assertSessionHasErrors('ingredient_qty');
        $this->post(route('menu.modifiers.store', $group), ['name' => 'Thêm tái', 'price' => 10000, 'ingredient_id' => 999999, 'ingredient_qty' => 30])
            ->assertSessionHasErrors('ingredient_id');

        $this->post(route('menu.modifiers.store', $group), ['name' => 'Thêm tái', 'price' => 10000, 'ingredient_id' => $ingredient->id, 'ingredient_qty' => 30.5, 'is_active' => 1])
            ->assertRedirect(route('menu.modifier-groups.edit', $group));
        $modifier = Modifier::query()->where('name', 'Thêm tái')->firstOrFail();
        $this->assertSame($ingredient->id, $modifier->ingredient_id);
        $this->assertSame('30.500', $modifier->ingredient_qty);

        // Không chọn nguyên liệu thì định lượng bị bỏ qua.
        $this->put(route('menu.modifiers.update', $modifier), ['name' => 'Thêm tái', 'price' => 12000, 'ingredient_qty' => 99, 'is_active' => 1])->assertRedirect();
        $modifier->refresh();
        $this->assertNull($modifier->ingredient_id);
        $this->assertNull($modifier->ingredient_qty);
        $this->assertSame(12000, (int) $modifier->price);

        $this->get(route('menu.modifier-groups.edit', $group))->assertOk()->assertSee('Thêm tái')->assertSee('+12.000đ');
    }

    public function test_unused_modifier_can_be_deleted(): void
    {
        $manager = $this->createUserWithRole(ConstantHelper::ROLE_BRANCH_MANAGER);
        $group = $this->createGroup();
        $free = $group->modifiers()->create(['name' => 'Quẩy', 'price' => 0, 'is_active' => true]);
        $this->actingAs($manager);

        $this->delete(route('menu.modifiers.destroy', $free))->assertRedirect(route('menu.modifier-groups.edit', $group));
        $this->assertDatabaseMissing('modifiers', ['id' => $free->id]);
    }

    public function test_groups_are_assigned_to_an_item_in_the_posted_order(): void
    {
        $manager = $this->createUserWithRole(ConstantHelper::ROLE_BRANCH_MANAGER);
        $item = $this->createMenuItem();
        $first = $this->createGroup('Nhóm A');
        $second = $this->createGroup('Nhóm B');
        $this->actingAs($manager);

        $this->put(route('menu.items.modifier-groups.sync', $item), ['group_ids' => [$second->id, $first->id]])
            ->assertRedirect(route('menu.items.edit', $item).'#groups');

        $this->assertDatabaseHas('menu_item_modifier_groups', ['menu_item_id' => $item->id, 'group_id' => $second->id, 'sort_order' => 0]);
        $this->assertDatabaseHas('menu_item_modifier_groups', ['menu_item_id' => $item->id, 'group_id' => $first->id, 'sort_order' => 1]);

        $html = $this->get(route('menu.items.edit', $item))->assertOk()->getContent();
        $this->assertLessThan(strpos($html, 'Nhóm A'), strpos($html, 'Nhóm B'), 'Nhóm đã gán phải hiện theo đúng thứ tự.');

        $this->put(route('menu.items.modifier-groups.sync', $item), ['group_ids' => [$first->id]])->assertRedirect();
        $this->assertDatabaseMissing('menu_item_modifier_groups', ['menu_item_id' => $item->id, 'group_id' => $second->id]);

        $this->put(route('menu.items.modifier-groups.sync', $item), ['group_ids' => [999999]])->assertSessionHasErrors('group_ids.0');
    }
}
