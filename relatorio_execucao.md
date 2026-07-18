# Relatório de Execução – Módulos e Subscriptions (Fases 1 e 2)

## Metadados
- **Data de execução:** 18 de julho de 2026
- **Tech-plan de referência:** Plano técnico das Fases 1 e 2 do modelo de módulos contratáveis, subscriptions e billing do NeuraBar.
- **Desenvolvedor:** Rodrigo

## Tarefas Concluídas
- [x] Implementar jobs diários de transição de status (`ExpireTrialsJob`, `SuspendOverdueSubscriptionsJob`, `MarkInvoicesOverdueJob`).
- [x] Registrar jobs no scheduler (`routes/console.php`).
- [x] Criar actions de ativação/desativação de módulos (`EnableCorporateModuleAction`, `DisableCorporateModuleAction`, `ActivateVenueModuleAction`, `DeactivateVenueModuleAction`).
- [x] Proteger rotas operacionais com middleware `module:` conforme mapeamento do tech-plan.
- [x] Adicionar shared prop `tenant.modules` no `HandleInertiaRequests`.
- [x] Criar composable Vue `useModules.ts`.
- [x] Atualizar `DatabaseSeeder` para incluir `ModuleCatalogsSeeder` e `AffiliateCodesSeeder`.
- [x] Corrigir ordem das migrations e remover migration duplicada de `affiliate_code_id`.
- [x] Tornar `venue_subscription_id` nullable em `venue_invoices` para suportar faturas de agrupamento.
- [x] Recuperar e corrigir `CreateUserOwnerDefinitions.php` que havia ficado truncado.
- [x] Escrever testes de feature para billing, jobs, middleware e actions de módulos.
- [x] Atualizar `TestCase::loginAs` para garantir subscription e módulo `menu` default em testes operacionais.
- [x] Atualizar `docs/module-subscription-architecture.md` com progresso concluído.
- [x] Corrigir `CreateCorporationAction` para criar owner manualmente, vincular `owner_id` e evitar duplicação de corporation/venue.
- [x] Corrigir `CorporationController` para carregar `subscription.planCatalog` em vez de `planCatalog` removido.
- [x] Atualizar `AssignPlanToCorporationAction` para aplicar todos os campos validados e criar `VenueSubscription` quando necessário.
- [x] Invalidar `VenueModuleCache` nas actions de ativação/desativação de módulos.
- [x] Validar `ModuleCode` e `CorporationModule` em `ActivateVenueModuleAction`.
- [x] Adicionar verificação de `CorporationModule` no middleware `RequireModule`.
- [x] Considerar `ended_at` em `BillingStatusService` e relações `subscription()` de `Corporation` e `Venue`.
- [x] Corrigir `SuspendOverdueSubscriptionsJob` para usar `grace_period_days` da `CorporationSubscription` ao suspender `VenueSubscription`.
- [x] Separar `overdue` de `is_finalized` em `MarkInvoicesOverdueJob`.
- [x] Atualizar `MetricsService`, frontend (`Edit.vue`, `Index.vue`) e testes antigos para não referenciar campos removidos de `corporations`.
- [x] Atualizar `SchemaIntegrityTest` para refletir schema atual.
- [x] Rodar `vendor/bin/sail bin pint --dirty --format agent` com sucesso.

## Cobertura de Testes
- **Cobertura final:** suite completa de feature + unit tests.
- **Testes criados/atualizados:** 16+ testes de feature (Fases 1 e 2) + testes antigos ajustados.
  - `tests/Feature/Billing/BillingStatusServiceTest.php`
  - `tests/Feature/Billing/Jobs/ExpireTrialsJobTest.php`
  - `tests/Feature/Billing/Jobs/SuspendOverdueSubscriptionsJobTest.php`
  - `tests/Feature/Billing/Jobs/MarkInvoicesOverdueJobTest.php`
  - `tests/Feature/Module/RequireModuleMiddlewareTest.php`
  - `tests/Feature/Module/ModuleActivationActionsTest.php`
  - `tests/Feature/Platform/CorporationTest.php` (atualizado)
  - `tests/Feature/Platform/MetricsTest.php` (atualizado)
  - `tests/Feature/Platform/PlanTest.php` (atualizado)
  - `tests/Feature/Auth/TenantContextTest.php` (atualizado)
  - `tests/Feature/Kitchen/KdsTest.php` (atualizado)
  - `tests/Feature/Migrations/SchemaIntegrityTest.php` (atualizado)
- **Resultado final:** 268 passed, 7 skipped, 786 assertions.

