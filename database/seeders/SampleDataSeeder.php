<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\BranchStatus;
use App\Enums\IngredientType;
use App\Enums\ItemType;
use App\Enums\PrinterType;
use App\Enums\ScopeType;
use App\Enums\SequenceResetPolicy;
use App\Enums\StationCode;
use App\Enums\TableShape;
use App\Enums\TableStatus;
use App\Enums\UserStatus;
use App\Enums\WarehouseType;
use App\Helpers\ConstantHelper;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * SampleDataSeeder – Bộ dữ liệu mẫu cho hệ thống quản lý chuỗi quán phở.
 *
 * Idempotent: chạy lại nhiều lần không nhân đôi dữ liệu.
 */
class SampleDataSeeder extends Seeder
{
    public function run(): void
    {
        $branchIds = $this->seedBranches();
        $this->seedAreasAndTables($branchIds);
        $warehouseIds = $this->seedWarehouses($branchIds);
        $printerIds = $this->seedPrinters($branchIds);
        $this->seedStations($branchIds, $printerIds);
        $this->seedUsersAndRoles($branchIds);
        $categoryIds = $this->seedCategories();
        $ingredientIds = $this->seedIngredients();
        $itemData = $this->seedMenuItemsAndVariants($categoryIds);
        $this->seedModifierGroupsAndModifiers($ingredientIds, $itemData['itemIds']);
        $this->seedBranchMenuItems($branchIds, $itemData['itemIds']);
        $this->seedRecipesAndItems($itemData, $ingredientIds);
        $this->seedSuppliers();
        $this->seedStockLevels($warehouseIds, $ingredientIds);
        $this->seedDocumentSequences($branchIds);
    }

