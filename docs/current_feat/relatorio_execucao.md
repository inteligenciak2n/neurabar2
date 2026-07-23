# Relatório de Execução – Correções da Revisão `feat/sistema-modulos-assinaturas`

## Metadados
- **Data de execução:** 2026-07-20
- **Tech-plan de referência:** Fases 1–5 de módulos, subscriptions e billing (`tech-plan_fase_1_e_2.md`, `tech-plan_fase_3_4_5.md`)
- **Revisão de referência:** `revisao_branch.md` (17 findings: 3 CRITICAL, 11 WARNING, 3 DEBT)
- **Desenvolvedor:** Rodrigo

## Tarefas Concluídas
- [x] Alinhar `User::canAccessModule()` com a lógica do middleware `RequireModule` (corporation module + dependências).
- [x] Implementar listeners de uso medido para `ItemStatusUpdated` (`RecordKdsUsage`) e `GuestSignaled` (`RecordSignalUsage`).
- [x] Remover `Kds` do listener `RecordOrderModuleUsage` (agora contabilizado no despacho de item).
- [x] Adicionar autorização `view-invoice` no `InvoiceController::show` (super_admin/finance/read_only).
- [x] Garantir vinculação `corporation_invoice_id` em faturas unified (teste de cobertura).
- [x] Otimizar `GenerateInvoicesJob` com `cursor()` para reduzir consumo de memória.
- [x] Adicionar cache de 60s em `BillingStatusService::isBlocked()` com invalidação nos jobs de suspensão e expiração de trial.
- [x] Implementar `RecalculateSubscriptionJob` previsto no tech-plan.
- [x] Adicionar teste para `NotifyTrialEndingSoonJob`.
- [x] Extrair criação de defaults da venue para `CreateVenueDefaultsAction`, eliminando duplicação e inconsistência de idiomas entre `CreateVenueAction` e `CreateUserOwnerDefinitions`.
- [x] Atualizar factories de faturas para usar `InvoiceStatus::Open`.
- [x] Invalidar `VenueModuleCache` ao desabilitar módulo corporativo (`DisableCorporateModuleAction`).

## Cobertura de Testes
- **Resultado da suite completa:** 345 passed, 7 skipped, 982 assertions.
- **Pint:** passed.
- **Testes criados/atualizados:**
  - `tests/Feature/Module/UserCanAccessModuleTest.php` (novo, 6 testes)
  - `tests/Feature/Billing/Listeners/RecordKdsUsageTest.php` (novo, 3 testes)
  - `tests/Feature/Billing/Listeners/RecordSignalUsageTest.php` (novo, 2 testes)
  - `tests/Feature/Billing/Listeners/RecordOrderModuleUsageTest.php` (atualizado)
  - `tests/Feature/Platform/InvoiceControllerTest.php` (atualizado, 3 testes de autorização)
  - `tests/Feature/Billing/BillingStatusServiceTest.php` (atualizado, teste de cache)
  - `tests/Feature/Billing/Jobs/NotifyTrialEndingSoonJobTest.php` (novo, 3 testes)
  - `tests/Feature/Billing/Jobs/RecalculateSubscriptionJobTest.php` (novo, 2 testes)
  - `tests/Feature/Billing/Jobs/GenerateInvoicesJobTest.php` (atualizado, teste de vinculação unified)

## Arquivos Modificados
### Backend
- `app/Models/User.php`
- `app/Providers/AppServiceProvider.php`
- `app/Http/Controllers/Platform/InvoiceController.php`
- `app/Jobs/Billing/GenerateInvoicesJob.php`
- `app/Jobs/Billing/RecalculateSubscriptionJob.php` (novo)
- `app/Jobs/Billing/SuspendOverdueSubscriptionsJob.php`
- `app/Jobs/Billing/ExpireTrialsJob.php`
- `app/Services/Billing/BillingStatusService.php`
- `app/Actions/Platform/DisableCorporateModuleAction.php`
- `app/Actions/Corporation/CreateVenueAction.php`
- `app/Actions/Corporation/CreateVenueDefaultsAction.php` (novo)
- `app/Actions/Fortify/CreateUserOwnerDefinitions.php`
- `app/Listeners/Billing/RecordOrderModuleUsage.php`
- `app/Listeners/Billing/RecordKdsUsage.php` (novo)
- `app/Listeners/Billing/RecordSignalUsage.php` (novo)

