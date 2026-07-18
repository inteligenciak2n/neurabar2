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
- [x] Rodar `vendor/bin/sail bin pint --dirty --format agent` com sucesso.

## Cobertura de Testes
- **Cobertura final:** foco em feature tests das Fases 1 e 2.
- **Testes criados:** 16 testes de feature (nenhum unitário adicional necessário).
  - `tests/Feature/Billing/BillingStatusServiceTest.php`
  - `tests/Feature/Billing/Jobs/ExpireTrialsJobTest.php`
  - `tests/Feature/Billing/Jobs/SuspendOverdueSubscriptionsJobTest.php`
  - `tests/Feature/Billing/Jobs/MarkInvoicesOverdueJobTest.php`
  - `tests/Feature/Module/RequireModuleMiddlewareTest.php`
  - `tests/Feature/Module/ModuleActivationActionsTest.php`
- **Resultado:** 16 passed (22 assertions).

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
- `app/Actions/Corporation/CreateVenueAction.php`
- `app/Actions/Fortify/CreateUserOwnerDefinitions.php`
- `app/Actions/Platform/AssignPlanToCorporationAction.php`
- `app/Actions/Platform/CreateCorporationAction.php`
- `app/Http/Controllers/Platform/PlanAssignmentController.php`
- `app/Http/Middleware/HandleInertiaRequests.php`
- `app/Models/Tenant/Corporation.php`
- `app/Models/Tenant/Venue.php`
- `app/Models/User.php`
- `bootstrap/app.php`
- `database/factories/Tenant/CorporationFactory.php`
- `database/factories/Tenant/VenueFactory.php`
- `database/seeders/DatabaseSeeder.php`
- `docs/module-subscription-architecture.md`
- `routes/console.php`
- `routes/web.php`
- `tests/TestCase.php`

## Padrões Aplicados
- **Repository / Service Layer:** `BillingStatusService` centraliza regras de bloqueio financeiro.
- **Middleware Pipeline:** `RequireModule` compõe verificação de tenant, billing e módulo ativo.
- **Cache Invalidation Pattern:** `VenueModuleCache` com chave por venue (invalidação a ser acoplada nas actions na próxima fase).
- **State Machine:** jobs diários executam transições determinísticas de `SubscriptionStatus`.
- **Soft Deletes em Faturas:** `VenueInvoice` e `CorporationInvoice` usam `SoftDeletes`.

## Decisões Arquiteturais Seguidas
- Reutilização do `PlanCatalog` existente como pacote base por venue.
- Remoção de campos legacy de `corporations`.
- Cardápio (`menu`) como módulo base default em toda venue.
- Cache de módulos ativos por venue via Redis.
- Aplicação antecipada de `module:` nas rotas operacionais na Fase 2.
- Integração com Asaas adiada para fase futura; schema e jobs preparados.

## Overrides do Desenvolvedor
- Nenhum override registrado nesta execução.

## Bloqueadores Encontrados
- **Ordem das migrations:** `affiliate_codes` e `corporation_invoices` precisavam ser criados antes das tabelas que referenciam suas foreign keys. Resolvido renomeando timestamps.
- **Migration duplicada:** `add_affiliate_code_id_to_subscriptions_and_invoices_table` duplicava colunas já presentes nas criações das tabelas. Resolvido removendo-a.
- **`venue_subscription_id` not null:** impedia faturas de agrupamento sem subscription vinculada. Resolvido tornando nullable.
- **`CreateUserOwnerDefinitions.php` truncado:** arquivo havia ficado incompleto durante edições anteriores. Resolvido restaurando do git e reaplicando mudanças do novo modelo.
- **Testes existentes quebrados por `module:menu`:** `TestCase::loginAs` foi atualizado para garantir subscription ativa e módulo `menu` default, refletindo o estado real de uma venue.

## Orientações para Code Review
- Validar se a ordem das migrations não quebra em ambientes já existentes (como não há produção, `db:migrate-all --fresh --seed` é aceitável).
- Revisar `BillingStatusService::isBlocked` para garantir que `past_due` nunca bloqueia e que `unified`/`per_venue` estão corretos.
- Verificar se `module:` foi aplicado em todas as rotas do apêndice do tech-plan.
- Confirmar que `CreateUserOwnerDefinitions`, `CreateVenueAction` e `CreateCorporationAction` criam subscription + módulo `menu` consistentemente.
- Checar se `VenueModuleCache` precisa de invalidação explícita nas actions de ativação/desativação na Fase 3.
- Rodar suite completa de testes e investigar falhas pré-existentes em `Support/TicketTest`, `Settings/VenueTest`, `Settings/VenueSettingsTest` e `Settings/InviteUserTest`, que parecem independentes das Fases 1 e 2.
