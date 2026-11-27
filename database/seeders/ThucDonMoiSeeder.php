<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\ItemType;
use App\Enums\StationCode;
use App\Helpers\ConstantHelper;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Thực đơn mới của quán (theo menu giấy 11/2026).
 *
 * - Phở: 12 món, mỗi món 1 giá, không chia size.
 * - Gọi thêm: 5 món riêng (thịt / trứng thêm), nấu ở quầy phở.
 * - Ăn kèm & Đồ uống: Sữa Đậu, Trà Đá, Quẩy Mã Mây.
 * - Món cũ không có trong menu: xóa mềm + ngừng bán (lịch sử order vẫn giữ tên/giá).
 *
 * Chạy sau SampleDataSeeder. Idempotent: chạy lại nhiều lần không nhân đôi dữ liệu.
 * Máy đã có dữ liệu: php artisan db:seed --class=ThucDonMoiSeeder
 */
class ThucDonMoiSeeder extends Seeder
{
    private const CAT_PHO = 'Phở';

    private const CAT_GOI_THEM = 'Gọi thêm';

    private const CAT_AN_KEM_DO_UONG = 'Ăn kèm & Đồ uống';

    /**
     * Món cũ không còn trong menu mới.
     */
    private const REMOVED_SKUS = ['PHO_DAC_BIET', 'NUOC_NGOT'];

    /**
     * Nhóm topping cũ trùng với mục "Gọi thêm" (giá không khớp menu) → ngừng dùng.
     */
    private const REMOVED_MODIFIER_GROUPS = ['Thêm thịt', 'Ăn kèm'];

    public function run(): void
    {
        DB::transaction(function (): void {
            $categoryIds = $this->seedCategories();
            $itemIds = $this->seedMenuItems($categoryIds);
            $this->removeOldItems();
            $this->seedModifierGroups($itemIds);
            $this->seedBranchMenuItems($itemIds);
            $this->seedRecipes($itemIds);
        });
    }

