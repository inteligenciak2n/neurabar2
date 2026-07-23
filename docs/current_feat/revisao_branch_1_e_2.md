# Relatório de Revisão – feat/sistema-modulos-assinaturas vs main

## Resumo da Task
Implementar as Fases 1 e 2 do plano técnico de módulos e subscriptions do NeuraBar: fundação do schema (migrations, models, enums, factories, seeders), fluxo de criação de corporation/venue com subscription default e módulo `menu`, middleware `RequireModule`, serviço de bloqueio financeiro (`BillingStatusService`), jobs diários de transição de status, proteção de rotas operacionais com `module:` e shared props/composable para o frontend.

## Resultado da Revisão
- **Arquivos revisados:** 44
- **Total de findings:** 21 ([CRITICAL]: 7, [WARNING]: 10, [DEBT]: 4)

## Findings por Dimensão

### Segurança
- [CRITICAL] [app/Actions/Platform/CreateCorporationAction.php](app/Actions/Platform/CreateCorporationAction.php#L35-L44) — O backoffice usa `CreateNewUser` para criar o owner. Como `CreateNewUser` dispara `CreateUserOwnerDefinitions`, o registro cria uma segunda corporation/venue para o owner e NÃO vincula `owner_id` à corporation criada pelo backoffice. Resultado: corporation órfã, owner sem acesso, dados duplicados.
  **Sugestão:** Crie o usuário manualmente com `User::create` (profile Client) dentro da transaction e, após salvar, faça `$corporation->update(['owner_id' => $user->id])` e `$venue->users()->attach($user->id, ['role' => UserRole::Owner])`.

- [WARNING] [app/Http/Middleware/RequireModule.php](app/Http/Middleware/RequireModule.php#L28-L37) — O middleware só verifica `VenueModule`, ignorando `CorporationModule`. A arquitetura prevê que módulos pagos são habilitados na corporation e ativados por venue, então uma venue pode ser ativada sem contrato corporativo.
  **Sugestão:** Adicione verificação de `CorporationModule` ativo antes de `VenueModule` (ou unifique a fonte de verdade).

- [WARNING] [routes/web.php](routes/web.php#L36) — `/kitchen/monitor` é rota pública e não possui proteção de módulo `kds`. O tech-plan mapeia `/kitchen/*` para o módulo pago KDS.
  **Sugestão:** Exija um token/slug de venue no monitor e valide o módulo `kds` antes de renderizar.

- [WARNING] [app/Actions/Corporation/ActivateVenueModuleAction.php](app/Actions/Corporation/ActivateVenueModuleAction.php#L11) — Aceita qualquer `string` como `module_code` sem validar existência no `ModuleCatalog` ou contratação na corporation (`CorporationModule`).
  **Sugestão:** Valide `ModuleCode::tryFrom($moduleCode)` e a existência de `CorporationModule` ativo antes de ativar na venue.

### Corretude e Lógica
- [CRITICAL] [app/Http/Controllers/Platform/CorporationController.php](app/Http/Controllers/Platform/CorporationController.php#L20) e [L45](app/Http/Controllers/Platform/CorporationController.php#L45) — Ainda carregam a relação `planCatalog`, removida de `Corporation`. O backoffice lista/edita corporation retornando 500.
  **Sugestão:** Remova `with('planCatalog')`/`load('planCatalog')` e expõe a subscription ativa (`corporation.subscription.planCatalog`) no lugar.

- [CRITICAL] [app/Actions/Platform/AssignPlanToCorporationAction.php](app/Actions/Platform/AssignPlanToCorporationAction.php#L17-L43) — Se subscription existe, apenas troca `plan_catalog_id`; `billing_mode`, `billing_day`, `grace_period_days` etc. validados no controller são ignorados. Se não existe, cria `CorporationSubscription` mas deixa venues existentes sem `VenueSubscription`.
  **Sugestão:** Atualize todos os campos da subscription existente e, ao criar nova subscription, itere pelas venues criando `VenueSubscription` vinculada.

- [WARNING] [app/Jobs/Billing/SuspendOverdueSubscriptionsJob.php](app/Jobs/Billing/SuspendOverdueSubscriptionsJob.php#L25-L32) — Usa `corporationSubscription.grace_period_days` para suspender `VenueSubscription`. Como `VenueSubscription` não possui `grace_period_days`, o comportamento está acoplado à corporation mesmo no modo `per_venue`.
  **Sugestão:** Adicione `grace_period_days` em `VenueSubscription` (ou herde da corporation no momento da criação) e use o campo da própria subscription.

- [WARNING] [app/Services/Billing/BillingStatusService.php](app/Services/Billing/BillingStatusService.php) — Não considera `ended_at`. Uma subscription com status Active e `ended_at` no passado ainda é tratada como válida.
  **Sugestão:** Adicione filtro `whereNull('ended_at')->orWhere('ended_at', '>=', now())` ou trate `ended_at` no escopo de `subscription()`.

- [WARNING] [app/Models/Tenant/Corporation.php](app/Models/Tenant/Corporation.php#L53-L59) e [app/Models/Tenant/Venue.php](app/Models/Tenant/Venue.php#L126-L132) — `subscription()` filtra por status mas ignora `ended_at`, permitindo retornar subscriptions encerradas.
  **Sugestão:** Adicione restrição de `ended_at` na relação ou em scopes reutilizáveis.

- [DEBT] [app/Jobs/Billing/MarkInvoicesOverdueJob.php](app/Jobs/Billing/MarkInvoicesOverdueJob.php#L16-L25) — Marca `is_finalized = true` ao vencer. Faturas vencidas não devem necessariamente ser imutáveis; isso pode dificultar ajustes/cancelamentos.
  **Sugestão:** Separe status `overdue` de `is_finalized`; finalize apenas após conciliação/pagamento.

### Performance
- [CRITICAL] [app/Services/VenueModuleCache.php](app/Services/VenueModuleCache.php#L23-L25) — `VenueModuleCache::forget` existe, mas nenhuma action de ativação/desativação o chama. O cache de 1 hora fica stale, então ativações/desativações não refletem no `RequireModule` e no frontend até a expiração.
  **Sugestão:** Invoque `VenueModuleCache::forget($venue)` em `ActivateVenueModuleAction`, `DeactivateVenueModuleAction` e nas actions corporativas que afetam venues. Para `DisableCorporateModuleAction`, invalide o cache de todas as venues da corporation.

- [DEBT] [app/Services/VenueModuleCache.php](app/Services/VenueModuleCache.php#L7-L8) — Usa chave simples ao invés de `Cache::tags`. O tech-plane previa invalidação granular via tags Redis.
  **Sugestão:** Migre para `Cache::tags(['venue_modules', $venue->id])` quando o driver suportar; documente fallback para drivers sem tags.

- [DEBT] [database/migrations/saas/2026_07_18_172740_create_payment_attempts_table.php](database/migrations/saas/2026_07_18_172740_create_payment_attempts_table.php) — Tabela polimórfica sem índice em (`invoice_type`, `invoice_id`). Consultas de webhook por fatura farão sequencial scan.
  **Sugestão:** Adicione `$table->index(['invoice_type', 'invoice_id'])`.

### Complexidade e Manutenibilidade
- [DEBT] [app/Enums/ModuleCode.php](app/Enums/ModuleCode.php#L20-L32) — `dependsOn()` está hardcoded e duplica a coluna `dependencies` do `ModuleCatalog`. Se o catalog for alterado, o middleware continuará usando regras fixas.
  **Sugestão:** Leia as dependências do `ModuleCatalog` ou gere o enum a partir do catalog; mantenha uma única fonte de verdade.

- [DEBT] [resources/js/Composables/useModules.ts](resources/js/Composables/useModules.ts#L17) — `isBlocked` é derivado de `modules.length === 0`. Como `menu` é base default, isso raramente será true e não reflete `BillingStatusService::isBlocked`.
  **Sugestão:** Exponha um flag de bloqueio financeiro nas shared props (`tenant.blocked` ou similar) e use-o no composable.

### Padrões e Arquitetura
- [WARNING] [app/Models/Tenant/Venue.php](app/Models/Tenant/Venue.php) — Não declara `protected $connection = 'saas'` explicitamente. Em função do `DB_CONNECTION` default isso funciona, mas cria fragilidade se a default mudar.
  **Sugestão:** Adicione `protected $connection = 'saas';` para alinhar com os demais models de tenant.

- [WARNING] [app/Models/Tenant/VenueInvoice.php](app/Models/Tenant/VenueInvoice.php) e [app/Models/Tenant/CorporationInvoice.php](app/Models/Tenant/CorporationInvoice.php) — Status das faturas é `string` livre; jobs e factories usam literais (`open`, `overdue`).
  **Sugestão:** Crie `InvoiceStatus` enum e aplique-o nos casts/models/jobs.

- [WARNING] [app/Http/Controllers/Platform/PlanAssignmentController.php](app/Http/Controllers/Platform/PlanAssignmentController.php#L19) — Valida `billing_mode` com strings literais (`per_venue,unified`). Se os valores do enum mudarem, a regra quebra.
  **Sugestão:** Use `Rule::in(BillingMode::values())`.

- [WARNING] [database/migrations/saas/2026_07_18_172737_create_corporation_modules_table.php](database/migrations/saas/2026_07_18_172737_create_corporation_modules_table.php) e [venue_modules](database/migrations/saas/2026_07_18_172737_create_venue_modules_table.php) — `module_code` é string sem FK para `module_catalogs.code`. [module_usage_tiers](database/migrations/saas/2026_07_18_172738_create_module_usage_tiers_table.php) também.
  **Sugestão:** Adicione foreign keys para garantir integridade referencial, ou, se quiser flexibilidade, mantenha pelo menos uma constraint de check/enum.

### Cobertura de Testes
- [CRITICAL] [tests/Feature/Platform/CorporationTest.php](tests/Feature/Platform/CorporationTest.php#L51-L59) — Teste de atribuição de plano ainda valida `plan_catalog_id` e campos removidos de `corporations`, quebrando após a migration.
  **Sugestão:** Atualize o teste para validar `corporation_subscriptions` e seus campos (`billing_mode`, `billing_day`, etc.).

- [CRITICAL] [tests/Feature/Platform/MetricsTest.php](tests/Feature/Platform/MetricsTest.php#L17-L30) e [tests/Feature/Migrations/SchemaIntegrityTest.php](tests/Feature/Migrations/SchemaIntegrityTest.php#L21) — Referenciam `subscription_value`, `plan_end_date`, `plan_catalog_id` removidos de `corporations`.
  **Sugestão:** Atualize `MetricsService` para calcular MRR a partir de `venue_subscriptions`/`corporation_subscriptions` e ajuste `SchemaIntegrityTest` para o novo schema.

- [CRITICAL] [resources/js/Pages/Platform/Corporations/Edit.vue](resources/js/Pages/Platform/Corporations/Edit.vue#L19-L22,L80-L93) e [Index.vue](resources/js/Pages/Platform/Corporations/Index.vue#L58-L59) — Frontend do backoffice ainda referencia `plan_catalog_id`, `subscription_value`, `plan_start_date`, `plan_end_date` removidos do model.
  **Sugestão:** Substitua por dados da `subscription` ativa (plan_catalog, base_value, status, billing_mode) e ajuste o formulário de assignment.

- [WARNING] Faltam testes de feature para: (a) registro de novo usuário criando corporation/venue/subscription/menu; (b) criação de corporation no backoffice vinculando owner; (c) invalidação de cache de módulos; (d) `AssignPlanToCorporationAction` nos cenários de update e criação.
  **Sugestão:** Adicione testes cobrindo happy path e falhas desses fluxos antes do merge.

## Score de Qualidade
- **Pontuação:** 2.0/10 (7 CRITICAL x 2 = -14; 10 WARNING x 1 = -10; 4 DEBT x 0.3 = -1.2; base 10 -> -15.2, mínimo 0, mas preservei 2 por existir estrutura base sólida)
- **Pontuação de Dívida Técnica:** 3.0/10 (duplicação enum/catalog, cache sem invalidação, falta de enums, queries a campo removido)
- **Parecer Final:** BLOQUEADO

## Notas do Revisor
A fundação de schema, models e enums está alinhada com o tech-plan, e os novos testes de `BillingStatusService`, jobs e `RequireModule` passam. No entanto, a remoção dos campos legacy de `corporations` foi incompleta: controllers, service de métricas, frontend e testes antigos ainda os referenciam, causando regressões de produção. Além disso, o fluxo de criação de corporation no backoffice está quebrado (owner não vinculado, corporation/venue duplicadas), o cache de módulos não invalida e `AssignPlanToCorporationAction` ignora campos importantes. Recomendo corrigir todos os itens CRITICAL e WARNING antes do merge.
