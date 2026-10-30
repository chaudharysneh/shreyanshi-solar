<?php

namespace Database\Seeders;

use App\Models\Subsidy;
use Illuminate\Database\Seeder;

class SubsidySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $subsidies = [
            [
                'category' => 'residential_0_2',
                'label' => 'Residential Subsidy (0-2 kW)',
                'amount' => 30000.00,
                'is_active' => true,
            ],
            [
                'category' => 'residential_2_3',
                'label' => 'Residential Subsidy (2-3 kW)',
                'amount' => 18000.00,
                'is_active' => true,
            ],
            [
                'category' => 'residential_above_3',
                'label' => 'Residential Subsidy (Above 3 kW)',
                'amount' => 78000.00,
                'is_active' => true,
            ],
            [
                'category' => 'common_meter',
                'label' => 'Common Meter',
                'amount' => 5000.00,
                'is_active' => true,
            ],
        ];

        foreach ($subsidies as $subsidy) {
            Subsidy::updateOrCreate(
                ['category' => $subsidy['category']],
                $subsidy
            );
        }
    }
}
