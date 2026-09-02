# Relatório de Revisão – feat/modules-implements vs main

## Resumo da Task

Implementação completa dos módulos DirectWaiter, Delivery, Dashboard Financeiro e Dashboard Operacional (Produção), mais um novo módulo `self_order` que permite ao visitante/cliente fazer o próprio pedido.

Escopo revisado: unificação de chamados em `ServiceRequest` (substituindo `GuestSignaled`/`CallWaiter`), fluxo público de delivery/retirada (`/delivery/{token}`), zonas de taxa por CEP, lane de delivery no KDS, serviços de métricas (`FinancialMetricsService`, `ProductionMetricsService`), gating de módulo no Guest Hub e as telas Vue correspondentes. `docs/application-architecture.md` e `package-lock.json` foram lidos como contexto, não como alvo de findings.

## Resultado da Revisão

- **Arquivos revisados:** 128 arquivos alterados (5.605 inserções / 567 remoções)
- **Total de findings:** 33 ([CRITICAL]: 5, [WARNING]: 13, [DEBT]: 15)

---

## Findings por Dimensão

### Segurança

- **[CRITICAL]** `app/Http/Controllers/Guest/Delivery/DeliveryCustomerLookupController.php:26-49` — Endpoint público de lookup por telefone devolve **nome completo e todos os endereços residenciais** do cliente (rua, número, complemento, bairro, cidade, CEP, ponto de referência). O acesso depende apenas do `token`, que é `base64(json_encode(['v' => venue_id]))` **sem assinatura** e cujo link é *publicamente distribuído por design* (é o link de pedido do delivery, exibido em `Delivery/Index.vue` para o lojista copiar e divulgar). Qualquer pessoa com o link pode enumerar telefones (`throttle:20,1` por IP não é barreira para botnet/proxy rotativo) e colher PII de toda a base de clientes da corporation. Exposição direta de LGPD/OWASP A01+A02. **Sugestão:** exigir prova de posse do telefone antes de devolver dados (OTP por SMS/WhatsApp, ou sessão de convidado). Enquanto isso não existir, reduzir drasticamente o payload (devolver apenas `{ found: true }` e dados mascarados), aplicar rate limit por telefone além de por IP, e registrar auditoria dos lookups.
[OBSERVAÇÃO_DESENVOLVEDOR] implementar redução drastica, caso seja necessario dados a mais, inserir validação via OTP no celular ou login com senha. Vamos precisar de um provider de SMS, mas crie um layer de abstração para desacoplar com o provider. nesse momento podemos usar o https://docs.simpletext.dev/api-reference/send-sms

- **[CRITICAL]** `app/Actions/Guest/PlaceDeliveryOrderAction.php:117-141` + `app/Http/Requests/Guest/StoreDeliveryOrderRequest.php:38-44` — Nem o FormRequest nem a Action validam que `product_id`, `variation_id` e `modifiers[]` pertencem à venue do token, nem que a variação/modificador pertence ao produto informado. `Rule::exists(Product::class, 'id')` não aplica global scopes e `ProductVariation::withoutGlobalScopes()->findOrFail()` sobrescreve `$unitPrice` cegamente. Consequências: (a) **manipulação de preço** — enviar um produto caro com o `variation_id` de um produto barato faz o pedido ser cobrado pelo preço da variação errada; (b) **injeção cross-venue** — pedir produtos de outra venue da mesma corporation, que caem no KDS da venue errada. **Sugestão:** resolver produtos/variações/modificadores em uma única query escopada ao menu ativo da venue (`whereHas('category.menu', fn ($q) => $q->where('venue_id', $venue->id))`) e validar `variation->product_id === $product->id` e que cada `modifier_option_id` pertence a um `modifierGroup` do produto. Rejeitar com 422 em qualquer divergência.
[OBSERVAÇÃO_DESENVOLVEDOR] implementar

- **[WARNING]** `app/Services/GuestTokenService.php:20-23` — `encodeVenueOnly()` gera token sem HMAC/assinatura; qualquer UUID de venue vira token válido. Combinado com o finding anterior, remove qualquer barreira ao lookup de clientes. **Sugestão:** assinar o payload (`hash_hmac` com `APP_KEY`) e validar a assinatura no `decode()`, ou usar `Crypt::encryptString`.
[OBSERVAÇÃO_DESENVOLVEDOR] implementar

