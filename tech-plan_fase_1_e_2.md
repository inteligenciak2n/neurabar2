# Plano Tecnico — Módulos e Subscriptions (Fases 1 e 2)

## Metadados
- **Task:** Implementar fundação e permissionamento do modelo de módulos contratáveis, subscriptions e billing no NeuraBar, conforme `docs/module-subscription-architecture.md`.
- **Tech Lead:** Rodrigo (desenvolvedor conduzindo a decisão)
- **Data de criação:** 18 de julho de 2026
- **Serviços impactados:** Aplicação Laravel SaaS (banco `saas` + bancos operacionais), backoffice `/backoffice`, rotas operacionais, cache Redis, filas de jobs.

## Resumo do Contexto
Transformar o NeuraBar de um SaaS com planos fixos (`basic/pro/enterprise`) em um SaaS com módulos ativáveis por venue, subscriptions separadas por corporation/venue, controle de acesso por módulo e status financeiro. As Fases 1 e 2 entregam o schema, models, enums, middleware, jobs de transição de status e aplicação do permissionamento nas rotas existentes.

## Decisoes Arquiteturais

### 1. Reutilização do PlanCatalog existente
- **Decisão:** Manter a tabela `plan_catalogs` existente como pacote base por venue e adaptá-la ao novo modelo.
- **Opções consideradas:**
  1. **Manter `plan_catalogs` como pacote base:** reaproveita dados e migrations existentes. Contra: tabela foi projetada para planos fixos, precisa de ajustes semânticos.
  2. **Criar nova tabela `venue_plan_catalogs`:** modelo mais puro. Contra: duplica informação e exige migração de dados.
- **Rationale:** Não há dados de produção a preservar, mas há código (seeder, backoffice, factories) que depende de `plan_catalogs`. Reutilizar reduz retrabalho e mantém o backoffice funcional.
- **Decidido por:** Rodrigo

### 2. Remoção de campos legacy de `corporations`
- **Decisão:** Remover `plan_catalog_id`, `plan_name`, `subscription_value`, `plan_start_date` e `plan_end_date` da tabela `corporations`.
- **Opções consideradas:**
  1. **Remover na Fase 1:** elimina fonte de verdade duplicada desde o início. Contra: exige atualizar `CreateUserOwnerDefinitions`, `CreateCorporationAction` e factories imediatamente.
  2. **Depreciar e manter sincronizado:** menor impacto imediato. Contra: duplicação de estado e risco de inconsistência ao longo das fases.
- **Rationale:** Como não há produção, é mais barato eliminar a duplicação agora do que corrigir inconsistências depois.
- **Decidido por:** Rodrigo

### 3. Cardápio como módulo base default
- **Decisão:** Criar `VenueModule` com `module_code = menu` e status ativo ao criar qualquer venue.
- **Opções consideradas:**
  1. **Considerar `menu` sempre ativo sem registro em `venue_modules`:** menos dados. Contra: quebra a convenção de que todo módulo ativo existe em `venue_modules` e dificulta relatórios futuros.
  2. **Criar `VenueModule::menu` default:** mantém consistência e permite desativar no futuro se necessário.
- **Rationale:** Consistência de modelo e facilidade de auditoria superam a economia de uma linha.
- **Decidido por:** Rodrigo

### 4. Cache de módulos ativos por venue
- **Decisão:** `Venue::activeModules()` deve usar cache Laravel (`Cache::tags` ou `Cache::remember`) com invalidação nas actions de ativação/desativação.
- **Opções consideradas:**
  1. **Cache com `Cache::tags(['venue_modules', $venueId])`:** permite invalidação granular. Contra: requer driver Redis com suporte a tags.
  2. **Cache com `Cache::remember` por chave simples:** mais simples. Contra: invalidação menos flexível e risco de stale em workers.
  3. **Sem cache, consulta direta ao banco:** mais simples ainda. Contra: `RequireModule` executa em toda requisição operacional.
- **Rationale:** O middleware `RequireModule` será executado em praticamente toda rota operacional. O cache reduz carga no banco `saas` e a latência do request. Como o Redis já é usado pelo `TenantConnectionResolver`, o custo de adoção é baixo.
- **Decidido por:** Rodrigo