### Factories
- `database/factories/Tenant/CorporationInvoiceFactory.php`
- `database/factories/Tenant/VenueInvoiceFactory.php`

### Tests
- `tests/Feature/Module/UserCanAccessModuleTest.php` (novo)
- `tests/Feature/Billing/Listeners/RecordKdsUsageTest.php` (novo)
- `tests/Feature/Billing/Listeners/RecordSignalUsageTest.php` (novo)
- `tests/Feature/Billing/Listeners/RecordOrderModuleUsageTest.php`
- `tests/Feature/Billing/BillingStatusServiceTest.php`
- `tests/Feature/Billing/Jobs/GenerateInvoicesJobTest.php`
- `tests/Feature/Billing/Jobs/NotifyTrialEndingSoonJobTest.php` (novo)
- `tests/Feature/Billing/Jobs/RecalculateSubscriptionJobTest.php` (novo)
- `tests/Feature/Platform/InvoiceControllerTest.php`

## Padrões Aplicados
- **TDD:** todos os comportamentos novos foram escritos com teste falhando antes da implementação.
- **Event-Driven + Jobs Async:** listeners síncronos disparam jobs de registro de uso (`RecordModuleUsageJob`).
- **Idempotent Writes:** `RecordModuleUsageJob` mantém `updateOrCreate` por `(venue_id, module_code, period)`.
- **Cache Invalidation Pattern:** `VenueModuleCache::forget` e `BillingStatusService::flushBlockedCache` nas transições relevantes.
- **Service Layer:** `BillingStatusService` centraliza regra de bloqueio com cache.
- **Gate/Policy:** autorização de faturas via Gate `view-invoice`.

## Decisões Arquiteturais Seguidas
- Não implementar proration (cobrança de mês inteiro para ativações/desativações no meio do ciclo).
- Uso de `updateOrCreate` com `is_finalized => false` para preservar faturas finalizadas.
- Modo `unified` agrega valores em `CorporationInvoice` e vincula `VenueInvoice` via `corporation_invoice_id`.
- Grace period corporativo derivado de `CorporationSubscription.grace_period_days` também para `VenueSubscription`.

## Overrides do Desenvolvedor
- Nenhum.

## Bloqueadores Encontrados
- Nenhum bloqueador técnico. Ajuste rápido: import de `KitchenStation` precisou ser mantido em `CreateUserOwnerDefinitions` devido ao uso em `createProductCategories`.

## Itens de Dívida Técnica Não Resolvidos
- `SubscriptionCalculator` ainda concentra cálculo, atualização de `VenueSubscription` e uso medido. Recomenda-se separar em `SubscriptionCalculator` (cálculo puro), `VenueSubscriptionUpdater` e `UsageRecordCalculator` em refactoring futuro.
- Fallback hardcoded de plano "pro" em `CreateVenueAction` permanece. Recomenda-se garantir subscription válida antes da criação de venue ou tornar o fallback explícito via configuração/plano default.

## Orientações para Code Review
- Validar se a Gate `view-invoice` cobre todos os perfis de backoffice desejados (atualmente `super_admin`, `finance`, `read_only`; `registration` está excluído).
- Revisar se a invalidação de cache de bloqueio cobre todos os pontos de mudança de status (foram cobertos suspensão e expiração de trial; reativações manuais futuras devem chamar `flushBlockedCache`).
- Confirmar se o listener `RecordSignalUsage` contabilizar `DirectWaiter` e `VoiceCommand` no mesmo evento está alinhado com as regras de negócio de cobrança.
- Verificar se a extração `CreateVenueDefaultsAction` não alterou dados default de venues criadas via registro de usuário (mantidos `cover_charge=10`, `service_fee_percent=10`, `table_count=30`).
- Rodar suite completa antes do merge: `vendor/bin/sail artisan test --compact`.
- Rodar Pint antes do merge: `vendor/bin/sail bin pint --dirty --format agent`.
