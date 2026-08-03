<?php

namespace Database\Seeders;

use App\Enums\ModuleCode;
use App\Models\Tenant\ModuleUsageTier;
use Illuminate\Database\Seeder;

class ModuleUsageTiersSeeder extends Seeder
{
    public function run(): void
    {
        $hybridModules = [
            ModuleCode::Kds,
            ModuleCode::Taker,
            ModuleCode::Delivery,
            ModuleCode::DirectPrint,
            ModuleCode::FiscalNote,
            ModuleCode::VoiceCommand,
        ];

        foreach ($hybridModules as $code) {
            ModuleUsageTier::updateOrCreate(
                [
                    'module_code' => $code->value,
                    'min_quantity' => 0,
                ],
                [
                    'max_quantity' => 1000,
                    'included_quantity' => 100,
                    'price_per_unit' => 0,
                    'flat_price' => 0,
                    'overage_price_per_unit' => 500,
                    'overage_flat_fee' => 0,
                    'currency' => 'BRL',
                ]
            );

            ModuleUsageTier::updateOrCreate(
                [
                    'module_code' => $code->value,
                    'min_quantity' => 1001,
                ],
                [
                    'max_quantity' => null,
                    'included_quantity' => 100,
                    'price_per_unit' => 0,
                    'flat_price' => 0,
                    'overage_price_per_unit' => 300,
                    'overage_flat_fee' => 0,
                    'currency' => 'BRL',
                ]
            );
        }
    }
}
