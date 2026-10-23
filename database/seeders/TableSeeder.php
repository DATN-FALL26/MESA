<?php

namespace Database\Seeders;

use App\Models\Table;
use Illuminate\Database\Seeder;

class TableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $zones = [
            'Tầng 1' => 6,
            'Tầng 2' => 4,
        ];

        foreach ($zones as $zone => $count) {
            for ($i = 1; $i <= $count; $i++) {
                Table::create([
                    'name' => "Bàn {$i}",
                    'zone' => $zone,
                    'capacity' => ($i % 2 === 0) ? 6 : 4,
                    'status' => collect(['empty', 'occupied', 'cleaning'])->random(),
                ]);
            }
        }
    }
}
