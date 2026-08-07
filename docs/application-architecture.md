# User ↔ Venue Architecture

**Versão:** 1.3 · **Data:** 23 de julho de 2026

> Referência técnica do modelo de identidade e acesso do NeuraBar: como usuários se relacionam com Corporations, Venues e roles operacionais.

> **Changelog 1.3:** adiciona o wizard de onboarding (assinatura + empresa) que substitui a criação automática de Corporation/Venue no registro, a arquitetura multi-database (banco `saas` vs. bancos operacionais por tenant) e o módulo de Módulos/Subscriptions/Billing (planos, módulos contratáveis, faturamento e afiliados).

---

## Visão Geral

```
User  (guard: web — único model de autenticação)
  │
  │  profile: ProfileEnum
  │    ├── Client        → acesso operacional (via user_venue)
  │    ├── SuperAdmin    → acesso total ao backoffice
  │    ├── Finance       → acesso financeiro ao backoffice
  │    ├── Registration  → acesso de cadastro ao backoffice
  │    └── ReadOnly      → acesso somente leitura ao backoffice
  │
  ├── administra (platform profiles)
  │       ▼
  │   Corporation  ─── owner_id ──►  User
  │         │
  │         │  has many
  │         ▼
  │       Venue  ◄────── user_venue (pivot) ──────►  User (profile=Client)
  │                      role: UserRole (operacional)
  │
  └── pertence a (Client profile, via user_venue)
          ▼
        Venue (role: Owner | GeneralManager | SectionManager | Attendant)
```

Um `User` pode pertencer a **múltiplas Venues** através da tabela pivot `user_venue`. Cada entrada no pivot carrega a `role` daquele usuário naquela venue específica. A venue ativa no momento é rastreada por `users.current_venue_id`.

Toda a autenticação usa um **único guard `web`** e um **único model `User`**. O campo `profile` (cast para `ProfileEnum`) distingue usuários operacionais (clientes SaaS) de usuários internos do NeuraBar.

---

## Estrutura do Banco de Dados

### Tabela `users`