## Arquivos Modificados
### Criados
- `app/Actions/Corporation/ActivateVenueModuleAction.php`
- `app/Actions/Corporation/DeactivateVenueModuleAction.php`
- `app/Actions/Platform/DisableCorporateModuleAction.php`
- `app/Actions/Platform/EnableCorporateModuleAction.php`
- `app/Enums/AffiliateCodeStatus.php`
- `app/Enums/BillingMode.php`
- `app/Enums/ModuleBillingType.php`
- `app/Enums/ModuleCode.php`
- `app/Enums/ModuleStatus.php`
- `app/Enums/SubscriptionStatus.php`
- `app/Http/Middleware/RequireModule.php`
- `app/Jobs/Billing/ExpireTrialsJob.php`
- `app/Jobs/Billing/MarkInvoicesOverdueJob.php`
- `app/Jobs/Billing/SuspendOverdueSubscriptionsJob.php`
- `app/Models/Tenant/AffiliateCode.php`
- `app/Models/Tenant/CorporationInvoice.php`
- `app/Models/Tenant/CorporationModule.php`
- `app/Models/Tenant/CorporationSubscription.php`
- `app/Models/Tenant/ModuleCatalog.php`
- `app/Models/Tenant/ModuleUsageTier.php`
- `app/Models/Tenant/PaymentAttempt.php`
- `app/Models/Tenant/VenueInvoice.php`
- `app/Models/Tenant/VenueModule.php`
- `app/Models/Tenant/VenueSubscription.php`
- `app/Models/Tenant/VenueUsageRecord.php`
- `app/Services/Billing/BillingStatusService.php`
- `app/Services/VenueModuleCache.php`
- `config/billing.php`
- `database/factories/Tenant/CorporationInvoiceFactory.php`
- `database/factories/Tenant/CorporationModuleFactory.php`
- `database/factories/Tenant/CorporationSubscriptionFactory.php`
- `database/factories/Tenant/ModuleCatalogFactory.php`
- `database/factories/Tenant/VenueInvoiceFactory.php`
- `database/factories/Tenant/VenueModuleFactory.php`
- `database/factories/Tenant/VenueSubscriptionFactory.php`
- `database/migrations/saas/2026_07_18_172730_create_affiliate_codes_table.php`
- `database/migrations/saas/2026_07_18_172735_create_module_catalogs_table.php`
- `database/migrations/saas/2026_07_18_172736_create_corporation_subscriptions_table.php`
- `database/migrations/saas/2026_07_18_172736_create_venue_subscriptions_table.php`
- `database/migrations/saas/2026_07_18_172737_create_corporation_modules_table.php`
- `database/migrations/saas/2026_07_18_172737_create_venue_modules_table.php`
- `database/migrations/saas/2026_07_18_172738_create_corporation_invoices_table.php`
- `database/migrations/saas/2026_07_18_172738_create_module_usage_tiers_table.php`
- `database/migrations/saas/2026_07_18_172739_create_venue_invoices_table.php`
- `database/migrations/saas/2026_07_18_172739_create_venue_usage_records_table.php`
- `database/migrations/saas/2026_07_18_172740_create_payment_attempts_table.php`
- `database/migrations/saas/2026_07_18_172839_add_affiliate_code_id_to_corporations_and_venues_table.php`
- `database/migrations/saas/2026_07_18_172840_remove_legacy_plan_fields_from_corporations_table.php`
- `database/seeders/AffiliateCodesSeeder.php`
- `database/seeders/ModuleCatalogsSeeder.php`
- `resources/js/Composables/useModules.ts`
- `tech-plan.md`
- `tests/Feature/Billing/BillingStatusServiceTest.php`
- `tests/Feature/Billing/Jobs/ExpireTrialsJobTest.php`
- `tests/Feature/Billing/Jobs/MarkInvoicesOverdueJobTest.php`
- `tests/Feature/Billing/Jobs/SuspendOverdueSubscriptionsJobTest.php`
- `tests/Feature/Module/ModuleActivationActionsTest.php`
- `tests/Feature/Module/RequireModuleMiddlewareTest.php`

### Modificados
- `app/Actions/Corporation/ActivateVenueModuleAction.php`
- `app/Actions/Corporation/CreateVenueAction.php`
- `app/Actions/Corporation/DeactivateVenueModuleAction.php`
- `app/Actions/Fortify/CreateUserOwnerDefinitions.php`
- `app/Actions/Platform/AssignPlanToCorporationAction.php`
- `app/Actions/Platform/CreateCorporationAction.php`
- `app/Http/Controllers/Platform/CorporationController.php`
- `app/Http/Controllers/Platform/PlanAssignmentController.php`
- `app/Http/Middleware/HandleInertiaRequests.php`
- `app/Http/Middleware/RequireModule.php`
- `app/Jobs/Billing/MarkInvoicesOverdueJob.php`
- `app/Jobs/Billing/SuspendOverdueSubscriptionsJob.php`
- `app/Models/Tenant/Corporation.php`
- `app/Models/Tenant/CorporationSubscription.php`
- `app/Models/Tenant/Venue.php`
- `app/Models/User.php`
- `app/Services/Billing/BillingStatusService.php`
- `app/Services/Platform/MetricsService.php`
- `bootstrap/app.php`
- `database/factories/Tenant/CorporationFactory.php`
- `database/factories/Tenant/VenueFactory.php`
- `database/seeders/DatabaseSeeder.php`
- `docs/module-subscription-architecture.md`
- `resources/js/Pages/Platform/Corporations/Edit.vue`
- `resources/js/Pages/Platform/Corporations/Index.vue`
- `routes/console.php`
- `routes/web.php`
- `tests/TestCase.php`

