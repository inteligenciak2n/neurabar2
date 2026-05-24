Read [](file:///home/rodrigo/workplace/inteligenciakn2/neurabar2/resources/js/Pages/Orders/Taker.vue#1-1), lines 1 to 100

Read [](file:///home/rodrigo/workplace/inteligenciakn2/neurabar2/resources/js/Pages/Orders/Taker.vue#80-80), lines 80 to 80

Read [](file:///home/rodrigo/workplace/inteligenciakn2/neurabar2/resources/js/Pages/Orders/Taker.vue#100-100), lines 100 to 179

Read [](file:///home/rodrigo/workplace/inteligenciakn2/neurabar2/app/Actions/Menu/CreateProductAction.php)

Read [](file:///home/rodrigo/workplace/inteligenciakn2/neurabar2/app/Http/Requests/Menu/StoreProductRequest.php)

Read [](file:///home/rodrigo/workplace/inteligenciakn2/neurabar2/tests/Feature/Menu/ModifierTest.php#1-1), lines 1 to 80

Read [](file:///home/rodrigo/workplace/inteligenciakn2/neurabar2/resources/js/Pages/Kitchen/Kds.vue#100-100), lines 100 to 120

Read [](file:///home/rodrigo/workplace/inteligenciakn2/neurabar2/resources/js/Pages/Kitchen/Kds.vue#140-140), lines 140 to 182

Read [](file:///home/rodrigo/workplace/inteligenciakn2/neurabar2/resources/js/Pages/Attendances/Index.vue#1-1), lines 1 to 80

Searched for regex `partialTotal\|ordersTotal\|order\.items` (`**/*.vue`), no results

Searched for text `auth\.venue` (`**/*.vue`), 2 results

Read [](file:///home/rodrigo/workplace/inteligenciakn2/neurabar2/app/Http/Middleware/HandleInertiaRequests.php)

Searched for text `auth.venue` (`**/app/**/*.php`), no results

Searched for text `share(` (`**/app/**/*.php`), 2 results

Searched for text `auth.user` (`**/vendor/laravel/jetstream/**/*.php`), no results

Searched for text `share` (`**/vendor/laravel/jetstream/**/*.php`), no results

Searched for text `share` (`**/vendor/laravel/jetstream/**/*.php`), 8 results

Read [](file:///home/rodrigo/workplace/inteligenciakn2/neurabar2/vendor/laravel/jetstream/src/Http/Middleware/ShareInertiaData.php)

Searched for text `abort_if` (`**/app/Http/Controllers/**/*.php`), 10 results

Read [](file:///home/rodrigo/workplace/inteligenciakn2/neurabar2/app/Models/Menu/Menu.php)

Searched for text `function destroy\|function update\|function toggleActive` (`**/app/Http/Controllers/Menu/ProductController.php`), no results

Read [](file:///home/rodrigo/workplace/inteligenciakn2/neurabar2/app/Http/Controllers/Menu/ProductController.php#60-60), lines 60 to 95

Read [](file:///home/rodrigo/workplace/inteligenciakn2/neurabar2/app/Actions/Menu/UpdateProductAction.php)

Read [](file:///home/rodrigo/workplace/inteligenciakn2/neurabar2/app/Actions/Menu/ToggleProductActiveAction.php)

Read [](file:///home/rodrigo/workplace/inteligenciakn2/neurabar2/app/Http/Requests/Menu/UpdateProductRequest.php)

Compacted conversation

Read [](file:///home/rodrigo/workplace/inteligenciakn2/neurabar2/database/factories/VenueFactory.php)

Read [](file:///home/rodrigo/workplace/inteligenciakn2/neurabar2/database/factories/AttendanceFactory.php)

Agora tenho contexto suficiente para compilar o relatório completo.

---

## Revisão Completa da Implementação — Blocos 0 a 14

Abaixo estão todos os problemas identificados, organizados por categoria e severidade.

---

### 🔴 BUGS — Código que existe mas está errado

**1. `HandleInertiaRequests` — `auth.venue` nunca é compartilhado**
Kds.vue lê `page.props.auth?.venue?.id` e AppLayout.vue exibe `auth.venue.name`, mas `ShareInertiaData` (Jetstream) só compartilha `auth.user`. O campo `venue` nunca chega ao frontend — o KDS vai falhar silenciosamente ao tentar assinar o canal do Echo, e o layout não exibe o nome do venue.

**2. `OrderPlaced` event — `attendance` pode não estar carregado**
`PlaceOrderAction` dispara `event(new OrderPlaced($order))` sem carregar a relação `attendance`. O método `broadcastOn()` acessa `$this->order->attendance->venue_id` — isso dispara um lazy load durante broadcasting, que pode falhar dependendo do contexto do job.

**3. `StoreOrderRequest` — queries duplicadas**
`prepareForValidation()` e `rules()` consultam Menu → Category → Product → Variations independentemente. Cada requisição ao Order Taker duplica ~8 queries de banco.

**4. `UpdateItemStatusAction::isLastPendingItem()` — `ready_at` nunca pode ser limpo**
A linha `$item->ready_at ?? now()` preserva o valor existente. Não há como retroceder um item ao status anterior após ele ser marcado como pronto. A lógica de "last pending" também verifica outros itens *antes* de salvar o atual, o que é correto, mas o `ready_at` irreversível é um bug.

**5. `SendServiceRequestAction` — comparação de passphrase não é timing-safe**
```php
$venue->call_waiter_passphrase !== $validated['passphrase']
```
Deve usar `hash_equals()`. Vulnerável a timing attacks.

**6. `Platform\LoginController` — não verifica flag `active`**
`auth('platform')->attempt()` não valida `$user->active`. Usuários de plataforma desativados conseguem logar.

**7. `AppServiceProvider` — Gate `access-corporation` não inclui `GeneralManager`**
O plano define acesso ao painel corporativo para `CorporationAdmin`, `Owner` **e** `GeneralManager`, mas o Gate só registra os dois primeiros. Isso bloqueia GMs dos routes corporativos mesmo com o middleware de role correto.

**8. `KdsController::monitor()` — query órfã**
```php
$venues = Venue::all(); // declarada mas nunca usada
```
Dead code + query desnecessária a cada abertura do monitor KDS.

**9. `MetricsService` — double-caching de MRR**
`calculateMRR()` armazena em `platform.metrics.mrr` (300s). `operationalSummary()` chama `$this->calculateMRR()` internamente e armazena o resultado composto em `platform.metrics.summary` (300s). Dois caches com TTLs independentes — inconsistência nos dados exibidos entre as duas chamadas.

**10. channels.php — `session()` em contexto de broadcasting**
```php
session('active_venue_id') === $id
```
A autorização de canais privados ocorre em uma request separada (POST `/broadcasting/auth`). A sessão HTTP pode não estar disponível nesse contexto, especialmente com Sanctum token-auth ou Soketi. A verificação funciona apenas via `$user->venue_id`.

**11. `VenueSelectorController` — Owner sem `corporation_id` recebe 404**
`User::activeVenue()` para role `Owner` sem `corporation_id` retorna `$this->venue`, mas o controller busca venues por `corporation_id`. Owners vinculados diretamente a um venue (sem corporation) não conseguem usar o seletor.

**12. `DashboardController` — N+1 no `stationsSummary`**
Uma query COUNT é executada por estação dentro de um loop. Com 10 estações = 11 queries em vez de 1.

---

### 🟠 LACUNAS DE IMPLEMENTAÇÃO — Feature prevista no plano mas ausente

**1. Order Taker — grupos de modificadores obrigatórios (Bloco 11.2)**
Taker.vue hardcoda `modifiers: []` ao enviar o pedido:
```js
modifiers: [], // ← nunca populado
```
`StoreOrderRequest` não tem nenhuma validação de grupos obrigatórios. O backend aceita qualquer pedido sem modificadores, mesmo quando o produto exige.

**2. Order Taker — modal de seleção de variação (Bloco 11.1)**
Clicar em um produto com variações o adiciona diretamente ao carrinho sem mostrar opções. Nenhuma modal de seleção de variação existe em Taker.vue.

**3. Order Taker — aba de Combos (Bloco 11.3)**
Nenhuma seção de combos existe no frontend. `PlaceOrderAction` também não trata `combo_id` como alternativa a `product_id`.

**4. `TrackOrderController` — não filtra canal `service_request` (Bloco 12.1)**
O plano especifica que orders com `channel = service_request` nunca devem aparecer no rastreamento do cliente. O controller atual não filtra isso.

**5. `CallWaiterController` — rate limit sem chave composta**
O throttle `1,1` é baseado apenas em IP. O plano especifica chave composta `ip + slug + customer_identifier` para evitar que um mesmo cliente faça múltiplas chamadas com IPs distintos ou que um IP sirva múltiplos clientes.

**6. `AttendanceController::close()` — endpoint público contradiz o plano**
O plano especifica que `CloseAttendanceAction` só deve ser invocada internamente via `RegisterPaymentAction`. Existe um endpoint HTTP direto `POST /attendances/{id}/close` que permite fechar attendances sem registrar pagamento — burla a obrigatoriedade do pagamento.

---

### 🟡 PROBLEMAS DE SEGURANÇA / AUTORIZAÇÃO

**1. `UserController::update()` e `destroy()` — sem verificação de tenant**
`User` não usa `BelongsToVenue` (sem `TenantScope`). Um usuário autenticado pode enviar qualquer UUID de usuário e modificar/deletar registros de outros venues.

**2. `ProductController::destroy()` e `toggleActive()` — sem verificação de tenant**
`Product` não tem `BelongsToVenue`. `destroy()` e `toggleActive()` não verificam que o produto pertence ao tenant atual (ao contrário de `syncModifierGroups()` que tem o `abort_if` correto). Inconsistência no mesmo controller.

**3. `AttendanceController::update()` aceita `channel` como mutável**
Usa `StoreAttendanceRequest` tanto no `store()` quanto no `update()`, permitindo alterar o `channel` de uma attendance aberta. Canal deve ser imutável após criação.

---

### 🔵 DESVIOS DE PADRÃO

**1. `CreateCorporationAction` usa `bcrypt()` (deprecado)**
Deveria usar `Hash::make()` para consistência com o driver de hashing configurado no `config/hashing.php`.

**2. `DashboardController` não usa scope declarado**
`OrderItem::scopeInPreparation()` existe mas o controller inline a condição `whereNull('ready_at')` diretamente.

**3. `CorporationDashboardController` carrega todas as attendances para contar**
```php
$venue->load('attendances') // depois usa ->count()
```
Deve usar `withCount('attendances')`.

**4. `CallWaiterController::store()` retorna JSON puro**
Retorna `response()->json(...)` enquanto toda a arquitetura é Inertia. O frontend precisaria usar `axios` em vez de `useForm`, quebrando o padrão da aplicação.

**5. `HandleInertiaRequests::share()` vazio**
A aplicação tem dados críticos que precisam ser compartilhados globalmente (venue ativo, role do usuário, flash messages de payment) mas o método `share()` está completamente vazio — toda a responsabilidade caiu no middleware do Jetstream que não conhece o contexto da aplicação.

---

### 📋 SUMÁRIO

| Categoria | Qtd |
|---|---|
| Bugs | 12 |
| Lacunas de implementação | 6 |
| Problemas de autorização/segurança | 3 |
| Desvios de padrão | 5 |
| **Total** | **26** |

---

Deseja que eu priorize e corrija algum grupo específico? Posso sugerir uma ordem baseada em impacto (segurança primeiro, depois funcionalidade crítica).