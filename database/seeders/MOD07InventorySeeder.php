<?php

namespace Database\Seeders;

use App\Enums\Inventory\WarehouseType;
use App\Models\Ingredient;
use App\Models\IngredientCategory;
use App\Models\Inventory;
use App\Models\Unit;
use App\Models\Warehouse;
use Illuminate\Database\Seeder;

class MOD07InventorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Units
        $units = [
            ['code' => 'g', 'name' => 'Gram', 'symbol' => 'g'],
            ['code' => 'ml', 'name' => 'Milliliter', 'symbol' => 'ml'],
            ['code' => 'piece', 'name' => 'Cái', 'symbol' => 'cái'],
        ];

        $unitModels = [];
        foreach ($units as $unitData) {
            $unitModels[$unitData['code']] = Unit::updateOrCreate(
                ['code' => $unitData['code']],
                ['name' => $unitData['name'], 'symbol' => $unitData['symbol']]
            );
        }

        // 2. Ingredient Categories
        $categories = [
            ['slug' => 'thit', 'name' => 'Thịt', 'is_active' => true],
            ['slug' => 'rau', 'name' => 'Rau', 'is_active' => true],
            ['slug' => 'gia-vi', 'name' => 'Gia vị', 'is_active' => true],
            ['slug' => 'banh-pho', 'name' => 'Bánh phở', 'is_active' => true],
        ];

        $categoryModels = [];
        foreach ($categories as $catData) {
            $categoryModels[$catData['slug']] = IngredientCategory::updateOrCreate(
                ['slug' => $catData['slug']],
                ['name' => $catData['name'], 'is_active' => $catData['is_active']]
            );
        }

        // 3. Ingredients
        $ingredients = [
            [
                'code' => 'NL_BO_TAI',
                'name' => 'Thịt bò tái',
                'category_slug' => 'thit',
                'unit_code' => 'g',
                'shelf_life_days' => 2,
            ],
            [
                'code' => 'NL_NAM_BO',
                'name' => 'Nạm bò',
                'category_slug' => 'thit',
                'unit_code' => 'g',
                'shelf_life_days' => 3,
            ],
            [
                'code' => 'NL_BANH_PHO',
                'name' => 'Bánh phở',
                'category_slug' => 'banh-pho',
                'unit_code' => 'g',
                'shelf_life_days' => 1,
            ],
            [
                'code' => 'NL_XUONG_ONG',
                'name' => 'Xương ống',
                'category_slug' => 'thit',
                'unit_code' => 'g',
                'shelf_life_days' => 3,
            ],
            [
                'code' => 'NL_HANH_LA',
                'name' => 'Hành lá',
                'category_slug' => 'rau',
                'unit_code' => 'g',
                'shelf_life_days' => 2,
            ],
            [
                'code' => 'NL_QUE',
                'name' => 'Quế',
                'category_slug' => 'gia-vi',
                'unit_code' => 'g',
                'shelf_life_days' => 180,
            ],
            [
                'code' => 'NL_HOI',
                'name' => 'Hồi',
                'category_slug' => 'gia-vi',
                'unit_code' => 'g',
                'shelf_life_days' => 180,
            ],
        ];

        $ingredientModels = [];
        foreach ($ingredients as $ingData) {
            $ingredientModels[$ingData['code']] = Ingredient::updateOrCreate(
                ['code' => $ingData['code']],
                [
                    'category_id' => $categoryModels[$ingData['category_slug']]->id,
                    'unit_id' => $unitModels[$ingData['unit_code']]->id,
                    'name' => $ingData['name'],
                    'shelf_life_days' => $ingData['shelf_life_days'],
                    'is_active' => true,
                ]
            );
        }

        // 4. Central Warehouse (Independent of stores table)
        $centralWarehouse = Warehouse::updateOrCreate(
            ['code' => 'WH_CENTRAL'],
            [
                'store_id' => null,
                'name' => 'Kho trung tâm MESA',
                'type' => WarehouseType::CENTRAL,
                'is_active' => true,
            ]
        );

        // 5. Sample Inventories in Central Warehouse
        $sampleInventories = [
            'NL_BO_TAI' => [
                'quantity' => '50000.000',
                'reserved_quantity' => '5000.000',
                'min_stock_level' => '10000.000',
                'max_stock_level' => '100000.000',
            ],
            'NL_NAM_BO' => [
                'quantity' => '35000.000',
                'reserved_quantity' => '0.000',
                'min_stock_level' => '10000.000',
                'max_stock_level' => '80000.000',
            ],
            'NL_BANH_PHO' => [
                'quantity' => '0.000',
                'reserved_quantity' => '0.000',
                'min_stock_level' => '20000.000',
                'max_stock_level' => '50000.000',
            ],
            'NL_XUONG_ONG' => [
                'quantity' => '60000.000',
                'reserved_quantity' => '0.000',
                'min_stock_level' => '15000.000',
                'max_stock_level' => '120000.000',
            ],
            'NL_HANH_LA' => [
                'quantity' => '3000.000',
                'reserved_quantity' => '0.000',
                'min_stock_level' => '5000.000',
                'max_stock_level' => '15000.000',
            ],
            'NL_QUE' => [
                'quantity' => '10000.000',
                'reserved_quantity' => '0.000',
                'min_stock_level' => '2000.000',
                'max_stock_level' => '30000.000',
            ],
            'NL_HOI' => [
                'quantity' => '8000.000',
                'reserved_quantity' => '0.000',
                'min_stock_level' => '2000.000',
                'max_stock_level' => '25000.000',
            ],
        ];

        foreach ($sampleInventories as $code => $invData) {
            Inventory::updateOrCreate(
                [
                    'warehouse_id' => $centralWarehouse->id,
                    'ingredient_id' => $ingredientModels[$code]->id,
                ],
                [
                    'quantity' => $invData['quantity'],
                    'reserved_quantity' => $invData['reserved_quantity'],
                    'min_stock_level' => $invData['min_stock_level'],
                    'max_stock_level' => $invData['max_stock_level'],
                    'last_checked_at' => now(),
                ]
            );
        }
    }
}
