# Relatório de Execução – Fases 3, 4 e 5 (Módulos, Subscriptions e Billing)

## Metadados
- **Data de execução:** 20 de julho de 2026
- **Tech-plan de referência:** [tech-plan_fase_3_4_5.md](tech-plan_fase_3_4_5.md)
- **Desenvolvedor:** Rodrigo

## Tarefas Concluídas
- [x] Criar enum `InvoiceStatus` e aplicar casts em `VenueInvoice` e `CorporationInvoice`
- [x] Atualizar `MarkInvoicesOverdueJob` para usar `InvoiceStatus` e enviar notificações `InvoiceOverdue`
- [x] Implementar `SubscriptionCalculator` (cálculo por venue, por corporation, módulos fixos, medidos e dedicated surcharge)
- [x] Implementar `GenerateInvoicesJob` com geração idempotente de `VenueInvoice` e `CorporationInvoice` (modo unified)
- [x] Implementar notificações de billing (`InvoiceGenerated`, `InvoiceOverdue`, `TrialExpired`, `TrialEndingSoon`, `SubscriptionSuspended`)
- [x] Integrar `SubscriptionCalculator` nas actions de ativação/desativação de módulos e atribuição de plano
- [x] Adicionar validação de `ModuleCode` e `ModuleCatalog` ativo em `EnableCorporateModuleAction`
- [x] Criar backoffice controllers: `CorporationModuleController`, `VenueModuleController`, `InvoiceController`
- [x] Criar requests de validação para backoffice de módulos
- [x] Criar views Vue do backoffice (`Platform/Corporations/Modules/Index`, `Platform/Corporations/Venues/Modules/Index`, `Platform/Invoices/Index`, `Platform/Invoices/Show`)
- [x] Criar `ModuleUsageTiersSeeder` e registrá-lo no `DatabaseSeeder`
- [x] Criar `RecordModuleUsageJob` e listener `RecordOrderModuleUsage` registrado para `OrderPlaced`
- [x] Criar scaffolds dos módulos operacionais restantes (Delivery, FiscalNote, VoiceCommand, Production, Finance, DirectWaiter, DirectPrint)
- [x] Modularizar `routes/web.php` em `routes/web/guest.php`, `operational.php`, `corporation.php`, `platform.php`
- [x] Adicionar `tenant.blocked` nas shared props via `HandleInertiaRequests` e atualizar `useModules.ts`
- [x] Registrar jobs de billing no scheduler (`routes/console.php`)
- [x] Escrever e manter testes de feature/unit para billing, backoffice, uso medido e scaffolds
- [x] Rodar Pint e build do frontend

## Cobertura de Testes
- **Resultado da suite completa:** 319 passed, 7 skipped, 946 assertions
- **Testes criados/alterados:**
  - `tests/Unit/Enums/InvoiceStatusTest.php`
  - `tests/Feature/Billing/SubscriptionCalculatorTest.php`
  - `tests/Feature/Billing/Jobs/GenerateInvoicesJobTest.php`
  - `tests/Feature/Billing/Jobs/MarkInvoicesOverdueJobTest.php`
  - `tests/Feature/Billing/Jobs/ExpireTrialsJobTest.php`
  - `tests/Feature/Billing/Jobs/RecordModuleUsageJobTest.php`
  - `tests/Feature/Billing/Listeners/RecordOrderModuleUsageTest.php`
  - `tests/Feature/Billing/Notifications/TrialEndingSoonNotificationTest.php`
  - `tests/Feature/Module/ModuleActivationActionsTest.php`
  - `tests/Feature/Module/ModuleScaffoldRoutesTest.php`
  - `tests/Feature/Platform/AssignPlanToCorporationActionTest.php`
  - `tests/Feature/Platform/CorporationModuleControllerTest.php`
  - `tests/Feature/Platform/VenueModuleControllerTest.php`
  - `tests/Feature/Platform/InvoiceControllerTest.php`

