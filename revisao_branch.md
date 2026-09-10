# Relatório de Re-Revisão – feat/modules-implements vs main

## Resumo da Task

Verificação das correções aplicadas em resposta ao relatório anterior (módulos DirectWaiter, Delivery, Dashboard Financeiro, Dashboard Operacional e `self_order`). Cada finding marcado com `[OBSERVAÇÃO_DESENVOLVEDOR] implementar` foi re-inspecionado no código atual; o único marcado como "NÃO implementar" (authorize() nos FormRequests) foi excluído do escopo.

Validação executada: suíte completa `vendor/bin/sail artisan test --compact` — 630 passaram, 4 skipped, 0 falhas.

## Resultado da Revisão

- **Arquivos revisados:** 156 arquivos alterados (6.860 inserções / 619 remoções)
- **Findings anteriores:** 33 → **31 resolvidos**, 2 pendentes
- **Findings novos:** 6 ([WARNING]: 1, [DEBT]: 5)
- **Total atual:** 8 ([CRITICAL]: 0, [WARNING]: 2, [DEBT]: 6)

---

## Correções Confirmadas

### Segurança
- **[CRITICAL] Lookup de clientes vazando PII** — RESOLVIDO. `DeliveryCustomerLookupController` devolve apenas `{ found: bool }`; `DeliveryCheckoutPanel.vue` não preenche mais nada a partir da resposta. Coberto por `DeliveryCustomerLookupControllerTest` (gate de módulo, isolamento por corporation, telefone desconhecido, assert explícito de que nome/endereço não vazam).
- **[CRITICAL] Manipulação de preço / injeção cross-venue nos itens** — RESOLVIDO. `ResolveOrderItemsAction` escopa produtos por `whereHas('category.menu', venue_id + active)`, valida `variation->product_id === product->id` e que cada `modifier_option` pertence a um grupo do produto. Testes de caminho de ataque adicionados (variação de outro produto, produto de venue vizinha).
- **[WARNING] Token sem assinatura** — RESOLVIDO. `GuestTokenService::encodeVenueOnly()` assina com `hash_hmac` + `APP_KEY`; `decode()` valida apenas quando `s` existe, mantendo compatibilidade com QR codes físicos já impressos. Coberto por `GuestTokenServiceTest`.
- **[WARNING] `save_address` ignorado** — RESOLVIDO. `persistAddressIfRequested()` só grava com consentimento e usa `updateOrCreate` por `customer_id` + CEP + número.
- **[DEBT] IDOR implícito no KDS** — RESOLVIDO. `advanceDeliveryStatus` tem `abort_unless($order->attendance?->venue_id === app('tenant')->id, 404)` explícito.

### Corretude e Lógica
- **[CRITICAL] Transação na conexão errada** — RESOLVIDO. `DB::connection(OperationalConnection::current())->transaction(...)`, e toda a validação (métodos aceitos, soma vs total, zona de entrega, resolução de itens) acontece **antes** de qualquer escrita. `PlaceDeliveryOrderTest` agora assere `assertDatabaseCount('orders'|'attendances'|'delivery_orders', 0)` na rejeição.
- **[CRITICAL] `__()` sem import (5 arquivos Vue)** — RESOLVIDO em todos: `PeriodFilter`, `Finance/Index`, `Production/Index`, `DirectWaiter/Index`, `Kitchen/Kds`. Varredura em todos os `.vue` alterados na branch não encontrou nenhum uso remanescente de `__()` em `<script setup>` sem `useTranslate`.
- **[WARNING] `order_number` hardcoded** — RESOLVIDO (`max('order_number') + 1`).
- **[WARNING] Receita reconhecida no checkout** — RESOLVIDO. Nova tabela `delivery_order_payment_methods` guarda a intenção de pagamento; `Payment`/`PaymentItem` só nascem em `AdvanceDeliveryOrderStatusAction` quando o status vira `Delivered`, com guard idempotente `payment()->exists()`.
- **[WARNING] `feeZone.fee` inicializado com 0** — RESOLVIDO. Agora `fee: null`, e `canGoToPayment` exige `!loading && fee !== null && !error`.
- **[DEBT] `fresh()` duplicado / sem transação** — RESOLVIDO.