    /**
     * "Món ăn kèm" → "Gọi thêm"; "Đồ uống" → "Ăn kèm & Đồ uống".
     *
     * @return array<string, int>
     */
    private function seedCategories(): array
    {
        $renames = [
            'Món ăn kèm' => [self::CAT_GOI_THEM, 2],
            'Đồ uống' => [self::CAT_AN_KEM_DO_UONG, 3],
        ];

        foreach ($renames as $oldName => [$newName]) {
            $hasNew = DB::table(ConstantHelper::TABLE_CATEGORIES)->where('name', $newName)->exists();

            if ($hasNew) {
                // Đã đổi tên từ lần chạy trước (SampleDataSeeder chạy lại tạo lại tên cũ) → ẩn tên cũ.
                DB::table(ConstantHelper::TABLE_CATEGORIES)->where('name', $oldName)
                    ->update(['is_active' => false, 'updated_at' => now()]);
            } else {
                DB::table(ConstantHelper::TABLE_CATEGORIES)->where('name', $oldName)
                    ->update(['name' => $newName, 'updated_at' => now()]);
            }
        }

        $ids = [];

        foreach ([self::CAT_PHO => 1, self::CAT_GOI_THEM => 2, self::CAT_AN_KEM_DO_UONG => 3] as $name => $sortOrder) {
            $existing = DB::table(ConstantHelper::TABLE_CATEGORIES)->where('name', $name)->first();

            if ($existing) {
                DB::table(ConstantHelper::TABLE_CATEGORIES)->where('id', $existing->id)
                    ->update(['sort_order' => $sortOrder, 'is_active' => true, 'updated_at' => now()]);
                $ids[$name] = (int) $existing->id;
            } else {
                $ids[$name] = (int) DB::table(ConstantHelper::TABLE_CATEGORIES)->insertGetId([
                    'parent_id' => null,
                    'name' => $name,
                    'sort_order' => $sortOrder,
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        return $ids;
    }

    /**
     * @return array<int, array{sku: string, category: string, name: string, description: string, item_type: string, station_code: string, base_price: int, prep_time_seconds: int}>
     */
    private function menu(): array
    {
        $pho = fn (string $sku, string $name, int $price, string $description): array => [
            'sku' => $sku,
            'category' => self::CAT_PHO,
            // Tên món phở luôn có chữ "Phở" ở đầu (vd. "Phở Chín").
            'name' => 'Phở '.$name,
            'description' => $description,
            'item_type' => ItemType::DISH->value,
            'station_code' => StationCode::PHO->value,
            'base_price' => $price,
            'prep_time_seconds' => 300,
        ];

        $goiThem = fn (string $sku, string $name, int $price, string $description, int $prep = 180): array => [
            'sku' => $sku,
            'category' => self::CAT_GOI_THEM,
            'name' => $name,
            'description' => $description,
            'item_type' => ItemType::DISH->value,
            'station_code' => StationCode::PHO->value,
            'base_price' => $price,
            'prep_time_seconds' => $prep,
        ];

        return [
            // Phở
            $pho('PHO_TAI_SUON_SOT_VANG', 'Tái Sườn Nhừ Sốt Vang', 99_000, 'Phở tái kèm sườn bò hầm nhừ sốt vang'),
            $pho('PHO_SUON_SOT_VANG', 'Sườn Nhừ Sốt Vang', 69_000, 'Phở sườn bò hầm nhừ sốt vang'),
            $pho('PHO_TAI', 'Tái', 39_000, 'Phở bò tái'),
            $pho('PHO_CHIN', 'Chín', 49_000, 'Phở bò chín'),
            $pho('PHO_TAI_CHIN', 'Tái Chín', 49_000, 'Phở bò tái chín'),
            $pho('PHO_TAI_NAM', 'Tái Nạm', 49_000, 'Phở bò tái nạm'),
            $pho('PHO_SOT_VANG', 'Sốt Vang', 49_000, 'Phở bò sốt vang'),
            $pho('PHO_TAI_GAU', 'Tái Gầu', 59_000, 'Phở bò tái gầu'),
            $pho('PHO_GAU', 'Gầu', 59_000, 'Phở bò gầu'),
            $pho('PHO_LOI_RUA', 'Lõi Rùa', 59_000, 'Phở bò lõi rùa'),
            $pho('PHO_LOI_DAC_BIET', 'Lõi Đặc Biệt', 89_000, 'Phở lõi bò đặc biệt'),
            $pho('PHO_LOI_GAU', 'Lõi Gầu', 89_000, 'Phở lõi bò kèm gầu'),

            // Gọi thêm
            $goiThem('THEM_TRUNG_CHAN', 'Trứng Chần (1 quả)', 10_000, 'Trứng gà chần ăn kèm phở', 120),
            $goiThem('THEM_TAI', 'Tái Thêm', 39_000, 'Đĩa thịt bò tái gọi thêm'),
            $goiThem('THEM_CHIN', 'Chín Thêm', 39_000, 'Đĩa thịt bò chín gọi thêm'),
            $goiThem('THEM_SOT_VANG', 'Sốt Vang Thêm', 49_000, 'Bò sốt vang gọi thêm'),
            $goiThem('THEM_GAU', 'Gầu Thêm', 49_000, 'Đĩa gầu bò gọi thêm'),

            // Ăn kèm & Đồ uống
            [
                'sku' => 'SUA_DAU',
                'category' => self::CAT_AN_KEM_DO_UONG,
                'name' => 'Sữa Đậu',
                'description' => 'Sữa đậu nành',
                'item_type' => ItemType::DRINK->value,
                'station_code' => StationCode::DRINK->value,
                'base_price' => 12_000,
                'prep_time_seconds' => 60,
            ],
            [
                'sku' => 'TRA_DA',
                'category' => self::CAT_AN_KEM_DO_UONG,
                'name' => 'Trà Đá',
                'description' => 'Trà đá',
                'item_type' => ItemType::DRINK->value,
                'station_code' => StationCode::DRINK->value,
                'base_price' => 3_000,
                'prep_time_seconds' => 60,
            ],
            [
                'sku' => 'QUAY',
                'category' => self::CAT_AN_KEM_DO_UONG,
                'name' => 'Quẩy Mã Mây (4c đơn)',
                'description' => 'Quẩy Mã Mây, 4 chiếc đơn',
                'item_type' => ItemType::SIDE->value,
                'station_code' => StationCode::SIDE->value,
                'base_price' => 10_000,
                'prep_time_seconds' => 60,
            ],
        ];
    }

    /**
     * Thêm / cập nhật món theo SKU. Món phở cũ (PHO_TAI, PHO_CHIN) được dùng lại, bỏ size.
     *
     * @param  array<string, int>  $categoryIds
     * @return array<string, int> [sku => id]
     */
    private function seedMenuItems(array $categoryIds): array
    {
        $itemIds = [];

        foreach ($this->menu() as $item) {
            $row = [
                'category_id' => $categoryIds[$item['category']],
                'name' => $item['name'],
                'description' => $item['description'],
                'item_type' => $item['item_type'],
                'station_code' => $item['station_code'],
                'base_price' => $item['base_price'],
                'tax_rate' => 8.00,
                'prep_time_seconds' => $item['prep_time_seconds'],
                'is_active' => true,
                'deleted_at' => null,
                'updated_at' => now(),
            ];

            // Ảnh món đi theo git: đặt file public/images/<sku viết thường>.jpg|png|webp là seeder tự gắn.
            $imagePath = $this->imagePathFor($item['sku']);
            if ($imagePath !== null) {
                $row['image_path'] = $imagePath;
            }

            $existing = DB::table(ConstantHelper::TABLE_MENU_ITEMS)->where('sku', $item['sku'])->first();

            if ($existing) {
                DB::table(ConstantHelper::TABLE_MENU_ITEMS)->where('id', $existing->id)->update($row);
                $itemIds[$item['sku']] = (int) $existing->id;
            } else {
                $itemIds[$item['sku']] = (int) DB::table(ConstantHelper::TABLE_MENU_ITEMS)->insertGetId(
                    array_merge(['image_path' => null], $row, ['sku' => $item['sku'], 'created_at' => now()])
                );
            }
        }

        // Menu mới mỗi món 1 giá: ngừng dùng size cũ (giữ bản ghi để order cũ vẫn hiển thị đúng).
        DB::table(ConstantHelper::TABLE_ITEM_VARIANTS)
            ->whereIn('menu_item_id', array_values($itemIds))
            ->update(['is_active' => false, 'is_default' => false, 'updated_at' => now()]);

        return $itemIds;
    }

    /**
     * Tìm ảnh món trong public/images theo sku (vd PHO_GAU -> images/pho_gau.jpg).
     * Không có file thì giữ nguyên ảnh hiện tại của món.
     */
    private function imagePathFor(string $sku): ?string
    {
        foreach (['jpg', 'jpeg', 'png', 'webp'] as $extension) {
            $relativePath = 'images/'.strtolower($sku).'.'.$extension;

            if (is_file(public_path($relativePath))) {
                return $relativePath;
            }
        }

        return null;
    }

    /**
     * Món cũ không có trong menu: xóa mềm + ngừng bán (màn gọi món lọc theo is_active).
     */
    private function removeOldItems(): void
    {
        $removedIds = DB::table(ConstantHelper::TABLE_MENU_ITEMS)
            ->whereIn('sku', self::REMOVED_SKUS)
            ->pluck('id');

        DB::table(ConstantHelper::TABLE_MENU_ITEMS)
            ->whereIn('id', $removedIds)
            ->update(['is_active' => false, 'deleted_at' => DB::raw('COALESCE(deleted_at, NOW())'), 'updated_at' => now()]);

        DB::table(ConstantHelper::TABLE_BRANCH_MENU_ITEMS)
            ->whereIn('menu_item_id', $removedIds)
            ->update(['is_available' => false, 'updated_at' => now()]);

        DB::table(ConstantHelper::TABLE_MENU_ITEM_MODIFIER_GROUPS)
            ->whereIn('menu_item_id', $removedIds)
            ->delete();
    }

    /**
     * Bỏ nhóm topping cũ trùng "Gọi thêm"; các món phở chỉ giữ nhóm "Ghi chú chế biến".
     *
     * @param  array<string, int>  $itemIds
     */
    private function seedModifierGroups(array $itemIds): void
    {
        $oldGroupIds = DB::table(ConstantHelper::TABLE_MODIFIER_GROUPS)
            ->whereIn('name', self::REMOVED_MODIFIER_GROUPS)
            ->pluck('id');

        DB::table(ConstantHelper::TABLE_MODIFIER_GROUPS)
            ->whereIn('id', $oldGroupIds)
            ->update(['is_active' => false, 'updated_at' => now()]);

        DB::table(ConstantHelper::TABLE_MENU_ITEM_MODIFIER_GROUPS)
            ->whereIn('group_id', $oldGroupIds)
            ->delete();

        $noteGroupId = DB::table(ConstantHelper::TABLE_MODIFIER_GROUPS)->where('name', 'Ghi chú chế biến')->value('id');

        if (! $noteGroupId) {
            return;
        }

        $phoSkus = array_column(array_filter($this->menu(), fn (array $item): bool => $item['category'] === self::CAT_PHO), 'sku');

        foreach ($phoSkus as $sku) {
            DB::table(ConstantHelper::TABLE_MENU_ITEM_MODIFIER_GROUPS)->upsert(
                ['menu_item_id' => $itemIds[$sku], 'group_id' => $noteGroupId, 'sort_order' => 1],
                ['menu_item_id', 'group_id'],
                ['sort_order']
            );
        }
    }

    /**
     * Món mới bán ở mọi chi nhánh, giá theo base_price.
     *
     * @param  array<string, int>  $itemIds
     */
    private function seedBranchMenuItems(array $itemIds): void
    {
        $branchIds = DB::table(ConstantHelper::TABLE_BRANCHES)->pluck('id');

        foreach ($branchIds as $branchId) {
            foreach ($itemIds as $itemId) {
                DB::table(ConstantHelper::TABLE_BRANCH_MENU_ITEMS)->upsert(
                    [
                        'branch_id' => $branchId,
                        'menu_item_id' => $itemId,
                        'price' => null,
                        'is_available' => true,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ],
                    ['branch_id', 'menu_item_id'],
                    ['price', 'is_available', 'updated_at']
                );
            }
        }
    }

    /**
     * Định lượng cho các món dùng được nguyên liệu đang có trong kho.
     * Món cần nguyên liệu chưa có (sườn, sốt vang, nạm, gầu, lõi, trứng, sữa đậu) để trống, bổ sung ở mục Định lượng (BOM).
     *
     * @param  array<string, int>  $itemIds
     */
    private function seedRecipes(array $itemIds): void
    {
        $base = ['NL_BANH_PHO' => 150.000, 'NL_NUOC_DUNG' => 400.000, 'NL_HANH_LA' => 15.000];

        $recipes = [
            'PHO_TAI' => $base + ['NL_THIT_BO_TAI' => 60.000],
            'PHO_CHIN' => $base + ['NL_THIT_BO_CHIN' => 60.000],
            'PHO_TAI_CHIN' => $base + ['NL_THIT_BO_TAI' => 40.000, 'NL_THIT_BO_CHIN' => 40.000],
            'THEM_TAI' => ['NL_THIT_BO_TAI' => 60.000],
            'THEM_CHIN' => ['NL_THIT_BO_CHIN' => 60.000],
            'TRA_DA' => ['NL_TRA' => 5.000],
            'QUAY' => ['NL_QUAY' => 4.000],
        ];

        $ingredientIds = DB::table(ConstantHelper::TABLE_INGREDIENTS)->pluck('id', 'sku');

        foreach ($recipes as $sku => $ingredients) {
            $itemId = $itemIds[$sku];

            $recipeId = DB::table(ConstantHelper::TABLE_RECIPES)
                ->where('menu_item_id', $itemId)
                ->whereNull('variant_id')
                ->where('version', 1)
                ->value('id');

            if ($recipeId) {
                DB::table(ConstantHelper::TABLE_RECIPES)->where('id', $recipeId)
                    ->update(['is_active' => true, 'updated_at' => now()]);
            } else {
                $recipeId = DB::table(ConstantHelper::TABLE_RECIPES)->insertGetId([
                    'menu_item_id' => $itemId,
                    'variant_id' => null,
                    'version' => 1,
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            DB::table(ConstantHelper::TABLE_RECIPE_ITEMS)->where('recipe_id', $recipeId)->delete();

            $rows = [];
            foreach ($ingredients as $ingredientSku => $quantity) {
                if (isset($ingredientIds[$ingredientSku])) {
                    $rows[] = [
                        'recipe_id' => $recipeId,
                        'ingredient_id' => $ingredientIds[$ingredientSku],
                        'quantity' => $quantity,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
            }

            if ($rows !== []) {
                DB::table(ConstantHelper::TABLE_RECIPE_ITEMS)->insert($rows);
            }
        }

        // Size cũ đã bỏ → công thức theo size của các món này ngừng dùng.
        DB::table(ConstantHelper::TABLE_RECIPES)
            ->whereIn('menu_item_id', array_values($itemIds))
            ->whereNotNull('variant_id')
            ->update(['is_active' => false, 'updated_at' => now()]);
    }
}
