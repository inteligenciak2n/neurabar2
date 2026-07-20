# Relatório de Revisão – feat/sistema-modulos-assinaturas vs main

## Resumo da Task
A task solicitou a implementação do plano completo de cobrança descrito em `docs/billing-policies-non-technical.md` e nos planos técnicos das Fases 1–5. O escopo inclui: schema de módulos, subscriptions, faturas, afiliados, middleware de controle de acesso, jobs de transição de status, cálculo de subscription/fatura, backoffice comercial e scaffolds dos módulos operacionais.

A revisão focou nos arquivos alterados relacionados a esse escopo (migrations, models, enums, actions, services, jobs, listeners, controllers, rotas, frontend Vue e tests).

## Resultado da Revisão
- **Arquivos revisados:** ~90 (foco nos 60+ arquivos relacionados a billing, módulos e subscription)
- **Total de findings:** 17 ([CRITICAL]: 3, [WARNING]: 11, [DEBT]: 3)

## Findings por Dimensão

### Segurança
- [CRITICAL] `app/Jobs/Billing/GenerateInvoicesJob.php:61` — `VenueInvoice::updateOrCreate` usa apenas `(venue_id, period)` como chave. Quando `SubscriptionCalculator::calculateVenue()` encontra uma fatura finalizada, ele retorna valores zerados (`emptyResult()`), mas o job ainda chama `updateOrCreate`, sobrescrevendo a fatura finalizada com `base=0, modules=0, metered=0, total=0`. Isso viola a regra de integridade financeira de faturas finalizadas.
  **Sugestão:** Adicione `->where('is_finalized', false)` na cláusula de busca do `updateOrCreate` ou faça o calculator retornar `null` para faturas finalizadas e pule a atualização no job.

- [CRITICAL] `app/Http/Controllers/Platform/VenueModuleController.php` — A rota `platform.corporations.venues.modules.*` recebe `{corporation}` e `{venue}`, mas o route model binding resolve `Venue` independentemente da corporation. Não há validação de que `$venue->corporation_id === $corporation->id`. Um usuário do backoffice pode gerenciar módulos de uma venue de outra corporation conhecendo os UUIDs (IDOR).
  **Sugestão:** Adicione validação explícita ou use route binding customizado (`Route::bind`) para garantir que a venue pertença à corporation.

- [CRITICAL] `app/Jobs/Billing/SuspendOverdueSubscriptionsJob.php` — O job suspende apenas com base em `trial_ends_at + grace_period_days`. Assinaturas ativas (`status = active`) que deixaram de pagar uma fatura nunca são suspensas, pois não há verificação de faturas `overdue`. Em produção, clientes em modo ativo nunca serão bloqueados por inadimplência.
  **Sugestão:** Implemente suspensão com base em faturas vencidas (`VenueInvoice`/`CorporationInvoice` com `status = overdue` e `due_date < now() - grace_period_days`).

- [WARNING] `app/Http/Controllers/Platform/InvoiceController.php:39` — `InvoiceController::show` permite que qualquer usuário autenticado no backoffice visualize qualquer fatura (corporate ou venue) apenas conhecendo o UUID, sem verificar se a fatura pertence a uma corporation sob sua alçada.
  **Sugestão:** Adicione autorização via Policy ou verifique o perfil/escopo do usuário platform antes de exibir a fatura.

### Corretude e Lógica
- [CRITICAL] `app/Services/Billing/SubscriptionCalculator.php:24` — `calculateVenue()` atualiza `VenueSubscription` mesmo quando há fatura finalizada para o período. Isso permite que o valor da subscription fique desatualizado/incorreto após uma fatura ser fechada.
  **Sugestão:** Não atualize `VenueSubscription` quando `hasFinalizedInvoice()` for true, ou mantenha o snapshot histórico separado do valor atual.