### 5. Aplicação de `module:` nas rotas existentes na Fase 2
- **Decisão:** Proteger rotas operacionais existentes com middleware `module:` já na Fase 2.
- **Opções consideradas:**
  1. **Aplicar `module:` já na Fase 2:** garante que funcionalidades pagas só sejam acessíveis quando contratadas. Contra: requer que `menu` esteja ativo por default e que os módulos pagos já existam no `module_catalogs`.
  2. **Adiar para a Fase 5:** menor risco de quebrar funcionalidades existentes durante o desenvolvimento. Contra: deixa funcionalidades pagas desprotegidas por mais tempo.
- **Rationale:** Como os módulos base serão seedados e o `menu` será default, o risco é controlado. A proteção antecipada evita regressão de segurança.
- **Decidido por:** Rodrigo

### 6. Integração com Asaas
- **Decisão:** Preparar schema e jobs para gateway, mas não implementar integração real nas Fases 1 e 2.
- **Opções consideradas:**
  1. **Implementar integração já na Fase 2:** entrega end-to-end mais cedo. Contra: requer credenciais, ambiente de testes do Asaas e adiciona complexidade antes da base estar sólida.
  2. **Adiar integração para fase dedicada:** permite validar status financeiro, jobs e cálculo de fatura antes de acoplar um serviço externo.
- **Rationale:** A arquitetura prevê desacoplamento via `payment_attempts` e webhooks. É mais seguro estabilizar o fluxo de status interno primeiro.
- **Decidido por:** Rodrigo

## Padroes Aplicados

### Repository / Service Layer
- **Aplica-se a:** `BillingStatusService`, `SubscriptionCalculator`, `TenantConnectionResolver`.
- **Justificativa:** A lógica de bloqueio financeiro e cálculo de fatura é transversal a controllers, middleware e jobs. Isolar em services evita duplicação e facilita testes unitários.

### Middleware Pipeline
- **Aplica-se a:** `RequireModule`, `RequireRole`, `SetVenueContext`.
- **Justificativa:** O acesso a funcionalidades é decidido em camadas: tenant ativo (`tenant`), subscription em dia (`BillingStatusService` dentro de `RequireModule`), módulo contratado (`RequireModule`), role operacional (`RequireRole`). Essa composição é declarativa nas rotas e alinhada com a arquitetura existente.

### Cache Invalidation Pattern
- **Aplica-se a:** cache de módulos ativos da venue.
- **Justificativa:** Módulos são alterados por actions de plataforma e precisam refletir imediatamente para frontend e workers. Invalidar chaves de cache nas actions garante consistência eventual controlada.

### State Machine
- **Aplica-se a:** transições de status de `SubscriptionStatus` (`trial → past_due → suspended/active → canceled`).
- **Justificativa:** Jobs diários executam transições determinísticas baseadas em datas (`trial_ends_at`, `grace_period_days`, `due_date`). Modelar como máquina de estados reduz lógica condicional esparsa.

### Soft Deletes em Faturas
- **Aplica-se a:** `VenueInvoice` e `CorporationInvoice`.
- **Justificativa:** Registros financeiros não podem ser apagados fisicamente por auditoria e conciliação com gateway. `SoftDeletes` é padrão do Laravel e atende ao requisito sem complexidade adicional.

## Análise de Impacto

### Serviços afetados
- **Banco `saas`:** novas tabelas e alterações em `corporations`, `venues` e `plan_catalogs`.
- **Models em `App\Models\Tenant`:** expansão significativa com novos models de subscription, módulos, faturas e afiliados.
- **Middleware em `App\Http\Middleware`:** novo `RequireModule` e atualização de aliases em `bootstrap/app.php`.
- **Rotas operacionais:** adição de `module:` nas rotas de menu, kitchen, orders, payment, support e settings.
- **Backoffice `/backoffice`:** novas telas serão necessárias, mas Fases 1 e 2 preparam apenas controllers/actions fundamentais.
- **Cache Redis:** novo namespace de cache para módulos ativos.
- **Scheduler:** jobs diários de expiração de trial, suspensão e vencimento de fatura.
- **Tests:** novos tests de feature para middleware, services e actions; ajuste em factories existentes.

### Breaking changes
- **Sim.** Remoção de colunas em `corporations` quebra `CreateUserOwnerDefinitions`, `CreateCorporationAction`, `AssignPlanToCorporationAction`, controllers, requests e factories.
- **Sim.** Alteração no fluxo de registro exige criação de `CorporationSubscription` + `VenueSubscription` + `VenueModule::menu`.
- **Não há migração de produção**, então o impacto é restrito ao código e aos dados de desenvolvimento.

