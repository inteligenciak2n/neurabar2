# Revisão de Código – Fases 1, 2, 3, 4 e 5 (Módulos, Subscriptions e Billing)

## Escopo da Revisão
- **Branch atual:** working tree
- **Branch de origem:** `main`
- **Foco:** Revisão completa das implementações das Fases 1, 2, 3, 4 e 5 do modelo de módulos contratáveis, subscriptions e billing do NeuraBar.
- **Resultado dos testes:** 319 passed, 7 skipped, 946 assertions
- **Pint:** passed

## Resumo Executivo
A implementação está funcional, bem testada e alinhada com o tech-plan. A arquitetura de módulos, subscriptions, cálculo de billing, geração de faturas, notificações, backoffice comercial, scaffolds de módulos e registro de uso medido foi entregue. Os principais pontos de atenção estão relacionados a refinamentos de segurança, consistência de factories e evolução futura (Asaas e proration).

---

## Pontos Fortes

1. **Arquitetura clara e separada**
   - `SubscriptionCalculator` centraliza toda a lógica de cálculo de valores (`base`, `modules`, `metered`, `dedicated_surcharge`, `total`).
   - `BillingStatusService` isola a regra de bloqueio por status financeiro, respeitando os modos `per_venue` e `unified`.
   - `RequireModule` middleware centraliza o permissionamento por módulo e dependências.

2. **Jobs assíncronos bem organizados**
   - `ExpireTrialsJob`, `SuspendOverdueSubscriptionsJob`, `MarkInvoicesOverdueJob`, `GenerateInvoicesJob`, `NotifyTrialEndingSoonJob` e `RecordModuleUsageJob` seguem o padrão `ShouldQueue`.
   - Notificações são disparadas dentro dos jobs apropriados.

3. **Cálculo de billing robusto**
   - `SubscriptionCalculator::calculateVenue` respeita faturas finalizadas (`is_finalized = true`).
   - Módulos fixos e medidos são calculados separadamente.
   - Preço customizado por corporation é respeitado (`custom_monthly_price`).

4. **Geração de faturas idempotente**
   - `GenerateInvoicesJob` usa `updateOrCreate` por `(venue_id/corporation_id, period)`, evitando duplicatas.
   - Fatura unified agrega corretamente os valores de todas as venues.

5. **Backoffice funcional**
   - Controllers de gestão de módulos corporate/venue e faturas criados com rotas nomeadas e protegidas.
   - Views Vue seguem o padrão visual do projeto (Tailwind + dark mode).

6. **Modularização das rotas**
   - `routes/web.php` foi quebrado em `guest.php`, `operational.php`, `corporation.php` e `platform.php`.
   - Todas as named routes foram preservadas conforme validação via `route:list`.

7. **Cobertura de testes abrangente**
   - Testes unitários para enums.
   - Feature tests para calculator, jobs, listeners, notificações, actions, controllers e scaffolds.

---

## Pontos de Atenção e Recomendações

### 1. Validação de permissão no backoffice de módulos
**Arquivos:** [app/Http/Controllers/Platform/CorporationModuleController.php](app/Http/Controllers/Platform/CorporationModuleController.php), [app/Http/Controllers/Platform/VenueModuleController.php](app/Http/Controllers/Platform/VenueModuleController.php)

**Observação:** Os controllers usam `StoreCorporationModuleRequest`/`StoreVenueModuleRequest` para validação de input, mas a autorização depende apenas do middleware global `['auth', 'platform_profile']`. Não há verificação explícita de `platform_role`.

**Recomendação:** Confirmar se qualquer usuário com `platform_profile` pode habilitar/desabilitar módulos e visualizar faturas, ou se apenas `super_admin` deveria ter esse poder. Se necessário, adicionar middleware `platform_role:super_admin` ou outra gate.

---

### 2. `InvoiceController::show` resolve por UUID sem distinção de tipo
**Arquivo:** [app/Http/Controllers/Platform/InvoiceController.php](app/Http/Controllers/Platform/InvoiceController.php)

**Observação:** O método `show(string $invoice)` busca primeiro em `CorporationInvoice` e depois em `VenueInvoice`. Se os UUIDs das duas tabelas colidirem (improvável, mas possível), pode haver ambiguidade.

**Recomendação:** Considerar rotas separadas ou parâmetro de tipo (`?type=corporation|venue`) para desambiguação futura. Para o escopo atual, funciona.

---

### 3. Fábricas de faturas ainda usam string para status
**Arquivos:** [database/factories/Tenant/CorporationInvoiceFactory.php](database/factories/Tenant/CorporationInvoiceFactory.php), [database/factories/Tenant/VenueInvoiceFactory.php](database/factories/Tenant/VenueInvoiceFactory.php)

**Observação:** Ambas as factories definem `'status' => 'open'` em vez de `InvoiceStatus::Open->value`. O cast do Eloquent resolve, mas perde-se a padronização.

**Recomendação:** Atualizar as factories para usar `InvoiceStatus::Open->value`.

---

### 4. `RecordOrderModuleUsage` registra módulos fixos como uso medido
**Arquivo:** [app/Listeners/Billing/RecordOrderModuleUsage.php](app/Listeners/Billing/RecordOrderModuleUsage.php)

**Observação:** O listener incrementa `Kds`, `Taker` e `DirectPrint` a cada pedido. Módulos fixos (`Fixed`) e híbridos (`Hybrid`) têm cobrança de base mensal independente de uso. O registro de uso medido só deveria impactar módulos `metered`/`hybrid` com tiers configurados.