| Coluna | Tipo | Descrição |
|---|---|---|
| `id` | UUID PK | Identificador único |
| `current_venue_id` | UUID FK nullable | Venue ativa no momento (→ `venues.id`) |
| `name` | string | Nome completo |
| `email` | string unique | Email de login |
| `active` | boolean | Se falso, login bloqueado |
| `lang` | varchar default 'pt' | Idioma preferido |
| `pin` | string nullable | PIN de acesso rápido |
| `onboarding_completed_at` | timestamp nullable | Nulo enquanto o usuário não concluiu o wizard de onboarding (assinatura + empresa). Ver [Wizard de Onboarding](#wizard-de-onboarding-novo-owner) |

> `role`, `venue_id` e `corporation_id` foram **removidos** da tabela `users` — esses dados vivem no pivot `user_venue`.

### Tabela `user_venue` (pivot)

| Coluna | Tipo | Descrição |
|---|---|---|
| `user_id` | UUID FK | → `users.id` |
| `venue_id` | UUID FK | → `venues.id` |
| `role` | varchar | Valor do enum `UserRole` operacional |
| `created_at` / `updated_at` | timestamp | Gerenciados automaticamente |

Chave primária composta: `(user_id, venue_id)`.

### Tabela `corporations`

| Coluna relevante | Tipo | Descrição |
|---|---|---|
| `owner_id` | UUID FK nullable | User que é dono da corporation (→ `users.id`) |

### Tabela `venue_invitations`

| Coluna | Tipo | Descrição |
|---|---|---|
| `id` | UUID PK | Identificador único |
| `email` | string | Email do convidado |
| `venue_id` | UUID FK | Venue para a qual está sendo convidado |
| `role` | varchar | Role que o convidado terá ao aceitar |
| `invited_by_id` | UUID FK nullable | User que enviou o convite |
| `token` | string(64) unique | Token de 64 chars para o link de aceitação |
| `expires_at` | datetime | Expiração (padrão: 72h após criação) |
| `accepted_at` | datetime nullable | Nulo enquanto pendente |

---

## Enum UserRole

```php
// Roles de plataforma — usadas apenas por PlatformUser
UserRole::SuperAdmin   // 'super_admin'
UserRole::Finance      // 'finance'
UserRole::Registration // 'registration'
UserRole::ReadOnly     // 'read_only'

// Roles operacionais — vivem no pivot user_venue
UserRole::Owner          // 'owner'
UserRole::GeneralManager // 'general_manager'
UserRole::SectionManager // 'section_manager'
UserRole::Attendant      // 'attendant'
```

**Regra crítica:** Roles de plataforma **nunca** devem ser atribuídas via `user_venue`. O `StoreUserRequest` valida isso com `abort(403)` se uma role de plataforma for enviada no formulário de criação de usuário da venue.

---

## Modelos e Relacionamentos

### `User`

```php
// Venue ativa — BelongsTo via current_venue_id
$user->currentVenue

// Todas as venues do usuário — BelongsToMany via user_venue
$user->venues()

// Corporation de que é dono — HasOne via owner_id
$user->ownedCorporation()

// Role na venue ativa
$user->currentVenueRole(): ?UserRole
```

### `Venue`

```php
// Todos os usuários da venue — BelongsToMany via user_venue
$venue->users()

// Corporation proprietária
$venue->corporation()
```

### `UserVenue` (Pivot)

Estende `Illuminate\Database\Eloquent\Relations\Pivot`. Cast automático de `role` para `UserRole`:

```php
$pivot->role // instância de UserRole, não string
```

---

## Wizard de Onboarding (novo Owner)

> Substitui o fluxo antigo de criação automática de Corporation/Venue no registro. Desde a Fase de Módulos/Subscriptions, `CreateNewUser` **não cria mais** Corporation nem Venue — o usuário só ganha acesso operacional depois de concluir um wizard de 2 passos.

### Visão Geral

```
POST /register (Fortify)
    │  CreateNewUser::create()
    │  → cria apenas o User (profile=Client, sem Corporation/Venue)
    ▼
LoginResponse / VerifyEmailResponse
    │  user->onboarding_completed_at == null?
    │       └── SIM → redirect /onboarding/subscription
    ▼
Passo 1 — GET/POST /onboarding/subscription   (Onboarding/Subscription.vue)
    │  Exibe catálogo de módulos (ModuleCatalog) — Cardápio incluso e grátis.
    │  Usuário escolhe módulos à la carte + quantidade de venues + aceite de termos.
    │  StartCorporationSubscriptionAction::execute()
    │       → cria Corporation (sem venues ainda)
    │       → cria CorporationSubscription (status=trial, billing_mode=per_venue)
    │       → cria CorporationModule para 'menu' + módulos selecionados (status=trial)
    │       → grava session('onboarding.venue_count')
    ▼
Passo 2 — GET/POST /onboarding/corporation   (Onboarding/Corporation.vue)
    │  Exibe dados da empresa (nome, tax_id, email, telefone) e N formulários de venue
    │  (um por venue_count), cada um podendo ser marcado "Preencher depois" (skip).
    │  FinalizeOnboardingAction::execute()
    │       → atualiza dados da Corporation
    │       → cria uma Venue por entrada (dados reais ou fake quando skip=true)
    │       → define users.current_venue_id = primeira venue criada
    │       → grava users.onboarding_completed_at = now()
    ▼
redirect /dashboard
```

### Regras e Guardas

- **Idempotência de passo:** cada controller (`SubscriptionController`, `CorporationController`) verifica `onboarding_completed_at` e o estado já persistido (`user->ownedCorporation`) para redirecionar automaticamente para o passo correto — evita repetir um passo já concluído ou pular um passo pendente.
- **Redirecionamento forçado:** `SetVenueContext` redireciona para `onboarding.subscription.create` sempre que o usuário autenticado não tem `current_venue_id` e ainda não concluiu o onboarding (antes disso caía direto no fallback `/no-venue`).
- **Sem contexto de tenant:** as rotas `/onboarding/*` usam apenas `auth:sanctum` + `verified` — não passam pelo middleware `tenant`, pois a venue ainda não existe no Passo 1.
- **Módulos à la carte, sem `PlanCatalog`:** o wizard ativa módulos individualmente via `CorporationModule` (status `trial`), sem vincular a corporation a um `PlanCatalog`. `PlanCatalog.included_modules` continua sendo usado apenas pelo backoffice (`CreateCorporationAction`, `CreateNewUserPlatform`) para criação administrativa/seed.
- **Skip de venue:** ao marcar uma venue como "preencher depois", `FinalizeOnboardingAction` gera um nome fake (`"{$user->name} - Ponto de Venda {n}"`) e demais campos nulos — a venue é criada normalmente (com módulos, menu e locais default via `CreateVenueAction`/`CreateVenueDefaultsAction`) e pode ser editada depois em `/settings`.
- **Fluxo legado ainda existe:** `CreateUserOwnerDefinitions` (usada por `CreateNewUserPlatform` e pelo `DatabaseSeeder`) continua criando Corporation + Venue + Subscription de forma síncrona e completa em uma única chamada — reservado a contas de desenvolvimento/seed e criação administrativa de usuários de plataforma, não ao registro público.

### Referência de Arquivos — Wizard de Onboarding

| Arquivo | Responsabilidade |
|---|---|
| `routes/web/onboarding.php` | Rotas `onboarding.subscription.*` e `onboarding.corporation.*` |
| `app/Http/Controllers/Onboarding/SubscriptionController.php` | Passo 1 — exibe módulos e grava seleção |
| `app/Http/Controllers/Onboarding/CorporationController.php` | Passo 2 — exibe/grava dados da empresa e venues |
| `app/Actions/Onboarding/StartCorporationSubscriptionAction.php` | Cria Corporation + CorporationSubscription (trial) + CorporationModule por módulo selecionado |
| `app/Actions/Onboarding/FinalizeOnboardingAction.php` | Atualiza Corporation, cria as Venues e marca `onboarding_completed_at` |
| `app/Http/Requests/Onboarding/StoreSubscriptionRequest.php` | Valida `module_codes`, `venue_count`, aceite de termos |
| `app/Http/Requests/Onboarding/StoreCorporationRequest.php` | Valida dados da empresa e array de venues (com `skip`) |
| `app/Http/Responses/LoginResponse.php` | Redireciona para `onboarding.subscription.create` se `onboarding_completed_at` for nulo |
| `app/Http/Responses/VerifyEmailResponse.php` | Mesmo redirecionamento após verificação de email |
| `app/Http/Middleware/SetVenueContext.php` | Redireciona para o onboarding (em vez de `/no-venue`) enquanto ele não foi concluído |
| `resources/js/Pages/Onboarding/Subscription.vue` | Tela do Passo 1 (seleção de módulos + quantidade de venues) |
| `resources/js/Pages/Onboarding/Corporation.vue` | Tela do Passo 2 (dados da empresa + formulário por venue) |
| `database/migrations/saas/2026_07_22_120000_add_onboarding_completed_at_to_users_table.php` | Adiciona a coluna `onboarding_completed_at` |
| `tests/Feature/OnboardingTest.php` | Cobertura dos dois passos, redirecionamentos e skip de venue |

---

## Fluxo de Convite por Email

```
Owner/GM faz POST /settings/users
    │
    ├── Email já existe na tabela users?
    │       └── SIM → InviteUserToVenueAction::execute()
    │                   → cria VenueInvitation
    │                   → envia VenueInvitationMail
    │                   → retorna 'invitation_sent'
    │
    └── NÃO → CreateUserAction::execute()
                → cria User com senha temporária
                → faz attach em user_venue com role
                → retorna User criado
```

### Aceitação do convite

1. Convidado acessa `GET /invitations/{token}` → página de aceitação.
2. Faz `POST /invitations/{token}/accept` (requer auth).
3. `AcceptVenueInvitationAction::execute()` valida:
   - Convite não foi aceito ainda
   - Convite não expirou (72h)
   - Email do usuário autenticado bate com o email do convite
4. Faz `syncWithoutDetaching` no pivot com a role do convite.
5. Se o usuário não tinha `current_venue_id`, define esta venue como ativa.
6. Marca `accepted_at = now()`.

---

## Contexto de Tenant (Multi-tenancy)

O middleware `SetVenueContext` (alias `tenant`) é responsável por injetar a venue e a **conexão de banco operacional** no container:

```
Request chega
    │
    ├── Usuário não autenticado → passa adiante (Fortify cuida do redirect)
    │
    ├── users.current_venue_id = null
    │       ├── onboarding_completed_at = null → redirect para /onboarding/subscription
    │       └── onboarding concluído          → redirect para /no-venue
    │
    ├── user_venue não tem entrada para esta venue → abort(403)
    │
    └── OK → venue->load('corporation')
             → connectionName = TenantConnectionResolver::resolve($venue)
             → app()->instance('tenant', $venue)
             → app()->instance('operational_connection', $connectionName)
             → app()->instance('operational_is_dedicated', $venue->corporation->is_dedicated)
             → request->merge(['_venue' => $venue])
             → passa adiante
```

**Rotas que NÃO usam o middleware `tenant`:**
- `/login`, `/register` e rotas Fortify
- `/onboarding/*` — wizard de assinatura/empresa (ver [Wizard de Onboarding](#wizard-de-onboarding-novo-owner))
- `/venue-select` — troca de venue ativa
- `/no-venue/*` — fallback sem venue
- `/invitations/*` — aceitação de convite
- `/user/profile-information` e demais rotas do Jetstream (perfil, senha, 2FA)

**Rotas que usam o middleware `tenant`:**
- Todas as rotas operacionais (`/dashboard`, `/menu/*`, `/orders/*`, `/settings/*`, etc.)

### Acessando a venue em controllers/actions

```php
$venue = app('tenant'); // instância de Venue
```

---

## Multi-Database Tenancy (banco `saas` vs. bancos operacionais)

A aplicação usa **duas famílias de conexão de banco de dados**:

1. **Conexão `saas`** (fixa, única) — banco compartilhado com dados de identidade e comerciais: `users`, `corporations`, `venues`, `user_venue`, `venue_invitations`, todas as entidades de Módulos/Subscriptions/Billing (`corporation_subscriptions`, `venue_subscriptions`, `module_catalogs`, `corporation_modules`, `venue_modules`, `plan_catalogs`, `venue_invoices`, `corporation_invoices`, `affiliate_codes`, etc.) e o módulo de Suporte (via conexão dedicada `support`).
2. **Conexões operacionais** (dinâmicas, uma por tenant ou grupo de tenants) — bancos com os dados operacionais do dia a dia: cardápio, pedidos, pagamentos, atendimentos, service locations. Nome padrão: `operation_default_1` (compartilhado entre múltiplas corporations) ou `operation_tenant_{slug}` (dedicado a uma única corporation).

```
Corporation.self_connection = "operation_default_1"   (tenant compartilhado, padrão)
Corporation.is_dedicated    = false

Corporation.self_connection = "operation_tenant_a1b2c3d4e5f6"  (tenant dedicado)
Corporation.is_dedicated    = true
```

### `TenantConnectionResolver`

Resolve, a partir de uma `Venue`, qual conexão operacional deve ser usada — com cache Redis (`venue_connection:{venue_id}`, TTL 3600s) para evitar consultas repetidas por request:

- **Tenant compartilhado:** retorna `corporation->self_connection` (ex.: `operation_default_1`).
- **Tenant dedicado:** registra dinamicamente a conexão via `Config::set('database.connections.{nome}', ...)` (mesmas credenciais base, banco diferente) e retorna o nome.
- `invalidate(Venue $venue)` / `invalidateCorporation($corporationId)` — devem ser chamados sempre que `self_connection` ou `is_dedicated` mudarem (ex.: migração de tenant compartilhado → dedicado).

### `HasOperationalConnection` (trait)

Todos os models operacionais (Menu, Category, Product, ProductVariation, ModifierGroup, ModifierOption, Combo, ComboItem, Attendance, Order, OrderItem, OrderItemModifier, Payment, PaymentItem, AttendanceChannel, KitchenStation, PreparationStatus, ServiceLocation) usam o trait `App\Models\Concerns\HasOperationalConnection`, que sobrescreve `getConnectionName()` para ler `app('operational_connection')` no momento da query — permitindo que o **mesmo model** aponte para bancos diferentes conforme a venue ativa no request, sem precisar de multi-tenancy por schema ou parâmetro explícito em cada query.

Fora do ciclo de request HTTP (jobs, seeders, comandos artisan), é necessário registrar manualmente o contexto operacional antes de instanciar esses models — ex.: `CreateUserOwnerDefinitions` faz `DB::connection($operationalConnection)->beginTransaction()` e usa transações espelhadas nas duas conexões (`saas` + operacional) para manter atomicidade cross-database.

> **Atenção:** não há foreign keys entre a conexão `saas` e as conexões operacionais — a relação `Venue → Menu`, por exemplo, é resolvida em código (venue_id como coluna simples), nunca via constraint de banco.

### Referência de Arquivos — Multi-Database Tenancy

| Arquivo | Responsabilidade |
|---|---|
| `app/Services/TenantConnectionResolver.php` | Resolve/registra a conexão operacional de uma venue, com cache Redis |
| `app/Models/Concerns/HasOperationalConnection.php` | Trait que direciona models operacionais para `app('operational_connection')` |
| `app/Http/Middleware/SetVenueContext.php` | Resolve a conexão e injeta `tenant`/`operational_connection`/`operational_is_dedicated` no container |
| `config/database.php` | Conexão `saas` fixa + conexão operacional base (`operation_default_1`) |
| `database/migrations/saas/` | Migrations do banco `saas` (identidade + billing) |
| `database/migrations/operational/` (ou raiz) | Migrations replicadas em cada banco operacional |

---

## Autorização por Role

O middleware `RequireRole` (alias `role`) lê a role do usuário na venue ativa via `currentVenueRole()`:

```php
// Exemplo de uso nas rotas
Route::middleware(['tenant', 'role:owner,general_manager'])->group(function () {
    Route::resource('settings/users', UserController::class);
});
```

### Hierarquia de acesso (operacional)

| Role | Nível de Acesso |
|---|---|
| `Owner` | Total — gerencia usuários, venue, corporation |
| `GeneralManager` | Gerencia configurações, menu, pedidos |
| `SectionManager` | Gerencia pedidos e atendimento |
| `Attendant` | Apenas atendimento (pedidos, mesas) |

---

## Troca de Venue

Um usuário pode pertencer a múltiplas venues. Para trocar a venue ativa:

```
POST /venue-select/{id}   (rota: venue.select)
    │
    └── VenueSelectorController::store()
            → verifica se o user tem entrada em user_venue para esta venue
            → atualiza users.current_venue_id
            → flash venue_switched = true (para reconexão de WebSocket)
            → redirect para /dashboard
```

O `AppLayout.vue` observa `page.props.venue_switched` e reconecta o WebSocket quando `true`.

---

## Fallback "No Venue"

Se o usuário está autenticado mas `current_venue_id = null` (ex: recém-criado via convite antes de aceitar, ou removido de todas as venues), o `SetVenueContext` redireciona para:

```
GET /no-venue   (rota: no-venue.index)
```

A página `NoVenue.vue` exibe as opções disponíveis: venues às quais o usuário pertence (para selecionar uma) ou orientação para aguardar um convite.

---

## Platform Users (Equipe Interna NeuraBar)

A equipe interna do NeuraBar usa o mesmo model `User` e o mesmo guard `web`. A distinção é feita pelo campo `profile` (cast para `ProfileEnum`):

```php
// Profiles de plataforma — acesso ao backoffice
ProfileEnum::SuperAdmin   // 'super_admin' — acesso total
ProfileEnum::Finance      // 'finance'
ProfileEnum::Registration // 'registration'
ProfileEnum::ReadOnly     // 'read_only'

// Profile operacional — acesso SaaS
ProfileEnum::Client       // 'client'
```

### Login

O login é unificado em `/login` (Fortify). Após autenticação, o `LoginResponse` detecta o `profile` e redireciona:
- `ProfileEnum::Client` → `/dashboard` (venue operacional)
- Qualquer profile de plataforma → `/backoffice` (painel interno)

```php
// app/Http/Responses/LoginResponse.php
private function resolveHome(Request $request): string
{
    $profile = $request->user()?->profile;

    if ($profile instanceof ProfileEnum && in_array($profile->value, ProfileEnum::platformProfiles(), true)) {
        return url(config('platform.path', 'backoffice'));
    }

    return config('fortify.home', '/dashboard');
}
```

### Proteção das Rotas do Backoffice

Todas as rotas `/backoffice/*` usam dois middlewares em cascata:

```
auth               → requer autenticação (guard web)
platform_profile   → verifica que profile ∈ ProfileEnum::platformProfiles()
                     caso contrário: abort(403)
```

Isso garante que qualquer `User` com `profile = Client` receba `403` se tentar acessar o backoffice, independentemente de estar autenticado.

Para restrições por profile específico dentro do backoffice:

```php
Route::middleware(['platform_role:super_admin'])->group(function () {
    // apenas SuperAdmin pode gerenciar usuários de plataforma
});
```

### Criação de Usuários de Plataforma

Use `CreateNewUserPlatform` (ou o seeder para o ambiente de desenvolvimento):

```php
$action = new CreateNewUserPlatform();
$action->create([
    'name'     => 'Nome do Agente',
    'email'    => 'agente@neurabar.com',
    'password' => 'senha_segura',
    'profile'  => ProfileEnum::Finance->value,
]);
```

Usuários de plataforma **nunca** têm entradas em `user_venue` e **nunca** devem ter `current_venue_id` preenchido.

### Referência de Arquivos — Platform

| Arquivo | Responsabilidade |
|---|---|
| `app/Enums/ProfileEnum.php` | Enum com `platformProfiles()` / `operationalProfiles()` |
| `app/Http/Responses/LoginResponse.php` | Redireciona ao backoffice ou dashboard conforme `profile` |
| `app/Http/Middleware/RequirePlatformProfile.php` | Bloqueia profiles não-plataforma com `abort(403)` |
| `app/Http/Middleware/RequirePlatformRole.php` | Restringe por profile específico dentro do backoffice |
| `app/Actions/Fortify/CreateNewUserPlatform.php` | Cria `User` com profile de plataforma |
| `app/Http/Controllers/Platform/DashboardController.php` | Dashboard do backoffice |
| `app/Http/Controllers/Platform/PlatformUserController.php` | CRUD de usuários de plataforma |
| `app/Http/Controllers/Platform/CorporationController.php` | Gestão de corporations |
| `app/Http/Controllers/Platform/PlanCatalogController.php` | Catálogo de planos |
| `config/platform.php` | Prefixo da URL do backoffice (`PLATFORM_PATH`) |

---

---

## Arquitetura do Menu

### Visão Geral

```
Venue
  └── Menu (1 ou mais menus por venue)
        └── Category (categorias ordenadas por sort_order)
              └── Product (produtos com preço base, station, descrição, active)
                    ├── ProductVariation (variações: tamanho, sabor — têm preço próprio)
                    └── ModifierGroup  ◄─── (pivot) product_modifier_group
                          └── ModifierOption (opção com extra_price, active)

Venue
  └── Combo (combos com preço fixo)
        └── ComboItem (product_id + variation_id + quantity)
```

### Entidades e Tabelas

| Entidade | Tabela | Escopo |
|---|---|---|
| `Menu` | `menus` | `venue_id` |
| `Category` | `menu_categories` | via `menu_id` |
| `Product` | `products` | via `category.menu.venue_id` |
| `ProductVariation` | `product_variations` | via `product_id` |
| `ModifierGroup` | `modifier_groups` | `venue_id` |
| `ModifierOption` | `modifier_options` | via `modifier_group_id` |
| `Combo` | `combos` | `venue_id` |
| `ComboItem` | `combo_items` | via `combo_id` |

### Regras de Negócio

**Modificadores**
- Um `ModifierGroup` pertence diretamente à venue (não ao produto) para reuso entre produtos.
- A associação produto ↔ grupo é feita pela pivot `product_modifier_group`.
- `required = true` → o atendente **deve** selecionar ao menos uma opção do grupo antes de confirmar o item no pedido.
- `multiple_selection = true` → checkboxes (várias opções); `false` → radio (uma opção).
- `extra_price` da opção é capturado no momento do pedido em `order_item_modifiers.extra_price_snapshot` — imutável após o registro.

**Variações**
- Quando um produto tem variações, o preço da variação prevalece sobre o `product.price`.
- Se `variation_id` está preenchido no `OrderItem`, o preço base usado é `product_variations.price`.

**Combos**
- Um combo tem preço fixo (`combos.price`) independente dos produtos que o compõem.
- Ao registrar um pedido, cada `ComboItem` vira um `OrderItem` separado, todos com o mesmo `combo_id` para agrupamento visual.
- Itens de combo não aceitam modificadores no fluxo atual (são bundled fixos).

### Fluxo de Pedido com Modificadores

```
Atendente abre produto no Taker.vue
    │
    ├── Tem variações?  → Seleciona variação (radio)
    │
    ├── Tem modifier_groups?
    │       ├── required=true, multiple_selection=false → radio, obrigatório
    │       ├── required=true, multiple_selection=true  → checkboxes, obrigatório
    │       └── required=false → opcional
    │
    └── Adiciona ao cart com modifiers[]={modifier_option_id, name}
          ↓
PlaceOrderAction::execute()
    → cria OrderItem com unit_price (snapshot do preço)
    → para cada modifier_option_id:
          cria OrderItemModifier com extra_price_snapshot (snapshot do extra_price)
```

### Exibição no KDS

O `KdsController` carrega os `OrderItem` abertos com:
- `modifiers.modifierOption` — lista de modificadores aplicados
- `combo` — combo ao qual o item pertence (se houver)
- `preparationStatus` — status de preparo atual

O `Kds.vue` exibe, por item:
- Nome do produto + variação
- Badge "🍱 NomeDoCombo" quando `item.combo` não é nulo
- Lista de modificadores com nome e extra price
- Notas do item

### Referência de Arquivos do Menu

| Arquivo | Responsabilidade |
|---|---|
| `app/Models/Menu/Menu.php` | Model do menu |
| `app/Models/Menu/Category.php` | Categoria com `sort_order` |
| `app/Models/Menu/Product.php` | Produto com BelongsToVenue e relações |
| `app/Models/Menu/ProductVariation.php` | Variações de produto |
| `app/Models/Menu/ModifierGroup.php` | Grupo de modificadores (venue-scoped) |
| `app/Models/Menu/ModifierOption.php` | Opção com `extra_price` |
| `app/Models/Menu/Combo.php` | Combo com BelongsToVenue |
| `app/Models/Menu/ComboItem.php` | Linha do combo com product + variation + quantity |
| `app/Models/Orders/OrderItem.php` | Item de pedido com `combo_id`, `variation_id`, `unit_price` |
| `app/Models/Orders/OrderItemModifier.php` | Modificador aplicado com `extra_price_snapshot` |
| `app/Http/Controllers/Menu/ProductController.php` | CRUD de produtos + sync de modifier groups |
| `app/Http/Controllers/Menu/ModifierGroupController.php` | CRUD de grupos |
| `app/Http/Controllers/Menu/ModifierOptionController.php` | CRUD de opções |
| `app/Http/Controllers/Menu/ComboController.php` | CRUD de combos |
| `app/Actions/Menu/CreateComboAction.php` | Criação transacional de combo com items |
| `app/Actions/Menu/UpdateComboAction.php` | Atualização transacional (delete + recreate items) |
| `app/Actions/Orders/PlaceOrderAction.php` | Registra pedido com modifiers e combos |
| `resources/js/Pages/Menu/Products.vue` | Tela de produtos com filtro por categoria |
| `resources/js/Pages/Menu/Modifiers.vue` | CRUD completo de grupos e opções |
| `resources/js/Pages/Menu/Combos.vue` | CRUD de combos com itens dinâmicos |
| `resources/js/Pages/Orders/Taker.vue` | Order taker com seleção de modifiers e combos |
| `resources/js/Pages/Kitchen/Kds.vue` | KDS com exibição de modifiers e badge de combo |

---

## Referência de Arquivos

| Arquivo | Responsabilidade |
|---|---|
| `app/Models/User.php` | Model do usuário SaaS |
| `app/Models/UserVenue.php` | Pivot model com cast de role |
| `app/Models/VenueInvitation.php` | Model do convite com `isExpired()` / `isAccepted()` |
| `app/Models/Tenant/Venue.php` | Model da venue |
| `app/Models/Tenant/Corporation.php` | Model da corporation com `owner_id` |
| `app/Enums/UserRole.php` | Enum com `platformRoles()` / `operationalRoles()` |
| `app/Http/Middleware/SetVenueContext.php` | Injeta tenant no container |
| `app/Http/Middleware/RequireRole.php` | Autorização por role operacional |
| `app/Actions/Fortify/CreateNewUser.php` | Registro de novo usuário |
| `app/Actions/Fortify/CreateUserOwnerDefinitions.php` | Cria corporation + venue para o owner |
| `app/Actions/Settings/CreateUserAction.php` | Cria usuário dentro de uma venue |
| `app/Actions/Settings/InviteUserToVenueAction.php` | Envia convite por email |
| `app/Actions/Settings/AcceptVenueInvitationAction.php` | Aceita convite e faz attach no pivot |
| `app/Actions/Settings/DeleteUserAction.php` | Remove usuário do pivot da venue |
| `app/Http/Controllers/InvitationController.php` | Exibe e aceita convites |
| `app/Http/Controllers/NoVenueController.php` | Fallback sem venue |
| `app/Http/Controllers/VenueSelectorController.php` | Troca de venue ativa |
| `database/migrations/*_create_user_venue_table.php` | Pivot many-to-many |
| `database/migrations/*_create_venue_invitations_table.php` | Tabela de convites |
| `database/migrations/*_add_foreign_keys_to_users_table.php` | FK current_venue_id + corporations.owner_id |

---

## Service Locations (Locais de Atendimento)

### Visão Geral

`ServiceLocation` representa um local físico de atendimento dentro de uma venue — uma mesa, balcão, área ou ponto de retirada. Cada local pode ter um canal de atendimento padrão e um QR code para uso pelo cliente final.

```
Venue
  └── ServiceLocation (mesas, balcões, áreas...)
        ├── type: ServiceLocationType
        ├── active: boolean
        ├── default_attendance_channel_id → AttendanceChannel
        └── qr_token: string nullable
```

### Enum `ServiceLocationType`

```php
ServiceLocationType::Table     // 'table'
ServiceLocationType::Bar       // 'bar'
ServiceLocationType::Area      // 'area'
ServiceLocationType::Delivery  // 'delivery'
ServiceLocationType::Takeaway  // 'takeaway'
```

### Tabela `service_locations`

| Coluna | Tipo | Descrição |
|---|---|---|
| `id` | UUID PK | Identificador único |
| `venue_id` | UUID FK | → `venues.id` (cascade delete) |
| `name` | string | Nome do local (ex: "Mesa 7") |
| `type` | varchar | Valor do enum `ServiceLocationType` |
| `active` | boolean default true | Se falso, não aparece no order taker |
| `default_attendance_channel_id` | UUID FK nullable | → `attendance_channels.id` (null on delete) |
| `qr_token` | string unique nullable | Token codificado em base64 para o QR code |

### QR Code — Geração e Estrutura do Token

O `qr_token` é gerado por `GenerateQrTokenAction` e codifica um JSON com os IDs relevantes:

```php
$payload = [
    'v' => $location->venue_id,               // venue
    'l' => $location->id,                     // service location
    'c' => $location->default_attendance_channel_id, // canal padrão (ou null)
];
$token = rtrim(base64_encode(json_encode($payload)), '=');
```

O token é **regenerável** — ao chamar `POST /settings/service-locations/{id}/qr` novamente, o token é substituído. O endpoint `GET /settings/service-locations/{id}/qr-pdf` gera um PDF (via `barryvdh/laravel-dompdf`) com o QR code renderizado usando `endroid/qr-code` v6.

**Nota de compatibilidade (`endroid/qr-code` v6):** a API fluente `Builder::create()->...->build()` foi removida. Use o construtor nomeado:

```php
$qrResult = (new Builder(
    writer: new PngWriter,
    data: $url,
    encoding: new Encoding('UTF-8'),
    errorCorrectionLevel: ErrorCorrectionLevel::High,
    size: 400,
    margin: 10,
    roundBlockSizeMode: RoundBlockSizeMode::Margin,
))->build();
```

### Rotas

```
GET    /settings/service-locations                  → index (lista)
POST   /settings/service-locations                  → store
PUT    /settings/service-locations/{location}       → update
DELETE /settings/service-locations/{location}       → destroy
POST   /settings/service-locations/{location}/qr   → generateQr (gera/regenera token)
GET    /settings/service-locations/{location}/qr-pdf → qrPdf (download PDF)
```

### Referência de Arquivos — Service Locations

| Arquivo | Responsabilidade |
|---|---|
| `app/Models/Settings/ServiceLocation.php` | Model com cast de `type` para `ServiceLocationType` |
| `app/Enums/ServiceLocationType.php` | Enum dos tipos de local |
| `app/Actions/Settings/CreateServiceLocationAction.php` | Criação do local |
| `app/Actions/Settings/UpdateServiceLocationAction.php` | Atualização do local |
| `app/Actions/Settings/DeleteServiceLocationAction.php` | Exclusão do local |
| `app/Actions/Settings/GenerateQrTokenAction.php` | Gera e persiste o `qr_token` |
| `app/Http/Controllers/Settings/ServiceLocationController.php` | Controller com CRUD + QR |
| `app/Http/Requests/Settings/StoreServiceLocationRequest.php` | Validação de criação |
| `app/Http/Requests/Settings/UpdateServiceLocationRequest.php` | Validação de atualização |
| `resources/js/Pages/Settings/ServiceLocations.vue` | UI de gerenciamento de locais |
| `resources/views/pdf/service-location-qr.blade.php` | Template do PDF do QR code |
| `database/migrations/2026_05_22_184250_create_service_locations_table.php` | Criação da tabela |
| `database/migrations/2026_05_29_182149_add_qr_fields_to_service_locations_table.php` | Adiciona `default_attendance_channel_id` e `qr_token` |

---

## Módulos, Subscriptions e Billing

> Documentação completa em [current_feat/module-subscription-architecture.md](current_feat/module-subscription-architecture.md). Esta seção resume o modelo para referência rápida.

### Visão Geral

O NeuraBar vende funcionalidades como **módulos contratáveis**. Uma `Corporation` habilita módulos com um preço unitário negociado (`CorporationModule`); cada `Venue` ativa individualmente quais desses módulos usa (`VenueModule`). A cobrança é sempre **proporcional ao número de venues** que ativam o módulo.

```
Corporation ── billing_mode (per_venue | unified) ──► CorporationSubscription (status, trial, grace period)
   │
   ├── CorporationModule (module_code, custom_monthly_price, status)
   │        │
   │        ▼
   └── Venue ── VenueModule (module_code, quantity, status)
          │
          ├── VenueSubscription (base_value + modules_value + metered_value = total_value)
          ├── VenueUsageRecord (consumo medido por módulo/período, sempre por venue)
          └── VenueInvoice (fatura mensal; agrupada em CorporationInvoice no modo unified)
```

### Entidades (conexão `saas`)

| Entidade | Tabela | Descrição |
|---|---|---|
| `ModuleCatalog` | `module_catalogs` | Catálogo de módulos vendáveis: `code`, `billing_type` (fixed/metered/hybrid), `base_monthly_price`, `dependencies`, `required_roles` |
| `PlanCatalog` | `plan_catalogs` | Pacotes pré-montados por venue (`plan_type` shared/dedicated, `included_modules` — usado só na criação administrativa/seed, não no wizard) |
| `CorporationSubscription` | `corporation_subscriptions` | Contrato comercial da corporation: `billing_mode`, `status`, `billing_day`, `grace_period_days`, `trial_ends_at` |
| `VenueSubscription` | `venue_subscriptions` | Faturamento por venue: `base_value`, `modules_value`, `metered_value`, `total_value`, `status` |
| `CorporationModule` | `corporation_modules` | Módulo habilitado na corporation + preço unitário negociado |
| `VenueModule` | `venue_modules` | Módulo ativo em uma venue específica + `quantity` licenciada |
| `ModuleUsageTier` | `module_usage_tiers` | Faixas de preço/limite incluso para módulos `metered`/`hybrid` |
| `VenueUsageRecord` | `venue_usage_records` | Consumo mensal por venue+módulo+período, com cálculo de excedente |
| `VenueInvoice` / `CorporationInvoice` | `venue_invoices` / `corporation_invoices` | Faturas (mensais); `is_finalized` trava recálculo após emissão |
| `PaymentAttempt` | `payment_attempts` | Registro polimórfico de webhooks de gateway (idempotência) |
| `AffiliateCode` | `affiliate_codes` | Código de indicação, propagado em Corporation/Venue/Subscriptions/Invoices |
| `CorporationDiscount` | `corporation_discounts` | Descontos negociados (fixo/percentual) por corporation |

### Enums principais

`BillingMode` (`per_venue`, `unified`) · `ModuleBillingType` (`fixed`, `metered`, `hybrid`) · `ModuleStatus` (`trial`, `active`, `suspended`, `canceled`) · `SubscriptionStatus` (`trial`, `active`, `past_due`, `suspended`, `canceled`) · `ModuleCode` (`menu`, `kds`, `taker`, `direct_waiter`, `delivery`, `production_dashboard`, `financial_dashboard`, `direct_print`, `fiscal_note`, `voice_command` — cada um com `dependsOn()` e `label()`).

### Permissionamento por Módulo

O acesso a uma funcionalidade passa por 4 checagens: **tenant ativo** (`SetVenueContext`) → **subscription em dia** (`BillingStatusService::isBlocked()`, respeitando `billing_mode` e grace period) → **módulo contratado** (`RequireModule`) → **role operacional** (`RequireRole`). Não existe tabela de permissão por módulo — os roles permitidos são declarados diretamente nos grupos de rota:

```php
Route::middleware(['tenant', 'module:kds', 'role:owner,general_manager,section_manager,attendant'])
    ->prefix('kitchen/kds')->group(function () { ... });
```

`User::canAccessModule(ModuleCode $module)` replica a mesma lógica para checagens manuais fora de rotas (controllers condicionais, jobs). O middleware alias `module` → `App\Http\Middleware\RequireModule` é registrado em `bootstrap/app.php` junto com `tenant` e `role`.

### Ciclo de Vida da Subscription

```
trial ──(trial_ends_at)──► past_due ──(grace_period_days expira)──► suspended ──(cancelamento)──► canceled
  │                              │
  └────(pagamento confirmado)────┴────────────────────► active ──(fatura vence)──► past_due
```

- **Trial** inicia automaticamente na criação da `Corporation` (`trial_ends_at = now + billing.trial_days`).
- **Grace period** (padrão 3 dias, `CorporationSubscription.grace_period_days`) mantém acesso liberado durante `past_due` — só `suspended`/`canceled` bloqueiam.
- Jobs diários: `ExpireTrialsJob`, `SuspendOverdueSubscriptionsJob`, `MarkInvoicesOverdueJob`, `NotifyTrialEndingSoonJob`, `RecalculateSubscriptionJob`, `GenerateInvoicesJob`, `RecordModuleUsageJob` (todos em `app/Jobs/Billing/`).
- Sem proration: ativação/desativação de módulo no meio do ciclo cobra/isenta o mês inteiro.

### Cálculo de Fatura (`SubscriptionCalculator`)

`total_value = base_value + modules_value + metered_value (+ dedicated_surcharge)`. `modules_value` soma `custom_monthly_price` (ou `base_monthly_price` do catálogo) de cada módulo ativo × `quantity`. `metered_value` usa `ModuleUsageTier` para calcular excedente sobre `included_quantity` — sempre por venue, mesmo no modo `unified`. Consumo é registrado via listeners de eventos operacionais (ex.: `RecordKdsUsage`, `RecordSignalUsage`, `RecordOrderModuleUsage`) que disparam `RecordModuleUsageJob` (idempotente via `updateOrCreate` por `venue_id`+`module_code`+`period`).

### Cache

`VenueModuleCache` (por venue) e `CorporationModuleCache` (por corporation) usam `Cache::remember` com tags Redis (`Cache::tags(['venue', $id])`), TTL 3600s. Toda action que muda `CorporationModule`/`VenueModule` deve chamar `::forget()` dentro da mesma transação. `BillingStatusService::isBlocked()` também tem cache de 60s, invalidado nas transições de status.

### Referência de Arquivos — Módulos, Subscriptions e Billing

| Arquivo | Responsabilidade |
|---|---|
| `app/Enums/ModuleCode.php`, `BillingMode.php`, `ModuleBillingType.php`, `ModuleStatus.php`, `SubscriptionStatus.php` | Enums do domínio |
| `app/Models/Tenant/ModuleCatalog.php`, `PlanCatalog.php`, `CorporationSubscription.php`, `VenueSubscription.php`, `CorporationModule.php`, `VenueModule.php`, `ModuleUsageTier.php`, `VenueUsageRecord.php`, `VenueInvoice.php`, `CorporationInvoice.php`, `PaymentAttempt.php`, `AffiliateCode.php`, `CorporationDiscount.php` | Models (conexão `saas`) |
| `app/Http/Middleware/RequireModule.php` | Verifica billing + módulo ativo + dependências |
| `app/Services/Billing/BillingStatusService.php` | Regra de bloqueio (`isBlocked`) respeitando `billing_mode` e grace period, com cache |
| `app/Services/Billing/SubscriptionCalculator.php` | Cálculo de `VenueSubscription`/fatura (base + módulos + medido) |
| `app/Services/VenueModuleCache.php`, `CorporationModuleCache.php` | Cache de módulos ativos com invalidação por tags |
| `app/Actions/Platform/EnableCorporateModuleAction.php`, `DisableCorporateModuleAction.php` | Habilita/desabilita módulo na corporation |
| `app/Actions/Platform/ActivateVenueModuleAction.php`, `DeactivateVenueModuleAction.php` | Ativa/desativa módulo em uma venue |
| `app/Actions/Platform/CreateCorporationAction.php`, `AssignPlanToCorporationAction.php`, `UpdateCorporationSubscriptionAction.php` | Gestão administrativa de corporation/plano/subscription (backoffice) |
| `app/Jobs/Billing/ExpireTrialsJob.php`, `SuspendOverdueSubscriptionsJob.php`, `MarkInvoicesOverdueJob.php`, `NotifyTrialEndingSoonJob.php`, `RecalculateSubscriptionJob.php`, `GenerateInvoicesJob.php`, `RecordModuleUsageJob.php` | Jobs diários/assíncronos de billing |
| `app/Listeners/Billing/RecordKdsUsage.php`, `RecordSignalUsage.php`, `RecordOrderModuleUsage.php` | Listeners que traduzem eventos operacionais em consumo medido |
| `app/Http/Controllers/Platform/CorporationController.php`, `PlanAssignmentController.php`, `SubscriptionController.php`, `CorporationModuleController.php`, `VenueModuleController.php`, `InvoiceController.php`, `ManualInvoiceController.php`, `CorporationDiscountController.php` | Backoffice: gestão de corporations, planos, módulos, faturas e descontos |
| `resources/js/Composables/useModules.ts` | Composable Vue para checar módulos ativos via shared prop `tenant.modules` |
| `resources/js/Pages/Platform/Corporations/Edit.vue` | UI do backoffice para plano, assinatura, módulos, descontos, faturas e afiliado |
| `config/billing.php` | `trial_days`, `grace_period_days`, `default_billing_day`, `currency`, `default_plan_code` |
| `docs/current_feat/module-subscription-architecture.md` | Documentação técnica completa (entidades, enums, cálculo, ciclo de vida) |

---

## Guest Hub

### Visão Geral

O Guest Hub é a interface pública acessada pelo cliente final ao escanear o QR code de um `ServiceLocation`. Não requer autenticação da venue — o contexto é inferido a partir do `qr_token` embutido na URL.

```
GET /g/{token}   → GuestHubController::show()   → Guest/Hub.vue
```

### Decodificação do Token

`GuestTokenService::decode(string $token)` realiza o processo inverso de `GenerateQrTokenAction`:

1. Decodifica o base64 → JSON com `v`, `l`, `c`.
2. Carrega `Venue`, `ServiceLocation` e `AttendanceChannel` (quando presentes).
3. Retorna o array `['venue', 'serviceLocation', 'attendanceChannel']`.

### Sessão de Guest

`GuestTokenService::resolveSession(Request $request, Venue $venue)` resolve ou cria uma sessão de guest anônima associada ao IP/sessão do visitante. A sessão persiste dados como `geolocation_verified`.

### Verificação de Geolocalização

Quando `venue.require_geolocation = true`, o Hub solicita a posição GPS do cliente antes de liberar ações. O `POST /g/{token}/verify-location` usa `GeolocationService` para calcular a distância entre o cliente e as coordenadas da venue (`venues.latitude`, `venues.longitude`). Se dentro do raio permitido, marca `geolocation_verified = true` na sessão.

### Sinalizações (Chamada de Atendente)

O cliente pode enviar um sinal ao atendente via `POST /g/{token}/signal`, que dispara o evento `GuestSignaled`. Esse evento é transmitido via WebSocket para os atendentes da venue.

### Fluxo Completo

```
Cliente escaneia QR
    │
    └── GET /g/{token}
            │
            GuestTokenService::decode($token)
            → carrega Venue, ServiceLocation, AttendanceChannel
            │
            GuestTokenService::resolveSession()
            → recupera ou cria sessão anônima
            │
            Inertia::render('Guest/Hub', [...])
                ├── token, venue (id, name, logo_url, require_geolocation)
                ├── serviceLocation (id, name, type)
                ├── attendanceChannel (id, name)
                ├── hasSession: bool
                └── geolocationVerified: bool

Cliente chama atendente
    └── POST /g/{token}/signal
            → valida sessão ativa (abort 403 se não há sessão)
            → dispara GuestSignaled(venueId, locationName, message, signalOnly)
```

### Rotas do Guest Hub

```
GET  /g/{token}                   → show (exibe o Hub)
POST /g/{token}/signal            → signal (chama atendente)
POST /g/{token}/verify-location   → verifyLocation (valida GPS)
```

> Essas rotas **não usam** o middleware `tenant` nem requerem autenticação de `User`.

### Referência de Arquivos — Guest Hub

| Arquivo | Responsabilidade |
|---|---|
| `app/Http/Controllers/Guest/GuestHubController.php` | Controller do hub público |
| `app/Services/GuestTokenService.php` | Decodificação do token e resolução de sessão |
| `app/Services/GeolocationService.php` | Cálculo de distância e verificação de raio |
| `app/Events/Orders/GuestSignaled.php` | Evento de sinalização transmitido via WebSocket |
| `app/Http/Requests/Guest/StoreGuestSignalRequest.php` | Validação do payload de sinalização |
| `resources/js/Pages/Guest/Hub.vue` | Interface pública do cliente |

---

## Módulo de Suporte

### Visão Geral

O módulo de suporte oferece um sistema completo de chamados (tickets) entre clientes SaaS e a equipe interna do NeuraBar, além de uma base de tutoriais e manuais em Markdown. Toda a persistência é isolada em um banco de dados dedicado (`laravel_support`), acessado pela conexão Eloquent `support` — sem chaves estrangeiras cruzadas com o banco principal.

```
User (banco principal)
  │  user_id (UUID sem FK)
  ▼
Ticket ──── TicketMessage ──── TicketAttachment
  │              │
  │         is_internal (notas internas — visíveis apenas ao backoffice)
  │
  ├── category_id → TicketCategory
  ├── assigned_to (UUID sem FK → User com profile de plataforma)
  └── TicketRating (avaliação 1–5 estrelas após resolução)

TutorialCategory
  └── Tutorial (conteúdo Markdown, slug único, publicado/rascunho)
```

### Banco de Dados Isolado

A conexão `support` aponta para o banco `laravel_support` (configurável via `DB_SUPPORT_DATABASE`). Todos os models do módulo declaram `protected $connection = 'support'`. As referências a entidades do banco principal (`user_id`, `venue_id`, `assigned_to`) são armazenadas como UUIDs simples, sem constraints de FK — o enriquecimento é feito no momento da consulta.

Para executar as migrations do módulo, use o comando Artisan dedicado:

```bash
php artisan support:migrate           # aplica migrations pendentes
php artisan support:migrate --fresh   # recria as tabelas (wipe + migrate)
php artisan support:migrate --seed    # aplica e semeia dados iniciais
```

### Tabelas (banco `laravel_support`)

| Tabela | Descrição |
|---|---|
| `support_ticket_categories` | Categorias de chamado (ex: Financeiro, Técnico) |
| `support_tickets` | Chamados com status, prioridade e referências cross-DB |
| `support_ticket_messages` | Mensagens do thread, com flag `is_internal` |
| `support_ticket_attachments` | Arquivos anexados a mensagens (máx. 5 × 10 MB) |
| `support_ticket_ratings` | Avaliação 1–5 estrelas do chamado após resolução |
| `support_tutorial_categories` | Categorias de tutoriais com `position` |
| `support_tutorials` | Tutoriais em Markdown com slug único e flag `published` |

### Enums

| Enum | Valores |
|---|---|
| `TicketStatus` | `Open`, `InProgress`, `Resolved`, `Closed` |
| `TicketPriority` | `Low`, `Medium`, `High`, `Urgent` |
| `TicketAuthorType` | `User`, `PlatformUser` |

### Ciclo de Vida de um Chamado

```
Cliente abre chamado (POST /support/tickets)
    │   OpenTicketAction → cria Ticket + TicketMessage + anexos
    │   → TicketOpenedNotification → email backoffice
    │
    ├── Agente responde (POST /backoffice/support/tickets/{id}/messages)
    │       AgentReplyToTicketAction → cria TicketMessage (author_type=platform_user)
    │       → is_internal=true? → nota interna, sem notificação ao cliente
    │       → is_internal=false? → NewMessageNotification → email do cliente
    │
    ├── Cliente responde (POST /support/tickets/{id}/messages)
    │       ReplyToTicketAction → cria TicketMessage (author_type=user)
    │       → NewMessageNotification → email backoffice
    │
    ├── Agente resolve (PUT /backoffice/support/tickets/{id} status=resolved)
    │       UpdateTicketStatusAction → seta closed_at, status=Resolved
    │       → TicketResolvedNotification → email cliente com link de avaliação
    │
    └── Cliente avalia (POST /support/tickets/{id}/rate)
            RateTicketAction → cria/atualiza TicketRating (score 1–5)
```

### Notas Internas

Mensagens com `is_internal = true` são visíveis **apenas** para agentes do backoffice. O frontend cliente (`Support/Tickets/Show.vue`) nunca recebe essas mensagens — o controller filtra via `where('is_internal', false)`. No backoffice, notas internas são renderizadas com fundo amarelo/âmbar e badge "Nota Interna".

### Anexos

Arquivos são armazenados no disco `local` em `support/attachments/{ticket_id}/`. O acesso é controlado: o endpoint `GET /support/attachments/{attachmentId}` verifica se o usuário autenticado é dono do chamado antes de transmitir o arquivo via `Storage::download()`.

### Tutoriais (Markdown)

Os tutoriais são escritos em Markdown e renderizados em HTML no frontend. A geração do `slug` é feita automaticamente por `Tutorial::generateSlug(string $title)`, que garante unicidade adicionando um sufixo contador se necessário. Imagens destacadas ficam no disco `public` em `support/tutorials/`.

### Notificações (Queued)

Todas as notificações implementam `ShouldQueue` e usam o canal `mail`.

| Notificação | Acionador | Destinatário |
|---|---|---|
| `TicketOpenedNotification` | Chamado aberto | Email do backoffice (`config('support.email')`) |
| `NewMessageNotification` | Nova mensagem no thread | Cliente OU backoffice (detectado por tipo) |
| `TicketResolvedNotification` | Status → Resolved | Cliente (inclui link para avaliação) |

### Rotas

**Client (auth:sanctum + verified + tenant)**

```
GET    /support                              → support.dashboard
GET    /support/tickets                      → support.tickets.index
GET    /support/tickets/create               → support.tickets.create
POST   /support/tickets                      → support.tickets.store
GET    /support/tickets/{ticketId}           → support.tickets.show
POST   /support/tickets/{ticketId}/messages  → support.tickets.messages.store
POST   /support/tickets/{ticketId}/close     → support.tickets.close
POST   /support/tickets/{ticketId}/rate      → support.tickets.rate
GET    /support/tutorials                    → support.tutorials.index
GET    /support/tutorials/{slug}             → support.tutorials.show
GET    /support/attachments/{attachmentId}   → support.attachments.show
```

**Backoffice (auth + platform_profile)**

```
GET    /backoffice/support/tickets                              → platform.support.tickets.index
GET    /backoffice/support/tickets/{ticketId}                  → platform.support.tickets.show
PUT    /backoffice/support/tickets/{ticketId}                  → platform.support.tickets.update
POST   /backoffice/support/tickets/{ticketId}/messages         → platform.support.tickets.messages.store
GET    /backoffice/support/tutorials                           → platform.support.tutorials.index
GET    /backoffice/support/tutorials/create                    → platform.support.tutorials.create
POST   /backoffice/support/tutorials                          → platform.support.tutorials.store
GET    /backoffice/support/tutorials/{tutorialId}/edit         → platform.support.tutorials.edit
PUT    /backoffice/support/tutorials/{tutorialId}              → platform.support.tutorials.update
DELETE /backoffice/support/tutorials/{tutorialId}              → platform.support.tutorials.destroy
POST   /backoffice/support/tutorials/{tutorialId}/toggle-published → platform.support.tutorials.toggle-published
```

### Referência de Arquivos — Módulo de Suporte

| Arquivo | Responsabilidade |
|---|---|
| `app/Enums/Support/TicketStatus.php` | Status do chamado com métodos `label()`, `color()`, `openStatuses()` |
| `app/Enums/Support/TicketPriority.php` | Prioridade com `label()` e `color()` |
| `app/Enums/Support/TicketAuthorType.php` | Tipo do autor da mensagem |
| `app/Models/Support/Ticket.php` | Model principal do chamado |
| `app/Models/Support/TicketCategory.php` | Categoria de chamado |
| `app/Models/Support/TicketMessage.php` | Mensagem do thread com `isFromPlatform()` |
| `app/Models/Support/TicketAttachment.php` | Anexo com `url()` |
| `app/Models/Support/TicketRating.php` | Avaliação pós-resolução |
| `app/Models/Support/TutorialCategory.php` | Categoria de tutorial com `publishedTutorials()` |
| `app/Models/Support/Tutorial.php` | Tutorial com `generateSlug()` e `scopePublished()` |
| `app/Actions/Support/OpenTicketAction.php` | Abre chamado + primeira mensagem + anexos + notificação |
| `app/Actions/Support/ReplyToTicketAction.php` | Resposta do cliente ao chamado |
| `app/Actions/Support/AgentReplyToTicketAction.php` | Resposta do agente (suporta `is_internal`) |
| `app/Actions/Support/UpdateTicketStatusAction.php` | Muda status, seta `closed_at`, notifica se Resolved |
| `app/Actions/Support/AssignTicketAction.php` | Atribui chamado a um agente |
| `app/Actions/Support/RateTicketAction.php` | Cria/atualiza avaliação do chamado |
| `app/Actions/Support/StoreAttachmentsAction.php` | Persiste arquivos no disco `local` |
| `app/Actions/Support/ManageTutorialAction.php` | CRUD de tutoriais com upload de imagem |
| `app/Policies/Support/TicketPolicy.php` | Autorização por ownership e status do chamado |
| `app/Policies/Support/TutorialPolicy.php` | Autorização de acesso a tutoriais publicados |
| `app/Http/Controllers/Support/SupportDashboardController.php` | Dashboard do cliente |
| `app/Http/Controllers/Support/TicketController.php` | CRUD de chamados (cliente) |
| `app/Http/Controllers/Support/TicketMessageController.php` | Resposta do cliente |
| `app/Http/Controllers/Support/TicketRatingController.php` | Avaliação do cliente |
| `app/Http/Controllers/Support/TutorialController.php` | Listagem e exibição de tutoriais + download de anexo |
| `app/Http/Controllers/Backoffice/Support/BackofficeTicketController.php` | Gestão de chamados (backoffice) |
| `app/Http/Controllers/Backoffice/Support/BackofficeTutorialController.php` | CRUD de tutoriais (backoffice) |
| `app/Notifications/Support/TicketOpenedNotification.php` | Email para backoffice ao abrir chamado |
| `app/Notifications/Support/NewMessageNotification.php` | Email bidirecional de nova mensagem |
| `app/Notifications/Support/TicketResolvedNotification.php` | Email ao cliente com link de avaliação |
| `app/Console/Commands/SupportMigrate.php` | Comando `support:migrate` com `--fresh` e `--seed` |
| `database/migrations/support/` | 7 migrations do banco `laravel_support` |
| `resources/js/Pages/Support/Dashboard.vue` | Dashboard do cliente com chamados e tutoriais |
| `resources/js/Pages/Support/Tickets/Index.vue` | Lista paginada de chamados do cliente |
| `resources/js/Pages/Support/Tickets/Create.vue` | Formulário de abertura de chamado |
| `resources/js/Pages/Support/Tickets/Show.vue` | Thread do chamado com resposta e avaliação |
| `resources/js/Pages/Support/Tutorials/Index.vue` | Categorias e cards de tutoriais |
| `resources/js/Pages/Support/Tutorials/Show.vue` | Artigo Markdown renderizado com sidebar |
| `resources/js/Pages/Backoffice/Support/Tickets/Index.vue` | Lista de chamados do backoffice com filtros |
| `resources/js/Pages/Backoffice/Support/Tickets/Show.vue` | Detalhe do chamado para agentes |
| `resources/js/Pages/Backoffice/Support/Tutorials/Index.vue` | Listagem e gerenciamento de tutoriais |
| `resources/js/Pages/Backoffice/Support/Tutorials/Form.vue` | Formulário compartilhado criar/editar tutorial |
| `tests/Feature/Support/TicketTest.php` | Testes de abertura, resposta, fechamento e avaliação |
| `tests/Feature/Support/TutorialTest.php` | Testes de CRUD de tutoriais e acesso público |