### Migrações necessárias
1. Criar tabelas: `module_catalogs`, `corporation_subscriptions`, `venue_subscriptions`, `corporation_modules`, `venue_modules`, `module_usage_tiers`, `venue_usage_records`, `venue_invoices`, `corporation_invoices`, `payment_attempts`, `affiliate_codes`.
2. Adicionar `affiliate_code_id` em `corporations`, `venues`, `corporation_subscriptions`, `venue_subscriptions`, `venue_invoices`, `corporation_invoices`.
3. Adicionar campos em `plan_catalogs`: `description` já existe; garantir `plan_type`; manter `monthly_price` como preço base.
4. Remover campos de `corporations`: `plan_catalog_id`, `plan_name`, `subscription_value`, `plan_start_date`, `plan_end_date`.
5. Unique constraints compostas conforme especificação.

### Notas de migração / deploy
- Como não há produção, o deploy seguro envolve:
  1. Rodar `db:migrate-all --fresh --seed` em desenvolvimento.
  2. Atualizar factories e seeders antes de rodar testes.
  3. Verificar se todos os ambientes de dev re-criam os bancos com o comando acima.
- Atenção: `ModuleCatalogSeeder` deve rodar antes de `CreateUserOwnerDefinitions` criar `VenueModule::menu`.

## Riscos

| Risco | Severidade | Mitigação | Status |
|---|---|---|---|
| Quebra do fluxo de registro de novos usuários ao remover campos legacy de `corporations` | Alta | Atualizar `CreateUserOwnerDefinitions`, `CreateCorporationAction`, factories e requests em um único passo; testar registro end-to-end. | Identificado |
| `RequireModule` bloquear funcionalidades existentes durante desenvolvimento se `menu` não for seedado/default | Média | Garantir que `VenueModule::menu` seja criado em toda venue e que o seeder de módulos rode antes dos fluxos de criação. | Identificado |
| Cache de módulos ficar stale em ambientes sem Redis configurado corretamente | Média | Usar `Cache::tags` apenas se driver suportar; fallback para `Cache::forget` por chave; documentar requisito de Redis. | Identificado |
| Concorrência em `updateOrCreate` de `venue_modules`/`corporation_modules` gerando duplicatas | Média | Adicionar unique constraints compostas no banco; capturar `QueryException` e retry quando apropriado. | Identificado |
| Jobs diários alterarem subscription sem notificar o usuário | Baixa | Implementar notificações por email (já previstas na arquitetura) na Fase 2 para trial expirando e suspensão. | Identificado |
| Integração futura com Asaas exigir ajustes no schema de `payment_attempts` | Baixa | Manter payload JSON flexível e campos polimórficos genéricos (`invoice_type`, `invoice_id`). | Identificado |

## Orientações para Desenvolvimento (Dev Guidance)

### Ordem de implementação sugerida

1. **Migrations e schema (Fase 1)**
   - Criar migrations das novas tabelas.
   - Alterar `corporations` para remover campos legacy.
   - Adicionar `affiliate_code_id` nas tabelas necessárias.
   - Garantir unique constraints.

2. **Enums (Fase 1)**
   - Criar `BillingMode`, `ModuleCode`, `ModuleBillingType`, `ModuleStatus`, `SubscriptionStatus`, `AffiliateCodeStatus`.

3. **Models e relacionamentos (Fase 1)**
   - Criar todos os models novos com `connection = 'saas'`, `HasUuids`, `HasFactory` e casts.
   - Adicionar relações em `Corporation` e `Venue`.
   - Adicionar `SoftDeletes` em `VenueInvoice` e `CorporationInvoice`.

4. **Seeders e factories (Fase 1)**
   - Criar `ModuleCatalogSeeder` com módulos base e pagos.
   - Atualizar `CorporationFactory` e `VenueFactory` para refletir schema novo.
   - Criar factories para os novos models.

5. **Fluxo de criação de corporation/venue (Fase 1)**
   - Atualizar `CreateUserOwnerDefinitions` para criar `CorporationSubscription` + `VenueSubscription` + `VenueModule::menu`.
   - Corrigir `CreateCorporationAction` para não usar campos removidos de `users`.

