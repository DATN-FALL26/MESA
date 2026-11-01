<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\BranchStatus;
use App\Enums\TableShape;
use App\Enums\TableStatus;
use App\Models\Area;
use App\Models\Branch;
use App\Models\DiningTable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TableSeeder extends Seeder
{
    /**
     * Seed table areas and dining tables for every active branch.
     */
    public function run(): void
    {
        $branches = Branch::query()
            ->where('status', BranchStatus::ACTIVE->value)
            ->get();

        if ($branches->isEmpty()) {
            $branches = collect([
                Branch::query()->create([
                    'code' => 'BD01',
                    'name' => 'CN Ba Đình',
                    'timezone' => 'Asia/Ho_Chi_Minh',
                    'status' => BranchStatus::ACTIVE,
                ]),
            ]);
        }

        DB::transaction(function () use ($branches): void {
            foreach ($branches as $branch) {
                foreach ([
                    ['code' => 'B', 'name' => 'Khu B (Tầng 2)', 'sort_order' => 2],
                    ['code' => 'C', 'name' => 'Khu C (Tầng 3)', 'sort_order' => 3],
                ] as $areaData) {
                    $area = Area::query()->updateOrCreate(
                        [
                            'branch_id' => $branch->getKey(),
                            'code' => $areaData['code'],
                        ],
                        [
                            'name' => $areaData['name'],
                            'default_seats' => 4,
                            'sort_order' => $areaData['sort_order'],
                            'is_active' => true,
                        ],
                    );

                    for ($i = 1; $i <= 8; $i++) {
                        $code = \sprintf('%s%02d', $areaData['code'], $i);
                        $table = DiningTable::query()->firstOrNew([
                            'branch_id' => $branch->getKey(),
                            'code' => $code,
                        ]);

                        $table->area_id = $area->getKey();
                        $table->seats = 4;
                        $table->shape = TableShape::SQUARE;
                        $table->pos_x = (($i - 1) % 4) + 1;
                        $table->pos_y = intdiv($i - 1, 4) + 1;
                        $table->width = 1;
                        $table->height = 1;
                        $table->sort_order = $i;
                        $table->is_active = true;

                        if (! $table->exists) {
                            $table->status = TableStatus::AVAILABLE;
                        }

                        $table->save();
                    }
                }
            }
        });
    }
}
