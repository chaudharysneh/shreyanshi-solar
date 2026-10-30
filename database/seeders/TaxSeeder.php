<?php

namespace Database\Seeders;

use App\Models\Tax;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class TaxSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $taxes = [
            ['name' => 'GST (CGST + SGST)', 'rate' => 5.00, 'is_active' => true],
            ['name' => 'GST (CGST + SGST)', 'rate' => 12.00, 'is_active' => true],
            ['name' => 'GST (CGST + SGST)', 'rate' => 10.00, 'is_active' => true],
            ['name' => 'GST (CGST + SGST)', 'rate' => 8.00, 'is_active' => true],
            ['name' => 'GST (IGST)', 'rate' => 18.00, 'is_active' => true],
        ];

        foreach ($taxes as $tax) {
            Tax::create($tax);
        }
    }
}
