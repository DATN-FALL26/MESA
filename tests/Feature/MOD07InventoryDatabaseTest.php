<?php

namespace Tests\Feature;

use App\Enums\Inventory\WarehouseType;
use App\Models\Ingredient;
use App\Models\IngredientCategory;
use App\Models\Inventory;
use App\Models\Unit;
use App\Models\Warehouse;
use Database\Seeders\MOD07InventorySeeder;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class MOD07InventoryDatabaseTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Schema::enableForeignKeyConstraints();
    }

    /**
     * Test 1: Migration creates all tables properly.
     */
    public function test_migration_creates_mod07_tables(): void
    {
        $this->assertTrue(Schema::hasTable('units'));
        $this->assertTrue(Schema::hasTable('ingredient_categories'));
        $this->assertTrue(Schema::hasTable('ingredients'));
        $this->assertTrue(Schema::hasTable('warehouses'));
        $this->assertTrue(Schema::hasTable('inventories'));

        $this->assertTrue(Schema::hasColumns('units', ['id', 'code', 'name', 'symbol', 'created_at', 'updated_at']));
        $this->assertTrue(Schema::hasColumns('ingredient_categories', ['id', 'name', 'slug', 'is_active', 'created_at', 'updated_at']));
        $this->assertTrue(Schema::hasColumns('ingredients', ['id', 'category_id', 'unit_id', 'code', 'name', 'shelf_life_days', 'is_active', 'created_at', 'updated_at']));
        $this->assertTrue(Schema::hasColumns('warehouses', ['id', 'store_id', 'code', 'name', 'type', 'is_active', 'created_at', 'updated_at']));
        $this->assertTrue(Schema::hasColumns('inventories', ['id', 'warehouse_id', 'ingredient_id', 'quantity', 'reserved_quantity', 'min_stock_level', 'max_stock_level', 'last_checked_at', 'created_at', 'updated_at']));
    }

    /**
     * Test 2: Eloquent relationships work properly.
     */
    public function test_eloquent_relationships_work_properly(): void
    {
        $unit = Unit::create(['code' => 'kg', 'name' => 'Kilogram', 'symbol' => 'kg']);
        $category = IngredientCategory::create(['name' => 'Thịt tươi', 'slug' => 'thit-tuoi']);

        $ingredient = Ingredient::create([
            'category_id' => $category->id,
            'unit_id' => $unit->id,
            'code' => 'NL_BO_BAP',
            'name' => 'Bắp bò',
            'shelf_life_days' => 5,
        ]);

        $warehouse = Warehouse::create([
            'code' => 'WH_TEST',
            'name' => 'Kho Bếp Test',
            'type' => WarehouseType::STORE,
        ]);

        $inventory = Inventory::create([
            'warehouse_id' => $warehouse->id,
            'ingredient_id' => $ingredient->id,
            'quantity' => '25.750',
            'reserved_quantity' => '5.250',
            'min_stock_level' => '10.000',
            'max_stock_level' => '50.000',
        ]);

        // Relationships
        $this->assertTrue($unit->ingredients->contains($ingredient));
        $this->assertTrue($category->ingredients->contains($ingredient));
        $this->assertEquals($category->id, $ingredient->category->id);
        $this->assertEquals($unit->id, $ingredient->unit->id);
        $this->assertTrue($ingredient->inventories->contains($inventory));
        $this->assertTrue($warehouse->inventories->contains($inventory));
        $this->assertEquals($warehouse->id, $inventory->warehouse->id);
        $this->assertEquals($ingredient->id, $inventory->ingredient->id);

        // Enum cast
        $this->assertInstanceOf(WarehouseType::class, $warehouse->type);
        $this->assertEquals('Kho cửa hàng', $warehouse->type->label());
    }

    /**
     * Test 3: UNIQUE(warehouse_id, ingredient_id) prevents duplicates.
     */
    public function test_unique_warehouse_and_ingredient_constraint(): void
    {
        $unit = Unit::create(['code' => 'g', 'name' => 'Gram', 'symbol' => 'g']);
        $cat = IngredientCategory::create(['name' => 'Gia vị', 'slug' => 'gia-vi']);
        $ingredient = Ingredient::create([
            'category_id' => $cat->id,
            'unit_id' => $unit->id,
            'code' => 'NL_MUOI',
            'name' => 'Muối hạt',
        ]);
        $warehouse = Warehouse::create([
            'code' => 'WH_01',
            'name' => 'Kho 1',
            'type' => WarehouseType::STORE,
        ]);

        Inventory::create([
            'warehouse_id' => $warehouse->id,
            'ingredient_id' => $ingredient->id,
            'quantity' => '100.000',
        ]);

        $this->expectException(QueryException::class);

        // Attempt duplicate entry for same warehouse and ingredient
        Inventory::create([
            'warehouse_id' => $warehouse->id,
            'ingredient_id' => $ingredient->id,
            'quantity' => '50.000',
        ]);
    }

    /**
     * Test 4: FK constraint restricts deletion of referenced records.
     */
    public function test_foreign_key_restricts_deletion(): void
    {
        $unit = Unit::create(['code' => 'g', 'name' => 'Gram', 'symbol' => 'g']);
        $cat = IngredientCategory::create(['name' => 'Rau', 'slug' => 'rau']);
        $ingredient = Ingredient::create([
            'category_id' => $cat->id,
            'unit_id' => $unit->id,
            'code' => 'NL_NGO',
            'name' => 'Ngò gai',
        ]);

        $this->expectException(QueryException::class);
        // Deleting category referenced by ingredient must throw QueryException
        $cat->delete();
    }

    /**
     * Test 5: DECIMAL stores precision accurately (3 decimal places).
     */
    public function test_decimal_stores_three_decimals_accurately(): void
    {
        $unit = Unit::create(['code' => 'g', 'name' => 'Gram', 'symbol' => 'g']);
        $cat = IngredientCategory::create(['name' => 'Thịt', 'slug' => 'thit']);
        $ingredient = Ingredient::create([
            'category_id' => $cat->id,
            'unit_id' => $unit->id,
            'code' => 'NL_GAU_BO',
            'name' => 'Gầu bò',
        ]);
        $warehouse = Warehouse::create([
            'code' => 'WH_02',
            'name' => 'Kho 2',
            'type' => WarehouseType::STORE,
        ]);

        $inventory = Inventory::create([
            'warehouse_id' => $warehouse->id,
            'ingredient_id' => $ingredient->id,
            'quantity' => '12345.678',
            'reserved_quantity' => '123.456',
            'min_stock_level' => '1000.123',
            'max_stock_level' => '50000.789',
        ]);

        $fresh = Inventory::find($inventory->id);
        $this->assertSame('12345.678', (string) $fresh->quantity);
        $this->assertSame('123.456', (string) $fresh->reserved_quantity);
        $this->assertSame('1000.123', (string) $fresh->min_stock_level);
        $this->assertSame('50000.789', (string) $fresh->max_stock_level);
        $this->assertSame('12222.222', (string) $fresh->available_quantity);
    }

    /**
     * Test 6: Seeder idempotency (running multiple times produces identical record counts).
     */
    public function test_seeder_idempotency(): void
    {
        $this->seed(MOD07InventorySeeder::class);
        $unitCount1 = Unit::count();
        $catCount1 = IngredientCategory::count();
        $ingCount1 = Ingredient::count();
        $whCount1 = Warehouse::count();
        $invCount1 = Inventory::count();

        // Run seeder second time
        $this->seed(MOD07InventorySeeder::class);

        $this->assertEquals($unitCount1, Unit::count());
        $this->assertEquals($catCount1, IngredientCategory::count());
        $this->assertEquals($ingCount1, Ingredient::count());
        $this->assertEquals($whCount1, Warehouse::count());
        $this->assertEquals($invCount1, Inventory::count());
    }

    /**
     * Test 7: Rollback works cleanly.
     */
    public function test_migrations_can_rollback(): void
    {
        $this->artisan('migrate:rollback', ['--step' => 5])
            ->assertSuccessful();

        $this->assertFalse(Schema::hasTable('inventories'));
        $this->assertFalse(Schema::hasTable('warehouses'));
        $this->assertFalse(Schema::hasTable('ingredients'));
        $this->assertFalse(Schema::hasTable('ingredient_categories'));
        $this->assertFalse(Schema::hasTable('units'));
    }
}
