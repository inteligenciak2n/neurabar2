# Relatório de Revisão – feat/sistema-modulos-assinaturas vs main

## Resumo da Task
A task implementa o modelo de módulos contratáveis, subscriptions e permissionamento de tenants descrito em `docs/module-subscription-architecture.md` e nos planos técnicos (`tech-plan_fase_1_e_2.md`, `tech-plan_fase_3_4_5.md`): schema de módulos/subscriptions/faturas, middleware `RequireModule`, `BillingStatusService`, cálculo de billing (`SubscriptionCalculator`), geração de faturas, jobs de transição de status, registro de uso medido, backoffice comercial e scaffolds de módulos operacionais. Integração com gateway de pagamento externo está fora do escopo desta revisão, conforme informado.

Diff analisado: `git diff main..HEAD` — 217 arquivos alterados (~11.640 inserções). Revisão focada nos arquivos de backend/billing/permissionamento diretamente relacionados à task (middleware, services, actions, jobs, controllers de backoffice, migrations e testes). Arquivos de infraestrutura não relacionados (traduções, scaffolds de UI de módulos futuros) foram inspecionados apenas superficialmente.

## Resultado da Revisão
- **Arquivos revisados (foco):** 24
- **Total de findings:** 8 ([CRITICAL]: 2, [WARNING]: 4, [DEBT]: 2)

## Findings por Dimensão

### Segurança

- [CRITICAL] `routes/web/platform.php:39-46` (`CorporationModuleController`, `VenueModuleController`) — As rotas de habilitar/desabilitar módulo por corporation e por venue (incluindo definição de `custom_monthly_price`, dado financeiro sensível) estão protegidas apenas por `['auth', 'platform_profile']`, sem o middleware `platform_role:super_admin,finance` aplicado às demais rotas financeiras (edição de corporation, subscription, discounts, faturas manuais). Isso permite que um usuário de plataforma com perfil `registration` ou até `read_only` (nome que sugere acesso somente leitura) habilite módulos pagos e altere preços customizados. Confirmado por [tests/Feature/Platform/CorporationModuleControllerTest.php](tests/Feature/Platform/CorporationModuleControllerTest.php) e [tests/Feature/Platform/VenueModuleControllerTest.php](tests/Feature/Platform/VenueModuleControllerTest.php), que só testam `super_admin` e não incluem nenhum caso negativo para outros perfis. **Sugestão:** envolver as rotas de `corporations.modules.*` e `corporations.venues.modules.*` no mesmo grupo `platform_role:super_admin,finance` usado para subscription/discounts/invoices, e adicionar teste de regressão para perfis não autorizados.

- [CRITICAL] `app/Http/Controllers/Platform/InvoiceController.php:19-32` (`index`) — O método `show()` chama `Gate::authorize('view-invoice')`, restringindo a visualização de faturas a `super_admin`, `finance` e `read_only` (confirmado por [tests/Feature/Platform/InvoiceControllerTest.php:78-90](tests/Feature/Platform/InvoiceControllerTest.php), `test_registration_user_cannot_show_invoice`). Porém o método `index()`, que lista **todas** as faturas de **todas** as corporations/venues, não chama o mesmo Gate — qualquer usuário com `platform_profile` (inclusive `registration`) pode listar dados financeiros completos via `/backoffice/invoices`. Isso é uma falha de controle de acesso (OWASP A01) inconsistente com a intenção de design já demonstrada no próprio código/testes. **Sugestão:** adicionar `Gate::authorize('view-invoice')` no início de `index()` e criar teste equivalente ao de `show()` para o caso negativo.

### Corretude e Lógica

- [WARNING] `app/Jobs/Billing/RecordModuleUsageJob.php:27-40` — `handle()` faz `firstOrNew` seguido de `$record->quantity += $this->quantity` e `save()`. Essa sequência read-modify-write não é atômica: se dois workers processarem jobs do mesmo `(venue_id, module_code, period)` concorrentemente (cenário plausível com filas de produção e múltiplos eventos de pedido/KDS quase simultâneos), um incremento pode ser perdido, subestimando o uso medido e, consequentemente, a fatura de excedente. **Sugestão:** usar incremento atômico, por exemplo `VenueUsageRecord::query()->upsert(...)` com `quantity = quantity + ?` via `DB::raw`, ou `lockForUpdate()` dentro de uma transação antes do `save()`.

- [WARNING] `app/Services/Billing/SubscriptionCalculator.php:150-176` (`calculateRecord`) — Quando `flat_price` é nulo, `basePrice = price_per_unit * quantity` usa a quantidade **total** do registro (não limitada a `included_quantity`), e em seguida soma `overagePrice` para a quantidade excedente. Se um tier tiver `price_per_unit > 0` junto com `included_quantity > 0`, as unidades excedentes seriam cobradas duas vezes (uma vez dentro de `basePrice`, outra em `overagePrice`), e as unidades "inclusas" deixariam de ser gratuitas, contrariando a definição documentada em `docs/module-subscription-architecture.md` (seção 2.6). O bug está mascarado hoje porque `ModuleUsageTiersSeeder` sempre usa `price_per_unit = 0`, e não há controller de backoffice para `ModuleUsageTier` nesta branch — mas o modelo/schema permite essa configuração e nenhum teste cobre `price_per_unit > 0` (todos os testes em [tests/Feature/Billing/SubscriptionCalculatorTest.php](tests/Feature/Billing/SubscriptionCalculatorTest.php) usam `price_per_unit: 0.00`). **Sugestão:** corrigir o cálculo para `price_per_unit * min(quantity, included)` (ou equivalente) e adicionar teste com `price_per_unit > 0` cobrindo o caso de excedente.