## Arquivos Modificados
### Criados
- `app/Enums/InvoiceStatus.php`
- `app/Services/Billing/SubscriptionCalculator.php`
- `app/Jobs/Billing/GenerateInvoicesJob.php`
- `app/Jobs/Billing/NotifyTrialEndingSoonJob.php`
- `app/Jobs/Billing/RecordModuleUsageJob.php`
- `app/Listeners/Billing/RecordOrderModuleUsage.php`
- `app/Notifications/Billing/InvoiceGenerated.php`
- `app/Notifications/Billing/InvoiceOverdue.php`
- `app/Notifications/Billing/SubscriptionSuspended.php`
- `app/Notifications/Billing/TrialEndingSoon.php`
- `app/Notifications/Billing/TrialExpired.php`
- `app/Http/Controllers/Platform/CorporationModuleController.php`
- `app/Http/Controllers/Platform/VenueModuleController.php`
- `app/Http/Controllers/Platform/InvoiceController.php`
- `app/Http/Requests/Platform/StoreCorporationModuleRequest.php`
- `app/Http/Requests/Platform/StoreVenueModuleRequest.php`
- `app/Http/Controllers/Delivery/DashboardController.php`
- `app/Http/Controllers/DirectPrint/DashboardController.php`
- `app/Http/Controllers/DirectWaiter/DashboardController.php`
- `app/Http/Controllers/Finance/DashboardController.php`
- `app/Http/Controllers/FiscalNote/DashboardController.php`
- `app/Http/Controllers/Production/DashboardController.php`
- `app/Http/Controllers/VoiceCommand/DashboardController.php`
- `database/seeders/ModuleUsageTiersSeeder.php`
- `routes/web/guest.php`
- `routes/web/operational.php`
- `routes/web/corporation.php`
- `routes/web/platform.php`
- `resources/js/Pages/Delivery/Index.vue`
- `resources/js/Pages/DirectPrint/Index.vue`
- `resources/js/Pages/DirectWaiter/Index.vue`
- `resources/js/Pages/Finance/Index.vue`
- `resources/js/Pages/FiscalNote/Index.vue`
- `resources/js/Pages/Production/Index.vue`
- `resources/js/Pages/VoiceCommand/Index.vue`
- `resources/js/Pages/Platform/Corporations/Modules/Index.vue`
- `resources/js/Pages/Platform/Corporations/Venues/Modules/Index.vue`
- `resources/js/Pages/Platform/Invoices/Index.vue`
- `resources/js/Pages/Platform/Invoices/Show.vue`
- `tests/Unit/Enums/InvoiceStatusTest.php`
- `tests/Feature/Billing/SubscriptionCalculatorTest.php`
- `tests/Feature/Billing/Jobs/GenerateInvoicesJobTest.php`
- `tests/Feature/Billing/Jobs/RecordModuleUsageJobTest.php`
- `tests/Feature/Billing/Listeners/RecordOrderModuleUsageTest.php`
- `tests/Feature/Billing/Notifications/TrialEndingSoonNotificationTest.php`
- `tests/Feature/Platform/AssignPlanToCorporationActionTest.php`
- `tests/Feature/Platform/CorporationModuleControllerTest.php`
- `tests/Feature/Platform/VenueModuleControllerTest.php`
- `tests/Feature/Platform/InvoiceControllerTest.php`
- `tests/Feature/Module/ModuleScaffoldRoutesTest.php`
- `tech-plan_fase_3_4_5.md`
- `relatorio_execucao_3_4_5.md`

### Alterados
- `app/Actions/Corporation/ActivateVenueModuleAction.php`
- `app/Actions/Corporation/DeactivateVenueModuleAction.php`
- `app/Actions/Platform/AssignPlanToCorporationAction.php`
- `app/Actions/Platform/EnableCorporateModuleAction.php`
- `app/Actions/Platform/DisableCorporateModuleAction.php`
- `app/Http/Middleware/HandleInertiaRequests.php`
- `app/Jobs/Billing/ExpireTrialsJob.php`
- `app/Jobs/Billing/MarkInvoicesOverdueJob.php`
- `app/Jobs/Billing/SuspendOverdueSubscriptionsJob.php`
- `app/Models/Tenant/CorporationInvoice.php`
- `app/Models/Tenant/VenueInvoice.php`
- `app/Models/Tenant/VenueModule.php`
- `app/Providers/AppServiceProvider.php`
- `database/factories/Tenant/CorporationFactory.php`
- `database/factories/Tenant/CorporationInvoiceFactory.php`
- `database/seeders/DatabaseSeeder.php`
- `resources/js/Composables/useModules.ts`
- `routes/web.php`
- `routes/console.php`
- `tests/Feature/Billing/Jobs/ExpireTrialsJobTest.php`
- `tests/Feature/Billing/Jobs/MarkInvoicesOverdueJobTest.php`
- `tests/Feature/Module/ModuleActivationActionsTest.php`

