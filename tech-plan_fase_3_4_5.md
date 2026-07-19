# Plano Técnico – Módulos e Subscriptions (Fases 3, 4 e 5)

## Metadados
- **Task:** Implementar Fases 3, 4 e 5 do modelo de módulos contratáveis, subscriptions e billing do NeuraBar, conforme `docs/module-subscription-architecture.md`.
- **Tech Lead:** Rodrigo (desenvolvedor conduzindo a decisão)
- **Data de criação:** 19 de julho de 2026
- **Serviços impactados:** Aplicação Laravel SaaS (banco `saas` + bancos operacionais), backoffice `/backoffice`, rotas operacionais, filas de jobs, frontend Vue/Inertia.

## Resumo do Contexto
As Fases 1 e 2 entregaram a fundação do schema, permissionamento por módulo, status financeiro e jobs diários de transição de status. As Fases 3, 4 e 5 devem entregar: (3) cálculo de subscription, geração de faturas e notificações; (4) backoffice comercial para gestão de módulos e faturas; (5) scaffolds dos módulos operacionais restantes, registro de uso medido e modularização das rotas.

## Decisões Arquiteturais

### 1. Cálculo de Subscription e Faturamento (Fase 3)
- **Decisão:** Implementar `SubscriptionCalculator` centralizado para calcular `base_value`, `modules_value`, `metered_value` e `total_value` por venue, e fatura unificada por corporation.
- **Opções consideradas:**
  1. **Cálculo síncrono dentro das actions de ativação/desativação:** simples, mas acopla regras de preço a lógica de negócio. Contra: dificulta recálculos futuros e geração de fatura.
  2. **Service dedicado `SubscriptionCalculator` chamado por actions e jobs:** separa responsabilidades e permite testes isolados. Contra: exige manter `VenueSubscription` sempre atualizada.
  3. **Cálculo apenas no momento de gerar fatura:** menos writes. Contra: `VenueSubscription.total_value` ficaria desatualizado entre ciclos, quebrando métricas e front.
- **Rationale:** O backoffice e as shared props dependem de `VenueSubscription.total_value` e `CorporationSubscription` não armazena total. Calcular via service dedicado permite reutilização, testes e manutenção do snapshot mensal.
- **Decidido por:** Rodrigo

### 2. Geração de Faturas
- **Decisão:** Criar job mensal `GenerateInvoicesJob` que, para cada `CorporationSubscription` ativa, gera `VenueInvoice` (sempre) e `CorporationInvoice` (somente no modo `unified`).
- **Opções consideradas:**
  1. **Gerar faturas para todas as venues independentemente do modo:** alinhado com o schema (`VenueInvoice` sempre existe). Contra: no modo unificado, as `VenueInvoice` serão agrupadas na `CorporationInvoice`.
  2. **Gerar apenas `CorporationInvoice` no modo unificado:** simplifica, mas perde granularidade por venue no backoffice.
- **Rationale:** O schema prevê `VenueInvoice.venue_subscription_id` nullable justamente para suportar faturas de agrupamento. Manter `VenueInvoice` em ambos os modos permite conciliação por venue e agrupamento quando necessário.
- **Decidido por:** Rodrigo

### 3. Enum de Status de Fatura
- **Decisão:** Criar `InvoiceStatus` enum (`open`, `overdue`, `paid`, `canceled`, `refunded`) e aplicar nos casts de `VenueInvoice` e `CorporationInvoice`.
- **Opções consideradas:**
  1. **Manter strings literais:** não muda schema. Contra: risco de erro de digitação e dificuldade de manutenção.
  2. **Criar enum dedicado:** padroniza estados e permite métodos auxiliares (`isFinalized()`). Contra: pequeno refactor em `MarkInvoicesOverdueJob`.
- **Rationale:** O documento de revisão das Fases 1 e 2 já sinalizou essa dívida. Corrigir agora evita propagação de literais.
- **Decidido por:** Rodrigo

### 4. Proration
- **Decisão:** Não implementar proration na Fase 3. Módulos ativados/desativados no meio do ciclo são cobrados/isentos pelo mês inteiro.
- **Opções consideradas:**
  1. **Implementar proration agora:** maior precisão comercial. Contra: adiciona complexidade em cálculo, faturas e testes; não é exigência atual.
  2. **Manter mês inteiro e documentar:** alinhado com as Fases 1 e 2. Contra: menos justo para ativações no fim do mês.
