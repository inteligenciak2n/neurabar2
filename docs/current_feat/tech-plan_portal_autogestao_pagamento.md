# Plano Tecnico – Portal de Autogestao da Assinatura e Camada de Pagamento

## Metadados
- **Task:** Entregar ao cliente (perfil Client) autonomia para gerenciar sua assinatura (planos, modulos, ciclo, metodo de pagamento, historico de faturas, configuracoes de faturamento) e estruturar a camada de pagamento SaaS desacoplada por contrato, com gateway fake e fluxos de checkout cartao/PIX/boleto.
- **Tech Lead:** Rodrigo
- **Data de criacao:** 2026-07-24
- **Servicos impactados:** Portal do cliente (`/settings/subscription/*`), camada de pagamento (`App\Services\Subscription`), billing (`App\Services\Billing`), models do banco `saas` (`corporations`, `venues`, `users`, `corporation_subscriptions`, `venue_subscriptions`, `corporation_modules`, `venue_modules`, `venue_invoices`, `corporation_invoices`, `payment_attempts`), rotas da API publica (`routes/api.php`), rotas operacionais (`routes/web/operational.php`), Vue (`resources/js/Pages/Settings/Subscription/*`).

## Resumo do Contexto
A aplicacao ja possui as Fases 1 e 2 do modulo de Subscriptions/Billing implementadas: entities, enums, middleware `RequireModule`, `BillingStatusService`, `SubscriptionCalculator`, jobs diarios, cache de modulos e backoffice de gestao comercial. O que falta e a face do cliente: telas para ativar/desativar modulos, escolher metodo de pagamento, pagar faturas e gerenciar dados fiscais. Junto disso, precisamos criar a camada de pagamento propriamente dita — contrato, servico e gateway fake — para que a integracao com gateway real seja so uma implementacao a mais depois.

## Decisoes Arquiteturais

### 1. Namespace e nome do servico de pagamento SaaS
- **Decisao:** Usar `App\Services\Subscription\PaymentSaasService` para toda a orquestracao de pagamento de assinaturas.
- **Opcoes consideradas:**
  - `App\Services\Billing\PaymentGatewayService` — bom, mas confunde com os jobs de billing ja existentes.
  - `App\Services\Payment\PaymentService` — colidiria com `App\Services\Payment\PaymentService` existente, que calcula totais de comanda/POS.
  - `App\Services\Subscription\PaymentSaasService` — escolhida por deixar claro que e pagamento SaaS de assinatura, sem colidir com o servico de POS.
- **Rationale:** Manter domínios separados (POS vs Subscription) evita ambiguidade e facilita a manutencao.
- **Decidido por:** Desenvolvedor (resposta direta).

### 2. Enum de metodo de pagamento da assinatura
- **Decisao:** Criar enum separado `App\Enums\PaymentSaasMethod` com `credit_card`, `pix`, `boleto`.
- **Opcoes consideradas:**
  - Reusar `App\Enums\PaymentMethod` — possivel, mas o enum atual inclui `cash`, `debit_card`, `other`, inapropriados para fatura recorrente.
  - Criar `PaymentSaasMethod` — escolhida porque isola o dominio e evita poluir o enum de POS.
- **Rationale:** Metodos de pagamento de assinatura tem regras distintas (tokenizacao, recorrencia, vencimento) e merecem um enum proprio.
- **Decidido por:** Desenvolvedor.

### 3. Persistencia de dados fiscais e de pagamento
- **Decisao:** Enriquecer `corporations` e `venues` com dados fiscais/endereco de faturamento; salvar metodos de pagamento vinculados ao `User` (cartao), com `holder_name` e `holder_document`.
- **Opcoes consideradas:**
  - Tabela separada `billing_profiles` polimorfica — adicionaria complexidade sem ganho imediato.
  - Colunas em `corporations`/`venues` + tabela `user_payment_methods` — escolhida porque reflete a arquitetura atual: cobranca e por venue ou por corporation, e o cartao pertence ao usuario que paga.
- **Rationale:** A arquitetura de billing ja trabalha com `Corporation` e `Venue` como entidades de faturamento. Endereco e dados fiscais precisam estar nelas. Cartao salvo e pessoal do `User`, entao fica no escopo do usuario.
- **Decidido por:** Desenvolvedor.

### 4. Escopo de plano no portal do cliente
- **Decisao:** O portal permite apenas gerenciar modulos a la carte (ativar/desativar por venue), nao alterar `PlanCatalog`.
- **Opcoes consideradas:**
  - Permitir upgrade/downgrade de plano — demandaria reconciliar `PlanCatalog.included_modules` com modulos ja contratados e recalcular `base_value`.
  - Gerenciar apenas modulos a la carte — alinhado ao onboarding atual, que nao vincula `PlanCatalog`.