    /**
     * 1. Chi nhánh (2 chi nhánh: PHO01, PHO02)
     *
     * @return array<string, int> [branch_code => id]
     */
    private function seedBranches(): array
    {
        $branches = [
            [
                'code' => 'PHO01',
                'name' => 'Phở MESA - Chi nhánh 1 (Quận 1)',
                'address' => '123 Lê Lợi, P. Bến Thành, Quận 1, TP.HCM',
                'phone' => '0901000001',
                'timezone' => 'Asia/Ho_Chi_Minh',
                'status' => BranchStatus::ACTIVE->value,
                'opened_at' => '2026-01-01',
                'settings' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'PHO02',
                'name' => 'Phở MESA - Chi nhánh 2 (Cầu Giấy)',
                'address' => '456 Cầu Giấy, P. Quan Hoa, Q. Cầu Giấy, Hà Nội',
                'phone' => '0902000002',
                'timezone' => 'Asia/Ho_Chi_Minh',
                'status' => BranchStatus::ACTIVE->value,
                'opened_at' => '2026-02-01',
                'settings' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        foreach ($branches as $branch) {
            DB::table(ConstantHelper::TABLE_BRANCHES)->upsert(
                $branch,
                ['code'],
                ['name', 'address', 'phone', 'timezone', 'status', 'opened_at', 'settings', 'updated_at']
            );
        }

        /** @var array<string, int> */
        return DB::table(ConstantHelper::TABLE_BRANCHES)
            ->whereIn('code', ['PHO01', 'PHO02'])
            ->pluck('id', 'code')
            ->all();
    }

    /**
     * 2. Khu vực & Bàn ăn
     * Mỗi chi nhánh 2 khu (A, B) × 10 bàn × 4 ghế.
     * Mã bàn theo khu vực: Khu A -> A01..A10, Khu B -> B01..B10. Tọa độ pos_x/pos_y theo lưới (5x2).
     *
     * @param  array<string, int>  $branchIds
     */
    private function seedAreasAndTables(array $branchIds): void
    {
        foreach ($branchIds as $branchCode => $branchId) {
            $areas = [
                [
                    'branch_id' => $branchId,
                    'code' => 'A',
                    'name' => 'Khu A (Tầng 1)',
                    'default_seats' => 4,
                    'layout_config' => json_encode(['cols' => 5, 'rows' => 2, 'cell_size' => 80]),
                    'sort_order' => 1,
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'branch_id' => $branchId,
                    'code' => 'B',
                    'name' => 'Khu B (Tầng 2)',
                    'default_seats' => 4,
                    'layout_config' => json_encode(['cols' => 5, 'rows' => 2, 'cell_size' => 80]),
                    'sort_order' => 2,
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            ];

            foreach ($areas as $areaData) {
                $existingArea = DB::table(ConstantHelper::TABLE_AREAS)
                    ->where('branch_id', $branchId)
                    ->where('code', $areaData['code'])
                    ->whereNull('deleted_at')
                    ->first();

                if ($existingArea) {
                    DB::table(ConstantHelper::TABLE_AREAS)
                        ->where('id', $existingArea->id)
                        ->update([
                            'name' => $areaData['name'],
                            'default_seats' => $areaData['default_seats'],
                            'layout_config' => $areaData['layout_config'],
                            'sort_order' => $areaData['sort_order'],
                            'is_active' => $areaData['is_active'],
                            'updated_at' => now(),
                        ]);
                    $areaId = $existingArea->id;
                } else {
                    $areaId = DB::table(ConstantHelper::TABLE_AREAS)->insertGetId($areaData);
                }

                // 10 bàn trong mỗi khu: pos_x (1..5), pos_y (1..2)
                // Đặt tên bàn theo khu: Khu A -> A01..A10, Khu B -> B01..B10
                for ($i = 1; $i <= 10; $i++) {
                    $tableCode = sprintf('%s%02d', $areaData['code'], $i);
                    $posX = (($i - 1) % 5) + 1;
                    $posY = intdiv($i - 1, 5) + 1;

                    $tablePayload = [
                        'branch_id' => $branchId,
                        'area_id' => $areaId,
                        'code' => $tableCode,
                        'seats' => 4,
                        'shape' => TableShape::SQUARE->value,
                        'pos_x' => $posX,
                        'pos_y' => $posY,
                        'width' => 1,
                        'height' => 1,
                        'sort_order' => $i,
                        'status' => TableStatus::AVAILABLE->value,
                        'is_active' => true,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];

                    $existingTable = DB::table(ConstantHelper::TABLE_DINING_TABLES)
                        ->where('branch_id', $branchId)
                        ->where('code', $tableCode)
                        ->whereNull('deleted_at')
                        ->first();

                    if ($existingTable) {
                        DB::table(ConstantHelper::TABLE_DINING_TABLES)
                            ->where('id', $existingTable->id)
                            ->update([
                                'area_id' => $areaId,
                                'seats' => 4,
                                'shape' => TableShape::SQUARE->value,
                                'pos_x' => $posX,
                                'pos_y' => $posY,
                                'width' => 1,
                                'height' => 1,
                                'sort_order' => $i,
                                'is_active' => true,
                                'updated_at' => now(),
                            ]);
                    } else {
                        DB::table(ConstantHelper::TABLE_DINING_TABLES)->insert($tablePayload);
                    }
                }
            }
        }
    }

    /**
     * 3. Kho (1 kho cho mỗi chi nhánh)
     *
     * @param  array<string, int>  $branchIds
     * @return array<string, int> [branch_code => warehouse_id]
     */
    private function seedWarehouses(array $branchIds): array
    {
        $warehouseIds = [];

        foreach ($branchIds as $branchCode => $branchId) {
            $name = ($branchCode === 'PHO01') ? 'Kho Chi nhánh 1' : 'Kho Chi nhánh 2';

            $existing = DB::table(ConstantHelper::TABLE_WAREHOUSES)
                ->where('branch_id', $branchId)
                ->where('name', $name)
                ->first();

            if ($existing) {
                $warehouseIds[$branchCode] = $existing->id;
            } else {
                $warehouseIds[$branchCode] = DB::table(ConstantHelper::TABLE_WAREHOUSES)->insertGetId([
                    'branch_id' => $branchId,
                    'name' => $name,
                    'type' => WarehouseType::BRANCH->value,
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        return $warehouseIds;
    }

    /**
     * 4. Máy in (mỗi chi nhánh 2 máy: bếp + hóa đơn)
     *
     * @param  array<string, int>  $branchIds
     * @return array<string, array<string, int>> [branch_code => [kitchen => id, receipt => id]]
     */
    private function seedPrinters(array $branchIds): array
    {
        $printerIds = [];

        foreach ($branchIds as $branchCode => $branchId) {
            $ipOctet = ($branchCode === 'PHO01') ? '1' : '2';

            $kitchenName = "Máy in Bếp {$branchCode}";
            $receiptName = "Máy in Hóa đơn {$branchCode}";

            $kitchenPrinter = DB::table(ConstantHelper::TABLE_PRINTERS)
                ->where('branch_id', $branchId)
                ->where('name', $kitchenName)
                ->first();

            if ($kitchenPrinter) {
                $kitchenId = $kitchenPrinter->id;
            } else {
                $kitchenId = DB::table(ConstantHelper::TABLE_PRINTERS)->insertGetId([
                    'branch_id' => $branchId,
                    'name' => $kitchenName,
                    'type' => PrinterType::KITCHEN->value,
                    'connection' => "192.168.{$ipOctet}.201:9100",
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            $receiptPrinter = DB::table(ConstantHelper::TABLE_PRINTERS)
                ->where('branch_id', $branchId)
                ->where('name', $receiptName)
                ->first();

            if ($receiptPrinter) {
                $receiptId = $receiptPrinter->id;
            } else {
                $receiptId = DB::table(ConstantHelper::TABLE_PRINTERS)->insertGetId([
                    'branch_id' => $branchId,
                    'name' => $receiptName,
                    'type' => PrinterType::RECEIPT->value,
                    'connection' => "192.168.{$ipOctet}.202:9100",
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            $printerIds[$branchCode] = [
                'kitchen' => $kitchenId,
                'receipt' => $receiptId,
            ];
        }

        return $printerIds;
    }

    /**
     * 5. Quầy chế biến (mỗi chi nhánh 3 quầy: PHO, DRINK, SIDE)
     *
     * @param  array<string, int>  $branchIds
     * @param  array<string, array<string, int>>  $printerIds
     */
    private function seedStations(array $branchIds, array $printerIds): void
    {
        foreach ($branchIds as $branchCode => $branchId) {
            $kitchenPrinterId = $printerIds[$branchCode]['kitchen'] ?? null;

            $stations = [
                [
                    'branch_id' => $branchId,
                    'station_code' => StationCode::PHO->value,
                    'name' => "Quầy Phở {$branchCode}",
                    'printer_id' => $kitchenPrinterId,
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'branch_id' => $branchId,
                    'station_code' => StationCode::DRINK->value,
                    'name' => "Quầy Nước {$branchCode}",
                    'printer_id' => null,
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'branch_id' => $branchId,
                    'station_code' => StationCode::SIDE->value,
                    'name' => "Quầy Ăn Kèm {$branchCode}",
                    'printer_id' => null,
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            ];

            foreach ($stations as $station) {
                DB::table(ConstantHelper::TABLE_STATIONS)->upsert(
                    $station,
                    ['branch_id', 'station_code', 'name'],
                    ['printer_id', 'is_active', 'updated_at']
                );
            }
        }
    }

    /**
     * 6. Users & User Roles
     * Mật khẩu chung: 'password', pin: '123456'
     *
     * @param  array<string, int>  $branchIds
     */
    private function seedUsersAndRoles(array $branchIds): void
    {
        $deptIds = DB::table(ConstantHelper::TABLE_DEPARTMENTS)->pluck('id', 'code');
        $roleIds = DB::table(ConstantHelper::TABLE_ROLES)->pluck('id', 'code');

        $users = [
            [
                'username' => 'admin',
                'employee_code' => 'NV001',
                'full_name' => 'Quản trị viên Hệ thống',
                'email' => 'admin@mesa.vn',
                'phone' => '0900000001',
                'dept_code' => ConstantHelper::DEPT_OFFICE,
                'role_code' => ConstantHelper::ROLE_ADMIN,
                'scope_type' => ScopeType::ALL->value,
                'scope_id' => null,
            ],
            [
                'username' => 'manager',
                'employee_code' => 'NV002',
                'full_name' => 'Nguyễn Văn Quản Lý',
                'email' => 'manager@mesa.vn',
                'phone' => '0900000002',
                'dept_code' => ConstantHelper::DEPT_OFFICE,
                'role_code' => ConstantHelper::ROLE_BRANCH_MANAGER,
                'scopes' => [
                    ['scope_type' => ScopeType::BRANCH->value, 'branch_code' => 'PHO01'],
                    ['scope_type' => ScopeType::BRANCH->value, 'branch_code' => 'PHO02'],
                ],
            ],
            [
                'username' => 'cashier1',
                'employee_code' => 'NV003',
                'full_name' => 'Trần Thị Thu Ngân 1',
                'email' => 'cashier1@mesa.vn',
                'phone' => '0900000003',
                'dept_code' => ConstantHelper::DEPT_CASHIER,
                'role_code' => ConstantHelper::ROLE_CASHIER,
                'scope_type' => ScopeType::BRANCH->value,
                'branch_code' => 'PHO01',
            ],
            [
                'username' => 'waiter1',
                'employee_code' => 'NV004',
                'full_name' => 'Lê Văn Phục Vụ 1',
                'email' => 'waiter1@mesa.vn',
                'phone' => '0900000004',
                'dept_code' => ConstantHelper::DEPT_SERVICE,
                'role_code' => ConstantHelper::ROLE_WAITER,
                'scope_type' => ScopeType::BRANCH->value,
                'branch_code' => 'PHO01',
            ],
            [
                'username' => 'kitchen1',
                'employee_code' => 'NV005',
                'full_name' => 'Phạm Văn Bếp 1',
                'email' => 'kitchen1@mesa.vn',
                'phone' => '0900000005',
                'dept_code' => ConstantHelper::DEPT_KITCHEN,
                'role_code' => ConstantHelper::ROLE_KITCHEN,
                'scope_type' => ScopeType::BRANCH->value,
                'branch_code' => 'PHO01',
            ],
            [
                'username' => 'warehouse1',
                'employee_code' => 'NV006',
                'full_name' => 'Hoàng Văn Kho 1',
                'email' => 'warehouse1@mesa.vn',
                'phone' => '0900000006',
                'dept_code' => ConstantHelper::DEPT_WAREHOUSE,
                'role_code' => ConstantHelper::ROLE_WAREHOUSE,
                'scope_type' => ScopeType::BRANCH->value,
                'branch_code' => 'PHO01',
            ],
            [
                'username' => 'cashier2',
                'employee_code' => 'NV007',
                'full_name' => 'Vũ Thị Thu Ngân 2',
                'email' => 'cashier2@mesa.vn',
                'phone' => '0900000007',
                'dept_code' => ConstantHelper::DEPT_CASHIER,
                'role_code' => ConstantHelper::ROLE_CASHIER,
                'scope_type' => ScopeType::BRANCH->value,
                'branch_code' => 'PHO02',
            ],
        ];

        $hashedPassword = Hash::make('password');
        $pinHash = Hash::make('123456');

        foreach ($users as $userData) {
            $departmentId = $deptIds[$userData['dept_code']] ?? null;

            $existingUser = DB::table(ConstantHelper::TABLE_USERS)
                ->where('username', $userData['username'])
                ->first();

            $userPayload = [
                'username' => $userData['username'],
                'employee_code' => $userData['employee_code'],
                'full_name' => $userData['full_name'],
                'email' => $userData['email'],
                'phone' => $userData['phone'],
                'password' => $hashedPassword,
                'pin_hash' => $pinHash,
                'department_id' => $departmentId,
                'status' => UserStatus::ACTIVE->value,
                'updated_at' => now(),
            ];

            if ($existingUser) {
                DB::table(ConstantHelper::TABLE_USERS)
                    ->where('id', $existingUser->id)
                    ->update($userPayload);
                $userId = $existingUser->id;
            } else {
                $userPayload['created_at'] = now();
                $userId = DB::table(ConstantHelper::TABLE_USERS)->insertGetId($userPayload);
            }

            // Gán role
            if (isset($userData['scopes'])) {
                foreach ($userData['scopes'] as $scopeItem) {
                    $scopeBranchId = $branchIds[$scopeItem['branch_code']] ?? null;
                    $roleId = $roleIds[$userData['role_code']] ?? null;
                    if ($roleId && $scopeBranchId) {
                        $this->assignUserRole($userId, $roleId, $scopeItem['scope_type'], $scopeBranchId);
                    }
                }
            } else {
                $scopeId = null;
                if ($userData['scope_type'] === ScopeType::BRANCH->value) {
                    $scopeId = $branchIds[$userData['branch_code']] ?? null;
                }
                $roleId = $roleIds[$userData['role_code']] ?? null;
                if ($roleId) {
                    $this->assignUserRole($userId, $roleId, $userData['scope_type'], $scopeId);
                }
            }
        }
    }

    /**
     * Gán user_role đảm bảo idempotent và thỏa mãn ràng buộc CHECK.
     */
    private function assignUserRole(int $userId, int $roleId, string $scopeType, ?int $scopeId): void
    {
        $existing = DB::table(ConstantHelper::TABLE_USER_ROLES)
            ->where('user_id', $userId)
            ->where('role_id', $roleId)
            ->where('scope_type', $scopeType)
            ->where('scope_id', $scopeId)
            ->first();

        if (! $existing) {
            DB::table(ConstantHelper::TABLE_USER_ROLES)->insert([
                'user_id' => $userId,
                'role_id' => $roleId,
                'scope_type' => $scopeType,
                'scope_id' => $scopeId,
                'valid_from' => now(),
                'valid_to' => null,
                'granted_by' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * 7. Danh mục thực đơn (Phở, Món ăn kèm, Đồ uống)
     *
     * @return array<string, int> [name => id]
     */
    private function seedCategories(): array
    {
        $categories = [
            ['name' => 'Phở', 'sort_order' => 1, 'is_active' => true],
            ['name' => 'Món ăn kèm', 'sort_order' => 2, 'is_active' => true],
            ['name' => 'Đồ uống', 'sort_order' => 3, 'is_active' => true],
        ];

        $categoryIds = [];

        foreach ($categories as $cat) {
            $existing = DB::table(ConstantHelper::TABLE_CATEGORIES)
                ->where('name', $cat['name'])
                ->first();

            if ($existing) {
                DB::table(ConstantHelper::TABLE_CATEGORIES)
                    ->where('id', $existing->id)
                    ->update([
                        'sort_order' => $cat['sort_order'],
                        'is_active' => $cat['is_active'],
                        'updated_at' => now(),
                    ]);
                $categoryIds[$cat['name']] = $existing->id;
            } else {
                $categoryIds[$cat['name']] = DB::table(ConstantHelper::TABLE_CATEGORIES)->insertGetId([
                    'parent_id' => null,
                    'name' => $cat['name'],
                    'sort_order' => $cat['sort_order'],
                    'is_active' => $cat['is_active'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        return $categoryIds;
    }

    /**
     * 8. Nguyên liệu mẫu (bánh phở, thịt bò tái, thịt bò chín, xương, hành lá, quẩy, nước dùng semi_finished, trà, nước ngọt)
     *
     * @return array<string, int> [sku => id]
     */
    private function seedIngredients(): array
    {
        $ingredients = [
            [
                'sku' => 'NL_BANH_PHO',
                'name' => 'Bánh phở',
                'ingredient_type' => IngredientType::RAW->value,
                'base_uom' => 'g',
                'min_stock' => 5000.000,
                'is_active' => true,
            ],
            [
                'sku' => 'NL_THIT_BO_TAI',
                'name' => 'Thịt bò tái',
                'ingredient_type' => IngredientType::RAW->value,
                'base_uom' => 'g',
                'min_stock' => 3000.000,
                'is_active' => true,
            ],
            [
                'sku' => 'NL_THIT_BO_CHIN',
                'name' => 'Thịt bò chín',
                'ingredient_type' => IngredientType::RAW->value,
                'base_uom' => 'g',
                'min_stock' => 3000.000,
                'is_active' => true,
            ],
            [
                'sku' => 'NL_XUONG_BO',
                'name' => 'Xương bò hầm',
                'ingredient_type' => IngredientType::RAW->value,
                'base_uom' => 'g',
                'min_stock' => 10000.000,
                'is_active' => true,
            ],
            [
                'sku' => 'NL_HANH_LA',
                'name' => 'Hành lá & rau thơm',
                'ingredient_type' => IngredientType::RAW->value,
                'base_uom' => 'g',
                'min_stock' => 1000.000,
                'is_active' => true,
            ],
            [
                'sku' => 'NL_QUAY',
                'name' => 'Quẩy giòn',
                'ingredient_type' => IngredientType::RAW->value,
                'base_uom' => 'cái',
                'min_stock' => 100.000,
                'is_active' => true,
            ],
            [
                'sku' => 'NL_NUOC_DUNG',
                'name' => 'Nước dùng phở',
                'ingredient_type' => IngredientType::SEMI_FINISHED->value,
                'base_uom' => 'ml',
                'min_stock' => 20000.000,
                'is_active' => true,
            ],
            [
                'sku' => 'NL_TRA',
                'name' => 'Trà khô',
                'ingredient_type' => IngredientType::RAW->value,
                'base_uom' => 'g',
                'min_stock' => 500.000,
                'is_active' => true,
            ],
            [
                'sku' => 'NL_NUOC_NGOT',
                'name' => 'Nước ngọt lon',
                'ingredient_type' => IngredientType::RAW->value,
                'base_uom' => 'lon',
                'min_stock' => 50.000,
                'is_active' => true,
            ],
        ];

        foreach ($ingredients as $ing) {
            DB::table(ConstantHelper::TABLE_INGREDIENTS)->upsert(
                array_merge($ing, ['created_at' => now(), 'updated_at' => now()]),
                ['sku'],
                ['name', 'ingredient_type', 'base_uom', 'min_stock', 'is_active', 'updated_at']
            );
        }

        /** @var array<string, int> */
        return DB::table(ConstantHelper::TABLE_INGREDIENTS)
            ->pluck('id', 'sku')
            ->all();
    }

    /**
     * 9. Món mẫu & Size
     * Phở tái/chín/đặc biệt kèm 3 size nhỏ/lớn/đặc biệt với prep_time_seconds=300,
     * quẩy=120, trà đá=60, nước ngọt=60.
     *
     * @param  array<string, int>  $categoryIds
     * @return array{itemIds: array<string, int>, variantIds: array<string, array<string, int>>}
     */
    private function seedMenuItemsAndVariants(array $categoryIds): array
    {
        $phoCatId = $categoryIds['Phở'] ?? 1;
        $sideCatId = $categoryIds['Món ăn kèm'] ?? 2;
        $drinkCatId = $categoryIds['Đồ uống'] ?? 3;

        $items = [
            [
                'sku' => 'PHO_TAI',
                'category_id' => $phoCatId,
                'name' => 'Phở Bò Tái',
                'description' => 'Phở bò tái truyền thống, nước dùng thanh ngọt đậm đà',
                'item_type' => ItemType::DISH->value,
                'station_code' => StationCode::PHO->value,
                'base_price' => 55000,
                'tax_rate' => 8.00,
                'prep_time_seconds' => 300,
                'image_path' => 'images/pho_tai.jpg',
                'is_active' => true,
                'variants' => [
                    ['name' => 'Nhỏ', 'price_delta' => 0, 'is_default' => true],
                    ['name' => 'Lớn', 'price_delta' => 10000, 'is_default' => false],
                    ['name' => 'Đặc biệt', 'price_delta' => 25000, 'is_default' => false],
                ],
            ],
            [
                'sku' => 'PHO_CHIN',
                'category_id' => $phoCatId,
                'name' => 'Phở Bò Chín',
                'description' => 'Phở bò nạm gầu chín mềm thơm ngon',
                'item_type' => ItemType::DISH->value,
                'station_code' => StationCode::PHO->value,
                'base_price' => 55000,
                'tax_rate' => 8.00,
                'prep_time_seconds' => 300,
                'image_path' => 'images/pho_chin.jpg',
                'variants' => [
                    ['name' => 'Nhỏ', 'price_delta' => 0, 'is_default' => true],
                    ['name' => 'Lớn', 'price_delta' => 10000, 'is_default' => false],
                    ['name' => 'Đặc biệt', 'price_delta' => 25000, 'is_default' => false],
                ],
            ],
            [
                'sku' => 'PHO_DAC_BIET',
                'category_id' => $phoCatId,
                'name' => 'Phở Đặc Biệt (Tái + Chín)',
                'description' => 'Tô phở đặc biệt kết hợp thịt bò tái tươi và nạm bò chín',
                'item_type' => ItemType::DISH->value,
                'station_code' => StationCode::PHO->value,
                'base_price' => 75000,
                'tax_rate' => 8.00,
                'prep_time_seconds' => 300,
                'image_path' => 'images/pho_dac_biet.jpg',
                'variants' => [
                    ['name' => 'Nhỏ', 'price_delta' => 0, 'is_default' => true],
                    ['name' => 'Lớn', 'price_delta' => 10000, 'is_default' => false],
                    ['name' => 'Đặc biệt', 'price_delta' => 25000, 'is_default' => false],
                ],
            ],
            [
                'sku' => 'QUAY',
                'category_id' => $sideCatId,
                'name' => 'Quẩy Giòn',
                'description' => 'Quẩy vàng giòn rụm ăn kèm phở',
                'item_type' => ItemType::SIDE->value,
                'station_code' => StationCode::SIDE->value,
                'base_price' => 10000,
                'tax_rate' => 8.00,
                'prep_time_seconds' => 120,
                'image_path' => 'images/quay.jpg',
                'variants' => [],
            ],
            [
                'sku' => 'TRA_DA',
                'category_id' => $drinkCatId,
                'name' => 'Trà Đá',
                'description' => 'Trà lài ướp lạnh giải khát',
                'item_type' => ItemType::DRINK->value,
                'station_code' => StationCode::DRINK->value,
                'base_price' => 5000,
                'tax_rate' => 8.00,
                'prep_time_seconds' => 60,
                'image_path' => 'images/tra_da.jpg',
                'variants' => [],
            ],
            [
                'sku' => 'NUOC_NGOT',
                'category_id' => $drinkCatId,
                'name' => 'Nước Ngọt Lon',
                'description' => 'Nước ngọt có gas các loại lon 330ml',
                'item_type' => ItemType::DRINK->value,
                'station_code' => StationCode::DRINK->value,
                'base_price' => 15000,
                'tax_rate' => 8.00,
                'prep_time_seconds' => 60,
                'image_path' => 'images/nuoc_ngot.jpg',
                'variants' => [],
            ],
        ];

        $itemIds = [];
        $variantIds = [];

        foreach ($items as $item) {
            $variants = $item['variants'];
            unset($item['variants']);

            $existingItem = DB::table(ConstantHelper::TABLE_MENU_ITEMS)
                ->where('sku', $item['sku'])
                ->first();

            if ($existingItem) {
                DB::table(ConstantHelper::TABLE_MENU_ITEMS)
                    ->where('id', $existingItem->id)
                    ->update(array_merge($item, ['updated_at' => now()]));
                $itemId = $existingItem->id;
            } else {
                $itemId = DB::table(ConstantHelper::TABLE_MENU_ITEMS)->insertGetId(
                    array_merge($item, ['created_at' => now(), 'updated_at' => now()])
                );
            }

            $itemIds[$item['sku']] = $itemId;
            $variantIds[$item['sku']] = [];

            foreach ($variants as $variant) {
                $existingVariant = DB::table(ConstantHelper::TABLE_ITEM_VARIANTS)
                    ->where('menu_item_id', $itemId)
                    ->where('name', $variant['name'])
                    ->first();

                if ($existingVariant) {
                    DB::table(ConstantHelper::TABLE_ITEM_VARIANTS)
                        ->where('id', $existingVariant->id)
                        ->update([
                            'price_delta' => $variant['price_delta'],
                            'is_default' => $variant['is_default'],
                            'is_active' => true,
                            'updated_at' => now(),
                        ]);
                    $variantIds[$item['sku']][$variant['name']] = $existingVariant->id;
                } else {
                    $variantId = DB::table(ConstantHelper::TABLE_ITEM_VARIANTS)->insertGetId([
                        'menu_item_id' => $itemId,
                        'name' => $variant['name'],
                        'price_delta' => $variant['price_delta'],
                        'is_default' => $variant['is_default'],
                        'is_active' => true,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                    $variantIds[$item['sku']][$variant['name']] = $variantId;
                }
            }
        }

        return [
            'itemIds' => $itemIds,
            'variantIds' => $variantIds,
        ];
    }

    /**
     * 10. Nhóm tùy chọn (Thêm thịt, Ăn kèm, Ghi chú chế biến) & Modifiers & Gán vào món
     *
     * @param  array<string, int>  $ingredientIds
     * @param  array<string, int>  $itemIds
     */
    private function seedModifierGroupsAndModifiers(array $ingredientIds, array $itemIds): void
    {
        $groups = [
            [
                'name' => 'Thêm thịt',
                'min_select' => 0,
                'max_select' => 3,
                'is_active' => true,
                'modifiers' => [
                    [
                        'name' => 'Thêm thịt bò tái',
                        'price' => 20000,
                        'ingredient_sku' => 'NL_THIT_BO_TAI',
                        'ingredient_qty' => 50.000,
                    ],
                    [
                        'name' => 'Thêm thịt bò chín',
                        'price' => 20000,
                        'ingredient_sku' => 'NL_THIT_BO_CHIN',
                        'ingredient_qty' => 50.000,
                    ],
                ],
            ],
            [
                'name' => 'Ăn kèm',
                'min_select' => 0,
                'max_select' => 2,
                'is_active' => true,
                'modifiers' => [
                    [
                        'name' => 'Thêm quẩy (2 cái)',
                        'price' => 5000,
                        'ingredient_sku' => 'NL_QUAY',
                        'ingredient_qty' => 2.000,
                    ],
                    [
                        'name' => 'Thêm trứng chần',
                        'price' => 10000,
                        'ingredient_sku' => null,
                        'ingredient_qty' => null,
                    ],
                ],
            ],
            [
                'name' => 'Ghi chú chế biến',
                'min_select' => 0,
                'max_select' => 5,
                'is_active' => true,
                'modifiers' => [
                    [
                        'name' => 'Nhiều hành lá',
                        'price' => 0,
                        'ingredient_sku' => 'NL_HANH_LA',
                        'ingredient_qty' => 20.000,
                    ],
                    [
                        'name' => 'Không hành lá',
                        'price' => 0,
                        'ingredient_sku' => null,
                        'ingredient_qty' => null,
                    ],
                    [
                        'name' => 'Nước dùng trong',
                        'price' => 0,
                        'ingredient_sku' => null,
                        'ingredient_qty' => null,
                    ],
                    [
                        'name' => 'Nước dùng béo',
                        'price' => 0,
                        'ingredient_sku' => null,
                        'ingredient_qty' => null,
                    ],
                ],
            ],
        ];

        $groupIds = [];

        foreach ($groups as $group) {
            $modifiers = $group['modifiers'];
            unset($group['modifiers']);

            $existingGroup = DB::table(ConstantHelper::TABLE_MODIFIER_GROUPS)
                ->where('name', $group['name'])
                ->first();

            if ($existingGroup) {
                DB::table(ConstantHelper::TABLE_MODIFIER_GROUPS)
                    ->where('id', $existingGroup->id)
                    ->update(array_merge($group, ['updated_at' => now()]));
                $groupId = $existingGroup->id;
            } else {
                $groupId = DB::table(ConstantHelper::TABLE_MODIFIER_GROUPS)->insertGetId(
                    array_merge($group, ['created_at' => now(), 'updated_at' => now()])
                );
            }

            $groupIds[] = $groupId;

            foreach ($modifiers as $mod) {
                $ingId = $mod['ingredient_sku'] ? ($ingredientIds[$mod['ingredient_sku']] ?? null) : null;

                $existingMod = DB::table(ConstantHelper::TABLE_MODIFIERS)
                    ->where('group_id', $groupId)
                    ->where('name', $mod['name'])
                    ->first();

                if ($existingMod) {
                    DB::table(ConstantHelper::TABLE_MODIFIERS)
                        ->where('id', $existingMod->id)
                        ->update([
                            'price' => $mod['price'],
                            'ingredient_id' => $ingId,
                            'ingredient_qty' => $mod['ingredient_qty'],
                            'is_active' => true,
                            'updated_at' => now(),
                        ]);
                } else {
                    DB::table(ConstantHelper::TABLE_MODIFIERS)->insert([
                        'group_id' => $groupId,
                        'name' => $mod['name'],
                        'price' => $mod['price'],
                        'ingredient_id' => $ingId,
                        'ingredient_qty' => $mod['ingredient_qty'],
                        'is_active' => true,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        }

        // Gán 3 nhóm topping vào các món Phở
        $phoSkus = ['PHO_TAI', 'PHO_CHIN', 'PHO_DAC_BIET'];
        foreach ($phoSkus as $sku) {
            $itemId = $itemIds[$sku] ?? null;
            if (! $itemId) {
                continue;
            }

            foreach ($groupIds as $idx => $gId) {
                DB::table(ConstantHelper::TABLE_MENU_ITEM_MODIFIER_GROUPS)->upsert(
                    [
                        'menu_item_id' => $itemId,
                        'group_id' => $gId,
                        'sort_order' => $idx + 1,
                    ],
                    ['menu_item_id', 'group_id'],
                    ['sort_order']
                );
            }
        }
    }

    /**
     * 11. Cấu hình món theo chi nhánh (branch_menu_items)
     *
     * @param  array<string, int>  $branchIds
     * @param  array<string, int>  $itemIds
     */
    private function seedBranchMenuItems(array $branchIds, array $itemIds): void
    {
        foreach ($branchIds as $branchCode => $branchId) {
            foreach ($itemIds as $sku => $itemId) {
                DB::table(ConstantHelper::TABLE_BRANCH_MENU_ITEMS)->upsert(
                    [
                        'branch_id' => $branchId,
                        'menu_item_id' => $itemId,
                        'price' => null, // NULL = dùng base_price
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
     * 12. Công thức định lượng theo món + size (recipes & recipe_items)
     *
     * @param  array{itemIds: array<string, int>, variantIds: array<string, array<string, int>>}  $itemData
     * @param  array<string, int>  $ingredientIds
     */
    private function seedRecipesAndItems(array $itemData, array $ingredientIds): void
    {
        $itemIds = $itemData['itemIds'];
        $variantIds = $itemData['variantIds'];

        // Danh sách công thức định lượng chi tiết
        $recipeDefinitions = [
            // Phở tái
            'PHO_TAI' => [
                'Nhỏ' => [
                    'NL_BANH_PHO' => 150.000,
                    'NL_THIT_BO_TAI' => 60.000,
                    'NL_NUOC_DUNG' => 400.000,
                    'NL_HANH_LA' => 15.000,
                ],
                'Lớn' => [
                    'NL_BANH_PHO' => 200.000,
                    'NL_THIT_BO_TAI' => 90.000,
                    'NL_NUOC_DUNG' => 500.000,
                    'NL_HANH_LA' => 20.000,
                ],
                'Đặc biệt' => [
                    'NL_BANH_PHO' => 250.000,
                    'NL_THIT_BO_TAI' => 130.000,
                    'NL_NUOC_DUNG' => 600.000,
                    'NL_HANH_LA' => 25.000,
                ],
            ],
            // Phở chín
            'PHO_CHIN' => [
                'Nhỏ' => [
                    'NL_BANH_PHO' => 150.000,
                    'NL_THIT_BO_CHIN' => 60.000,
                    'NL_NUOC_DUNG' => 400.000,
                    'NL_HANH_LA' => 15.000,
                ],
                'Lớn' => [
                    'NL_BANH_PHO' => 200.000,
                    'NL_THIT_BO_CHIN' => 90.000,
                    'NL_NUOC_DUNG' => 500.000,
                    'NL_HANH_LA' => 20.000,
                ],
                'Đặc biệt' => [
                    'NL_BANH_PHO' => 250.000,
                    'NL_THIT_BO_CHIN' => 130.000,
                    'NL_NUOC_DUNG' => 600.000,
                    'NL_HANH_LA' => 25.000,
                ],
            ],
            // Phở đặc biệt (Tái + Chín)
            'PHO_DAC_BIET' => [
                'Nhỏ' => [
                    'NL_BANH_PHO' => 150.000,
                    'NL_THIT_BO_TAI' => 40.000,
                    'NL_THIT_BO_CHIN' => 40.000,
                    'NL_NUOC_DUNG' => 400.000,
                    'NL_HANH_LA' => 15.000,
                ],
                'Lớn' => [
                    'NL_BANH_PHO' => 200.000,
                    'NL_THIT_BO_TAI' => 60.000,
                    'NL_THIT_BO_CHIN' => 60.000,
                    'NL_NUOC_DUNG' => 500.000,
                    'NL_HANH_LA' => 20.000,
                ],
                'Đặc biệt' => [
                    'NL_BANH_PHO' => 250.000,
                    'NL_THIT_BO_TAI' => 80.000,
                    'NL_THIT_BO_CHIN' => 80.000,
                    'NL_NUOC_DUNG' => 600.000,
                    'NL_HANH_LA' => 25.000,
                ],
            ],
            // Món phụ (không có variant)
            'QUAY' => [
                'default' => [
                    'NL_QUAY' => 3.000,
                ],
            ],
            'TRA_DA' => [
                'default' => [
                    'NL_TRA' => 5.000,
                ],
            ],
            'NUOC_NGOT' => [
                'default' => [
                    'NL_NUOC_NGOT' => 1.000,
                ],
            ],
        ];

        foreach ($recipeDefinitions as $sku => $variants) {
            $itemId = $itemIds[$sku] ?? null;
            if (! $itemId) {
                continue;
            }

            foreach ($variants as $variantName => $ingredients) {
                $variantId = ($variantName === 'default') ? null : ($variantIds[$sku][$variantName] ?? null);

                // Tìm hoặc tạo recipe
                $existingRecipe = DB::table(ConstantHelper::TABLE_RECIPES)
                    ->where('menu_item_id', $itemId)
                    ->where('variant_id', $variantId)
                    ->where('version', 1)
                    ->first();

                if ($existingRecipe) {
                    $recipeId = $existingRecipe->id;
                } else {
                    $recipeId = DB::table(ConstantHelper::TABLE_RECIPES)->insertGetId([
                        'menu_item_id' => $itemId,
                        'variant_id' => $variantId,
                        'version' => 1,
                        'is_active' => true,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }

                // Xóa và chèn lại recipe items
                DB::table(ConstantHelper::TABLE_RECIPE_ITEMS)->where('recipe_id', $recipeId)->delete();

                $recipeItemRows = [];
                foreach ($ingredients as $ingSku => $qty) {
                    $ingId = $ingredientIds[$ingSku] ?? null;
                    if ($ingId) {
                        $recipeItemRows[] = [
                            'recipe_id' => $recipeId,
                            'ingredient_id' => $ingId,
                            'quantity' => $qty,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ];
                    }
                }

                if (! empty($recipeItemRows)) {
                    DB::table(ConstantHelper::TABLE_RECIPE_ITEMS)->insert($recipeItemRows);
                }
            }
        }
    }

    /**
     * 13. Nhà cung cấp (1 nhà cung cấp)
     */
    private function seedSuppliers(): void
    {
        $supplier = [
            'name' => 'Công ty Thực phẩm Sạch Ba Vì',
            'contact' => 'Anh Tuấn',
            'phone' => '0912345678',
            'address' => 'Khu công nghiệp Thực phẩm Hà Nội',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ];

        $existing = DB::table(ConstantHelper::TABLE_SUPPLIERS)
            ->where('name', $supplier['name'])
            ->first();

        if ($existing) {
            DB::table(ConstantHelper::TABLE_SUPPLIERS)
                ->where('id', $existing->id)
                ->update([
                    'contact' => $supplier['contact'],
                    'phone' => $supplier['phone'],
                    'address' => $supplier['address'],
                    'is_active' => true,
                    'updated_at' => now(),
                ]);
        } else {
            DB::table(ConstantHelper::TABLE_SUPPLIERS)->insert($supplier);
        }
    }

    /**
     * 14. Tồn kho khởi tạo (stock_levels)
     *
     * @param  array<string, int>  $warehouseIds
     * @param  array<string, int>  $ingredientIds
     */
    private function seedStockLevels(array $warehouseIds, array $ingredientIds): void
    {
        $initialStocks = [
            'NL_BANH_PHO' => ['quantity' => 50000.000, 'avg_cost' => 18000.00],
            'NL_THIT_BO_TAI' => ['quantity' => 30000.000, 'avg_cost' => 220000.00],
            'NL_THIT_BO_CHIN' => ['quantity' => 30000.000, 'avg_cost' => 200000.00],
            'NL_XUONG_BO' => ['quantity' => 40000.000, 'avg_cost' => 60000.00],
            'NL_HANH_LA' => ['quantity' => 10000.000, 'avg_cost' => 25000.00],
            'NL_QUAY' => ['quantity' => 500.000,   'avg_cost' => 2000.00],
            'NL_NUOC_DUNG' => ['quantity' => 100000.000, 'avg_cost' => 15000.00],
            'NL_TRA' => ['quantity' => 5000.000,  'avg_cost' => 150000.00],
            'NL_NUOC_NGOT' => ['quantity' => 200.000,   'avg_cost' => 8000.00],
        ];

        foreach ($warehouseIds as $branchCode => $warehouseId) {
            foreach ($initialStocks as $sku => $stockData) {
                $ingId = $ingredientIds[$sku] ?? null;
                if (! $ingId) {
                    continue;
                }

                DB::table(ConstantHelper::TABLE_STOCK_LEVELS)->upsert(
                    [
                        'warehouse_id' => $warehouseId,
                        'ingredient_id' => $ingId,
                        'quantity' => $stockData['quantity'],
                        'avg_cost' => $stockData['avg_cost'],
                        'created_at' => now(),
                        'updated_at' => now(),
                    ],
                    ['warehouse_id', 'ingredient_id'],
                    ['quantity', 'avg_cost', 'updated_at']
                );
            }
        }
    }

    /**
     * 15. Chuỗi số chứng từ (document_sequences) cho từng chi nhánh × DocType
     *
     * @param  array<string, int>  $branchIds
     */
    private function seedDocumentSequences(array $branchIds): void
    {
        $docConfigs = [
            ConstantHelper::DOC_ORDER => [
                'prefix' => 'ORD',
                'period_key' => date('Ymd'),
                'padding' => 5,
                'reset_policy' => SequenceResetPolicy::DAILY->value,
            ],
            ConstantHelper::DOC_INVOICE => [
                'prefix' => 'INV',
                'period_key' => date('Ymd'),
                'padding' => 5,
                'reset_policy' => SequenceResetPolicy::DAILY->value,
            ],
            ConstantHelper::DOC_KITCHEN_TICKET => [
                'prefix' => 'KT',
                'period_key' => date('Ymd'),
                'padding' => 5,
                'reset_policy' => SequenceResetPolicy::DAILY->value,
            ],
            ConstantHelper::DOC_PURCHASE_ORDER => [
                'prefix' => 'PO',
                'period_key' => date('Ym'),
                'padding' => 4,
                'reset_policy' => SequenceResetPolicy::MONTHLY->value,
            ],
            ConstantHelper::DOC_STOCK_ADJUSTMENT => [
                'prefix' => 'ADJ',
                'period_key' => date('Ym'),
                'padding' => 4,
                'reset_policy' => SequenceResetPolicy::MONTHLY->value,
            ],
        ];

        foreach ($branchIds as $branchCode => $branchId) {
            foreach ($docConfigs as $docType => $cfg) {
                DB::table(ConstantHelper::TABLE_DOCUMENT_SEQUENCES)->upsert(
                    [
                        'branch_id' => $branchId,
                        'doc_type' => $docType,
                        'period_key' => $cfg['period_key'],
                        'prefix' => $cfg['prefix'],
                        'current_no' => 0,
                        'padding' => $cfg['padding'],
                        'reset_policy' => $cfg['reset_policy'],
                        'created_at' => now(),
                        'updated_at' => now(),
                    ],
                    ['branch_id', 'doc_type', 'period_key'],
                    ['prefix', 'padding', 'reset_policy', 'updated_at']
                );
            }
        }
    }
}