6. **Middleware `RequireModule` (Fase 2)**
   - Implementar verificação de billing + módulo ativo + dependências transitivas.
   - Registrar alias `module` em `bootstrap/app.php`.

7. **Serviços (Fase 2)**
   - Implementar `BillingStatusService`.
   - Implementar cache de módulos em `Venue::activeModules()`.

8. **Proteção de rotas (Fase 2)**
   - Adicionar `module:` nas rotas de menu, kitchen/kds, orders/take, payment, support, settings.
   - Manter `role:` conforme hierarquia operacional.

9. **Jobs diários (Fase 2)**
   - Criar `ExpireTrialsJob`, `SuspendOverdueSubscriptionsJob`, `MarkInvoicesOverdueJob`.
   - Registrar no scheduler (`routes/console.php`).

10. **Frontend (Fase 2)**
    - Adicionar `tenant.modules` nas shared props do Inertia.
    - Criar `resources/js/composables/useModules.ts`.

11. **Actions de ativação/desativação (Fase 2)**
    - Implementar `EnableCorporateModuleAction`, `ActivateVenueModuleAction`, `DeactivateVenueModuleAction`, `DisableCorporateModuleAction`.
    - Adicionar invalidação de cache nas actions.

12. **Testes (Fase 1 e 2)**
    - Tests de feature para criação de corporation/venue com subscription default.
    - Tests para `RequireModule` permitindo/bloqueando acesso.
    - Tests para `BillingStatusService` nos modos `per_venue` e `unified`.
    - Tests para jobs de transição de status.

### Pontos de atenção específicos do código

- `CreateCorporationAction` em [app/Actions/Platform/CreateCorporationAction.php](app/Actions/Platform/CreateCorporationAction.php) está quebrado: cria `User` com `role`, `corporation_id` e `venue_id` que não existem mais. Deve ser reescrito para usar `CreateNewUser` + `CreateUserOwnerDefinitions` ou equivalente.
- `SetVenueContext` já faz `app()->instance('tenant', $venue)`. O `RequireModule` pode confiar nisso.
- `TenantConnectionResolver` não deve ser usado para cache de módulos; criar service dedicado (`VenueModuleCache`) ou método no model.
- O middleware `RequireModule` deve receber `string $moduleCode` e converter via `ModuleCode::tryFrom()`.
- Rotas públicas como `/kitchen/monitor` precisam de tratamento especial se forem protegidas por módulo sem autenticação.

### Checklist pré-deploy

- [ ] Todas as migrations novas rodaram com `db:migrate-all --fresh`.
- [ ] Seeders populam `module_catalogs` e `affiliate_codes` de exemplo.
- [ ] Registro de novo usuário cria corporation, venue, subscription e `VenueModule::menu`.
- [ ] Backoffice de corporations não referencia campos removidos.
- [ ] Rotas protegidas por `module:` respondem 403 quando o módulo não está ativo.
- [ ] Jobs diários estão registrados e testados.
- [ ] Cache de módulos invalida corretamente ao ativar/desativar.
- [ ] Testes de feature passam (`vendor/bin/sail artisan test --compact`).
- [ ] Pint rodou sem alterações pendentes (`vendor/bin/sail bin pint --dirty --format agent`).

## Apêndice: Mapeamento de Módulos para Rotas (Fase 2)

| Rota / Grupo | Módulo | Roles permitidos | Observação |
|---|---|---|---|
| `/menu/*` | `menu` | `owner`, `general_manager` | Módulo base, sempre ativo. |
| `/attendances/*` | `menu` | todos operacionais | Depende de cardápio. |
| `/orders/take/*` | `taker` | `owner`, `general_manager`, `section_manager`, `attendant` | Módulo pago. |
| `/kitchen/*` | `kds` | todos operacionais | Módulo pago; depende de `menu`. |
| `/payment/*` | `menu` | todos operacionais | Faz parte do base. |
| `/support/*` | `menu` | todos operacionais | Suporte base. |
| `/settings/*` | `menu` | `owner`, `general_manager` | Configurações base. |
| `/corporation/*` | `menu` | `owner`, `general_manager` | Painel multi-venue base. |

> Módulos pagos adicionais (`direct_waiter`, `delivery`, `production_dashboard`, `financial_dashboard`, `direct_print`, `fiscal_note`, `voice_command`) serão aplicados quando suas rotas forem criadas nas Fases 5+.