- **Rationale:** A arquitetura já prevê a possibilidade futura com campos `started_at`/`ended_at`. Estabilizar o fluxo mensal primeiro é mais seguro.
- **Decidido por:** Rodrigo

### 5. Registro de Uso Medido
- **Decisão:** Registrar consumo assíncrono via jobs disparados por listeners de eventos existentes (`OrderPlaced`, `ItemStatusUpdated`, `GuestSignaled`).
- **Opções consideradas:**
  1. **Síncrono no controller/action:** mais simples. Contra: adiciona latência em operações críticas (KDS, Taker).
  2. **Listeners síncronos + jobs internos:** permite event-driven sem bloquear o request. Contra: exige configurar listeners e filas.
  3. **Apenas jobs agendados que contabilizam periodicamente:** menos acoplamento. Contra: perde granularidade e pode duplicar/contar errado.
- **Rationale:** Reutilizar eventos já existentes é o caminho mais limpo. Jobs async garantem que o request não é penalizado. Idempotência via `VenueUsageRecord` com `updateOrCreate` por `(venue_id, module_code, period)` evita duplicatas.
- **Decidido por:** Rodrigo

### 6. Scaffolds dos Módulos Operacionais
- **Decisão:** Implementar apenas scaffolds (controllers vazios/placeholder, rotas protegidas por `module:`, views Vue mínimas) para os 7 módulos pagos restantes.
- **Opções consideradas:**
  1. **Implementar funcionalidades completas:** entrega real. Contra: escopo muito grande, ultrapassa a task.
  2. **Apenas seeders no catálogo e proteção de rotas futuras:** mínimo. Contra: não demonstra o permissionamento funcionando.
  3. **Scaffolds com rotas, controllers e menu lateral:** equilíbrio entre entrega e esforço.
- **Rationale:** O objetivo desta fase é validar a arquitetura de módulos. Funcionalidades reais serão construídas em rodada separada.
- **Decidido por:** Rodrigo

### 7. Modularização das Rotas
- **Decisão:** Quebrar `routes/web.php` em arquivos por módulo (`routes/web/menu.php`, `routes/web/kitchen.php`, etc.) e incluí-los via `require_once`.
- **Opções consideradas:**
  1. **Manter tudo em `web.php`:** funciona, mas escala mal. Contra: dificulta manutenção e revisão.
  2. **Agrupar por domínio em arquivos separados:** melhora organização. Contra: requer cuidado com namespaces e middlewares.
- **Rationale:** A aplicação está crescendo em módulos. Agrupar rotas por domínio alinha a estrutura de controllers e facilita futuras mudanças.
- **Decidido por:** Rodrigo

### 8. Notificações por Email
- **Decisão:** Implementar notificações para: trial prestes a expirar, trial expirado/past_due, suspensão, fatura gerada e fatura vencida.
- **Opções consideradas:**
  1. **Implementar agora:** melhora experiência do cliente e operação. Contra: aumenta escopo da Fase 3.
  2. **Adiar para integração com Asaas:** menos trabalho agora. Contra: deixa comunicação comercial ausente.
- **Rationale:** As transições de status já ocorrem via jobs; notificar é baixo esforço e alto valor. Usar Laravel Mailables + queue preserva desempenho.
- **Decidido por:** Rodrigo

### 9. Validação de `CorporationModule`
- **Decisão:** `EnableCorporateModuleAction` deve validar `ModuleCode` e existência ativa no `ModuleCatalog` antes de criar/atualizar `CorporationModule`.
- **Opções consideradas:**
  1. **Manter como está (aceita string livre):** funciona para testes. Contra: risco de dados inválidos e módulos fantasmas.
  2. **Validar contra `ModuleCode` e `ModuleCatalog`:** garante integridade.
- **Rationale:** A revisão das Fases 1 e 2 já apontou essa falha. Corrigir na Fase 4 evita inconsistência entre catálogo e contratação.
- **Decidido por:** Rodrigo

### 10. Status de Bloqueio no Frontend
- **Decisão:** Substituir `isBlocked` de `useModules.ts` por um flag real vindo das shared props (`tenant.blocked` derivado de `BillingStatusService::isBlocked`).
- **Opções consideradas:**
  1. **Manter heurística `modules.length === 0`:** simples. Contra: incorreta (`menu` sempre ativo) e não reflete status financeiro.
  2. **Expor flag real:** preciso.
