<?php

namespace Tests\Unit\Components;

use PHPUnit\Framework\TestCase;

class SubscriptionModuleCardTest extends TestCase
{
    public function test_enabled_and_disabled_cards_share_the_same_fixed_height(): void
    {
        $component = file_get_contents(dirname(__DIR__, 3).'/resources/js/Components/SubscriptionModuleCard.vue');

        $this->assertNotFalse($component);
        $this->assertStringContainsString('min-h-[11.5rem]', $component);
        $this->assertStringContainsString('items-stretch', $component);
        $this->assertStringContainsString('self-stretch', $component);
        $this->assertStringContainsString('description.split(/\\n\\n+/)', $component);
        $this->assertStringContainsString('{{ __(module.name) }}', $component);
        $this->assertStringContainsString('min-w-0 font-heading text-lg font-bold leading-tight break-words sm:text-xl', $component);
        $this->assertStringContainsString("v-if=\"module.code === 'menu'\"", $component);
        $this->assertStringContainsString("__('Update and manage')", $component);
        $this->assertStringContainsString("__('your menu here')", $component);
        $this->assertStringNotContainsString('line-clamp-2', $component);
        $this->assertStringContainsString('w-full text-right font-semibold', $component);
        $this->assertStringContainsString('{{ clickToAddLine1 }}', $component);
        $this->assertStringContainsString('{{ clickToAddPriceLine }}', $component);
        $this->assertStringContainsString('{{ sequence }}', $component);
        $this->assertStringContainsString('text-[8rem]', $component);
        $this->assertStringContainsString('pointer-events-none', $component);
    }

    public function test_subscription_index_passes_a_sequence_number_to_each_module_card(): void
    {
        $page = file_get_contents(dirname(__DIR__, 3).'/resources/js/Pages/Settings/Subscription/Index.vue');

        $this->assertNotFalse($page);
        $this->assertStringContainsString('v-for="(module, moduleIndex) in availableModules"', $page);
        $this->assertStringContainsString(':sequence="moduleIndex + 1"', $page);
        $this->assertStringContainsString("delivery: 'Delivery sales copy'", $page);
        $this->assertStringContainsString("menu: 'Menu sales copy'", $page);
        $this->assertStringContainsString("self_order: 'Self order sales copy'", $page);
        $this->assertStringContainsString("taker: 'Taker sales copy'", $page);
        $this->assertStringContainsString("kds: 'Kds sales copy'", $page);
        $this->assertStringContainsString("direct_print: 'Direct print sales copy'", $page);
        $this->assertStringContainsString("direct_waiter: 'Direct waiter sales copy'", $page);
        $this->assertStringContainsString("financial_dashboard: 'Financial dashboard sales copy'", $page);
        $this->assertStringContainsString("production_dashboard: 'Production dashboard sales copy'", $page);
        $this->assertStringContainsString("fiscal_note: 'Fiscal note sales copy'", $page);
        $this->assertStringContainsString("voice_command: 'Voice command sales copy'", $page);
        $this->assertStringContainsString(':learn-more-label="__(\'Click here and learn more\')"', $page);
        $this->assertStringContainsString(':activate-label="__(\'Click here to activate this module\')"', $page);
        $this->assertStringContainsString(':monthly-value="moduleLearnMoreMonthlyValue(module)"', $page);
        $this->assertStringContainsString(':customer-access="moduleLearnMoreCustomerAccess(module)"', $page);
        $this->assertStringContainsString(':order-flow="moduleLearnMoreOrderFlow(module)"', $page);
        $this->assertStringContainsString(':advantage="moduleLearnMoreAdvantage(module)"', $page);
        $this->assertStringContainsString(':savings-title="moduleLearnMoreSavingsTitle(module)"', $page);
        $this->assertStringContainsString(':savings-kind="moduleLearnMoreSavingsKind(module)"', $page);
        $this->assertStringContainsString('proratedAmountFor(module.monthly_price)', $page);
        $this->assertStringContainsString(':savings-estimate-label="moduleLearnMoreSavingsEstimateLabel(module)"', $page);
        $this->assertStringContainsString(':savings-disclaimer="moduleLearnMoreSavingsDisclaimer(module)"', $page);
        $this->assertStringContainsString(':monthly-value-title="__(\'Monthly value\')"', $page);
        $this->assertStringContainsString(':customer-access-title="moduleLearnMoreCustomerAccessTitle(module)"', $page);
        $this->assertStringContainsString(':order-flow-title="moduleLearnMoreOrderFlowTitle(module)"', $page);
        $this->assertStringContainsString(':advantage-title="moduleLearnMoreAdvantageTitle(module)"', $page);
        $this->assertStringContainsString(':kitchen-trip-time-label="moduleLearnMoreKitchenTripTimeLabel(module)"', $page);
        $this->assertStringContainsString(':kitchen-trip-count-label="moduleLearnMoreKitchenTripCountLabel(module)"', $page);
        $this->assertStringContainsString(':initial-work-days="moduleLearnMoreInitialWorkDays(module)"', $page);
        $this->assertStringContainsString("__('Simple service time')", $page);
        $this->assertStringContainsString("__('Simple order count')", $page);
    }