- **[WARNING]** `app/Actions/Guest/PlaceDeliveryOrderAction.php:82-93` — `address.save_address` é validado no FormRequest mas **nunca lido** na Action: o endereço é sempre persistido em `customer_addresses`. Além de ignorar o consentimento do cliente (LGPD), cria uma linha nova a cada pedido — a base cresce indefinidamente e o lookup (`->latest()`) passa a devolver duplicatas. **Sugestão:** só persistir quando `save_address` for verdadeiro e usar `updateOrCreate` por `customer_id` + CEP + número.
[OBSERVAÇÃO_DESENVOLVEDOR] implementar

- **[DEBT]** `app/Http/Controllers/Kitchen/KdsController.php` (`advanceDeliveryStatus`) — `Order` não usa `BelongsToVenue`/`TenantScope`, então o route model binding aceita qualquer `Order` da conexão operacional (compartilhada entre venues da mesma corporation). O IDOR é mitigado **por acidente**, porque `$order->attendance->deliveryOrder` é escopado por `TenantScope` e retorna `null` para outra venue. **Sugestão:** adicionar guard explícito (`abort_unless($order->attendance->venue_id === app('tenant')->id, 404)`), para não depender de efeito colateral.
[OBSERVAÇÃO_DESENVOLVEDOR] implementar
---

### Corretude e Lógica

- **[CRITICAL]** `app/Actions/Guest/PlaceDeliveryOrderAction.php:71-177` — `DB::transaction()` abre a transação na **conexão default (`saas`, ver `.env:27`)**, mas `Attendance`, `Order`, `OrderItem`, `DeliveryOrder` e `Payment` vivem na **conexão operacional** (`HasOperationalConnection`). O `ValidationException` das linhas 171-175 (soma dos métodos de pagamento diferente do total) é lançado **depois** de criar attendance + order + itens + delivery order. O rollback só reverte `Customer`/`CustomerAddress`; tudo na conexão operacional **fica gravado**. Um cliente (ou bot) que envie repetidamente um payload com total divergente polui o KDS e os relatórios com pedidos fantasma. **Sugestão:** usar `DB::connection($model->getConnectionName())->transaction(...)`, mover toda a validação de total/métodos para **antes** de qualquer escrita (o total é calculável sem persistir), e tratar as escritas em `saas` fora da transação operacional com compensação explícita.
[OBSERVAÇÃO_DESENVOLVEDOR] implementar

- **[CRITICAL]** `resources/js/Components/PeriodFilter.vue:15-18`, `resources/js/Pages/Finance/Index.vue:17-23,58-63`, `resources/js/Pages/Production/Index.vue:15,45-62` — `__()` é chamado no `<script setup>` sem importar `useTranslate`. O helper é registrado apenas como `app.config.globalProperties.__` e `app.provide('__')` (`resources/js/Plugins/autoGlobalInject.js`), ou seja, **só existe no escopo de template**. As chamadas são avaliadas no topo do setup → `ReferenceError: __ is not defined` → **as duas telas de dashboard quebram por completo no carregamento**. Os outros 27 componentes do projeto fazem `const __ = useTranslate()` corretamente. **Sugestão:** adicionar `import { useTranslate } from '@/Composables/useTranslate'` e `const __ = useTranslate();` nos três arquivos.
[OBSERVAÇÃO_DESENVOLVEDOR] implementar

- **[CRITICAL]** `resources/js/Pages/DirectWaiter/Index.vue` (`elapsed()`) e `resources/js/Pages/Kitchen/Kds.vue` (`deliveryActionLabel()`) — Mesmo defeito: `__()` usado dentro de funções do `<script setup>` sem import. Não quebra no mount, mas explode na primeira renderização de card — ou seja, o painel do Direct Garçom quebra exatamente quando **existe** um chamado, e o KDS quebra quando existe pedido de delivery pronto. É o cenário de produção, não o vazio. **Sugestão:** mesma correção; adicionar regra de lint (`no-undef` com globals explícitos) para impedir regressão.
[OBSERVAÇÃO_DESENVOLVEDOR] implementar

- **[WARNING]** `app/Actions/Guest/PlaceDeliveryOrderAction.php:111` — `'order_number' => 1` hardcoded. `PlaceGuestOrderAction.php:39` calcula `max('order_number') + 1`. Hoje funciona por sorte (uma attendance nova por pedido de delivery), mas qualquer evolução que adicione um segundo pedido à mesma attendance gera numeração duplicada, visível para o cliente na tela de tracking. **Sugestão:** usar a mesma lógica de `max + 1`, ou extrair um helper compartilhado.
[OBSERVAÇÃO_DESENVOLVEDOR] implementar

