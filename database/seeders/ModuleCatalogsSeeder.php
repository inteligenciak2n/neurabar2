<?php

namespace Database\Seeders;

use App\Enums\ModuleBillingType;
use App\Enums\ModuleCode;
use App\Models\Tenant\ModuleCatalog;
use Illuminate\Database\Seeder;

class ModuleCatalogsSeeder extends Seeder
{
    public function run(): void
    {
        $modules = [
            [
                'code' => ModuleCode::Menu->value,
                'name' => 'Cardápio',
                'description' => 'Gestão de cardápio, produtos, categorias e combos.',
                'category' => 'basic',
                'billing_type' => ModuleBillingType::Fixed,
                'base_monthly_price' => 0,
                'unit_of_measure' => null,
                'dependencies' => [],
                'required_roles' => ['owner', 'general_manager'],
                'sort_order' => 1,
                'active' => true,
            ],
            [
                'code' => ModuleCode::Kds->value,
                'name' => 'KDS',
                'description' => 'Kitchen Display System para acompanhamento de pedidos.',
                'category' => 'premium',
                'billing_type' => ModuleBillingType::Hybrid,
                'base_monthly_price' => 4990,
                'unit_of_measure' => 'order',
                'dependencies' => [ModuleCode::Menu->value],
                'required_roles' => ['owner', 'general_manager', 'section_manager', 'attendant'],
                'sort_order' => 10,
                'active' => true,
            ],
            [
                'code' => ModuleCode::Taker->value,
                'name' => 'Anotar Pedido',
                'description' => 'Interface de anotação de pedidos para atendentes.',
                'category' => 'premium',
                'billing_type' => ModuleBillingType::Hybrid,
                'base_monthly_price' => 3990,
                'unit_of_measure' => 'order',
                'dependencies' => [ModuleCode::Menu->value],
                'required_roles' => ['owner', 'general_manager', 'section_manager', 'attendant'],
                'sort_order' => 20,
                'active' => true,
            ],
            [
                'code' => ModuleCode::DirectWaiter->value,
                'name' => 'Direct Garçom',
                'description' => 'Chamada de garçom e sinalização por mesa.',
                'category' => 'premium',
                'billing_type' => ModuleBillingType::Hybrid,
                'base_monthly_price' => 2990,
                'unit_of_measure' => 'signal',
                'dependencies' => [],
                'required_roles' => ['owner', 'general_manager', 'section_manager', 'attendant'],
                'sort_order' => 30,
                'active' => false,
            ],
            [
                'code' => ModuleCode::Delivery->value,
                'name' => 'Delivery',
                'description' => 'Gestão de pedidos para delivery e retirada.',
                'category' => 'premium',
                'billing_type' => ModuleBillingType::Hybrid,
                'base_monthly_price' => 5990,
                'unit_of_measure' => 'order',
                'dependencies' => [ModuleCode::Menu->value],
                'required_roles' => ['owner', 'general_manager', 'section_manager', 'attendant'],
                'sort_order' => 40,
                'active' => false,
            ],
            [
                'code' => ModuleCode::ProductionDashboard->value,
                'name' => 'Dashboard de Produção',
                'description' => 'Painel de acompanhamento da produção da cozinha.',
                'category' => 'premium',
                'billing_type' => ModuleBillingType::Fixed,
                'base_monthly_price' => 3990,
                'unit_of_measure' => null,
                'dependencies' => [ModuleCode::Menu->value],
                'required_roles' => ['owner', 'general_manager', 'section_manager'],
                'sort_order' => 50,
                'active' => false,
            ],
            [
                'code' => ModuleCode::FinancialDashboard->value,
                'name' => 'Dashboard Financeiro',
                'description' => 'Painel financeiro e relatórios de vendas.',
                'category' => 'premium',
                'billing_type' => ModuleBillingType::Fixed,
                'base_monthly_price' => 4990,
                'unit_of_measure' => null,
                'dependencies' => [ModuleCode::Menu->value],
                'required_roles' => ['owner', 'general_manager'],
                'sort_order' => 60,
                'active' => false,
            ],
            [
                'code' => ModuleCode::DirectPrint->value,
                'name' => 'Impressão Direta',
                'description' => 'Impressão automática de pedidos em impressoras térmicas.',
                'category' => 'premium',
                'billing_type' => ModuleBillingType::Hybrid,
                'base_monthly_price' => 3490,
                'unit_of_measure' => 'order',
                'dependencies' => [ModuleCode::Menu->value],
                'required_roles' => ['owner', 'general_manager'],
                'sort_order' => 70,
                'active' => false,
            ],
            [
                'code' => ModuleCode::FiscalNote->value,
                'name' => 'Nota Fiscal',
                'description' => 'Emissão de notas fiscais e cupons fiscais.',
                'category' => 'premium',
                'billing_type' => ModuleBillingType::Hybrid,
                'base_monthly_price' => 6990,
                'unit_of_measure' => 'order',
                'dependencies' => [ModuleCode::Menu->value],
                'required_roles' => ['owner', 'general_manager'],
                'sort_order' => 80,
                'active' => false,
            ],
            [
                'code' => ModuleCode::VoiceCommand->value,
                'name' => 'Comando por Voz',
                'description' => 'Transcrição de comandos de voz para anotação de pedidos.',
                'category' => 'premium',
                'billing_type' => ModuleBillingType::Hybrid,
                'base_monthly_price' => 4490,
                'unit_of_measure' => 'signal',
                'dependencies' => [ModuleCode::Menu->value],
                'required_roles' => ['owner', 'general_manager', 'section_manager', 'attendant'],
                'sort_order' => 90,
                'active' => false,
            ],
        ];

        foreach ($modules as $module) {
            ModuleCatalog::firstOrCreate(
                ['code' => $module['code']], array_merge($module, ['active' => $module['active'] || config('app.env') === 'local'])
            );
        }
    }
}