- [CRITICAL] `app/Services/Billing/BillingStatusService.php` — No modo `per_venue`, o bloqueio depende do status da `VenueSubscription`. Porém, `SuspendOverdueSubscriptionsJob` nunca suspende uma `VenueSubscription` individual por uma fatura daquela venue atrasada; ele apenas replica o status da corporation. Portanto, no modo `per_venue`, uma venue inadimplente nunca será suspensa individualmente, contradizendo `docs/billing-policies-non-technical.md` seção 9.2.
  **Sugestão:** Implemente lógica de suspensão por venue no modo `per_venue`, considerando `VenueInvoice.overdue` e grace period.

- [WARNING] `app/Listeners/Billing/RecordOrderModuleUsage.php:15` — Ao receber `OrderPlaced`, incrementa uso de `Kds`, `Taker` e `DirectPrint`. O documento de cobrança especifica que KDS é medido por "pedidos despachados" (evento de item finalizado), não por pedido criado. O listener atualmente contabiliza KDS no momento errado do fluxo.
  **Sugestão:** Registre KDS ao despachar item (`ItemStatusUpdated` com status final), Taker no `OrderPlaced` quando originado do taker, e DirectPrint no momento real da impressão.

- [WARNING] `app/Providers/AppServiceProvider.php:35` — Apenas `OrderPlaced` tem listener de uso medido. Os eventos `ItemStatusUpdated` e `GuestSignaled` (previstos no `tech-plan_fase_3_4_5.md`) não possuem listeners, então módulos `metered`/`hybrid` (Direct Waiter, Voice Command, KDS) não registram consumo conforme o plano.
  **Sugestão:** Adicione listeners específicos (`RecordKdsUsage`, `RecordSignalUsage`) e registre-os no `AppServiceProvider`.

- [WARNING] `app/Jobs/Billing/GenerateInvoicesJob.php:61-104` — No modo `unified`, o job cria `VenueInvoice` para cada venue e uma `CorporationInvoice`, mas não preenche `venue_invoices.corporation_invoice_id`. O schema prevê essa FK justamente para vincular faturas de agrupamento, prejudicando conciliação e relatórios futuros.
  **Sugestão:** Após criar/atualizar a `CorporationInvoice`, atualize `corporation_invoice_id` nas `VenueInvoice` geradas no modo unified.

- [WARNING] `app/Jobs/Billing/GenerateInvoicesJob.php:122` — Notificação `InvoiceGenerated` é enviada apenas quando a `CorporationInvoice` é criada (`wasRecentlyCreated`). No modo `per_venue`, o owner nunca recebe notificação de fatura gerada.
  **Sugestão:** Envie notificação para o owner também quando `VenueInvoice` for criada no modo `per_venue`.

### Performance
- [WARNING] `app/Jobs/Billing/GenerateInvoicesJob.php:23` — O job carrega todas as `CorporationSubscription` ativas e, para cada uma, carrega todas as venues e roda cálculos. Sem `chunk()` ou lazy loading, o job pode estourar memória em produção com centenas/milhares de corporations/venues.
  **Sugestão:** Use `CorporationSubscription::query()->cursor()` ou `chunkById()`; considere processar por corporation em jobs filhos.

- [WARNING] `app/Services/Billing/BillingStatusService.php` — `isBlocked()` executa queries contra `corporation_subscriptions`/`venue_subscriptions` a cada requisição operacional (via middleware e shared props `tenant.blocked`). Não há cache.
  **Sugestão:** Adicione cache curto (ex: 60s) para o status de bloqueio da venue, invalidado nas transições de status dos jobs.

### Complexidade e Manutenibilidade
- [DEBT] `app/Actions/Fortify/CreateUserOwnerDefinitions.php` e `app/Actions/Corporation/CreateVenueAction.php` — Ambos criam `KitchenStation`, `PreparationStatus` e `AttendanceChannel` defaults, mas com dados em idiomas diferentes (português vs inglês). Isso é duplicação de código e gera inconsistência de dados dependendo do fluxo de criação.
  **Sugestão:** Extraia a criação de defaults para uma action reutilizável (`CreateVenueDefaultsAction`) usada por ambos.

- [DEBT] `app/Services/Billing/SubscriptionCalculator.php` — A classe calcula valores, atualiza `VenueSubscription` e atualiza `VenueUsageRecord` ao mesmo tempo. Múltiplas responsabilidades dificultam testes e manutenção.
  **Sugestão:** Separe em `SubscriptionCalculator` (cálculo puro), `VenueSubscriptionUpdater` e `UsageRecordCalculator`.

