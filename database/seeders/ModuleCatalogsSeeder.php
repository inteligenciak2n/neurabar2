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
                'code' => ModuleCode::SelfOrder->value,
                'name' => 'Auto Serviço de Pedido',
                'description' => 'Interface de anotação de pedidos para visitantes realizarem o auto atendimento.',
                'category' => 'premium',
                'billing_type' => ModuleBillingType::Hybrid,
                'base_monthly_price' => 2990,
                'unit_of_measure' => 'order',
                'dependencies' => [ModuleCode::Menu->value],
                'required_roles' => ['owner', 'general_manager', 'section_manager', 'attendant'],
                'sort_order' => 25,
                'active' => true,
            ],
            [
                'code' => ModuleCode::DirectWaiter->value,
                'name' => 'Direct Garçom',
                'description' => "Direct Garçom — Esse é o nosso queridinho 🤩\n\nVocê coloca a Tag NeuraBar na mesa e pronto: seu cliente já sai pedindo direto pelo sistema, sem precisar chamar ninguém.\n\nSabe quando o cliente quer só mais uma bebida e já sabe o que quer? Com o DIRECT, ele mesmo faz o pedido em segundos — sem esperar, sem enrolar. Você ganha tempo, seu garçom ganha fôlego, e o cliente sai feliz.",
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
                'description' => "Pedidos de Delivery e Retirada — direto pelo celular, sem intermediários\n\nSeus clientes acessam seu cardápio atualizado de qualquer celular, a qualquer hora, e fazem o pedido direto pra você — sem precisar ligar, sem confusão.\n\nE o melhor: você não paga nada por pedido. Diferente do iFood e outros apps, aqui não tem taxa por entrega. Em poucos pedidos, a ativação já se paga sozinha.",
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
                'description' => "Painel Financeiro — tudo que aconteceu no seu estabelecimento, em um só lugar\n\nQuantas mesas foram atendidas, o que mais vendeu, o que os clientes sempre pedem, como foi o mês passado, como foi o ano passado — tudo registrado e fácil de ver.\n\nCom esses dados na mão, você começa a tomar decisões com mais segurança. Por exemplo: se novembro está indo bem, você já consegue comparar com o novembro do ano passado e se preparar para um dezembro ainda melhor.\n\nChega de achar. Aqui você sabe.",
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
                'description' => "Impressão Direta — o pedido vai pra cozinha sem você precisar gritar\n\nAssim que o pedido é feito, ele já imprime automaticamente na cozinha ou no bar — sem o garçom precisar sair do lugar, sem papel escrito à mão, sem pedido perdido.\n\nMenos erro, menos correria, atendimento mais rápido.",
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
                'description' => "Nota Fiscal — emita sem sair do sistema\n\nNa hora de fechar a conta, você emite o cupom fiscal direto pelo NeuraBar — com ou sem CPF/CNPJ do cliente, em segundos.\n\nPrecisa emitir uma nota completa (DANFE) para fornecedor ou outra finalidade? Também tem. Tudo no mesmo lugar, sem abrir outro programa, sem complicação.",
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
            $catalog = ModuleCatalog::firstOrCreate(
                ['code' => $module['code']], array_merge($module, ['active' => $module['active'] || config('app.env') === 'local'])
            );
            $catalog->update([
                'description' => $module['description'],
                'active' => $module['active'] || config('app.env') === 'local',
            ]);
        }
    }
}