    public function test_card_opens_a_learn_more_panel_from_the_bottom_right_button(): void
    {
        $component = file_get_contents(dirname(__DIR__, 3).'/resources/js/Components/SubscriptionModuleCard.vue');
        $learnMore = file_get_contents(dirname(__DIR__, 3).'/resources/js/Components/SubscriptionModuleLearnMore.vue');

        $this->assertNotFalse($component);
        $this->assertNotFalse($learnMore);
        $this->assertStringContainsString('absolute bottom-3 right-3', $component);
        $this->assertStringContainsString('{{ learnMoreLabel }}', $component);
        $this->assertStringContainsString('showLearnMore = true', $component);
        $this->assertStringContainsString('bg-primary', $component);
        $this->assertStringContainsString('text-sand', $component);
        $this->assertStringContainsString('[text-shadow:0_0_10px_rgba(196,179,155,0.9)]', $component);
        $this->assertStringContainsString('{{ monthlyValueTitle }}', $learnMore);
        $this->assertStringContainsString('{{ customerAccessTitle }}', $learnMore);
        $this->assertStringContainsString('{{ orderFlowTitle }}', $learnMore);
        preg_match_all('/<h3 class="font-heading text-xl font-bold">\{\{ (\w+) \}\}<\/h3>/', $learnMore, $cardTitles);
        $this->assertSame([
            'customerAccessTitle',
            'orderFlowTitle',
            'advantageTitle',
            'monthlyValueTitle',
            'savingsTitle',
        ], $cardTitles[1]);
        preg_match_all('/<section(?: v-if="[^"]+")? class="rounded-2xl bg-(\S+)/', $learnMore, $cardColors);
        $this->assertSame(['sand', 'ocean-light', 'primary', 'warm-gold', 'ocean-deep'], $cardColors[1]);
        $this->assertStringContainsString('v-if="orderFlow"', $learnMore);
        $this->assertStringContainsString('v-if="customerAccess"', $learnMore);
        $this->assertStringContainsString('v-if="advantage"', $learnMore);
        $this->assertStringContainsString("savingsKind === 'print'", $learnMore);
        $this->assertStringContainsString('kitchenTripSeconds', $learnMore);
        $this->assertStringContainsString('kitchenTripCount', $learnMore);
        $this->assertStringContainsString('freelancerDailyValue', $learnMore);
        $this->assertStringContainsString('waiterCount', $learnMore);
        $this->assertStringContainsString('workDays', $learnMore);
        $this->assertStringContainsString('{{ secondsLabel }}', $learnMore);
        $this->assertStringContainsString('ref(30)', $learnMore);
        $this->assertStringContainsString('ref(props.initialWorkDays)', $learnMore);
        $this->assertStringContainsString('ref(150)', $learnMore);
        $this->assertStringContainsString('ref(1)', $learnMore);
        $this->assertStringContainsString('grid-cols-2 items-stretch', $learnMore);
        $this->assertStringContainsString('from-ocean-deep via-primary to-warm-gold', $learnMore);
        $this->assertStringContainsString('bg-sand', $learnMore);
        $this->assertStringContainsString('bg-ocean-light', $learnMore);
        $this->assertStringContainsString('bg-ocean-deep', $learnMore);
        $this->assertStringContainsString('v-if="savingsTitle"', $learnMore);
        $this->assertStringContainsString('averageOrderValue', $learnMore);
        $this->assertStringContainsString('monthlyOrderCount', $learnMore);
        $this->assertStringContainsString('marketplaceFeePercent', $learnMore);
        $this->assertStringContainsString('ref(45)', $learnMore);
        $this->assertStringContainsString('ref(12)', $learnMore);
        $this->assertStringNotContainsString('ref(27)', $learnMore);
        $this->assertStringContainsString('grid-cols-3 items-stretch', $learnMore);
        $this->assertStringContainsString('min-h-[2.75rem]', $learnMore);
        $this->assertStringContainsString('h-11', $learnMore);
        $this->assertStringContainsString('text-4xl font-bold', $learnMore);
        $this->assertStringContainsString('{{ estimatedSavingsAmount }}', $learnMore);
        $this->assertStringContainsString('{{ savingsDisclaimer }}', $learnMore);
        $this->assertStringContainsString('v-if="savingsDisclaimer"', $learnMore);
        $this->assertSame(2, substr_count($learnMore, '{{ activateLabel }}'));
        $this->assertSame(2, substr_count($learnMore, 'activate-module-from-learn-more'));
        $this->assertStringContainsString('px-4 py-1.5 text-xs', $learnMore);
        $this->assertStringNotContainsString('w-full rounded-full bg-warm-gold', $learnMore);
        $this->assertStringNotContainsString('v-if="!enabled"', $learnMore);
        $this->assertStringContainsString("@click=\"\$emit('activate')\"", $learnMore);
        $this->assertStringContainsString('@activate="activateFromLearnMore"', $component);
        $this->assertStringContainsString('showLearnMore.value = false', $component);
        $this->assertStringContainsString('if (! props.enabled)', $component);
    }