- [DEBT] `app/Actions/Corporation/CreateVenueAction.php:44` — Fallback hardcoded cria um `PlanCatalog` "pro" com preço 99,90 se a corporation não tiver subscription. Isso mistura lógica de negócio com criação de venue e pode gerar planos inesperados.
  **Sugestão:** Garanta que toda corporation tenha subscription válida antes de criar venue; remova o fallback ou mova-o para uma configuração explícita.

### Padrões e Arquitetura
- [WARNING] `app/Actions/Platform/DisableCorporateModuleAction.php` — Desabilita o módulo corporativo, mas não invalida o cache `VenueModuleCache` das venues afetadas. Venues que tinham o módulo ativo continuam acessando-o por até 1 hora.
  **Sugestão:** Após desabilitar, itere pelas venues da corporation e chame `VenueModuleCache::forget($venue)`.

- [WARNING] `app/Models/User.php:138` — `canAccessModule()` verifica apenas se o módulo está ativo na venue e se não está bloqueado, mas não verifica se a corporation contratou o módulo (`hasActiveModule`). Isso é inconsistente com `RequireModule` e pode permitir bypass em lógicas condicionais futuras.
  **Sugestão:** Alinhe `canAccessModule()` com a mesma lógica de `RequireModule`: verifique `corporation->hasActiveModule()` e dependências transitivas.

- [WARNING] `database/factories/Tenant/VenueInvoiceFactory.php:23` e `database/factories/Tenant/CorporationInvoiceFactory.php:27` — Usam a string literal `'open'` para `status` ao invés de `InvoiceStatus::Open->value`. Funciona por causa do cast, mas é inconsistente com o restante do código e frágil a renomeações.
  **Sugestão:** Use o enum em todas as factories.

- [WARNING] `tech-plan_fase_3_4_5.md` prevê `RecalculateSubscriptionJob` para atualizar `VenueSubscription` fora do ciclo de fatura. O job não foi implementado, dificultando recálculos assíncronos em massa.
  **Sugestão:** Implemente o job ou remova a referência do plano técnico.

### Cobertura de Testes
- [WARNING] `tests/Feature/Billing/Jobs/GenerateInvoicesJobTest.php:117` — `test_does_not_generate_for_finalized_venue_invoice` apenas verifica a contagem de registros (`assertDatabaseCount`). Não valida se os valores da fatura finalizada não foram sobrescritos para zero. O bug descrito no finding CRITICAL passa despercebido.
  **Sugestão:** Adicione asserções sobre `base_value`, `modules_value` e `total_value` após executar o job.

- [WARNING] Não há testes que verifiquem se `VenueModuleController` impede a gestão de módulos de uma venue que não pertence à corporation informada na URL (IDOR).
  **Sugestão:** Adicione teste de feature que tenta acessar `/backoffice/corporations/{outra_corporation}/venues/{minha_venue}/modules` e espera 404/403.

- [WARNING] Não há testes para invalidação de cache ao desabilitar módulo corporativo (`DisableCorporateModuleAction`) nem para os listeners de `ItemStatusUpdated`/`GuestSignaled`.
  **Sugestão:** Adicione testes que validam `VenueModuleCache::forget` chamado para todas as venues da corporation e testes de listeners de uso medido.

## Score de Qualidade
- **Pontuação:** 0/10 (3 findings CRITICAL bloqueantes; cada um reduz 2 pontos, mínimo 0)
- **Pontuação de Dívida Técnica:** 3/10 (duplicação, responsabilidades mistas, fallback hardcoded, inconsistências de enum)
- **Parecer Final:** BLOQUEADO

A branch entrega a estrutura solicitada, mas possui três bugs críticos que afetam diretamente a integridade financeira (sobrescrita de faturas finalizadas), o controle de acesso (IDOR de venues) e a regra de negócio de suspensão por inadimplência (assinaturas ativas nunca suspensas). Esses itens devem ser corrigidos antes do merge.
