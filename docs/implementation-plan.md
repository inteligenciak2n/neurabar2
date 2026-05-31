# NeuraBar — Plano de Implementação SaaS

**Versão:** 1.1 · **Data:** 22 de maio de 2026

> Documento de referência para construção incremental do NeuraBar: SaaS multi-tenant para gestão de bares e restaurantes de pequeno e médio porte.

---

## Stack Confirmada

| Camada | Tecnologia |
|--------|-----------|
| Backend | Laravel 12 / PHP 8.2 |
| Frontend | Vue 3 + Inertia.js v2 |
| Auth | Fortify + Sanctum (já instalados e configurados) |
| Banco | PostgreSQL |
| Cache / Filas / Sessions | Redis |
| WebSockets | Soketi (Pusher-compatível) + Laravel Echo |
| Estilos | Tailwind CSS v3 |
| Base UI | Jetstream components (já existentes em `resources/js/Components/`) |

---

## Convenções de Arquitetura

### Namespaces por Tema

**Controllers** → `App\Http\Controllers\{Theme}\`

| Theme | Responsabilidade |
|-------|-----------------|
| `Auth` | Login, logout, troca de venue ativa |
| `Menu` | Categorias, produtos, variações, modificadores, combos |
| `Orders` | Attendances, orders, itens |
| `Kitchen` | KDS, atualização de status |
| `Payment` | Cálculo, divisão, registro de pagamento |
| `Settings` | Dashboard, configurações do venue, setores, usuários |
| `Guest` | Cardápio público, chama garçom, acompanhar pedido |
| `Corporation` | Painel multi-venue |
| `Platform` | Backoffice NeuraBar (super admin) |

**Models** → `App\Models\{Theme}\`

| Theme | Models |
|-------|--------|
| `Tenant` | `Corporation`, `Venue` |
| `Settings` | `VenueSettings`, `KitchenStation`, `PreparationStatus`, `ServiceLocation` |
| `Menu` | `Menu`, `Category`, `Product`, `ProductVariation`, `ModifierGroup`, `ModifierOption`, `Combo`, `ComboItem` |
| `Orders` | `Attendance`, `Order`, `OrderItem`, `OrderItemModifier` |
| `Payment` | `Payment`, `PaymentItem` |
| `Platform` | `PlanCatalog`, `PlatformUser` |

**Actions** → `App\Actions\{Theme}\` — operações atômicas sobre um recurso (criar, atualizar, excluir)

**Services** → `App\Services\{Theme}\` — orquestração de múltiplas operações ou integrações externas

**Requests** → `App\Http\Requests\{Theme}\`

**Policies** → `App\Policies\{Theme}\`

**Events** → `App\Events\{Theme}\`

**Jobs** → `App\Jobs\{Theme}\`

### Regras Gerais de Padrão

1. **Controllers são thin**: recebem `Request` → chamam `Action` ou `Service` → retornam `Inertia::render()` ou `redirect()`
2. **Actions** recebem dados validados e executam a operação dentro de `DB::transaction()` quando necessário
3. **Services** abstraem complexidade de cálculo, integrações externas e lógica orquestrada entre múltiplos models
4. **Form Requests** validam toda entrada antes de chegar ao controller
5. **Policies e Gates** verificam autorização server-side em cada operação — nunca apenas no frontend
6. **Events e Jobs** para operações assíncronas: broadcast KDS, envio de WhatsApp via fila Redis
7. Todo model operacional tem `HasUuids`; PKs são UUID em todas as tabelas

### Multi-tenancy

- Todos os models operacionais têm `venue_id` e usam um `TenantScope` (global scope) para escopo automático das queries
- O middleware `SetVenueContext` resolve o `venue_id` do usuário autenticado e o injeta no request
- Usuários corporativos armazenam o `active_venue_id` na sessão e podem trocar via `POST /account/venue/{id}`
- A camada de testes usa `actingAs($user)` com helpers que configuram o contexto do tenant

### Roles (`UserRole`)

```php
// app/Enums/UserRole.php
enum UserRole: string
{
    // Platform (equipe interna NeuraBar)
    case SuperAdmin       = 'super_admin';
    case Finance          = 'finance';
    case Registration     = 'registration';
    case ReadOnly         = 'read_only';