    public function test_portuguese_click_to_add_copy_is_split_into_click_here_and_price(): void
    {
        $translations = json_decode(
            file_get_contents(dirname(__DIR__, 3).'/resources/translations/pt/Index.json'),
            true,
            flags: JSON_THROW_ON_ERROR,
        );

        $this->assertSame('Clique aqui', $translations['Click here to add line 1']);
        $this->assertSame('Adicione essa função por :price', $translations['Click here to add for price']);
        $this->assertStringContainsString('Pedidos de Delivery e Retirada', $translations['Delivery sales copy']);
        $this->assertStringContainsString('não paga nada por pedido', $translations['Delivery sales copy']);
        $this->assertStringContainsString('iFood', $translations['Delivery sales copy']);
        $this->assertSame('Clique aqui e saiba mais', $translations['Click here and learn more']);
        $this->assertSame('Clique aqui para ativar esse módulo', $translations['Click here to activate this module']);
        $this->assertSame('Cardápio', $translations['Menu']);
        $this->assertSame('Atualize e gerencie', $translations['Update and manage']);
        $this->assertSame('seu cardápio aqui', $translations['your menu here']);
        $this->assertSame('O valor mensal', $translations['Monthly value']);
        $this->assertSame('Como o cliente acessa', $translations['How the customer accesses']);
        $this->assertSame('Como funciona', $translations['How it works']);
        $this->assertSame('Vantagem', $translations['Advantage']);
        $this->assertStringContainsString('garçom vendedor', $translations['Learn more direct print advantage']);
        $this->assertStringContainsString('melhores pratos da casa', $translations['Learn more direct print advantage']);
        $this->assertStringContainsString('mais atendimentos com o mesmo time', $translations['Learn more direct print advantage']);
        $this->assertStringContainsString('sai um papel impresso em cada setor', $translations['Learn more direct print how it works']);
        $this->assertStringContainsString('a equipe já sabe o que preparar', $translations['Learn more direct print how it works']);
        $this->assertStringContainsString('Menos dor de cabeça e mais agilidade', $translations['Learn more direct print how it works']);
        $this->assertStringContainsString('não precisa baixar nenhum aplicativo', $translations['Learn more delivery customer access']);
        $this->assertStringContainsString('QR Code', $translations['Learn more delivery customer access']);
        $this->assertStringContainsString('WhatsApp', $translations['Learn more delivery customer access']);
        $this->assertStringContainsString('Instagram', $translations['Learn more delivery customer access']);
        $this->assertStringContainsString('Ímã de geladeira', $translations['Learn more delivery customer access']);
        $this->assertStringContainsString('Tag NeuraBar', $translations['Learn more delivery customer access']);
        $this->assertStringContainsString('aproxime e visualize o cardápio', $translations['Learn more delivery customer access']);
        $this->assertSame('Como o pedido chega ao restaurante', $translations['How the order reaches the restaurant']);
        $this->assertStringContainsString('chega direto na cozinha', $translations['Learn more delivery order flow']);
        $this->assertStringContainsString('decide se aceita ou não', $translations['Learn more delivery order flow']);
        $this->assertStringContainsString('Pedido enviado', $translations['Learn more delivery order flow']);
        $this->assertStringContainsString('Pedido aceito', $translations['Learn more delivery order flow']);
        $this->assertStringContainsString('Em produção', $translations['Learn more delivery order flow']);
        $this->assertStringContainsString('Saiu para entrega', $translations['Learn more delivery order flow']);
        $this->assertStringContainsString('Entregue', $translations['Learn more delivery order flow']);
        $this->assertStringContainsString('nome e o celular', $translations['Learn more delivery order flow']);
        $this->assertStringContainsString('histórico de pedidos', $translations['Learn more delivery order flow']);
        $this->assertStringContainsString('Acionando hoje, você pagará :amount no dia da sua fatura', $translations['Learn more monthly value']);
        $this->assertStringContainsString('próximas faturas no valor da mensalidade', $translations['Learn more monthly value']);
        $this->assertSame('Como você economiza com esse módulo:', $translations['How you save with this module']);
        $this->assertStringContainsString('nenhum pedido para entrega vem com taxas adicionais', $translations['Learn more savings copy']);
        $this->assertSame('Valor médio dos pedidos', $translations['Average order value']);
        $this->assertSame('Tempo de ir à cozinha', $translations['Kitchen trip time']);
        $this->assertSame('Quantidade de idas à cozinha', $translations['Kitchen trip count']);
        $this->assertSame('Tempo de atendimento simples', $translations['Simple service time']);
        $this->assertSame('Quantidade de Pedidos Simples', $translations['Simple order count']);
        $this->assertSame('Valor do freela por dia (8 horas)', $translations['Freelancer daily rate']);
        $this->assertSame('Qtde garçom', $translations['Waiter count']);
        $this->assertSame('Dias de trabalho', $translations['Work days']);
        $this->assertSame('Segundos', $translations['Seconds']);
        $this->assertStringContainsString('já sai impresso no setor', $translations['Learn more print savings copy']);
        $this->assertStringContainsString('Valor economizado:', $translations['Estimated print monthly savings']);
        $this->assertStringContainsString('valores aproximados', $translations['Estimated savings disclaimer']);
        $this->assertStringContainsString('Beleza? sem Neura nesses cálculos', $translations['Estimated savings disclaimer']);
        $this->assertSame('Impressão direta', $translations['Direct Print']);
        $this->assertStringContainsString('o pedido vai pra cozinha sem você precisar gritar', $translations['Direct print sales copy']);
        $this->assertStringContainsString('imprime automaticamente na cozinha ou no bar', $translations['Direct print sales copy']);
        $this->assertStringContainsString('Menos erro, menos correria, atendimento mais rápido.', $translations['Direct print sales copy']);
        $this->assertStringContainsString('Esse é o nosso queridinho', $translations['Direct waiter sales copy']);
        $this->assertStringContainsString('Tag NeuraBar na mesa', $translations['Direct waiter sales copy']);
        $this->assertStringContainsString('ele mesmo faz o pedido em segundos', $translations['Direct waiter sales copy']);
        $this->assertStringContainsString('tudo que aconteceu no seu estabelecimento', $translations['Financial dashboard sales copy']);
        $this->assertStringContainsString('novembro do ano passado', $translations['Financial dashboard sales copy']);
        $this->assertStringContainsString('Chega de achar. Aqui você sabe.', $translations['Financial dashboard sales copy']);
        $this->assertStringContainsString('Sem planilha. Sem perder tempo.', $translations['Learn more financial dashboard advantage']);
        $this->assertStringContainsString('ao vivo como estão as vendas', $translations['Learn more financial dashboard advantage']);
        $this->assertStringContainsString('em tempo real', $translations['Learn more financial dashboard advantage']);
        $this->assertStringContainsString('emita sem sair do sistema', $translations['Fiscal note sales copy']);
        $this->assertStringContainsString('cupom fiscal direto pelo NeuraBar', $translations['Fiscal note sales copy']);
        $this->assertStringContainsString('nota completa (DANFE)', $translations['Fiscal note sales copy']);
        $this->assertStringContainsString('seu garçom só precisa da boca e do celular', $translations['Voice command sales copy']);
        $this->assertStringContainsString('Suco de laranja, mesa 4', $translations['Voice command sales copy']);
        $this->assertStringContainsString('técnico de seleção', $translations['Voice command sales copy']);
        $this->assertStringContainsString('fazer um pedido por voz', $translations['Learn more voice command how it works']);
        $this->assertStringContainsString('Mesa 5, copo com limão e gelo', $translations['Learn more voice command how it works']);
        $this->assertStringContainsString('Batata frita grande com queijo', $translations['Learn more voice command how it works']);
        $this->assertStringContainsString('pode corrigir quando quiser', $translations['Learn more voice command how it works']);
        $this->assertStringContainsString('agilidade ganhada nesse modo', $translations['Learn more voice command advantage']);
        $this->assertStringContainsString('não tem familiaridade com sistemas', $translations['Learn more voice command advantage']);
        $this->assertStringContainsString('Seu tio pode vir e ajudar na correria', $translations['Learn more voice command advantage']);
        $this->assertStringContainsString('seus clientes vão querer ficar olhando', $translations['Production dashboard sales copy']);
        $this->assertStringContainsString('redes de fast food', $translations['Production dashboard sales copy']);
        $this->assertStringContainsString('negócio que funciona de verdade', $translations['Production dashboard sales copy']);
        $this->assertStringContainsString('aparece automaticamente no monitor do restaurante', $translations['Learn more production dashboard how it works']);
        $this->assertStringContainsString('número da mesa e o número do pedido', $translations['Learn more production dashboard how it works']);
        $this->assertStringContainsString('sem precisar perguntar nada pra ninguém', $translations['Learn more production dashboard how it works']);
        $this->assertStringContainsString('não fica chamando garçom a cada cinco minutos', $translations['Learn more production dashboard advantage']);
        $this->assertStringContainsString('sabe que está sendo preparado', $translations['Learn more production dashboard advantage']);
        $this->assertStringContainsString('mais confiança na sua casa', $translations['Learn more production dashboard advantage']);
        $this->assertStringContainsString('seu garçom anota na mesa', $translations['Taker sales copy']);
        $this->assertStringContainsString('já chegou na cozinha', $translations['Taker sales copy']);
        $this->assertStringContainsString('Rápido, simples, sem erro.', $translations['Taker sales copy']);
        $this->assertStringContainsString('abre o NeuraBar no celular', $translations['Learn more taker how it works']);
        $this->assertStringContainsString('sem ele sair do lugar', $translations['Learn more taker how it works']);
        $this->assertStringContainsString('Mais tempo no salão, menos volta desnecessária', $translations['Learn more taker advantage']);
        $this->assertStringContainsString('sem ruído, sem retrabalho', $translations['Learn more taker advantage']);
        $this->assertStringContainsString('Cada pedido no monitor certo, no local certo', $translations['Kds sales copy']);
        $this->assertStringContainsString('monitor do setor responsável', $translations['Kds sales copy']);
        $this->assertStringContainsString('é só olhar e fazer', $translations['Kds sales copy']);
        $this->assertStringContainsString('manda automaticamente pro monitor certo', $translations['Learn more kds how it works']);
        $this->assertStringContainsString('tudo separado, tudo no lugar', $translations['Learn more kds how it works']);
        $this->assertStringContainsString('Menos grito, menos bilhetinho, menos erro', $translations['Learn more kds advantage']);
        $this->assertStringContainsString('tudo certinho, no tempo certo', $translations['Learn more kds advantage']);
        $this->assertStringContainsString('sua vitrine aberta 24 horas', $translations['Menu sales copy']);
        $this->assertStringContainsString('crie combos', $translations['Menu sales copy']);
        $this->assertStringContainsString('cortesia para quem assina o módulo KDS', $translations['Menu sales copy']);
        $this->assertStringContainsString('organiza por categoria e publica', $translations['Learn more menu how it works']);
        $this->assertStringContainsString('sem risco de cardápio desatualizado', $translations['Learn more menu how it works']);
        $this->assertStringContainsString('vitrine profissional acessível de qualquer celular', $translations['Learn more menu advantage']);
        $this->assertStringContainsString('aumenta o ticket', $translations['Learn more menu advantage']);
        $this->assertStringContainsString('cliente paga na hora, sem esperar', $translations['Self order sales copy']);
        $this->assertStringContainsString('sem esperar a maquininha', $translations['Self order sales copy']);
        $this->assertStringContainsString('nada escapa, nada some', $translations['Self order sales copy']);
        $this->assertStringContainsString('visualiza o resumo do consumo da mesa', $translations['Learn more self order how it works']);
        $this->assertStringContainsString('a mesa já fica disponível', $translations['Learn more self order how it works']);
        $this->assertStringContainsString('não depende do garçom pra fechar cada mesa', $translations['Learn more self order advantage']);
        $this->assertStringContainsString('sem surpresa no caixa', $translations['Learn more self order advantage']);
        $this->assertStringContainsString('Tag NeuraBar que está em todas as mesas', $translations['Learn more direct waiter customer access']);
        $this->assertStringContainsString('Quero uma coca, limão e gelo', $translations['Learn more direct waiter customer access']);
        $this->assertStringContainsString('Pode trazer um copo com gelo', $translations['Learn more direct waiter customer access']);
        $this->assertStringContainsString('texto que o cliente digitou', $translations['Learn more direct waiter order flow']);
        $this->assertStringContainsString('mesa e o horário', $translations['Learn more direct waiter order flow']);
        $this->assertStringContainsString('Aqui começa o nosso sistema', $translations['Learn more direct waiter advantage']);
        $this->assertStringContainsString('pesque-pague', $translations['Learn more direct waiter advantage']);
        $this->assertStringContainsString('hotel', $translations['Learn more direct waiter advantage']);
        $this->assertStringContainsString('pede sozinho pela Tag', $translations['Learn more direct waiter savings copy']);
    }