**Recomendação:** O `SubscriptionCalculator` já lida com isso corretamente: só adiciona `metered` se houver `ModuleUsageTier` e overage. No entanto, o listener está registrando uso para módulos que podem não ter tier (ex: `Taker` e `DirectPrint` têm tiers no seeder). Verificar se todos os módulos registrados possuem tiers no `ModuleUsageTiersSeeder` — atualmente sim, mas o acoplamento é implícito.

---

### 5. Ausência de teste para `NotifyTrialEndingSoonJob`
**Arquivo:** [app/Jobs/Billing/NotifyTrialEndingSoonJob.php](app/Jobs/Billing/NotifyTrialEndingSoonJob.php)

**Observação:** Existe teste para a notificação `TrialEndingSoon`, mas não para o job que a dispara.

**Recomendação:** Adicionar feature test para `NotifyTrialEndingSoonJob` cobrindo o cenário de trial próximo do vencimento.

---

### 6. `SuspendOverdueSubscriptionsJob` depende de `trial_ends_at` para venue
**Arquivo:** [app/Jobs/Billing/SuspendOverdueSubscriptionsJob.php](app/Jobs/Billing/SuspendOverdueSubscriptionsJob.php)

**Observação:** O job usa `trial_ends_at + grace_period_days` para `CorporationSubscription` e repete via `corporationSubscription` para `VenueSubscription`. Se a regra de suspensão for sempre corporativa, isso está correto, mas deve ser documentado.

**Recomendação:** Documentar no tech-plan ou em comentário que o grace period é sempre derivado da `CorporationSubscription`, mesmo no modo `per_venue`.

---

### 7. `CreateVenueAction` e `CreateUserOwnerDefinitions` duplicam lógica de fallback
**Arquivos:** [app/Actions/Corporation/CreateVenueAction.php](app/Actions/Corporation/CreateVenueAction.php), [app/Actions/Fortify/CreateUserOwnerDefinitions.php](app/Actions/Fortify/CreateUserOwnerDefinitions.php)

**Observação:** Ambos criam plano "pro" hardcoded caso não exista subscription. Isso é aceitável como fallback de desenvolvimento, mas pode causar inconsistência se o plano do catálogo mudar.

**Recomendação:** Considerar centralizar a criação de subscription default em uma action ou service, ou ao menos documentar que esse fallback existe.

---

### 8. Scaffolds de módulos sem funcionalidade real
**Arquivos:** [app/Http/Controllers/Delivery/DashboardController.php](app/Http/Controllers/Delivery/DashboardController.php), [app/Http/Controllers/DirectPrint/DashboardController.php](app/Http/Controllers/DirectPrint/DashboardController.php), etc.

**Observação:** Os 7 módulos restantes possuem apenas controllers placeholder e views Vue mínimas. Isso está de acordo com o escopo acordado, mas deve ser claro para o time que essas áreas precisam de implementação futura.

**Recomendação:** Criar issues/tickets de acompanhamento para cada módulo scaffold.

---

### 9. Proration não implementado
**Arquivo:** [app/Services/Billing/SubscriptionCalculator.php](app/Services/Billing/SubscriptionCalculator.php)

**Observação:** Módulos ativados/desativados no meio do mês são cobrados/isentos pelo mês inteiro. Essa decisão arquitetural foi documentada, mas não há comentário no código.

**Recomendação:** Adicionar comentário/documentação no `SubscriptionCalculator` informando que proration não é aplicado.

---

### 10. `tenant.blocked` exposto via Inertia
**Arquivo:** [app/Http/Middleware/HandleInertiaRequests.php](app/Http/Middleware/HandleInertiaRequests.php)

**Observação:** O flag `blocked` é derivado de `BillingStatusService::isBlocked($venue)`. Isso é preciso, mas o frontend deve usar essa informação para exibir mensagens apropriadas.

**Recomendação:** Verificar se há tratamento de redirecionamento/bloqueio no frontend quando `tenant.blocked === true`.

---

## Checklist de Merge

- [x] Testes passam (`vendor/bin/sail artisan test --compact`)
- [x] Pint passa (`vendor/bin/sail bin pint --dirty --format agent`)
- [x] Named routes preservadas (`vendor/bin/sail artisan route:list`)
- [x] Migrations e seeders criados
- [x] Jobs registrados no scheduler
- [x] Enum `InvoiceStatus` aplicado nos casts
- [x] Notificações de billing implementadas
- [x] Backoffice controllers e views criados
- [x] Modularização de rotas concluída
- [x] Relatório de execução gerado ([relatorio_execucao_3_4_5.md](relatorio_execucao_3_4_5.md))

## Conclusão
A branch está **aprovada para merge** com ressalvas leves. A implementação é funcional, bem testada e alinhada com o tech-plan. As recomendações acima são majoritariamente de refinamento, documentação e governança, não bloqueadores técnicos.

## Próximos Passos Sugeridos
1. Revisar e aplicar as recomendações de baixo risco (factories, comentários de proration, teste do `NotifyTrialEndingSoonJob`).
2. Definir regras de RBAC mais granulares no backoffice de módulos/faturas.
3. Iniciar a integração com Asaas (gateway de pagamento) na próxima fase.
4. Planejar implementação real dos scaffolds de módulos.