- **[WARNING]** `app/Actions/Guest/PlaceDeliveryOrderAction.php:177-193` — `Payment` é criado no ato do checkout, antes de qualquer captura/confirmação. O pedido nasce marcado como pago. `FinancialMetricsService::aggregate()` conta `payments` e soma `grand_total`, então **todo pedido de delivery é reconhecido como receita mesmo que nunca seja entregue ou seja cancelado** — inflando faturamento e ticket médio do dashboard financeiro recém-criado. **Sugestão:** definir a semântica (pagamento na entrega vs. pré-pago). Se for na entrega, mover a criação do `Payment` para `AdvanceDeliveryOrderStatusAction` quando o status vira `Delivered`, ou adicionar status ao `Payment` e filtrar no serviço de métricas.
[OBSERVAÇÃO_DESENVOLVEDOR] implementar

- **[WARNING]** `resources/js/Components/Guest/Delivery/DeliveryCheckoutPanel.vue:41,114` — `feeZone` é inicializado com `fee: 0`, então a guarda `feeZone.value.fee !== null` em `canGoToPayment` é **sempre verdadeira**. Se o debounce de 500 ms do lookup de CEP ainda não resolveu, o cliente vai para o pagamento com taxa 0, paga o total errado e o backend rejeita com "The sum of payment methods does not match the grand total" — mensagem opaca, sem indicar o motivo real. **Sugestão:** inicializar `fee` como `null`, bloquear o avanço enquanto `loading` for verdadeiro, e recalcular a taxa ao entrar no passo 2.
[OBSERVAÇÃO_DESENVOLVEDOR] implementar

- **[DEBT]** `app/Actions/Kitchen/AdvanceDeliveryOrderStatusAction.php:38-46` — `$order->fresh()` é chamado duas vezes (uma para o evento, outra para o retorno), gerando queries redundantes; `CloseAttendanceAction` + `update` + `event` acontecem sem transação. **Sugestão:** reaproveitar uma única instância `fresh` e envolver as escritas em transação na conexão operacional.
[OBSERVAÇÃO_DESENVOLVEDOR] implementar
---

### Performance

- **[WARNING]** `app/Support/DateRangeResolver.php:23` + `app/Services/Finance/FinancialMetricsService.php:132-146` — `period=custom` só valida `date` e `after_or_equal:from`; não há teto de intervalo. `?period=custom&from=1900-01-01&to=2100-01-01` faz `revenueTrend()` iterar ~73.000 dias montando um array em memória e serializar tudo no payload Inertia. DoS de baixo esforço para qualquer usuário autenticado com o módulo. **Sugestão:** adicionar teto de intervalo (ex.: 366 dias) no `DashboardPeriodRequest` e agrupar por semana/mês quando o intervalo exceder N dias.
[OBSERVAÇÃO_DESENVOLVEDOR] implementar

- **[WARNING]** `app/Actions/Guest/PlaceDeliveryOrderAction.php:116-142` — N+1 dentro de transação: um `findOrFail` por item, mais um por variação, mais um por modificador. Um carrinho de 10 itens com 3 modificadores dispara ~40 queries com a transação aberta, segurando locks. **Sugestão:** pré-carregar produtos/variações/modificadores em 3 queries `whereIn` antes de abrir a transação (o que também resolve a validação de escopo do finding de segurança).
[OBSERVAÇÃO_DESENVOLVEDOR] implementar

- **[DEBT]** `app/Http/Controllers/Orders/AttendanceController.php:39-43` — `ServiceRequest::open()->where('type','!=',...)->get()` sem `limit` nem `orderBy`. Uma venue com chamados nunca resolvidos acumula linhas indefinidamente no payload de `/attendances`. **Sugestão:** adicionar `latest()->limit(...)` e/ou job que auto-resolve chamados antigos.
[OBSERVAÇÃO_DESENVOLVEDOR] implementar

- **[DEBT]** `app/Http/Controllers/Kitchen/KdsController.php` (`readyDeliveryOrders`) — eager-load de `items.product` que a lane em `Kds.vue` não usa (só exibe `customer_identifier`, `order_number` e `status`). **Sugestão:** remover `items.product` do `with()`.
[OBSERVAÇÃO_DESENVOLVEDOR] implementar

- **[DEBT]** `app/Listeners/Billing/RecordOrderModuleUsage.php:22-26` — `$order->attendance->deliveryOrder()->exists()` executa uma query extra por evento de pedido. **Sugestão:** avaliar eager load da relação ou short-circuit explícito.
[OBSERVAÇÃO_DESENVOLVEDOR] implementar
---

### Complexidade e Manutenibilidade