    // Operational (clientes do SaaS)
    case CorporationAdmin = 'corporation_admin';
    case Owner            = 'owner';
    case GeneralManager   = 'general_manager';
    case SectionManager   = 'section_manager';
    case Attendant        = 'attendant';
}
```

Matriz de acesso operacional:

| Recurso | corporation_admin | owner | general_manager | section_manager | attendant |
|---------|:-----------------:|:-----:|:---------------:|:---------------:|:---------:|
| Dashboard | ✅ | ✅ | ✅ | ✅ | ✅ |
| Menu (editar) | ✅ | ✅ | ✅ | ❌ | ❌ |
| Order Taker | ✅ | ✅ | ✅ | ✅ | ✅ |
| KDS | ✅ | ✅ | ✅ | ✅ | ✅ |
| Payment | ✅ | ✅ | ✅ | ✅ | ✅ |
| Users | ❌ | ✅ | ✅ | ❌ | ❌ |
| Settings | ❌ | ✅ | ✅ | ❌ | ❌ |
| Corporation Panel | ✅ | ✅ | ✅ | ❌ | ❌ |

---

## Blocos de Implementação

Cada bloco deve ser concluído e com testes passando antes de iniciar o próximo que depende dele.

---

## BLOCO 0 — Infraestrutura e Design System

**Objetivo:** Ambiente funcional, seguro e com componentes visuais base prontos para uso em todos os blocos seguintes.

**Dependência:** nenhuma

---

### 0.1 — Ambiente e Soketi

- Verificar `compose.yaml`: confirmar serviços `app`, `postgres`, `redis`, `soketi`
- Adicionar serviço `soketi` ao `compose.yaml` se ausente, com imagem `quay.io/soketi/soketi`
- Configurar `.env` com variáveis Pusher apontando para Soketi local:
  ```
  BROADCAST_DRIVER=pusher
  PUSHER_APP_ID=neurabar
  PUSHER_APP_KEY=neurabar-key
  PUSHER_APP_SECRET=neurabar-secret
  PUSHER_HOST=soketi
  PUSHER_PORT=6001
  PUSHER_SCHEME=http
  VITE_PUSHER_APP_KEY="${PUSHER_APP_KEY}"
  VITE_PUSHER_HOST="${PUSHER_HOST}"
  VITE_PUSHER_PORT="${PUSHER_PORT}"
  VITE_PUSHER_SCHEME="${PUSHER_SCHEME}"
  ```
- Confirmar `config/broadcasting.php` com driver `pusher` e as opções `host`, `port`, `useTLS`
- Instalar `pusher-js` e configurar `Laravel Echo` em `resources/js/bootstrap.js`
- Criar evento de teste (`TestBroadcastEvent`) e verificar handshake funcionando

### 0.2 — Estrutura de Diretórios

Criar diretórios (via artisan ou manualmente):
```
app/Actions/
app/Services/
app/Enums/
app/Http/Requests/
app/Policies/
app/Events/
app/Jobs/
resources/js/Pages/Auth/
resources/js/Pages/Menu/
resources/js/Pages/Orders/
resources/js/Pages/Kitchen/
resources/js/Pages/Attendances/
resources/js/Pages/Payment/
resources/js/Pages/Settings/
resources/js/Pages/Guest/
resources/js/Pages/Corporation/
resources/js/Pages/Platform/
```

Criar `app/Enums/UserRole.php` com enum acima.

### 0.3 — Design System Tailwind

Atualizar `tailwind.config.js` com os tokens visuais do NeuraBar:

```js
theme: {
  extend: {
    fontFamily: {
      heading: ['Space Grotesk', 'sans-serif'],
      body:    ['DM Sans', 'sans-serif'],
      sans:    ['DM Sans', 'sans-serif'],
    },
    colors: {
      primary:     { DEFAULT: 'hsl(200 75% 45%)', foreground: '#ffffff', hover: 'hsl(200 75% 38%)' },
      accent:      { DEFAULT: 'hsl(180 55% 40%)', foreground: '#ffffff' },
      destructive: { DEFAULT: 'hsl(0 84% 60%)',   foreground: '#ffffff' },
      'ocean-light': 'hsl(200 60% 90%)',
      'ocean-deep':  'hsl(210 70% 30%)',
      'warm-gold':   'hsl(38 80% 55%)',
      sand:          'hsl(35 40% 85%)',
      muted:       { DEFAULT: 'hsl(40 20% 94%)', foreground: 'hsl(210 15% 46%)' },
    },
    borderRadius: { DEFAULT: '0.75rem', lg: '0.75rem', md: '0.5rem', sm: '0.25rem' },
    boxShadow: {
      ocean: '0 10px 40px -10px hsl(200 75% 45% / 0.25)',
      card:  '0 4px 24px -4px hsl(210 30% 12% / 0.08)',
    },
  },
}
```

Adicionar `@fontsource/space-grotesk` e `@fontsource/dm-sans` ou carregá-las via Google Fonts em `app.css`.

### 0.4 — Componentes Vue Base

Criar em `resources/js/Components/`:

| Componente | Props relevantes |
|-----------|-----------------|
| `AppButton.vue` | `variant` (primary\|secondary\|destructive\|ghost), `size`, `loading` |
| `AppCard.vue` | `title`, slot padrão |
| `AppBadge.vue` | `color` (hex ou named), `label` |
| `AppSkeleton.vue` | `lines`, `height` |
| `AppEmptyState.vue` | `title`, `description`, `actionLabel`, emit `action` |
| `AppConfirmModal.vue` | `title`, `message`, `confirmLabel`, `variant` — emite `confirm` e `cancel` |
| `AppToast.vue` | wrapper de biblioteca toast (ex: `vue-sonner`) |
| `AppTable.vue` | `columns[]`, `rows[]`, `pagination` (LengthAwarePaginator) |
| `AppPagination.vue` | `links` do paginator do Laravel |

Estes componentes devem reutilizar e/ou estender os que o Jetstream já criou em `Components/`.

### 0.5 — Layouts

- `resources/js/Layouts/AppLayout.vue` — sidebar com nav por role + topbar com nome do venue e usuário
- `resources/js/Layouts/GuestLayout.vue` — layout simples para páginas sem auth (logo, fundo neutro)
- `resources/js/Layouts/PlatformLayout.vue` — layout distinto para backoffice NeuraBar

### 0.6 — Base de Testes

- Confirmar `phpunit.xml` com banco separado para testes
- Criar `tests/TestCase.php` com helpers:
  ```php
  protected function loginAs(UserRole $role, ?Venue $venue = null): User
  protected function loginAsPlatformUser(UserRole $role): PlatformUser
  ```
- Verificar que `vendor/bin/sail artisan test --compact` passa nos testes existentes

---

## BLOCO 1 — Multi-tenant: Schema e Models

**Objetivo:** Schema completo no banco com todas as entidades do domínio e models Eloquent organizados.

**Dependência:** Bloco 0

---

### 1.1 — Adicionar campos ao `users`

Migration: `add_tenant_fields_to_users_table`
```
role               string (UserRole enum) — nullable inicialmente
venue_id           uuid FK → venues nullable
corporation_id     uuid FK → corporations nullable
pin                string nullable
active             boolean default true
```

Atualizar `User.php`:
- Adicionar `role` com cast para `UserRole`
- Relacionamentos: `belongsTo(Venue::class)`, `belongsTo(Corporation::class)`
- Método `activeVenue(): Venue` — retorna o venue ativo da sessão (usuários corporativos) ou o fixo (staff)

### 1.2 — Migrations de Tenant

```
create_plan_catalogs_table
  id uuid PK, code string unique, name, description, sort_order int
  monthly_price decimal(10,2), active boolean default true, timestamps

create_corporations_table
  id uuid PK, name, tax_id, email, contact_phone
  plan_catalog_id uuid FK nullable, plan_name, subscription_value decimal(10,2)
  plan_start_date date nullable, plan_end_date date nullable
  active boolean default true, timestamps

create_venues_table
  id uuid PK, corporation_id uuid FK, name, tax_id, phone, whatsapp_agent
  street, number, complement, neighborhood, city, state, zip_code
  timezone string default 'America/Sao_Paulo'
  active boolean default true
  require_table boolean default false
  require_tab boolean default false
  require_location boolean default false
  call_waiter_header_url string nullable
  call_waiter_passphrase string nullable
  call_waiter_slug string unique nullable
  evolution_api_url, evolution_api_key, evolution_api_instance (nullable)
  logo_url string nullable
  timestamps
```

### 1.3 — Migrations de Configuração Operacional

```
create_venue_settings_table
  id uuid PK, venue_id uuid FK unique
  cover_charge decimal(10,2) default 10.00
  service_fee_percent decimal(5,2) default 10.00
  table_count int default 30
  timestamps

create_kitchen_stations_table
  id uuid PK, venue_id uuid FK
  name string, sort_order int default 0, active boolean default true, timestamps

create_preparation_statuses_table
  id uuid PK, venue_id uuid FK
  name, color string nullable (hex), sort_order int default 0
  show_to_customer boolean default false, timestamps

create_service_locations_table
  id uuid PK, venue_id uuid FK
  name, type enum(table|counter|other) default 'table'
  active boolean default true, timestamps
```

### 1.4 — Migrations de Menu

```
create_menus_table
  id uuid PK, venue_id uuid FK, name, active boolean default true, timestamps

create_menu_categories_table
  id uuid PK, menu_id uuid FK, name, sort_order int default 0, timestamps

create_products_table
  id uuid PK, category_id uuid FK, kitchen_station_id uuid FK nullable
  name, description text nullable, price decimal(10,2)
  image_url string nullable, active boolean default true
  available_for_delivery boolean default false, timestamps

create_product_variations_table
  id uuid PK, product_id uuid FK
  name, price decimal(10,2), active boolean default true, timestamps

create_modifier_groups_table
  id uuid PK, venue_id uuid FK, name
  required boolean default false, multiple_selection boolean default false, timestamps

create_modifier_options_table
  id uuid PK, modifier_group_id uuid FK
  name, extra_price decimal(10,2) default 0, active boolean default true, timestamps

create_product_modifier_group_table  (pivot)
  product_id uuid FK, modifier_group_id uuid FK

create_combos_table
  id uuid PK, venue_id uuid FK, name, description nullable
  price decimal(10,2), active boolean default true, timestamps

create_combo_items_table
  id uuid PK, combo_id uuid FK, product_id uuid FK
  variation_id uuid FK nullable, quantity int default 1, timestamps
```

### 1.5 — Migrations de Orders e Attendances

```
create_attendances_table
  id uuid PK, venue_id uuid FK, service_location_id uuid FK nullable
  customer_identifier string nullable
  channel enum(counter|table|delivery|service_request) default 'table'
  status enum(open|closed) default 'open'
  party_size int nullable, notes text nullable
  created_by uuid FK → users nullable
  closed_at timestamp nullable, timestamps