- **Rationale:** A heurística atual é tecnicamente incorreta. Expor o status real permite mensagens e redirecionamentos adequados no frontend.
- **Decidido por:** Rodrigo

## Padrões Aplicados

### Repository / Service Layer
- **Padrão:** `SubscriptionCalculator`, `BillingStatusService`.
- **Aplica-se a:** Cálculo de valores, geração de faturas, verificação de bloqueio.
- **Justificativa:** Lógica financeira transversal a controllers, middleware e jobs. Isolar em services facilita testes e evita duplicação.

### State Machine
- **Padrão:** Transições determinísticas de `SubscriptionStatus` e `InvoiceStatus`.
- **Aplica-se a:** Jobs diários (`ExpireTrialsJob`, `SuspendOverdueSubscriptionsJob`, `MarkInvoicesOverdueJob`, `GenerateInvoicesJob`).
- **Justificativa:** Reduz lógica condicional esparsa e torna transições previsíveis e testáveis.

### Event-Driven + Jobs Async
- **Padrão:** Listeners que disparam jobs de registro de uso.
- **Aplica-se a:** `OrderPlaced` → `RecordOrderUsageJob`; `ItemStatusUpdated` → `RecordKdsUsageJob`; `GuestSignaled` → `RecordSignalUsageJob`.
- **Justificativa:** Não penaliza o request crítico e permite retry em fila. Reutiliza eventos existentes.

### Idempotent Writes
- **Padrão:** `updateOrCreate` com chave composta em `VenueUsageRecord`.
- **Aplica-se a:** Registro de volume mensal por venue.
- **Justificativa:** Evita duplicatas em retries de jobs ou eventos repetidos.

### Cache Invalidation Pattern
- **Padrão:** `VenueModuleCache::forget` em actions de ativação/desativação.
- **Aplica-se a:** Atualização de `tenant.modules` nas shared props.
- **Justificativa:** Mantém consistência entre backend e frontend após mudanças comerciais.

## Análise de Impacto

### Serviços afetados
- **Banco `saas`:** possível ajuste em `venue_invoices.status` para usar enum (migration de cast não exige alteração de schema se coluna for string, mas novos estados exigem validação).
- **Models:** `VenueInvoice`, `CorporationInvoice` recebem cast de `InvoiceStatus`; possível ajuste em relações.
- **Services:** novo `SubscriptionCalculator`; extensão de `BillingStatusService` se necessário.
- **Jobs:** `GenerateInvoicesJob`, `RecalculateSubscriptionJob`, `RecordOrderUsageJob`, `RecordKdsUsageJob`, `RecordSignalUsageJob`, notificações.
- **Listeners:** novos listeners para `OrderPlaced`, `ItemStatusUpdated`, `GuestSignaled`.
- **Controllers do backoffice:** gestão de `CorporationModule` e `VenueModule`; listagem de faturas.
- **Controllers operacionais:** scaffolds para 7 módulos novos.
- **Rotas:** modularização de `routes/web.php`.
- **Frontend:** menu lateral condicional; flag `tenant.blocked`; páginas placeholder.
- **Tests:** novos feature tests para billing, faturas, backoffice, uso medido e scaffolds de módulos.

### Breaking changes
- **Sim.** `InvoiceStatus` muda o tipo esperado de `VenueInvoice.status` e `CorporationInvoice.status` de string para enum. `MarkInvoicesOverdueJob` deve ser atualizado.
- **Sim.** Rotas de módulos serão movidas para arquivos separados; aliases e nomes devem ser preservados.
- **Sim.** `tenant.modules` passará a incluir flag `blocked`, exigindo ajuste em `useModules.ts` e possíveis componentes Vue.
- **Não há produção**, então o impacto é restrito ao código e dados de desenvolvimento.

### Migrações necessárias
1. Criar tabela de notificações enviadas (`notification_logs`) se houver necessidade de rastreio (opcional; Laravel `notifications` table já pode ser usada).
2. Seeders de `ModuleUsageTier` para módulos `metered`/`hybrid`.
3. Ajuste de cast de `status` em `VenueInvoice` e `CorporationInvoice` (pode ser feito via casts sem alterar coluna).

### Notas de migração / deploy
- Como não há produção, o deploy seguro envolve:
  1. Rodar `db:migrate-all --fresh --seed` em desenvolvimento.
  2. Atualizar seeders (`ModuleCatalogsSeeder` já existe; adicionar `ModuleUsageTiersSeeder`).
  3. Verificar se todas as rotas mantêm seus nomes após modularização.
  4. Rodar suite completa de testes.