- **[WARNING]** `app/Actions/Guest/PlaceDeliveryOrderAction.php` — `execute()` tem ~170 linhas, 4 níveis de aninhamento e acumula 6 responsabilidades (resolver zona, validar métodos, criar cliente, criar endereço, montar pedido, cobrar). As linhas 116-142 são **cópia literal** de `PlaceGuestOrderAction.php:52-80` — incluindo o bug de variação não validada, que agora existe em dois lugares. **Sugestão:** extrair `ResolveOrderItemsAction` compartilhado pelos dois fluxos e quebrar o restante em métodos privados coesos.
[OBSERVAÇÃO_DESENVOLVEDOR] implementar

- **[DEBT]** `app/Http/Requests/Delivery/StoreDeliveryFeeZoneRequest.php` e `UpdateDeliveryFeeZoneRequest.php` — arquivos byte-a-byte idênticos. **Sugestão:** unificar em um único `DeliveryFeeZoneRequest`.
[OBSERVAÇÃO_DESENVOLVEDOR] implementar

- **[DEBT]** `app/Http/Controllers/Finance/DashboardController.php:24-26` e `app/Http/Controllers/Production/DashboardController.php:22-24` — string mágica `'operation_default_1'` repetida em ambos, já existindo em `HasOperationalConnection`. **Sugestão:** extrair para helper/constante única.
[OBSERVAÇÃO_DESENVOLVEDOR] implementar

- **[DEBT]** `resources/js/Pages/Guest/Delivery/Menu.vue` (`handleOrderPlaced`) — `window.location.href` com URL hardcoded `/order/${orderId}/track`, enquanto o resto do projeto usa `route()`/Ziggy. **Sugestão:** usar a rota nomeada.
[OBSERVAÇÃO_DESENVOLVEDOR] implementar

- **[DEBT]** `resources/js/Pages/Delivery/Index.vue:15` — `allPaymentMethods` hardcoded em JS, duplicando o enum `App\Enums\PaymentMethod` já validado no backend. Um método novo no enum não aparece na UI. **Sugestão:** enviar `PaymentMethod::values()` como prop do controller.
[OBSERVAÇÃO_DESENVOLVEDOR] implementar

- **[DEBT]** `playSound()` duplicado em `resources/js/Pages/Attendances/Index.vue`, `resources/js/Pages/DirectWaiter/Index.vue` e `resources/js/Pages/Kitchen/Kds.vue` (três cópias idênticas, incluindo o path `/sounds/new-order.mp3`). **Sugestão:** extrair composable `useNotificationSound()`.
[OBSERVAÇÃO_DESENVOLVEDOR] implementar

- **[DEBT]** `app/Actions/Delivery/DeleteDeliveryFeeZoneAction.php` — abstração de uma linha (`$zone->delete()`). **Sugestão:** chamar direto no controller ou documentar como padrão do projeto.
[OBSERVAÇÃO_DESENVOLVEDOR] implementar

---

### Padrões e Arquitetura

- **[WARNING]** `app/Actions/Delivery/CreateDeliveryFeeZoneAction.php`, `UpdateDeliveryFeeZoneAction.php`, `UpdateDeliverySettingsAction.php` — recebem o `FormRequest` como parâmetro, acoplando a camada de domínio ao HTTP e tornando as Actions intestáveis fora de um request. Inconsistente com `PlaceDeliveryOrderAction`, que (corretamente) recebe `array $validated`. **Sugestão:** padronizar as Actions para receber arrays/DTOs.
[OBSERVAÇÃO_DESENVOLVEDOR] implementar

- **[WARNING]** `DashboardPeriodRequest`, `StoreDeliveryOrderRequest`, `StoreDeliveryFeeZoneRequest`, `UpdateDeliveryFeeZoneRequest`, `UpdateDeliverySettingsRequest` — todos com `authorize(): bool { return true; }`. A autorização está inteiramente delegada a middlewares de rota; qualquer rota futura que reutilize esses requests sem o middleware fica aberta. `FeeZoneController::update/destroy` depende exclusivamente do `TenantScope` implícito. **Sugestão:** implementar `authorize()` de verdade (Policy/Gate por venue), especialmente nos requests que manipulam zonas de taxa.
[OBSERVAÇÃO_DESENVOLVEDOR] NÃO implementar

- **[DEBT]** Models Eloquent serializados direto para o Inertia sem API Resource/DTO: `feeZones` (`Delivery/DashboardController`), `requests` (`DirectWaiter/DashboardController`), `readyDeliveryOrders` (`KdsController`). O contrato do frontend fica refém das colunas da tabela e vaza campos internos. Contraria a diretriz do `AGENTS.md` de preferir Eloquent API Resources. **Sugestão:** introduzir Resources ou ao menos `->get([...colunas])` explícito.
[OBSERVAÇÃO_DESENVOLVEDOR] implemente o resource