## Padrões Aplicados
- **Repository / Service Layer:** `BillingStatusService` centraliza regras de bloqueio financeiro.
- **Middleware Pipeline:** `RequireModule` compõe verificação de tenant, billing, `CorporationModule` e `VenueModule` ativos.
- **Cache Invalidation Pattern:** `VenueModuleCache` é invalidado em todas as actions de ativação/desativação de módulos.
- **State Machine:** jobs diários executam transições determinísticas de `SubscriptionStatus`.
- **Soft Deletes em Faturas:** `VenueInvoice` e `CorporationInvoice` usam `SoftDeletes`.
- **Validação Explícita:** `ActivateVenueModuleAction` valida `ModuleCode` e presença do módulo no plano da corporation.
- **Atomicidade:** `AssignPlanToCorporationAction` e `CreateCorporationAction` operam dentro de transações de banco.

## Decisões Arquiteturais Seguidas
- Reutilização do `PlanCatalog` existente como pacote base por venue.
- Remoção de campos legacy de `corporations`.
- Cardápio (`menu`) como módulo base default em toda venue.
- Cache de módulos ativos por venue via Redis.
- Aplicação antecipada de `module:` nas rotas operacionais na Fase 2.
- Integração com Asaas adiada para fase futura; schema e jobs preparados.
- Criação de owner via `User::create` direto na `CreateCorporationAction`, sem disparar `CreateUserOwnerDefinitions`, evitando duplicação de corporation/venue.
- `CorporationController` expõe `subscription.planCatalog` para o frontend, removendo dependência de campos legacy.
- `BillingStatusService` considera `ended_at` tanto em modo unificado quanto per-venue.
- `AssignPlanToCorporationAction` cria `VenueSubscription` para venues sem subscription quando um plano é atribuído.

## Overrides do Desenvolvedor
- Nenhum override registrado nesta execução.

## Bloqueadores Encontrados
- **Ordem das migrations:** `affiliate_codes` e `corporation_invoices` precisavam ser criados antes das tabelas que referenciam suas foreign keys. Resolvido renomeando timestamps.
- **Migration duplicada:** `add_affiliate_code_id_to_subscriptions_and_invoices_table` duplicava colunas já presentes nas criações das tabelas. Resolvido removendo-a.
- **`venue_subscription_id` not null:** impedia faturas de agrupamento sem subscription vinculada. Resolvido tornando nullable.
- **`CreateUserOwnerDefinitions.php` truncado:** arquivo havia ficado incompleto durante edições anteriores. Resolvido restaurando do git e reaplicando mudanças do novo modelo.
- **Testes existentes quebrados por `module:menu`:** `TestCase::loginAs` foi atualizado para garantir subscription ativa, `CorporationModule` e `VenueModule` `menu` default, refletindo o estado real de uma venue.
- **Regressão em `CreateCorporationAction`:** uso de `CreateNewUser` disparava `CreateUserOwnerDefinitions`, criando corporation/venue duplicadas. Resolvido criando owner manualmente e vinculando `owner_id`.
- **Campos legacy no frontend e testes:** `plan_catalog_id`, `subscription_value`, `plan_start_date`, `plan_end_date` foram removidos de `corporations`. Frontend e testes antigos foram atualizados para usar `subscription`.
- **Cache de módulos não invalidado:** `VenueModuleCache::forget()` passou a ser chamado nas actions de ativação/desativação.
- **Testes com `--env=testing`:** o flag `--env=testing` carregava `.env` com `APP_ENV=local`, fazendo CSRF falhar. Suite deve ser executada sem esse flag (phpunit.xml já define `APP_ENV=testing`).

## Orientações para Code Review
- Validar se a ordem das migrations não quebra em ambientes já existentes (como não há produção, `db:migrate-all --fresh --seed` é aceitável).
- Revisar `BillingStatusService::isBlocked` para garantir que `past_due` nunca bloqueia, que `ended_at` é considerado e que `unified`/`per_venue` estão corretos.
- Verificar se `module:` foi aplicado em todas as rotas do apêndice do tech-plan e se `/kitchen/monitor` permanece público conforme necessidade de negócio.
- Confirmar que `CreateUserOwnerDefinitions`, `CreateVenueAction` e `CreateCorporationAction` criam subscription + módulo `menu` consistentemente.
- Validar que `VenueModuleCache` é invalidado em todas as actions de ativação/desativação de módulos.
- Revisar `ActivateVenueModuleAction` para garantir que a validação de `CorporationModule` não impede fluxos legítimos de ativação.
- Rodar suite completa de testes com `vendor/bin/sail artisan test` (sem `--env=testing`).
- Atenção: `RefreshAllDatabases` pode deixar resíduos entre testes quando operações escapam da transação (ex: jobs sync). O MetricsTest foi tornado robusto a isso, mas um refactor futuro do trait pode ser necessário.