## Riscos

| Risco | Severidade | Mitigação | Status |
|---|---|---|---|
| `SubscriptionCalculator` acoplado a regras de tier mal compreendidas | Média | Validar regras de tier com exemplos; cobrir casos de borda em tests. | Identificado |
| Geração de fatura duplicada se `GenerateInvoicesJob` rodar mais de uma vez no período | Alta | Usar `updateOrCreate` por `(venue_id/corporation_id, period)` ou unique constraint. | Identificado |
| Jobs async de uso medido perderem eventos se listener não for registrado | Média | Registrar listeners em `EventServiceProvider` e testar dispatch. | Identificado |
| Modularização de rotas quebrar named routes ou middlewares | Média | Preservar nomes e agrupamentos; rodar `route:list` após refatoração. | Identificado |
| Notificações por email serem enviadas em ambiente de teste | Média | Usar `Mail::fake()` em tests; configurar driver `log` em `.env.testing`. | Identificado |
| Scaffolds de módulos deixarem rotas acessíveis sem funcionalidade real | Baixa | Adicionar views placeholder e middleware `module:`; documentar como stub. | Identificado |
| `tenant.blocked` exposto no frontend revelar informação sensível | Baixa | O status já é inferido pelo middleware; expor apenas boolean não aumenta risco. | Identificado |

## Orientações para Desenvolvimento (Dev Guidance)

### Ordem de implementação sugerida

#### Fase 3 — Billing e Faturamento Interno
1. Criar `InvoiceStatus` enum e aplicar casts em `VenueInvoice`/`CorporationInvoice`.
2. Atualizar `MarkInvoicesOverdueJob` para usar `InvoiceStatus::Overdue`.
3. Implementar `SubscriptionCalculator`:
   - `calculateVenue(Venue $venue, string $period): array`
   - `calculateModules(Venue $venue): float`
   - `calculateMetered(Venue $venue, string $period): float`
   - `calculateCorporation(Corporation $corporation, string $period): array`
   - Respeitar `is_finalized` para não recalcular faturas fechadas.
4. Implementar `GenerateInvoicesJob`:
   - Iterar `CorporationSubscription` ativas.
   - Calcular cada venue.
   - Criar/atualizar `VenueInvoice`.
   - No modo `unified`, criar/atualizar `CorporationInvoice` vinculada às `VenueInvoice`.
5. Implementar `RecalculateSubscriptionJob` para atualizar `VenueSubscription` fora do ciclo de fatura.
6. Integrar `SubscriptionCalculator` em:
   - `AssignPlanToCorporationAction`
   - `ActivateVenueModuleAction` / `DeactivateVenueModuleAction`
   - `EnableCorporateModuleAction` / `DisableCorporateModuleAction`
7. Criar Mailables e notificações:
   - `TrialEndingSoon`
   - `TrialExpired`
   - `SubscriptionSuspended`
   - `InvoiceGenerated`
   - `InvoiceOverdue`
8. Disparar notificações nos jobs apropriados.
9. Registrar `GenerateInvoicesJob` no scheduler (mensal).
10. Escrever tests:
    - `SubscriptionCalculatorTest`
    - `GenerateInvoicesJobTest`
    - `InvoiceStatusTest`
    - Notificações.

#### Fase 4 — Backoffice Comercial
1. Validar `ModuleCode` e `ModuleCatalog` em `EnableCorporateModuleAction`.
2. Criar controllers do backoffice:
   - `ModuleCatalogController` (já existe parcialmente; expandir)
   - `CorporationModuleController` (habilitar/desabilitar módulo corporate, preço customizado)
   - `VenueModuleController` (ativar/desativar módulo por venue, quantidade)
   - `InvoiceController` (listar `VenueInvoice` e `CorporationInvoice`, ações manuais)
3. Criar rotas no backoffice para os controllers acima.
4. Criar views Vue:
   - `Platform/Modules/Index.vue` (catálogo)
   - `Platform/Corporations/Modules.vue` (gestão corporate)
   - `Platform/Venues/Modules.vue` (gestão por venue)
   - `Platform/Invoices/Index.vue` e `Show.vue`
5. Escrever tests para controllers e actions.