- **Rationale:** Evita retrabalho e mantem consistencia com o wizard de onboarding, que cria `CorporationModule`/`VenueModule` sem plano.
- **Decidido por:** Desenvolvedor.

### 5. Rota de webhook
- **Decisao:** `POST /api/webhooks/payment/{gateway}` fora de `tenant` e `auth`, com CSRF desabilitado e validacao por header/token.
- **Opcoes consideradas:**
  - Rota web (`/webhooks/payment/{gateway}`) — funciona, mas mistura rotas publicas no grupo web.
  - Rota API (`/api/webhooks/payment/{gateway}`) — escolhida por ser o local natural de integracoes externas.
- **Rationale:** Webhooks sao chamadas de servidor para servidor e devem ficar em `api.php`, sem depender de sessao autenticada.
- **Decidido por:** Desenvolvedor.

### 6. Comportamento do cancelamento
- **Decisao:** Cancelamento ao fim do periodo: preenche `ended_at` e mantem acesso ate la.
- **Opcoes consideradas:**
  - Cancelamento imediato — pode gerar churn involuntario e reclamacoes.
  - Cancelamento ao fim do periodo — padrao de mercado (SaaS).
- **Rationale:** Respeita o ciclo de faturamento ja pago e da previsibilidade ao cliente.
- **Decidido por:** Desenvolvedor.

### 7. Permissao de acesso ao portal
- **Decisao:** Owner e GeneralManager podem acessar as telas de autogestao da assinatura.
- **Opcoes consideradas:**
  - Apenas Owner — muito restritivo para operacoes.
  - Owner e GeneralManager — padrao das rotas de `settings`.
- **Rationale:** GeneralManager ja gerencia configuracoes da venue no projeto; a assinatura e uma configuracao financeira importante, mas dentro do mesmo nivel de permissao.
- **Decidido por:** Desenvolvedor.

### 8. Visualizacao de faturas
- **Decisao:** Cliente visualiza todas as faturas de todas as venues da corporation.
- **Opcoes consideradas:**
  - Apenas faturas da venue ativa — no modo `unified` o cliente nao veria a fatura real.
  - Todas as faturas da corporation — escolhida para dar visibilidade completa.
- **Rationale:** O Owner/GM pensa na conta corporativa, nao apenas na venue ativa.
- **Decidido por:** Desenvolvedor.

## Padroes Aplicados

### Contrato de Servico (Strategy-like)
- **Padrao:** `PaymentGatewayContract` + resolucao dinamica de implementacao.
- **Aplica-se a:** `App\Services\Subscription\PaymentSaasService`.
- **Justificativa:** Desacopla a aplicacao do gateway. Hoje usamos `FakePaymentGateway`; amanha sera so adicionar `AsaasPaymentGateway` ou `StripePaymentGateway` e alterar a configuracao. No Laravel, isso e feito com `App::bind(PaymentGatewayContract::class, config('subscription.payment.gateway'))`.

### Idempotencia de Webhook
- **Padrao:** Registro polimorfico de tentativas via `PaymentAttempt`.
- **Aplica-se a:** `App\Http\Controllers\Api\PaymentWebhookController`.
- **Justificativa:** O model `PaymentAttempt` ja existe em `app/Models/Tenant/PaymentAttempt.php` com `invoice_type`, `invoice_id`, `gateway_payment_id`. A action de webhook deve usar `updateOrCreate(['gateway_payment_id' => $id])` para nao processar o mesmo evento duas vezes.

### Recalculo Sincrono apos Mudanca de Modulo
- **Padrao:** Action atualiza modulo + chama `SubscriptionCalculator::calculateVenue()` dentro da transacao.
- **Aplica-se a:** `ActivateVenueModuleAction`, `DeactivateVenueModuleAction` e as novas actions do portal.
- **Justificativa:** Ja e o padrao adotado no projeto (ver `app/Actions/Corporation/ActivateVenueModuleAction.php` linhas 44-48), garantindo que o cliente veja o valor atualizado imediatamente.

### Cache Tag Invalidation
- **Padrao:** `VenueModuleCache::forget($venue)` e `CorporationModuleCache::forget($corporation)` dentro da transacao.
- **Aplica-se a:** Todas as actions que alteram `VenueModule` ou `CorporationModule`.
- **Justificativa:** Padrao existente no projeto para garantir que frontend e workers vejam estado atualizado.

## Analise de Impacto

### Servicos afetados
1. **Portal do cliente (`/settings/subscription`)**: novas rotas, controllers, Vue pages e requests.
2. **Camada de pagamento**: novo contrato, servico e gateway fake.
3. **Models `Corporation` e `Venue`**: novas colunas de endereco/dados fiscais.
4. **Model `User`**: relacionamento com metodos de pagamento salvos.
5. **Billing**: `GenerateInvoicesJob` continuara criando faturas; o novo servico apenas as pagara.
6. **API publica**: nova rota de webhook em `routes/api.php`.