    public function test_module_catalog_seeder_uses_the_delivery_sales_copy(): void
    {
        $seeder = file_get_contents(dirname(__DIR__, 3).'/database/seeders/ModuleCatalogsSeeder.php');

        $this->assertNotFalse($seeder);
        $this->assertStringContainsString('Pedidos de Delivery e Retirada', $seeder);
        $this->assertStringContainsString('Cardápio Digital — sua vitrine aberta 24 horas', $seeder);
        $this->assertStringContainsString('O Cardápio Digital é cortesia para quem assina o módulo KDS', $seeder);
        $this->assertStringContainsString('Pagamento pelo Celular — cliente paga na hora, sem esperar', $seeder);
        $this->assertStringContainsString('Pedidos no Celular — seu garçom anota na mesa e o pedido já vai pra cozinha', $seeder);
        $this->assertStringContainsString('KDS — Cada pedido no monitor certo, no local certo', $seeder);
        $this->assertStringContainsString('Impressão Direta — o pedido vai pra cozinha sem você precisar gritar', $seeder);
        $this->assertStringContainsString('Direct Garçom — Esse é o nosso queridinho', $seeder);
        $this->assertStringContainsString('Painel Financeiro — tudo que aconteceu no seu estabelecimento', $seeder);
        $this->assertStringContainsString('Painel da Cozinha — seus clientes vão querer ficar olhando', $seeder);
        $this->assertStringContainsString('Nota Fiscal — emita sem sair do sistema', $seeder);
        $this->assertStringContainsString('Comandos de Voz — seu garçom só precisa da boca e do celular', $seeder);
        $this->assertStringContainsString("'name' => \$module['name']", $seeder);
        $this->assertStringContainsString("'active' => \$module['active'] || config('app.env') === 'local'", $seeder);
    }
}
