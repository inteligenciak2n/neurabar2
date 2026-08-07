<?php

namespace Database\Seeders;

use App\Enums\ModuleCode;
use App\Models\Tenant\PlanCatalog;
use Illuminate\Database\Seeder;

class PlanCatalogsSeeder extends Seeder
{
    public function run(): void
    {
        $plans = [
            [
                'code' => 'basic',
                'name' => 'Basic',
                'description' => 'Plano básico para pequenos estabelecimentos.',
                'sort_order' => 1,
                'monthly_price' => 9900,
                'included_modules' => [
                    ModuleCode::Menu->value,
                ],
                'active' => true,
            ],
            [
                'code' => 'pro',
                'name' => 'Pro',
                'description' => 'Plano intermediário com recursos avançados.',
                'sort_order' => 2,
                'monthly_price' => 19900,
                'included_modules' => [
                    ModuleCode::Menu->value,
                    ModuleCode::Kds->value,
                    ModuleCode::Taker->value,
                    ModuleCode::DirectWaiter->value,
                    ModuleCode::Delivery->value,
                ],
                'active' => true,
            ],
            [
                'code' => 'enterprise',
                'name' => 'Enterprise',
                'description' => 'Plano completo para redes e grupos.',
                'sort_order' => 3,
                'monthly_price' => 49900,
                'included_modules' => [
                    ModuleCode::Menu->value,
                    ModuleCode::Kds->value,
                    ModuleCode::Taker->value,
                    ModuleCode::DirectWaiter->value,
                    ModuleCode::Delivery->value,
                    ModuleCode::ProductionDashboard->value,
                    ModuleCode::FinancialDashboard->value,
                    ModuleCode::DirectPrint->value,
                    ModuleCode::FiscalNote->value,
                    ModuleCode::VoiceCommand->value,
                ],
                'active' => true,
            ],
        ];

        foreach ($plans as $plan) {
            PlanCatalog::firstOrCreate(['code' => $plan['code']], $plan);
        }
    }
}
