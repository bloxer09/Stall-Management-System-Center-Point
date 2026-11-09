<?php

namespace Database\Seeders;

use App\Models\Stall;
use App\Models\StallLocation;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class FloorStallSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            $floors = [
                ['name' => 'Ground Floor', 'description' => 'High foot-traffic retail arcade by the main entrance.', 'rows' => 6, 'cols' => 8, 'prefix' => 'G', 'count' => 14, 'price' => 6500],
                ['name' => 'Second Floor', 'description' => 'Specialty shops, services, and boutiques.', 'rows' => 5, 'cols' => 6, 'prefix' => 'S', 'count' => 8, 'price' => 4500],
                ['name' => 'Food Court', 'description' => 'Food counters with shared dining area.', 'rows' => 4, 'cols' => 5, 'prefix' => 'F', 'count' => 8, 'price' => 5000],
            ];

            foreach ($floors as $order => $floor) {
                $location = StallLocation::firstOrCreate(['name' => $floor['name']], [
                    'description' => $floor['description'], 'sort_order' => $order + 1,
                    'grid_rows' => $floor['rows'], 'grid_cols' => $floor['cols'],
                ]);
                $cell = 0;
                for ($i = 1; $i <= $floor['count']; $i++) {
                    $number = sprintf('%s-%02d', $floor['prefix'], $i);
                    $placed = !in_array($number, ['G-13', 'G-14'], true);
                    if ($placed && $cell % 3 === 2) $cell++;
                    Stall::firstOrCreate(['stall_number' => $number], [
                        'location_id' => $location->id, 'status' => 'available', 'price' => $floor['price'],
                        'description' => "{$floor['name']} unit {$number}",
                        'grid_row' => $placed ? intdiv($cell, $floor['cols']) + 1 : null,
                        'grid_col' => $placed ? ($cell % $floor['cols']) + 1 : null,
                    ]);
                    if ($placed) $cell++;
                }
            }
        });

        $this->command?->info('Floors and stalls seeded. No users or leases were created.');
    }
}