### Performance
- **[WARNING] `period=custom` sem teto** — RESOLVIDO. `DashboardPeriodRequest::MAX_CUSTOM_RANGE_DAYS = 366` com regra de validação dedicada.
- **[WARNING] N+1 na resolução de itens** — RESOLVIDO. 3 queries `whereIn` batched, independente do tamanho do carrinho, executadas fora da transação.
- **[DEBT] `ServiceRequest` sem limite** — RESOLVIDO (`latest()->limit(50)` + seleção explícita de colunas).
- **[DEBT] `items.product` desnecessário no KDS** — RESOLVIDO (`with(['attendance.deliveryOrder'])`).
- **[DEBT] Query extra em `RecordOrderModuleUsage`** — RESOLVIDO (`loadMissing('attendance.deliveryOrder')` antes do `match`).

### Complexidade, Padrões e Testes
- **[DEBT] FormRequests idênticos** — RESOLVIDO (`DeliveryFeeZoneRequest` único).
- **[DEBT] `'operation_default_1'` duplicado** — RESOLVIDO nos dashboards via `App\Support\OperationalConnection::current()`.
- **[DEBT] URL hardcoded no `Menu.vue`** — RESOLVIDO (`route('orders.track', orderId)`).
- **[DEBT] Enum de métodos duplicado no JS** — RESOLVIDO (`availablePaymentMethods` vem do controller via `PaymentMethod::values()`).
- **[DEBT] `playSound()` triplicado** — RESOLVIDO (`useNotificationSound()`, adotado inclusive por `Attendances/Show.vue`).
- **[WARNING] Actions acopladas a `FormRequest`** — RESOLVIDO (todas recebem `array $validated`).
- **[DEBT] Models crus no Inertia** — RESOLVIDO (`DeliveryFeeZoneResource`, `ReadyDeliveryOrderResource`, `ServiceRequestResource` + `JsonResource::withoutWrapping()`).
- **[DEBT] Mensagens em inglês** — RESOLVIDO pela via correta do projeto: backend mantém inglês simples, frontend envolve com `__()`.
- **[DEBT] `DateRangeResolver` sem teste** — RESOLVIDO (`tests/Unit/Support/DateRangeResolverTest`, que inclusive expôs e corrigiu o bug de `diffInDays()` retornando float).

---

## Findings Pendentes e Novos

### Segurança

- **[DEBT] Oráculo de telefone no lookup sem rate limit por telefone** — RESOLVIDO. Novo `RateLimiter::for('delivery-customer-lookup', ...)` (`AppServiceProvider::boot()`) combina 5 tentativas/telefone/min (telefone normalizado só para a chave do limiter) + 20/IP/min; rota `/delivery/{token}/customer` trocou `throttle:20,1` por `throttle:delivery-customer-lookup`. Log de auditoria ficou fora de escopo (decisão explícita — risco residual já é baixo, sem PII retornada).

- **[DEBT] Camada SMS/OTP sem consumidor** — RESOLVIDO. Fluxo completo de verificação de telefone no delivery: `POST /delivery/{token}/phone/otp` (`RequestDeliveryPhoneOtpAction`) e `POST /delivery/{token}/phone/otp/verify` (`VerifyDeliveryPhoneOtpAction`) usam `Sms::requestOtp`/`validateOtp`; `GuestSession` ganhou `verified_phone`/`phone_verified_at` (reaproveitando o cookie `guest_token` já usado no hub `/g/{token}`, agora também emitido no fluxo delivery) e `GET /delivery/{token}/customer/data` só revela nome/endereço salvo após `GuestSession::isPhoneVerifiedFor()` confirmar a posse do número (janela de frescor de 30 min). Novo rate limit dedicado `delivery-phone-otp` (3/telefone/10min + 20/IP/min). Testes de feature cobrindo happy path, código rejeitado pelo provider, sessão ausente, módulo inativo e rate limit (`RequestDeliveryPhoneOtpTest`, `VerifyDeliveryPhoneOtpTest`, `DeliveryCustomerDataRevealTest`).

### Corretude e Lógica