create_orders_table
  id uuid PK, attendance_id uuid FK
  order_number int, status enum(open|finalized) default 'open'
  notes text nullable
  created_by uuid FK → users nullable, timestamps

create_order_items_table
  id uuid PK, order_id uuid FK
  product_id uuid FK nullable, variation_id uuid FK nullable
  quantity int, unit_price decimal(10,2)
  notes text nullable
  preparation_status_id uuid FK nullable
  ready_at timestamp nullable, timestamps

create_order_item_modifiers_table
  id uuid PK, order_item_id uuid FK, modifier_option_id uuid FK
  extra_price_snapshot decimal(10,2), timestamps
```

### 1.6 — Migrations de Payment e Platform

```
create_payments_table
  id uuid PK, attendance_id uuid FK unique
  items_total decimal(10,2), cover_charge_total decimal(10,2)
  service_fee_total decimal(10,2), grand_total decimal(10,2)
  party_size int nullable
  created_by uuid FK → users, timestamps

create_payment_items_table
  id uuid PK, payment_id uuid FK
  method enum(cash|credit_card|debit_card|pix|other)
  amount decimal(10,2), notes string nullable, timestamps

createbackoffice_users_table
  id uuid PK, name, email string unique, password
  role enum(super_admin|finance|registration|read_only)
  active boolean default true, timestamps