#### Fase 5 — Módulos Operacionais e Consumo de Volume
1. Criar seeders de `ModuleUsageTier` para KDS, Taker, Direct Waiter, Delivery, Direct Print, Fiscal Note, Voice Command.
2. Criar jobs de registro de uso:
   - `RecordOrderUsageJob` (Taker, Delivery)
   - `RecordKdsUsageJob` (KDS)
   - `RecordSignalUsageJob` (Direct Waiter, Voice Command)
   - `RecordPrintUsageJob` (Direct Print)
   - `RecordFiscalNoteUsageJob` (Fiscal Note)
3. Registrar listeners em `EventServiceProvider`:
   - `OrderPlaced` → `RecordOrderUsageJob`
   - `ItemStatusUpdated` (quando status final) → `RecordKdsUsageJob`
   - `GuestSignaled` → `RecordSignalUsageJob`
4. Criar scaffolds dos módulos:
   - Controllers placeholder em `app/Http/Controllers/DirectWaiter`, `Delivery`, `ProductionDashboard`, `FinancialDashboard`, `DirectPrint`, `FiscalNote`, `VoiceCommand`.
   - Rotas em `routes/web/*.php` para cada módulo, protegidas por `module:`.
   - Views Vue placeholder em `resources/js/Pages/*`.
5. Modularizar `routes/web.php`:
   - Extrair grupos existentes (menu, attendances, orders, kitchen, payment, support, settings, corporation) para arquivos separados.
   - Criar `routes/web/dashboard.php`, `routes/web/menu.php`, `routes/web/orders.php`, `routes/web/kitchen.php`, `routes/web/payment.php`, `routes/web/support.php`, `routes/web/settings.php`, `routes/web/corporation.php`.
   - Criar `routes/web/direct_waiter.php`, `routes/web/delivery.php`, etc.
6. Atualizar menu lateral para usar `useModules` e exibir apenas módulos ativos.
7. Adicionar `tenant.blocked` nas shared props via `HandleInertiaRequests`.
8. Atualizar `useModules.ts` para usar `tenant.blocked`.
9. Escrever tests:
   - Registro de uso por evento.
   - Acesso bloqueado/liberado a rotas de módulos scaffolds.
   - Modularização preservando named routes.

### Pontos de atenção específicos do código

- `SuspendOverdueSubscriptionsJob` usa `trial_ends_at + grace_period_days` tanto para `CorporationSubscription` quanto para `VenueSubscription`. Como `VenueSubscription` não tem `grace_period_days`, o job consulta via `corporationSubscription`. Isso é aceitável se a regra de grace period for sempre corporativa, mas deve ser documentado.
- `CreateVenueAction` cria um plano "pro" hardcoded se a corporation não tiver subscription. Isso é um fallback de desenvolvimento; a Fase 4 deve garantir que corporations criadas pelo backoffice recebam plano correto.
- `EnableCorporateModuleAction` aceita `string` livre. A Fase 4 deve adicionar validação.
- `VenueModuleCache` usa chave simples. Se o driver suportar tags no futuro, considerar migrar.
- `MarkInvoicesOverdueJob` atualmente não reseta `is_finalized`. Com `InvoiceStatus`, manter `is_finalized` separado de `status`.
- O frontend `Edit.vue` do backoffice permite editar `plan_catalog_id` como input de texto livre. A Fase 4 deve substituir por select com lista de planos.

### Checklist pré-deploy

- [ ] Todas as migrations novas rodaram com `db:migrate-all --fresh`.
- [ ] Seeders populam `module_catalogs` e `module_usage_tiers`.
- [ ] `SubscriptionCalculator` calcula corretamente por venue e corporation unificada.
- [ ] `GenerateInvoicesJob` cria faturas sem duplicar.
- [ ] `InvoiceStatus` enum aplicado e `MarkInvoicesOverdueJob` atualizado.
- [ ] Notificações são enviadas nos eventos corretos (usar `Mail::fake()` em tests).
- [ ] Backoffice permite habilitar/desabilitar módulos corporate e por venue.
- [ ] Backoffice lista faturas e permite ações manuais.
- [ ] Scaffolds dos 7 módulos restantes possuem rotas protegidas por `module:`.
- [ ] Registro de uso medido é disparado por eventos e persiste em `venue_usage_records`.
- [ ] Rotas modularizadas preservam named routes e middlewares.
- [ ] Menu lateral reflete módulos ativos e status de bloqueio.
- [ ] Testes de feature passam (`vendor/bin/sail artisan test --compact`).
- [ ] Pint rodou sem alterações pendentes (`vendor/bin/sail bin pint --dirty --format agent`).