### Breaking changes
- **Nao.** Adicionamos colunas nullable e novos recursos; nao alteramos contratos existentes.
- **Cuidado:** O shared prop `tenant` em `HandleInertiaRequests.php` nao precisa mudar, mas podemos adicionar `billing` com flags uteis (grace period, trial ending).

### Migracoes necessarias
1. `corporations`: adicionar `billing_address_json`, `billing_tax_regime`, `billing_state_registration`.
2. `venues`: adicionar `billing_address_json`, `billing_email` (se diferente do corporation), `billing_phone`.
3. Nova tabela `user_payment_methods` (banco `saas`): `id`, `user_id`, `gateway`, `gateway_token`, `brand`, `last4`, `holder_name`, `holder_document`, `expiration_month`, `expiration_year`, `is_default`, `billing_address_json`, timestamps.
4. Opcional: `corporation_subscriptions.billing_address_snapshot` para snapshot no momento da fatura (nao essencial para o MVP).

### Notas de migracao
- Deploy sem downtime: adicionar colunas nullable primeiro; popular depois se necessario.
- A nova tabela `user_payment_methods` pode ser criada independentemente das outras.

## Riscos

| Risco | Severidade | Mitigacao | Status |
|---|---|---|---|
| Colisao entre `App\Services\Payment\PaymentService` (POS) e `PaymentSaasService` | Media | Namespace distinto (`Subscription`) e documentacao clara | Mitigado |
| Gateway fake nao representar comportamento real de PIX/boleto (vencimento, conciliacao) | Media | Implementar estados realistas (`pending`, `paid`, `expired`, `refunded`) e documentar limitacoes | Identificado |
| Cliente ativar modulo sem saldo/cartao valido, gerando inadimplencia | Media | Bloquear ativacao se `BillingStatusService::isBlocked()` for true; cobrar fatura atual imediatamente no checkout | Identificado |
| Webhook publico sem assinatura forte permitir eventos falsos | Alta | Validar header/token fixo em `config/subscription.php`; ignorar payloads invalidos; usar idempotencia por `gateway_payment_id` | Mitigado |
| Dados de cartao sendo logados ou serializados inseguramente | Alta | Armazenar apenas token do gateway (`gateway_token`), `last4`, `brand` e `holder_document`; nunca o numero completo | Mitigado |
| Mudanca de `billing_mode` pelo cliente causar inconsistencia entre faturas abertas | Alta | Nao permitir alteracao de `billing_mode` pelo cliente no MVP; apenas via backoffice | Mitigado |
| Cancelamento ao fim do periodo precisa de job para suspender no `ended_at` | Media | `SuspendOverdueSubscriptionsJob` ja existe; adicionar verificacao de `ended_at` nele | Identificado |

## Orientacoes para Desenvolvimento (Dev Guidance)

### Estrutura de arquivos sugerida
```
app/
  Contracts/
    Subscription/
      PaymentGatewayContract.php
  Services/
    Subscription/
      PaymentSaasService.php
      FakePaymentGateway.php
    Billing/
      BillingStatusService.php        # ja existe
      SubscriptionCalculator.php      # ja existe
  Actions/
    Subscription/
      SubscribeModuleAction.php
      UnsubscribeModuleAction.php
      UpdateBillingAddressAction.php
      SavePaymentMethodAction.php
      CancelSubscriptionAction.php
      ProcessWebhookPaymentAction.php
  Http/
    Controllers/
      Settings/
        SubscriptionController.php
        SubscriptionInvoiceController.php
        SubscriptionPaymentMethodController.php
        SubscriptionBillingAddressController.php
      Api/
        PaymentWebhookController.php
    Requests/
      Settings/
        StoreSubscriptionModuleRequest.php
        UpdateBillingAddressRequest.php
        StorePaymentMethodRequest.php
        PayInvoiceRequest.php
  Models/
    Tenant/
      UserPaymentMethod.php           # nova
  Enums/
    PaymentSaasMethod.php             # nova
resources/js/Pages/Settings/Subscription/
  Index.vue
  Invoices.vue
  PaymentMethods.vue
  BillingAddress.vue
routes/
  web/operational.php                 # adicionar grupo /settings/subscription
  api.php                             # adicionar webhook
config/
  subscription.php                    # novo
```

### Exemplos especificos do stack