```

### 1.7 — Models Eloquent

Criar todos os models com `HasUuids`, `fillable`, `casts()` e relacionamentos.

Criar `app/Concerns/BelongsToVenue.php` (trait):
```php
// Adiciona TenantScope global e relacionamento venue()
public static function bootBelongsToVenue(): void
{
    static::addGlobalScope(new TenantScope());
}
```

Aplicar o trait em: `Menu`, `Category`, `Product`, `ModifierGroup`, `Combo`, `KitchenStation`, `PreparationStatus`, `ServiceLocation`, `Attendance`, `VenueSettings`.

`OrderItem`, `Order`, `PaymentItem`, `Payment` — não precisam do scope diretamente (são acessados via relacionamento do `Attendance`).

`app/Scopes/TenantScope.php`:
```php
// Aplica where('venue_id', app('tenant')->id) se o tenant estiver resolvido
// Não aplica se não houver tenant (ex: em comandos artisan)
```

### 1.8 — Seeders Iniciais

```
DatabaseSeeder
  └── PlanCatalogsSeeder     — Basic, Pro, Enterprise
  └── TestVenueSeeder (apenas em local/testing)
        Cria: Corporation "Test Corp" → Venue "Test Bar"
        → Usuário owner@test.com / password
        → VenueSettings padrão
        → KitchenStations: Kitchen, Bar
        → PreparationStatuses: Pending (#94a3b8), In Progress (#f59e0b), Ready (#22c55e)
```

### 1.9 — Factories

Criar factory para cada model. Destacados:
- `VenueFactory` com state `withSlug()` que gera `call_waiter_slug`
- `ProductFactory` com state `inactive()`
- `AttendanceFactory` com states `open()`, `closed()`
- `OrderFactory` com `withItems(int $count)` — cria itens via `OrderItemFactory`

**Testes:**
```
Feature/Models/TenantScopeTest
  — query de Product retorna apenas itens do venue atual
  — query sem tenant resolvido (artisan context) não aplica scope

Feature/Migrations/SchemaIntegrityTest
  — tabelas existem com colunas críticas
```

---

## BLOCO 2 — Autenticação e Controle de Acesso

**Objetivo:** Login via Fortify com suporte a múltiplos roles, resolução de tenant e middleware de roles.

**Dependência:** Bloco 1

---

### 2.1 — Fortify: Redirecionar para `/dashboard`

Em `FortifyServiceProvider::boot()`:
```php
Fortify::authenticateUsing(function (Request $request) {
    $user = User::where('email', $request->email)->first();
    if ($user && $user->active && Hash::check($request->password, $user->password)) {
        return $user;
    }
});

Fortify::redirects('login', '/dashboard');
```

Criar `app/Http/Responses/LoginResponse.php` implementando `LoginResponseContract` para retornar redirect Inertia adequado por role.

### 2.2 — Middleware de Tenant

`app/Http/Middleware/SetVenueContext.php`:
- Para roles operacionais: usa `$user->venue_id`
- Para `corporation_admin` e `owner` com múltiplos venues: usa `session('active_venue_id')` com fallback ao primeiro disponível
- Injeta o `Venue` resolvido via `app()->instance('tenant', $venue)` e `request()->merge(['_venue' => $venue])`
- Retorna 403 se o tenant não puder ser resolvido

Registrar em `bootstrap/app.php`:
```php
->withMiddleware(function (Middleware $middleware) {
    $middleware->web(append: [...]);
    $middleware->alias([
        'tenant' => SetVenueContext::class,
        'role'   => RequireRole::class,
    ]);
})
```

### 2.3 — Middleware de Roles

`app/Http/Middleware/RequireRole.php`:
```php
// Uso nas rotas: ->middleware('role:owner,general_manager')
public function handle(Request $request, Closure $next, string ...$roles): Response
{
    if (! in_array(auth()->user()?->role?->value, $roles)) {
        abort(403);
    }
    return $next($request);
}
```

### 2.4 — Gates no AppServiceProvider

```php
Gate::define('manage-menu',        fn($u) => in_array($u->role, [Owner, GeneralManager, CorporationAdmin]));
Gate::define('manage-users',       fn($u) => in_array($u->role, [Owner, GeneralManager]));
Gate::define('manage-settings',    fn($u) => in_array($u->role, [Owner, GeneralManager]));
Gate::define('access-corporation', fn($u) => in_array($u->role, [CorporationAdmin, Owner, GeneralManager]));
Gate::define('register-payment',   fn($u) => $u->role !== ReadOnly);
```

### 2.5 — Controller de Troca de Venue

`App\Http\Controllers\Auth\VenueSelectorController`:
- `store($id)` — valida que o usuário tem acesso ao venue solicitado, salva `active_venue_id` na sessão, redireciona para `/dashboard`

### 2.6 — Page de Login

`resources/js/Pages/Auth/Login.vue`:
- Reutilizar estrutura Jetstream (`AuthenticationCard.vue`)
- Campos: email, senha, "lembrar-me"
- Loading state no botão de submit
- Mensagem de erro clara em credencial inválida

**Testes:**
```
Feature/Auth/LoginTest
  — credenciais válidas redirecionam para /dashboard
  — usuário inativo retorna erro 'Account disabled'
  — senha errada retorna erro de credenciais
  — logout limpa sessão e token

Feature/Auth/TenantContextTest
  — middleware resolve venue correto após login
  — trocar venue atualiza active_venue_id na sessão
  — acesso a recurso de outro tenant retorna 404
```

---

## BLOCO 3 — Configurações do Venue

**Objetivo:** CRUD das configurações operacionais: dados do venue, kitchen stations, preparation statuses, service locations e usuários.

**Dependência:** Bloco 2

---

### 3.1 — Dados do Venue

`App\Http\Controllers\Settings\VenueController`:
- `edit()` → `Settings/Venue.vue`
- `update()` → chama `UpdateVenueAction`

`App\Actions\Settings\UpdateVenueAction`:
- Atualiza name, contact, endereço, logo_url, call_waiter_slug, configurações de obrigatoriedade (require_table/tab/location)
- Valida `call_waiter_slug` como único globalmente

`App\Http\Requests\Settings\UpdateVenueRequest`

### 3.2 — Configurações de Negócio

`App\Http\Controllers\Settings\VenueSettingsController`:
- `edit()` → `Settings/General.vue` (cover_charge, service_fee_percent, table_count)
- `update()` → `UpdateVenueSettingsAction`

`App\Actions\Settings\UpdateVenueSettingsAction`:
- Upsert em `venue_settings` por `venue_id`

> Resolve BUG-008/009 do plano original: configurações saem do localStorage e vão para o banco.

### 3.3 — Kitchen Stations

`App\Http\Controllers\Settings\KitchenStationController` (resourceful: index, store, update, destroy):
- `index()` → embutido na Page `Settings/KitchenStations.vue`

`App\Actions\Settings\CreateKitchenStationAction`
`App\Actions\Settings\UpdateKitchenStationAction`
`App\Actions\Settings\DeleteKitchenStationAction` — impede exclusão se houver produtos vinculados

### 3.4 — Preparation Statuses

`App\Http\Controllers\Settings\PreparationStatusController` (resourceful):
- Page `Settings/PreparationStatuses.vue` — lista com seletor de cor e toggle `show_to_customer`

### 3.5 — Service Locations

`App\Http\Controllers\Settings\ServiceLocationController` (resourceful):
- Page `Settings/ServiceLocations.vue` — lista com type (table/counter/other) e toggle active

### 3.6 — Gestão de Usuários

`App\Http\Controllers\Settings\UserController` (resourceful):
- Escopado por `venue_id` do tenant ativo
- Page `Settings/Users.vue`

`App\Actions\Settings\CreateUserAction`:
- Cria `User` com `role`, `venue_id`, `active = true`, senha via `Hash::make()`
- Não permite criar `super_admin` ou `corporation_admin` por aqui

`App\Actions\Settings\UpdateUserAction`, `DeleteUserAction`

`App\Http\Requests\Settings\StoreUserRequest`, `UpdateUserRequest`

Middleware nas rotas de settings: `role:owner,general_manager`

**Testes:**
```
Feature/Settings/VenueTest
  — update salva todos os campos no banco
  — call_waiter_slug duplicado retorna erro de validação

Feature/Settings/VenueSettingsTest
  — cover_charge e service_fee persistem no banco por venue
  — upsert não duplica registro

Feature/Settings/KitchenStationTest
  — CRUD com scope de tenant
  — exclusão de station com produtos vinculados retorna erro

Feature/Settings/UserTest
  — criação de usuário com role válido
  — attendant não pode acessar rota de settings
  — usuário de outro venue não aparece na listagem
```

---

## BLOCO 4 — Menu Digital

**Objetivo:** CRUD de categorias e produtos com menu público sem autenticação.

**Dependência:** Bloco 3

---

### 4.1 — Menu e Categorias

`App\Http\Controllers\Menu\CategoryController` (resourceful + `reorder`):
- `index()` → `Menu/Index.vue` com categorias e produtos
- `reorder()` — recebe array de IDs com nova ordem, persiste

`App\Actions\Menu\CreateCategoryAction`
`App\Actions\Menu\UpdateCategoryAction`
`App\Actions\Menu\ReorderCategoriesAction`

O venue tem apenas um `Menu` ativo no MLP. Se não existir, é criado automaticamente no primeiro acesso (via `firstOrCreate` no controller).

### 4.2 — Produtos

`App\Http\Controllers\Menu\ProductController` (resourceful + `toggleActive`):
- Page `Menu/Products.vue` — listagem filtrada por category, com preço e badge de status

`App\Actions\Menu\CreateProductAction`
`App\Actions\Menu\UpdateProductAction`
`App\Actions\Menu\ToggleProductActiveAction`

`App\Http\Requests\Menu\StoreProductRequest`:
- `name` required, `price` required numeric min:0, `category_id` exists
- `kitchen_station_id` optional exists in stations do mesmo venue

`App\Http\Requests\Menu\UpdateProductRequest`

Middleware nas rotas de menu (edição): `role:corporation_admin,owner,general_manager`

### 4.3 — Menu Público

`App\Http\Controllers\Guest\PublicMenuController`:
- `show($slug)` — sem `auth` middleware
- Resolve venue pelo `call_waiter_slug`
- Retorna categorias com produtos ativos ordenados
- Rate limiting: `throttle:60,1` por IP
- Page `Guest/Menu.vue` — layout `GuestLayout.vue`, sem sidebar

**Testes:**
```
Feature/Menu/CategoryTest
  — CRUD com scope de tenant
  — reordenação persiste a ordem correta

Feature/Menu/ProductTest
  — criação com kitchen_station do mesmo tenant
  — kitchen_station de outro tenant é rejeitado
  — toggleActive alterna corretamente

Feature/Menu/PublicMenuTest
  — retorna produtos ativos sem autenticação
  — slug inexistente retorna 404
  — produtos inativos não aparecem
```

---

## BLOCO 5 — Attendances

**Objetivo:** Criar, listar e encerrar sessões de atendimento (mesas e comandas abertas).

**Dependência:** Bloco 3

---

### 5.1 — AttendanceController

`App\Http\Controllers\Orders\AttendanceController`:
- `index()` → `Attendances/Index.vue` — apenas status `open`
- `store()` → `OpenAttendanceAction`
- `show($id)` → `Attendances/Show.vue` — orders e itens da attendance
- `update($id)` → atualiza notes e `party_size`
- `close($id)` → `CloseAttendanceAction`

### 5.2 — Actions de Attendance

`App\Actions\Orders\OpenAttendanceAction`:
- Lê `VenueSettings` do tenant
- Valida: se `require_table`, exige `customer_identifier` quando `channel = table`
- Cria `Attendance` com `created_by = auth()->id()`

`App\Actions\Orders\CloseAttendanceAction`:
- Verifica que `status = open`
- Verifica que existe `Payment` registrado para a attendance
- Seta `status = closed`, `closed_at = now()`
- **Nota:** esta action é chamada internamente por `RegisterPaymentAction`, não diretamente pelo usuário

`App\Http\Requests\Orders\StoreAttendanceRequest`

### 5.3 — Page de Attendances

`resources/js/Pages/Attendances/Index.vue`:
- Cards de attendances abertas: identifier, channel, tempo aberto, total parcial (sum de itens)
- Botão "New Attendance" com drawer/modal de criação
- Botão "Take Order" → link para Order Taker
- Botão "Payment" → link para Payment
- Botão "Close" — abre `AppConfirmModal.vue` antes
- Empty state "No open attendances" com botão de criar

**Testes:**
```
Feature/Orders/AttendanceTest
  — criar attendance com campos obrigatórios satisfeitos
  — erro ao criar sem mesa quando require_table = true
  — encerrar attendance sem payment retorna erro
  — listagem retorna apenas attendances do tenant
  — attendance de outro tenant retorna 403/404
```

---

## BLOCO 6 — Order Taker

**Objetivo:** Interface de anotação de orders para garçons, com envio atômico para a cozinha.

**Dependência:** Blocos 4 e 5

---

### 6.1 — OrderController

`App\Http\Controllers\Orders\OrderController`:
- `create($attendanceId)` → `Orders/Taker.vue`
  - Passa via Inertia: attendance, categories com produtos ativos, kitchen_stations, venue settings
- `store($attendanceId)` → `PlaceOrderAction`

### 6.2 — PlaceOrderAction

`App\Actions\Orders\PlaceOrderAction`:

Recebe array de itens:
```php
[
  ['product_id' => '...', 'variation_id' => null, 'quantity' => 2,
   'unit_price' => 18.90, 'notes' => '...', 'modifiers' => []]
]
```

Dentro de `DB::transaction()`:
1. Verifica que attendance está `open` e pertence ao tenant
2. Calcula `order_number = max(order_number) + 1` para a attendance
3. Cria `Order` com `status = open`, `created_by`
4. Para cada item: cria `OrderItem` com price snapshot
5. Para cada modifier: cria `OrderItemModifier` com `extra_price_snapshot`
6. Dispara evento `OrderPlaced` (fora da transaction)

`App\Http\Requests\Orders\StoreOrderRequest`:
- Valida que `items` não é vazio
- Valida que cada `product_id` existe e está ativo no tenant
- Valida que cada `unit_price` bate com o produto/variação (evitar manipulação)

### 6.3 — Evento de Broadcast

`App\Events\Orders\OrderPlaced` implements `ShouldBroadcast`:
```php
public $broadcastQueue = 'broadcasts';

public function broadcastOn(): array
{
    return [
        new PrivateChannel("venue.{$this->order->attendance->venue_id}.kitchen"),
    ];
}

public function broadcastWith(): array
{
    return ['order' => $this->order->load('items.product', 'items.preparationStatus', 'attendance')];
}
```

### 6.4 — Page Order Taker

`resources/js/Pages/Orders/Taker.vue`:
- Tabs de categories na lateral
- Grid de produtos (name, price, botão +)
- Painel direito: carrinho com itens, quantity editável, notes por item
- Botão "Place Order": desabilitado se carrinho vazio, loading state durante envio
- Toast de sucesso ao enviar
- Modal de confirmação "Send X items?"
- Skeleton no carregamento do menu
- Empty state por category
- Após envio: limpa carrinho, mantém attendance selecionada para novo order

**Testes:**
```
Feature/Orders/OrderTest
  — enviar order cria Order, OrderItem e OrderItemModifier
  — unit_price é snapshot (não o preço atual do produto)
  — order_number incrementa corretamente
  — evento OrderPlaced é disparado
  — transação reverte todos os registros em caso de erro
  — order com itens de outro tenant retorna 422
  — attendance closed não aceita novos orders
  — carrinho vazio retorna erro de validação
```

---

## BLOCO 7 — KDS com Realtime

**Objetivo:** Cozinha visualiza e atualiza orders em tempo real via WebSocket, sem polling.

**Dependência:** Bloco 6

---

### 7.1 — KdsController

`App\Http\Controllers\Kitchen\KdsController`:
- `index()` → `Kitchen/Kds.vue`
  - Passa via Inertia: kitchen stations ativas, preparation statuses, order items abertos agrupados por station
- `updateItemStatus(Request $request, $itemId)` → `UpdateItemStatusAction`
- `monitor()` → `Kitchen/Monitor.vue` — sem auth, apenas itens com `show_to_customer = true`

### 7.2 — UpdateItemStatusAction

`App\Actions\Kitchen\UpdateItemStatusAction`:
1. Verifica que `OrderItem` pertence ao tenant
2. Atualiza `preparation_status_id`
3. Se todos os itens do order estão prontos: seta `ready_at = now()`
4. Dispara `ItemStatusUpdated`

### 7.3 — Eventos de Broadcast

`App\Events\Kitchen\ItemStatusUpdated` implements `ShouldBroadcast`:
- Canal: `venue.{id}.kitchen`
- Payload: `item_id`, `preparation_status_id`, `ready_at`

`App\Events\Kitchen\NewOrderReceived` (disparado como listener de `OrderPlaced`):
- Canal por station: `venue.{id}.station.{station_id}`

`App\Listeners\Kitchen\BroadcastNewOrderByStation`:
- Ouve `OrderPlaced`
- Agrupa itens por `kitchen_station_id`
- Para cada station, dispara `NewOrderReceived` no canal correto

Registrar em `App\Providers\AppServiceProvider` via `Event::listen()`.

### 7.4 — Channel Authorization

`routes/channels.php`:
```php
Broadcast::channel('venue.{id}.kitchen', function (User $user, string $id) {
    return $user->venue_id === $id
        || session('active_venue_id') === $id;
});

Broadcast::channel('venue.{venueId}.station.{stationId}', function (User $user, string $venueId) {
    return $user->venue_id === $venueId
        || session('active_venue_id') === $venueId;
});
```

### 7.5 — Page KDS

`resources/js/Pages/Kitchen/Kds.vue`:
- Colunas por kitchen station
- Cada coluna lista cards de `OrderItem` com: table/tab identifier, product, quantity, notes, badge de tempo de espera
- Badge de tempo: verde < 5min, amarelo 5–10min, vermelho > 10min (calculado com `Date.now() - item.created_at`)
- Botões de status por item (um por `PreparationStatus` configurado)
- **Sem polling** — conexão Echo em `onMounted`, `leaveChannel` em `onUnmounted`
- Alerta sonoro (`new Audio('/sounds/new-order.mp3').play()`) no evento `NewOrderReceived`
- Toast discreto ao atualizar status

`resources/js/Pages/Kitchen/Monitor.vue`:
- Versão simplificada para telão: apenas itens com `show_to_customer = true`
- Sem auth, sem sidebar, layout fullscreen

**Testes:**
```
Feature/Kitchen/KdsTest
  — atualizar status persiste no banco
  — ready_at preenchida quando todos os itens do order ficam prontos
  — evento ItemStatusUpdated é disparado no canal correto
  — KDS lista apenas itens do tenant autenticado
  — acesso ao canal com user de outro tenant é negado
```

---

## BLOCO 8 — Payment

**Objetivo:** Calcular, dividir e registrar payments por método com encerramento automático da attendance.

**Dependência:** Bloco 5

---

### 8.1 — PaymentService

`App\Services\Payment\PaymentService`:

```php
public function calculateTotal(Attendance $attendance): array
{
    // 1. items_total = sum(unit_price * quantity) + sum(extra_price_snapshot)
    // 2. Busca VenueSettings do tenant
    // 3. cover_charge_total = cover_charge * party_size
    // 4. subtotal = items_total + cover_charge_total
    // 5. service_fee_total = subtotal * (service_fee_percent / 100)
    // 6. grand_total = subtotal + service_fee_total
    return compact('items_total', 'cover_charge_total', 'service_fee_total', 'grand_total');
}

public function splitTotal(float $total, int $partySize): float
{
    return round($total / $partySize, 2);
}
```

### 8.2 — RegisterPaymentAction

`App\Actions\Payment\RegisterPaymentAction`:

Dentro de `DB::transaction()`:
1. Verifica attendance `open` sem payment registrado
2. Calcula totais via `PaymentService`
3. Cria `Payment` com snapshots
4. Cria `PaymentItem` para cada método informado
5. Valida: `sum(methods.amount) == grand_total` (tolerância de R$ 0,01)
6. Chama `CloseAttendanceAction`

`App\Http\Requests\Payment\StorePaymentRequest`:
- `methods` required array min:1
- Cada método: `type` enum, `amount` numeric min:0.01

### 8.3 — PaymentController

`App\Http\Controllers\Payment\PaymentController`:
- `show($attendanceId)` → `Payment/Index.vue` com totais calculados via `PaymentService`
- `store($attendanceId)` → `RegisterPaymentAction`

### 8.4 — Page Payment

`resources/js/Pages/Payment/Index.vue`:
- Resumo dos itens da attendance com subtotais
- Campo "Party size" para cálculo do cover charge e divisão
- Cards de totais: subtotal, cover charge, service fee, **grand total**, amount per person
- Seleção de métodos de pagamento com campos de valor (pode ser múltiplo)
- Validação client-side: soma dos métodos ≠ grand total exibe aviso
- Botão "Confirm Payment" com `AppConfirmModal.vue`
- Após sucesso: redirect para `/attendances`

**Testes:**
```
Feature/Payment/PaymentTest
  — calculateTotal aplica cover_charge e service_fee corretamente
  — cover_charge zerado quando party_size = 0
  — splitTotal arredonda para 2 casas
  — RegisterPaymentAction cria Payment e fecha attendance
  — soma dos métodos diferente do grand_total retorna erro
  — attendance already closed retorna 422
  — payment duplicado retorna 422
```

---

## BLOCO 9 — Call Waiter (QR Público)

**Objetivo:** Cliente solicita assistência via QR sem login; solicitação aparece no KDS.

**Dependência:** Bloco 7

---

### 9.1 — CallWaiterController

`App\Http\Controllers\Guest\CallWaiterController`:
- `show($slug)` → sem auth → `Guest/CallWaiter.vue`
  - Resolve venue pelo slug, passa `header_url`, `passphrase_required`, `name`
  - Slug inexistente retorna 404
- `store($slug)` → `SendServiceRequestAction` — sem auth, com rate limit

Rate limiting: `throttle:1,1` por hash de `ip + slug + customer_identifier`.

### 9.2 — SendServiceRequestAction

`App\Actions\Guest\SendServiceRequestAction`:

Dentro de `DB::transaction()`:
1. Resolve `Venue` pelo slug (404 se inexistente)
2. Se `call_waiter_passphrase` configurada: valida a passphrase (422 se errada)
3. Valida: `message` não vazio, ao menos um de table/tab/location informado
4. Upsert `ServiceLocation` pelo name + type + venue
5. Cria `Attendance` com `channel = service_request`
6. Cria `Order` com `order_number = 1`
7. Cria `OrderItem` fictício (`product_id = null`, `notes = $message`, `unit_price = 0`)
8. Dispara `OrderPlaced` → aparece no KDS como channel `service_request`

`App\Http\Requests\Guest\StoreServiceRequestRequest`:
- `message` required string max:500
- `customer_identifier` optional
- `passphrase` optional (validada na action)

### 9.3 — Page Pública Call Waiter

`resources/js/Pages/Guest/CallWaiter.vue`:
- Layout `GuestLayout.vue` com `header_url` (imagem ou logo do venue)
- Campos: identifier (table/tab), message da solicitação
- Campo de passphrase (exibido só se configurado)
- Após sucesso: animação "Request sent! The waiter has been notified." com número de protocolo
- Mensagem de erro clara para passphrase inválida

**Testes:**
```
Feature/Guest/CallWaiterTest
  — solicitação válida cria attendance, order e order_item no banco
  — passphrase inválida retorna 422
  — slug inexistente retorna 404
  — message vazia retorna 422
  — evento OrderPlaced é disparado (channel service_request)
  — rate limit bloqueia segunda solicitação imediata
```

---

## BLOCO 10 — Dashboard Operacional

**Objetivo:** Painel principal com métricas operacionais em tempo real e atalhos para ações frequentes.

**Dependência:** Blocos 5, 6, 7

---

### 10.1 — DashboardController

`App\Http\Controllers\Settings\DashboardController`:
- `index()` → `Dashboard.vue`

Props via Inertia (carregadas no servidor):
```php
[
    'open_attendances_count' => Attendance::open()->count(),
    'items_in_preparation'   => OrderItem::inPreparation()->count(),
    'todays_revenue'         => Payment::today()->sum('grand_total'),
    'attendances_list'       => Attendance::open()->with('serviceLocation', 'orders')->latest()->take(20)->get(),
    'stations_summary'       => KitchenStation::withCount('pendingItems')->get(),
]
```

Scopes nos models:
- `Attendance::scopeOpen()`, `Attendance::scopeClosed()`
- `OrderItem::scopeInPreparation()` — itens sem status = ready
- `Payment::scopeToday()` — criados hoje no timezone do venue

### 10.2 — Page Dashboard

`resources/js/Pages/Dashboard.vue`:
- Row de cards de métricas: open tables, items in preparation, today's revenue
- Tabela de attendances abertas com: identifier, channel, tempo aberto, total parcial, ações rápidas
- Atalhos rápidos: "New Attendance", "Open KDS", "Manage Menu"
- Atualização em tempo real: escuta canal `venue.{id}.kitchen` via Echo e re-fetch das métricas no evento (via `router.reload()` do Inertia)

---

## ═══════════════════════ BARREIRA MLP ═══════════════════════

**Critério de saída — todos os itens abaixo devem ser ✅ antes de avançar:**

- [ ] Blocos 0–10 implementados
- [ ] Fluxo completo testável end-to-end: login → attendance → order → KDS → payment
- [ ] KDS sem polling (100% WebSocket via Soketi)
- [ ] Call Waiter funcionando via QR com rate limiting
- [ ] cover_charge/service_fee persistidos no banco (não em localStorage)
- [ ] Nenhuma credencial hardcoded ou em `.env.example`
- [ ] `vendor/bin/sail artisan test --compact` verde
- [ ] Deploy em staging funcional

---

## Fase 2 — Incremento Comercial

---

## BLOCO 11 — Menu Avançado: Variações, Modificadores e Combos

**Objetivo:** Completar o menu com variações de tamanho, personalização de itens e combos com preço especial.

**Dependência:** Bloco 4

---

### 11.1 — Product Variations

`App\Http\Controllers\Menu\ProductVariationController` (resourceful, nested em `/menu/products/{product}`):
- CRUD de variations embutido como acordeão na Page `Menu/Products.vue`

`App\Actions\Menu\CreateProductVariationAction`, `UpdateProductVariationAction`, `DeleteProductVariationAction`

Atualizar `Orders/Taker.vue`:
- Ao adicionar produto com variations: exibe modal de seleção antes de adicionar ao carrinho
- `PlaceOrderAction`: se `variation_id` informado, usa `variation.price` como `unit_price`

### 11.2 — Modifier Groups e Options

`App\Http\Controllers\Menu\ModifierGroupController` (resourceful):
- Page `Menu/Modifiers.vue`

`App\Http\Controllers\Menu\ModifierOptionController` (resourceful, nested):

Criar pivot `product_modifier_group` para vincular groups a products.

Atualizar `Orders/Taker.vue`:
- Ao adicionar produto: se tem modifier groups, exibe modal
- Groups obrigatórios bloqueiam confirmação sem seleção
- `StoreOrderRequest`: valida que todos os modifier groups obrigatórios do produto foram respondidos

### 11.3 — Combos

`App\Http\Controllers\Menu\ComboController` (resourceful):
- Page `Menu/Combos.vue` — lista de combos com itens expandíveis

`App\Actions\Menu\CreateComboAction`:
- Cria `Combo` + `ComboItem[]` em transação
- Calcula `suggested_price = sum(product.price * quantity)` como referência

Atualizar `Orders/Taker.vue`:
- Nova aba "Combos" na listagem
- Adicionar combo ao order gera múltiplos `OrderItem` (um por item do combo)
- `PlaceOrderAction`: processar combos como grupo de itens com flag `combo_id`

**Testes:**
```
Feature/Menu/ProductVariationTest
  — order usa price da variation como snapshot

Feature/Menu/ModifierTest
  — modifier groups obrigatórios sem resposta retornam 422
  — modifier groups de outro tenant não aparecem no order taker

Feature/Menu/ComboTest
  — criação gera combo_items corretos
  — order com combo gera OrderItem para cada item do combo
```

---

## BLOCO 12 — Track Order (Público)

**Objetivo:** Cliente rastreia o status do seu pedido via link único sem login.

**Dependência:** Bloco 7

---

### 12.1 — TrackOrderController

`App\Http\Controllers\Guest\TrackOrderController`:
- `show($orderId)` → sem auth → `Guest/TrackOrder.vue`
- Retorna apenas itens com `PreparationStatus.show_to_customer = true`
- Não expõe `venue_id`, dados de outros clientes, ou preços
- `channel = service_request` nunca é exibido aqui
- Rate limiting: `throttle:30,1` por IP

### 12.2 — Page Pública

`resources/js/Pages/Guest/TrackOrder.vue`:
- Timeline visual dos itens e seus statuses
- Status representado com `color` do `PreparationStatus`
- Polling a cada 15s (ou Echo com canal público se implementado)
- Mensagem "Order not found" para IDs inválidos

**Testes:**
```
Feature/Guest/TrackOrderTest
  — retorna itens com show_to_customer = true sem auth
  — não expõe campos sensíveis
  — order de service_request não é acessível
  — order inexistente retorna 404
```

---

## BLOCO 13 — Corporation Panel (Multi-venue)

**Objetivo:** Administrador corporativo gerencia todos os venues da corporation.

**Dependência:** Bloco 2

---

### 13.1 — CorporationDashboardController

`App\Http\Controllers\Corporation\CorporationDashboardController`:
- `index()` → `Corporation/Dashboard.vue`
  - Passa todos os venues da corporation do usuário com métricas do dia
- `switchVenue($id)` → valida acesso e salva `active_venue_id` na sessão

Middleware: `role:corporation_admin,owner,general_manager`

### 13.2 — CRUD de Venues

`App\Http\Controllers\Corporation\VenueController`:
- `index`, `create`, `store`, `edit`, `update` — escopado pela `corporation_id` do usuário

`App\Actions\Corporation\CreateVenueAction`:
- Cria `Venue` vinculado à corporation do usuário
- Cria automaticamente: `VenueSettings` padrão, kitchen stations padrão (Kitchen, Bar), preparation statuses padrão (Pending, In Progress, Ready)
- Gera `call_waiter_slug` único via `Str::slug() + random suffix`

### 13.3 — Page Corporation Dashboard

`resources/js/Pages/Corporation/Dashboard.vue`:
- Grid de cards por venue: name, city, status (active/inactive)
- Métricas do dia: open attendances, revenue
- Botão "Switch to this venue"
- Botão "Settings" → abre Settings do venue selecionado

**Testes:**
```
Feature/Corporation/CorporationDashboardTest
  — corporation_admin vê apenas venues de sua corporation
  — switchVenue atualiza active_venue_id na sessão
  — criar venue gera VenueSettings, stations e statuses padrão
  — owner sem corporation recebe 403 no corporation panel
```

---

## BLOCO 14 — Platform (Backoffice NeuraBar)

**Objetivo:** Interface administrativa para a equipe NeuraBar gerenciar corporations, planos e assinaturas.

**Dependência:** Bloco 1

---

### 14.1 — Guard Dedicado

Adicionar guard `platform` em `config/auth.php`:
```php
'guards' => [
    'platform' => ['driver' => 'session', 'provider' => 'platform_users'],
],
'providers' => [
    'platform_users' => ['driver' => 'eloquent', 'model' => PlatformUser::class],
],
```

Rotas sob prefixo `/{PLATFORM_PATH}` (variável de `.env`), com middleware `auth:platform`.

`App\Http\Controllers\Platform\LoginController`:
- `index()` → `Platform/Login.vue`
- `store()` → Auth pelo guard `platform`, redireciona para dashboard
- `destroy()` → logout do guard

`App\Http\Middleware\RequirePlatformRole` — equivalente ao `RequireRole` mas para `PlatformUser`

### 14.2 — Dashboard de Métricas

`App\Http\Controllers\Platform\DashboardController`:
- `index()` → `Platform/Dashboard.vue`

`App\Services\Platform\MetricsService`:
```php
public function calculateMRR(): float       // soma subscription_value das corporations ativas com plano vigente
public function operationalSummary(): array // corporations ativas, venues, novos últimos 30d
```
Cache via `Cache::remember('platform.metrics', 300, fn() => ...)` (5 minutos).

### 14.3 — CRUD de Corporations

`App\Http\Controllers\Platform\CorporationController` (resourceful com busca e paginação):
- `index($search)` — paginado, com `paginate(20)`
- Pages `Platform/Corporations/Index.vue`, `Create.vue`, `Edit.vue`

`App\Actions\Platform\CreateCorporationAction`:
- Cria `Corporation` + primeiro `Venue` + usuário `owner` com senha aleatória
- Despacha `App\Jobs\Platform\SendWelcomeEmailJob` na fila Redis

`App\Jobs\Platform\SendWelcomeEmailJob`:
- Usa `Mail::to($email)->send(new WelcomeMail($corporation, $initialPassword))`

### 14.4 — Gestão de Planos

`App\Http\Controllers\Platform\PlanCatalogController` (resourceful):
- Page `Platform/Plans/Index.vue`

`App\Http\Controllers\Platform\PlanAssignmentController`:
- `update($corporationId)` → `AssignPlanToCorporationAction`

`App\Actions\Platform\AssignPlanToCorporationAction`:
- Atualiza `plan_catalog_id`, `plan_name`, `subscription_value`, `plan_start_date/end_date` na corporation

### 14.5 — Gestão de Platform Users

`App\Http\Controllers\Platform\PlatformUserController` (resourceful):
- Apenas `super_admin` pode criar/editar/excluir
- Page `Platform/Users/Index.vue`

### 14.6 — Pages da Platform

```
resources/js/Pages/Platform/
  Login.vue
  Dashboard.vue
  Corporations/Index.vue
  Corporations/Create.vue
  Corporations/Edit.vue
  Plans/Index.vue
  Users/Index.vue
```

Layout: `resources/js/Layouts/PlatformLayout.vue` — visual distinto (fundo escuro ou diferente do operacional)

**Testes:**
```
Feature/Platform/AuthTest
  — login via guard platform funciona independente do guard web
  — role 'read_only' não pode criar corporation (403)
  — logout do guard platform não derruba sessão operacional

Feature/Platform/CorporationTest
  — criar corporation gera venue, usuário owner e Job de email
  — paginação retorna 20 por página
  — busca por name funciona

Feature/Platform/MetricsTest
  — MRR soma apenas corporations com plano vigente
  — resultado é cacheado por 5 minutos (platform.metrics)

Feature/Platform/PlanTest
  — atribuição de plano persiste plan_start_date e plan_end_date
```

---

## BLOCO 15 — WhatsApp Integration (Evolution API)

**Objetivo:** Vincular instância WhatsApp ao venue; envio de notificações via fila.

**Dependência:** Bloco 3

---

### 15.1 — EvolutionApiService

`App\Services\Integration\EvolutionApiService`:
```php
public function __construct(private Venue $venue) {}

public function fetchInstances(): array
public function createInstance(string $instanceName): array
public function connectionState(): array
public function sendMessage(string $number, string $message): array
```

Usa `Http::withHeaders(['apikey' => $this->venue->evolution_api_key])->timeout(10)->post(...)`.

Credenciais lidas sempre do banco (`Venue`), **nunca** do frontend.

### 15.2 — WhatsappController

`App\Http\Controllers\Settings\WhatsappController`:
- `edit()` → `Settings/Whatsapp.vue`
- `store()` → salva `evolution_api_url`, `evolution_api_key`, `evolution_api_instance`
- `status()` → retorna `connectionState` via `EvolutionApiService`
- `connect()` → cria instância e retorna QR code para exibir no frontend

### 15.3 — Job de Notificação

`App\Jobs\Integration\SendWhatsappNotificationJob` (queue: `notifications`):
- Usado para: notificar garçom quando order item fica pronto (opcional, configurável)
- Failure handling: `$this->tries = 3`, `$this->backoff = [10, 30, 60]`

**Testes:**
```
Feature/Integration/WhatsappTest
  — credenciais salvas no banco não aparecem em respostas da API
  — config é escopada ao venue autenticado
  — Job é despachado na fila 'notifications'
  — HTTP timeout é tratado como erro (não 500)
```

---

## BLOCO 16 — Attendance History e Reports

**Objetivo:** Histórico de attendances com filtros por data e exportação CSV.

**Dependência:** Bloco 8

---

### 16.1 — AttendanceHistoryController

`App\Http\Controllers\Orders\AttendanceHistoryController`:
- `index()` → `Attendances/History.vue`
  - Filtros: `start_date`, `end_date`, `status`, `channel`, `search` (customer_identifier)
  - Paginado via `paginate(25)`
- `export()` → `ExportAttendancesAction` → `StreamedResponse`

### 16.2 — ExportAttendancesAction

`App\Actions\Orders\ExportAttendancesAction`:
- Mesmos filtros do histórico
- Gera CSV via `fputcsv` nativo com encoding UTF-8
- Colunas: date, identifier, channel, status, items_total, cover_charge, service_fee, grand_total, payment_methods, created_by
- Retorna `StreamedResponse` com header `Content-Disposition: attachment; filename=attendances.csv`

### 16.3 — Page History

`resources/js/Pages/Attendances/History.vue`:
- Filtros de data com date pickers
- Busca por customer_identifier com debounce 400ms
- Tabela paginada com `AppPagination.vue`
- Botão "Export CSV" (dispara download via link)

**Testes:**
```
Feature/Orders/AttendanceHistoryTest
  — paginação retorna 25 por página
  — filtro de data respeita timezone do venue
  — busca por customer_identifier funciona (case-insensitive)
  — exportação CSV contém colunas corretas e encoding UTF-8
  — dados de outro tenant não aparecem
```

---

## Apêndice A — Rotas Consolidadas

### Rotas Protegidas (middleware: `auth`, `tenant`)

```php
// Auth
GET|POST /login                                       Auth\LoginController
POST     /logout                                      Auth\LoginController@destroy
POST     /account/venue/{id}                          Auth\VenueSelectorController@store

// Dashboard
GET      /dashboard                                   Settings\DashboardController@index

// Settings (middleware extra: role:owner,general_manager)
GET|PUT  /settings/venue                              Settings\VenueController
GET|PUT  /settings/general                            Settings\VenueSettingsController
GET|POST /settings/whatsapp                           Settings\WhatsappController
GET      /settings/whatsapp/status                    Settings\WhatsappController@status
POST     /settings/whatsapp/connect                   Settings\WhatsappController@connect
Resource /settings/kitchen-stations                   Settings\KitchenStationController
Resource /settings/preparation-statuses               Settings\PreparationStatusController
Resource /settings/service-locations                  Settings\ServiceLocationController
Resource /settings/users                              Settings\UserController

// Menu (edição: middleware role:corporation_admin,owner,general_manager)
Resource /menu/categories                             Menu\CategoryController
POST     /menu/categories/reorder                     Menu\CategoryController@reorder
Resource /menu/products                               Menu\ProductController
POST     /menu/products/{id}/toggle                   Menu\ProductController@toggleActive
Resource /menu/products/{product}/variations          Menu\ProductVariationController   [Fase 2]
Resource /menu/modifier-groups                        Menu\ModifierGroupController      [Fase 2]
Resource /menu/modifier-groups/{group}/options        Menu\ModifierOptionController     [Fase 2]
Resource /menu/combos                                 Menu\ComboController              [Fase 2]

// Orders
Resource /attendances                                 Orders\AttendanceController
POST     /attendances/{id}/close                      Orders\AttendanceController@close
GET      /orders/take/{attendanceId}                  Orders\OrderController@create
POST     /attendances/{id}/orders                     Orders\OrderController@store

// Kitchen
GET      /kitchen/kds                                 Kitchen\KdsController@index
PUT      /kitchen/items/{id}/status                   Kitchen\KdsController@updateItemStatus

// Payment
GET|POST /payment/{attendanceId}                      Payment\PaymentController

// History [Fase 2]
GET      /attendances/history                         Orders\AttendanceHistoryController@index
GET      /attendances/history/export                  Orders\AttendanceHistoryController@export

// Corporation [Fase 2]
GET      /corporation/dashboard                       Corporation\CorporationDashboardController@index
POST     /corporation/venues/{id}/switch              Corporation\CorporationDashboardController@switchVenue
Resource /corporation/venues                          Corporation\VenueController
```

### Rotas Públicas (sem auth)

```php
GET      /menu/{slug}                                 Guest\PublicMenuController@show
GET      /call-waiter/{slug}                          Guest\CallWaiterController@show
POST     /call-waiter/{slug}                          Guest\CallWaiterController@store
GET      /kitchen/monitor                             Kitchen\KdsController@monitor
GET      /order/{id}/track                            Guest\TrackOrderController@show  [Fase 2]
```

### Rotas da Platform [Fase 2]

```php
// Prefixo: /{PLATFORM_PATH} via config | guard: platform
GET|POST /login                                       Platform\LoginController
POST     /logout                                      Platform\LoginController@destroy
GET      /                                            Platform\DashboardController@index
Resource /corporations                                Platform\CorporationController
PUT      /corporations/{id}/plan                      Platform\PlanAssignmentController@update
Resource /plans                                       Platform\PlanCatalogController
Resource /users                                       Platform\PlatformUserController
```

---

## Apêndice B — Broadcast Channels

```php
// routes/channels.php

// KDS e Dashboard — staff autenticado do venue
Broadcast::channel('venue.{id}.kitchen', function (User $user, string $id) {
    return $user->venue_id === $id
        || session('active_venue_id') === $id;
});

// Canal por kitchen station — para alerta sonoro segmentado
Broadcast::channel('venue.{venueId}.station.{stationId}', function (User $user, string $venueId) {
    return $user->venue_id === $venueId
        || session('active_venue_id') === $venueId;
});
```

---

## Apêndice C — Decisões de Design para Referência

| Decisão | Motivo |
|---------|--------|
| Fortify para auth (mantido) | Já instalado, configurado e integrado com Jetstream; não substituir por solução própria |
| Soketi em vez de Reverb | Definido pelo usuário; compatível com Pusher — mesma API |
| Um `Menu` por venue (MLP) | Simplifica o modelo; múltiplos menus (delivery, presencial) são Fase 3 |
| Snapshot de preço em `OrderItem.unit_price` | Preço no menu pode mudar sem alterar orders históricos |
| `CloseAttendance` chamado por `RegisterPayment` | Encerramento manual sem payment seria inconsistência; o fluxo sempre passa pelo caixa |
| `VenueSettings` no banco | BUG-008/009 do plano original: configs em localStorage não sincronizam entre dispositivos |
| Guard separado (`platform`) para backoffice | Isola completamente a sessão administrativa; usuário do SaaS não acessa o backoffice acidentalmente |
| UUID em todas as tabelas | Previne enumeração de IDs em endpoints públicos (call-waiter, track order) |
| `created_by` em `Attendance`, `Order`, `Payment` | Rastreabilidade e auditoria desde o início |
| Inglês para todo código | Consistência com convenções Laravel/PHP, autocompletion e legibilidade internacional |