- **[WARNING] Bind eager do `SmsProviderContract`** — RESOLVIDO. `AppServiceProvider::register()` agora usa `bind(SmsProviderContract::class, fn () => $this->app->make($this->resolveSmsProvider()))` — a exceção de credenciais Twilio ausentes só surge quando algo de fato resolve o contrato, não em todo boot. Agora há consumidor real (fluxo de OTP acima), então `TWILIO_ACCOUNT_SID`/`TWILIO_AUTH_TOKEN`/`TWILIO_VERIFY_SERVICE_SID` precisam estar provisionados em produção antes do deploy.

- **[DEBT] Comentário divergente em `config/sms.php`** — RESOLVIDO. Comentário alinhado ao comportamento real: `sms.provider` só tem efeito em local/testing; em produção `TwilioSmsProvider` é sempre resolvido, validado apenas por `TWILIO_ACCOUNT_SID`/`TWILIO_AUTH_TOKEN`.

### Complexidade e Manutenibilidade

- **[DEBT] `execute()` de `PlaceDeliveryOrderAction` longo demais** — RESOLVIDO. Corpo do closure de transação extraído em 5 métodos privados coesos (`createCustomer`, `createOrRetrieveAttendanceChannel`, `createAttendance`, `createOrderWithItems`, `createDeliveryOrderWithPaymentMethods`), mantendo `persistAddressIfRequested` e as validações pré-transação já existentes. Os 9 testes de `PlaceDeliveryOrderTest` continuam verdes sem alteração.

- **[DEBT] `DeleteDeliveryFeeZoneAction` como abstração de 1 linha** — DECISÃO: mantida como está. É consistente com `CreateDeliveryFeeZoneAction`/`UpdateDeliveryFeeZoneAction` (igualmente triviais); "1 Action por operação CRUD" é aceito como padrão do projeto. Nenhuma mudança de código.

### Cobertura de Testes

- **[WARNING] Sem ESLint / guard-rail de frontend** — RESOLVIDO. `eslint.config.js` (flat config, ESLint 9) com `eslint-plugin-vue` `flat/essential` (regras de corretude; `flat/recommended` foi descartado por gerar ~14k warnings puramente estilísticos incompatíveis com a formatação atual do projeto) + `no-undef: 'error'` reforçado — pega exatamente a classe de bug dos `__()`/`axios` sem import. Script `npm run lint` adicionado. Rodar hoje revela 11 erros `no-undef`/`vue/no-v-text-v-html-on-component` pré-existentes fora do módulo Delivery (`AppConfirmModal`, `AppPagination`, `ConfirmsPassword`, páginas `Platform/*`, `TwoFactorAuthenticationForm`) — fora do escopo desta rodada, ficam registrados para um follow-up. Vitest/Prettier/CI não foram adicionados (fora do pedido).

- **[DEBT] `advanceDeliveryStatus` sem teste de feature** — RESOLVIDO. Ao reinspecionar, `AdvanceDeliveryOrderStatusTest` já exercitava a rota via `$this->put(route('kitchen.orders.advance-delivery-status', ...))` (happy path pickup/delivery, sem `DeliveryOrder`, status errado, cross-venue → 404) — o finding original estava desatualizado nesse ponto. Único gap real era o teste de módulo KDS inativo, agora adicionado (`test_returns_forbidden_when_kds_module_is_inactive`).
---

## Score de Qualidade

- **Pontuação:** 6.2 / 10 (0 CRITICAL; 2 WARNING x 1 = -2; 6 DEBT x 0.3 = -1.8) — antes: 0/10
- **Pontuação de Dívida Técnica:** 3 / 10 (antes: 7) — duplicação eliminada, Resources introduzidos, composables extraídos; resta a camada SMS não consumida, a Action de checkout ainda longa e a ausência total de tooling de frontend.
- **Parecer Final:** **APROVADO COM RESSALVAS**

**Antes do deploy:**
1. Provisionar `TWILIO_ACCOUNT_SID`/`TWILIO_AUTH_TOKEN` em produção **ou** tornar o bind do `SmsProviderContract` lazy — hoje a ausência dessas variáveis derruba a aplicação inteira.
2. Decidir o destino da camada SMS/OTP: ligar a um fluxo real ou tirar do merge (código morto + dependência nova).

**Backlog recomendado:** ESLint com `no-undef`, teste de feature da rota `advance-delivery-status`, rate limit por telefone no lookup.