- **[DEBT]** Mensagens de erro em inglês hardcoded nas Actions/Controllers (`'This address is outside the delivery area.'`, `'One or more payment methods are not accepted by this venue.'`, `'The sum of payment methods does not match the grand total.'`) — expostas diretamente ao cliente final brasileiro no `DeliveryCheckoutPanel`, que renderiza `e.response?.data?.message` sem traduzir. **Sugestão:** usar chaves de tradução.
[OBSERVAÇÃO_DESENVOLVEDOR] implementar

---

### Cobertura de Testes

- **[WARNING]** `DeliveryCustomerLookupController` não tem **nenhum** teste — nem do gate de módulo, nem do escopo por corporation, nem do comportamento com telefone inexistente. É justamente o endpoint com o finding de segurança CRITICAL. **Sugestão:** cobrir gate de módulo, isolamento entre corporations e (após a correção) o novo mecanismo de autorização.
[OBSERVAÇÃO_DESENVOLVEDOR] implementar

- **[WARNING]** `tests/Feature/Guest/Delivery/PlaceDeliveryOrderTest.php:173-191` — `test_order_is_rejected_when_payment_methods_do_not_match_total` assere apenas `assertStatus(422)`. Não verifica ausência de `Attendance`/`Order`/`DeliveryOrder` órfãos, o que é exatamente o que mascara o bug CRITICAL de transação cross-connection. **Sugestão:** adicionar `assertDatabaseCount('orders', 0)` e `assertDatabaseMissing` para attendances e delivery_orders após a rejeição.
[OBSERVAÇÃO_DESENVOLVEDOR] implementar

- **[WARNING]** Nenhum teste cobre manipulação de preço via `variation_id` de outro produto, nem `product_id` de outra venue no fluxo de delivery. O teste existente (`test_product_not_available_for_delivery_is_rejected`) só cobre a flag, não o escopo. **Sugestão:** adicionar dois testes de caminho de ataque: variação de produto alheio e produto de venue vizinha na mesma corporation.
[OBSERVAÇÃO_DESENVOLVEDOR] implementar

- **[WARNING]** Zero cobertura de frontend nos módulos entregues (`tests/Node/` tem apenas `translation-manifest.test.mjs`). Os cinco bugs de `__()` — que derrubam Finance, Production, DirectWaiter e KDS em produção — passaram por toda a suíte sem sinal. `FinancialDashboardTest` e `ProductionDashboardTest` validam só o payload Inertia, nunca a renderização. **Sugestão:** smoke test de montagem (Vitest + `@vue/test-utils`) para as páginas novas, ou regra ESLint `no-undef` cobrindo `<script setup>`.
[OBSERVAÇÃO_DESENVOLVEDOR] implementar

- **[DEBT]** `app/Support/DateRangeResolver.php` não tem teste unitário, apesar de conter a lógica não trivial de "período anterior equivalente" usada por todos os deltas do dashboard financeiro. **Sugestão:** teste unitário cobrindo os 5 presets e o cálculo do período anterior.
[OBSERVAÇÃO_DESENVOLVEDOR] implementar

- **[DEBT]** `KdsController::advanceDeliveryStatus` (rota + payload `readyDeliveryOrders`) não tem teste — só a Action (`AdvanceDeliveryOrderStatusTest`). **Sugestão:** teste de feature cobrindo a rota, o gate `module:kds` e a tentativa de avançar pedido de outra venue.
[OBSERVAÇÃO_DESENVOLVEDOR] implementar
---

## Score de Qualidade

- **Pontuação:** 0 / 10 (5 CRITICAL x 2 = -10; 13 WARNING x 1 = -13; 15 DEBT x 0.3 = -4.5 → piso 0)
- **Pontuação de Dívida Técnica:** 7 / 10 (alta — duplicação literal entre as duas actions de pedido, FormRequests idênticos, três cópias de `playSound()`, enums duplicados no frontend, models crus no Inertia e ausência total de cobertura de UI)
- **Parecer Final:** **BLOQUEADO**

**Mínimo para desbloquear:**
1. Corrigir os imports de `useTranslate` nos 5 arquivos Vue (os dashboards, o Direct Garçom e o KDS estão quebrados hoje).
2. Escopar produto/variação/modificador à venue e validar o vínculo variação↔produto no fluxo de delivery.
3. Corrigir a transação para a conexão operacional e mover a validação de total para antes das escritas.
4. Fechar o endpoint público de lookup de clientes ou reduzir o payload ao mínimo, com autorização real.