## Padrões Aplicados
- **Service Layer:** `SubscriptionCalculator` e `BillingStatusService` centralizam a lógica financeira.
- **State Machine:** `InvoiceStatus` e `SubscriptionStatus` guiam transições determinísticas nos jobs.
- **Event-Driven + Jobs Async:** `OrderPlaced` dispara `RecordOrderModuleUsage`, que envia jobs para fila sem bloquear o request.
- **Idempotent Writes:** `VenueInvoice`/`CorporationInvoice` usam `updateOrCreate` por período; `VenueUsageRecord` incrementa quantidade existente.
- **Cache Invalidation Pattern:** `VenueModuleCache::forget` é chamado após ativação/desativação de módulos.
- **Route Modularization:** `routes/web.php` passa a carregar arquivos por domínio, melhorando a manutenibilidade.

## Decisões Arquiteturais Seguidas
1. `SubscriptionCalculator` dedicado para cálculo por venue e corporation unificada, reutilizado por actions e jobs.
2. `GenerateInvoicesJob` gera `VenueInvoice` sempre e `CorporationInvoice` apenas no modo `unified`, mantendo granularidade.
3. `InvoiceStatus` enum aplicado em ambos os modelos de fatura, substituindo strings literais.
4. Proration **não implementado** — módulos ativados/desativados no meio do mês são cobrados/isentos pelo mês inteiro.
5. Registro de uso medido assíncrono via listeners e jobs.
6. Scaffolds (controllers/views placeholder) para os 7 módulos operacionais restantes.
7. Notificações de email implementadas para trial, suspensão e faturas.
8. Validação de `ModuleCode` e `ModuleCatalog` ativo em `EnableCorporateModuleAction`.
9. Flag `tenant.blocked` real exposto ao frontend substituindo a heurística anterior.

## Overrides do Desenvolvedor
- Nenhum override registrado nesta execução.

## Bloqueadores Encontrados
- `VenueInvoice` não possuía relação com `Corporation`, necessária para rotear notificações. Resolvido com relação `hasOneThrough` via `Venue`.
- `CorporationFactory` não criava `owner` por padrão, causando falhas em testes de notificação. Resolvido criando `User` owner no factory.
- `EnableCorporateModuleAction` aceitava strings livres; adicionada validação de `ModuleCode` e catálogo ativo.
- `RecordModuleUsageJob` precisou incrementar quantidade ao invés de substituir. Resolvido com `firstOrNew` + incremento.
- Modularização exigiu atenção para preservar named routes e middlewares; validado via `route:list` e testes.

## Orientações para Code Review
- Verificar se `InvoiceStatus` enum não quebra integrações que esperavam strings (casts de Eloquent devem mascarar).
- Revisar se `GenerateInvoicesJob` lida corretamente com subscriptions sem venues.
- Confirmar que `tenant.blocked` não expõe informação sensível além do necessário.
- Validar se as rotas de scaffolds estão protegidas pelo middleware `module:` correto.
- Checar se `ModuleUsageTiersSeeder` cobre todos os módulos híbridos planejados.
- Garantir que jobs de billing estejam configurados no ambiente de filas em produção.
- Rodar `vendor/bin/sail artisan test --compact` e `vendor/bin/sail bin pint --dirty --format agent` antes do merge.

## Itens Pendentes / Deferidos
- Integração com Asaas (gateway de pagamento) — explicitamente adiada para fase posterior.
- Implementação de proration — documentado como não implementado; faturamento é de mês inteiro.
- Funcionalidades reais dos módulos scaffolds (Delivery, FiscalNote, VoiceCommand, Production, Finance, DirectWaiter, DirectPrint).
