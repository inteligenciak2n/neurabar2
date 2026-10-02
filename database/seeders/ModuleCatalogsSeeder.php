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
                'description' => "Cardápio Digital — sua vitrine aberta 24 horas\n\nMonte seu cardápio com foto, descrição e preço. Defina tamanhos, permita troca de ingredientes, crie combos — tudo do jeito que o seu negócio funciona.\n\n- O Cardápio Digital é cortesia para quem assina o módulo KDS, ou Anotar Pedido ou o Delivery",
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
                'description' => "KDS — Cada pedido no monitor certo, no local certo\n\nAssim que um pedido entra, ele já aparece direto no monitor do setor responsável — cozinha, bar, churrasqueira, onde for. Cada equipe vê só o que é dela, sem confusão.\n\nNa tela: o pedido, a mesa e há quanto tempo está esperando. Ninguém precisa perguntar nada — é só olhar e fazer.",
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
                'description' => "Pedidos no Celular — seu garçom anota na mesa e o pedido já vai pra cozinha\n\nChega de ir e vir. Com o celular na mão, seu garçom abre o cardápio, anota o pedido na hora e pronto — já chegou na cozinha.\n\nRápido, simples, sem erro.",
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
                'description' => "Pagamento pelo Celular — cliente paga na hora, sem esperar\n\nAcabou a conta, o cliente paga direto pelo celular — sem precisar chamar o garçom, sem esperar a maquininha, sem fila.\n\nMenos gargalo no fechamento, mais giro de mesa pra você. E o controle de quem pagou, quanto e quando fica tudo registrado — nada escapa, nada some.",
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
                'description' => "Painel da Cozinha — seus clientes vão querer ficar olhando\n\nSabe aqueles painéis que você vê nas redes de fast food, onde cada pedido entra e sai em tempo real? Agora você tem o mesmo no seu bar.\n\nColoca à vista e deixa rolar — seus clientes ficam vidrados de tanto pedido entrando e saindo. Cria aquela sensação de movimento, de casa cheia, de negócio que funciona de verdade.",
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
                'description' => "Comandos de Voz — seu garçom só precisa da boca e do celular\n\nSem digitar, sem papel, sem espera. Ele fala, o pedido já vai pra cozinha.\n\n\"Suco de laranja, mesa 4, copo com gelo.\"\n\"Mesa 5, mais uma Brahma.\"\n\"Copo na mesa 3.\"\n\"Batata frita pra mesa 7.\"\n\nPronto. Chegou lá. Direitinho.\n\nSeu garçom vira um técnico de seleção — vai soltando os comandos pelo celular enquanto ainda está no salão, sem parar o atendimento nem um segundo.",
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
                'name' => $module['name'],
                'description' => $module['description'],
                'active' => $module['active'] || config('app.env') === 'local',
            ]);
        }
    }
}