### Performance

- [DEBT] `app/Services/Billing/SubscriptionCalculator.php:108-125` (`resolveModuleUnitPrice`) — Para cada módulo ativo de cada venue, executa uma nova query `corporation->activeModules()->where(...)->first()`. Em `GenerateInvoicesJob`, isso é chamado para cada módulo de cada venue de cada corporation todo mês, gerando N+1 queries relevantes em bases com muitas venues/módulos. **Sugestão:** eager-load `corporation.activeModules` uma vez por corporation (ou por venue) antes do loop, ou cachear o resultado por request/job.

- [DEBT] `app/Http/Middleware/RequireModule.php:27` — `$corporation?->hasActiveModule($module)` executa uma query não cacheada em toda requisição a rota protegida por módulo, enquanto `Venue::activeModules()` já usa `VenueModuleCache` (Redis) conforme decisão registrada no tech-plan. A camada de corporation ficou sem cache, quebrando a consistência da estratégia de cache adotada para middleware que roda em "praticamente toda rota operacional". **Sugestão:** incluir o status de módulo ativo da corporation na mesma chave de cache do `VenueModuleCache`, ou criar cache dedicado com invalidação nas actions `EnableCorporateModuleAction`/`DisableCorporateModuleAction`.

### Complexidade e Manutenibilidade

- [DEBT] `app/Jobs/Billing/GenerateInvoicesJob.php:44-140` (`generateForSubscription`) — Método com ~90 linhas e múltiplos ramos condicionais (modo unified vs per_venue, fatura já existente vs nova, notificação condicional), acima do limite de 50 linhas sugerido. Mistura cálculo, persistência, agregação de totais e disparo de notificação. **Sugestão:** extrair a agregação de totais por corporation e a lógica de "reaproveitar fatura existente quando `calculateVenue` retorna null" para métodos privados dedicados.

### Padrões e Arquitetura

- [DEBT] `app/Actions/Fortify/CreateUserOwnerDefinitions.php:50-58` (`createCorporationAndVenue`) — Cria sempre uma `Corporation` com dados fixos (`tax_id => '00.000.000/0001-00'`, `name => 'Test Corp'`, `email => 'corp@test.com'`, `contact_phone => '11999990000'`). Confirmado que esta action só é usada por `CreateNewUserPlatform` (chamada por `database/seeders/DatabaseSeeder.php`), portanto não afeta o fluxo real de registro/onboarding (`CreateNewUser` + `Onboarding/*`, que coletam dados reais do usuário). Ainda assim, é uma action de produção com dados de teste embutidos, sem constante/flag que deixe explícito o caráter "dev/seed-only", e sem constraint única em `corporations.tax_id`/`email` que evite duplicidade silenciosa se reaproveitada para múltiplos owners. **Sugestão:** mover esse cenário para um `Seeder`/`Factory` dedicado ou marcar claramente a action como ferramenta de desenvolvimento, evitando reuso acidental fora de contexto de seed.

### Cobertura de Testes

- [WARNING] Nenhum teste cobre o caso negativo de autorização para `CorporationModuleController`/`VenueModuleController` (perfis `registration`/`read_only` tentando habilitar/ativar módulos). Dado que o mesmo pacote de testes já demonstra o padrão esperado para outras rotas financeiras (`test_registration_user_cannot_show_invoice`), a ausência aqui reforça que o gap de autorização (finding CRITICAL acima) não foi percebido durante o desenvolvimento. **Sugestão:** adicionar testes `test_registration_user_cannot_enable_corporation_module` e equivalente para `VenueModuleController`.

- [WARNING] Nenhum teste cobre `InvoiceController::index` para perfis não autorizados (todos os testes em [tests/Feature/Platform/InvoiceControllerTest.php](tests/Feature/Platform/InvoiceControllerTest.php) usam `super_admin` para `index`). **Sugestão:** adicionar `test_registration_user_cannot_list_invoices` espelhando o teste já existente para `show()`.

## Score de Qualidade
- **Pontuação:** 1/10 (2 CRITICAL de controle de acesso em rotas financeiras do backoffice, mais 4 WARNING)
- **Pontuação de Dívida Técnica:** 3/10 (arquitetura geral sólida e bem testada nas Fases 1-5; dívida concentrada em N+1 pontual, método longo em job de billing e action de seed com dados fixos)
- **Parecer Final:** BLOQUEADO

## Observação Final
A arquitetura de módulos/subscriptions/billing entregue (schema, `SubscriptionCalculator`, `BillingStatusService`, `RequireModule`, jobs de transição de status, geração de faturas e scaffolds) está bem estruturada e amplamente coberta por testes. Os dois findings CRITICAL são pontuais e de correção rápida (adicionar middleware/gate faltante), mas bloqueiam o merge por exporem controle de acesso quebrado sobre dados financeiros no backoffice — corrigir antes de liberar para produção.