**1. Contrato do gateway:**
```php
namespace App\Contracts\Subscription;

interface PaymentGatewayContract
{
    public function createCustomer(array $data): string;
    public function saveCard(string $customerId, array $cardData): array;
    public function chargeInvoice(\App\Models\Tenant\VenueInvoice|\App\Models\Tenant\CorporationInvoice $invoice, array $paymentData): array;
    public function processPix(\App\Models\Tenant\VenueInvoice|\App\Models\Tenant\CorporationInvoice $invoice): array;
    public function processBoleto(\App\Models\Tenant\VenueInvoice|\App\Models\Tenant\CorporationInvoice $invoice): array;
    public function handleWebhook(string $gateway, array $payload): array;
}
```

**2. Binding do gateway no `AppServiceProvider`:**
```php
$this->app->bind(
    \App\Contracts\Subscription\PaymentGatewayContract::class,
    config('subscription.payment.gateway', \App\Services\Subscription\FakePaymentGateway::class)
);
```

**3. Uso do servico:**
```php
class SubscriptionInvoiceController extends Controller
{
    public function __construct(private PaymentSaasService $paymentService) {}

    public function pay(PayInvoiceRequest $request, VenueInvoice $invoice)
    {
        $result = $this->paymentService->charge($invoice, $request->validated());
        return back()->with('success', $result['message']);
    }
}
```

**4. Ativacao de modulo com recalculo (padrao existente):**
```php
DB::transaction(function () use ($venue, $code, $quantity) {
    VenueModule::updateOrCreate(
        ['venue_id' => $venue->id, 'module_code' => $code->value],
        ['status' => ModuleStatus::Active, 'quantity' => $quantity, 'started_at' => now(), 'ended_at' => null]
    );
    app(SubscriptionCalculator::class)->calculateVenue($venue, now()->format('Y-m'));
    VenueModuleCache::forget($venue);
});
```

**5. Webhook idempotente:**
```php
PaymentAttempt::updateOrCreate(
    ['gateway_payment_id' => $payload['id']],
    [
        'invoice_type' => $invoiceType,
        'invoice_id' => $invoiceId,
        'gateway' => $gateway,
        'amount' => $amount,
        'status' => $status,
        'payload' => $payload,
        'attempted_at' => now(),
    ]
);
```

### Fluxos de checkout

**Cartao de credito:**
1. Cliente escolhe fatura e metodo `credit_card`.
2. Se nao tem cartao salvo, coleta dados (frontend) e envia para `SavePaymentMethodAction`.
3. Action chama gateway fake, que retorna `gateway_token`, `last4`, `brand`.
4. Persiste em `user_payment_methods` vinculado ao `User`.
5. Cliente confirma pagamento; `PaymentSaasService::charge()` usa o token para criar cobranca.
6. Gateway retorna `paid` ou `failed`; registramos `PaymentAttempt` e atualizamos `VenueInvoice`/`CorporationInvoice`.

**PIX:**
1. Cliente solicita pagamento de fatura via PIX.
2. `PaymentSaasService::processPix()` gera payload ficticio (`qr_code`, `qr_code_image`, `expiration_at`).
3. Gateway fake emite evento de confirmacao imediata ou demorada (simulacao).
4. Webhook atualiza invoice para `paid` quando confirmado.

**Boleto:**
1. Cliente solicita boleto para fatura.
2. `PaymentSaasService::processBoleto()` retorna `boleto_url`, `barcode`, `due_date`.
3. Webhook confirma pagamento ate o vencimento.

### Telas de UI (Vue)

**`Settings/Subscription/Index.vue`**
- Resumo da assinatura: status, trial/periodo, billing_mode, proxima fatura.
- Lista de modulos contratados por venue com toggle de ativacao.
- Aviso se `BillingStatusService::isBlocked()` ou em grace period.

**`Settings/Subscription/Invoices.vue`**
- Tabela de faturas (venue + corporation, respeitando `billing_mode`).
- Filtro por status/periodo.
- Botao "Pagar" para faturas abertas.
- Link para download/visualizacao (pode ser PDF gerado por Blade ou componente Vue).

**`Settings/Subscription/PaymentMethods.vue`**
- Lista de cartoes salvos do usuario.
- Formulario para adicionar cartao (dados ficticios no fake).
- Botao para definir padrao/remover.

**`Settings/Subscription/BillingAddress.vue`**
- Formulario de endereco e dados fiscais da corporation e/ou venue ativa.

### Checklist pre-deploy
- [ ] Migrations executadas em staging.
- [ ] Gateway fake habilitado em ambiente nao-prod (`config/subscription.php`).
- [ ] Webhook testado com cURL simulando eventos de pagamento.
- [ ] Rotas de settings protegidas por `tenant`, `module:menu` e `role:owner,general_manager`.
- [ ] `PaymentAttempt` registrando corretamente em caso de duplicacao.
- [ ] Cache de modulos invalidado apos ativacao/desativacao via portal.
- [ ] Pint passando (`vendor/bin/sail bin pint --dirty --format agent`).
- [ ] Testes de feature escritos e passando para: ativacao de modulo, pagamento fake, webhook.
