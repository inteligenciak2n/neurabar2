# Arquitetura de Módulos, Subscriptions e Permissionamento de Tenants

**Versão:** 1.0 · **Data:** 17 de julho de 2026

> Modelo de produto SaaS para o NeuraBar: como funcionalidades são empacotadas em módulos contratáveis, como o acesso é isolado por tenant, como a cobrança reflete número de venues, volume de uso e personalização de preços, e como o rastreio de afiliados permeia cadastros, assinaturas e pagamentos.

---

## 1. Visão Geral

O NeuraBar é um SaaS multi-tenant onde uma `Corporation` pode ter uma ou mais `Venue`s. Os módulos são contratados pela `Corporation`, mas a **cobrança é sempre proporcional à quantidade de venues**. Cada venue pode ter módulos ativos independentes, mas o preço unitário é negociado no nível corporate.

A `Corporation` define ainda o **modo de faturamento** (`billing_mode`):

- **`per_venue`** — cada venue recebe sua própria fatura e o bloqueio por inadimplência ocorre venue a venue.
- **`unified`** — todas as venues são agrupadas em uma única fatura no nível corporate; o bloqueio por inadimplência é global (afeta todas as venues da corporation).

```
PlanCatalog (pacote base por venue / tiers de infra)
   │
   ▼
Corporation ── owns ──► Venue(s)
   │                        │
   ├── billing_mode ────────┤
   │   unified  → fatura única + bloqueio global
   │   per_venue → fatura por venue + bloqueio por venue
   │
   ├── modules ──► CorporationModule (preço unitário negociado)
   │                    │
   │                    ├── Module:Menu          (base — sempre ativo)
   │                    ├── Module:Kds           (pago)
   │                    ├── Module:Taker         (pago)
   │                    └── ...
   │
   ├── subscription ──► CorporationSubscription
   │        │
   │        ▼
   │    VenueSubscription (por venue)
   │        │
   │        ├── base_value   = plano base da venue
   │        ├── module_value = Σ (módulos ativos × preço unitário)
   │        ├── metered_value= volume excedente por venue
   │        └── total_value
   │
   └── affiliate_code ──► AffiliateCode (rastreio de indicações)
            │
            ├── Corporation.affiliate_code_id
            ├── Venue.affiliate_code_id
            ├── CorporationSubscription.affiliate_code_id
            ├── VenueSubscription.affiliate_code_id
            ├── VenueInvoice.affiliate_code_id
            └── CorporationInvoice.affiliate_code_id
```

### Princípios

1. **Módulos são ativáveis individualmente** — o cliente contrata apenas o que precisa.
2. **Preço é customizável por tenant** — o catálogo define preços-base, mas cada corporation pode ter valores negociados por venue.
3. **Cobrança proporcional ao número de venues** — plano base e módulos são precificados por venue; o total depende de quantas venues ativam o módulo.
4. **Faturamento flexível** — o cliente escolhe se recebe uma fatura única da corporate ou faturas separadas por estabelecimento.
5. **Bloqueio segue a regra de faturamento** — no modo unificado, inadimplência bloqueia todas as venues; no modo per-venue, bloqueia apenas a venue inadimplente.
6. **Volume importa e é medido por venue** — módulos sensíveis a escala usam tiers de uso que alteram a fatura da venue. O limite e o excedente nunca são agregados no nível corporate.
7. **Acesso é controlado por módulo + role** — um módulo ativo ainda respeita a hierarquia operacional (`Owner`, `GeneralManager`, etc.).
8. **Dependências são explícitas** — módulos que precisam de Cardápio declaram `Module::Menu` como dependência.
9. **Isolamento em banco compartilhado vs. dedicado** — a lógica de módulos vive no banco `saas`; o acesso a dados operacionais continua usando `HasOperationalConnection` + `TenantScope`.
10. **Rastreio de afiliados desde a origem** — um código de afiliado fixo pode ser vinculado em múltiplos pontos (`Corporation`, `Venue`, subscriptions e faturas), permitindo relatórios futuros de indicação e comissão sem alterar o fluxo comercial.

---

## 2. Entidades e Relacionamentos

### 2.1 Catálogo de Módulos (`module_catalogs`)

Tabela central no banco `saas`. Define todos os produtos que podem ser vendidos.

| Coluna | Tipo | Descrição |
|---|---|---|
| `id` | UUID PK | Identificador |
| `code` | varchar unique | Código canônico do módulo (`menu`, `kds`, `taker`, `direct_waiter`, `delivery`, `production_dashboard`, `financial_dashboard`, `direct_print`, `fiscal_note`, `voice_command`) |
| `name` | varchar | Nome comercial |
| `description` | text | Descrição curta para o backoffice |
| `category` | varchar | `basic` ou `premium` |
| `billing_type` | varchar | `fixed` (mensalidade fixa), `metered` (por volume) ou `hybrid` (fixo + volume excedente) |
| `base_monthly_price` | decimal(10,2) | Preço-base mensal |
| `unit_of_measure` | varchar nullable | `order`, `attendance`, `signal`, `user`, `location` — usado quando `billing_type` é `metered` |
| `dependencies` | json | Array de códigos de módulos obrigatórios (ex.: `["menu"]` para KDS) |
| `required_roles` | json nullable | Roles operacionais que, por padrão, podem acessar o módulo |
| `icon` | varchar nullable | Ícone para o menu |
| `sort_order` | int | Ordem de exibição |
| `active` | boolean | Se o módulo está disponível para venda |

```php
// app/Models/Tenant/ModuleCatalog.php
class ModuleCatalog extends Model
{
    use HasFactory;
    use HasUuids;

    protected $connection = 'saas';

    protected $fillable = [
        'code', 'name', 'description', 'category', 'billing_type',
        'base_monthly_price', 'unit_of_measure', 'dependencies',
        'required_roles', 'icon', 'sort_order', 'active',
    ];

    protected function casts(): array
    {
        return [
            'active' => 'boolean',
            'base_monthly_price' => 'decimal:2',
            'sort_order' => 'integer',
            'dependencies' => 'array',
            'required_roles' => 'array',
            'billing_type' => ModuleBillingType::class,
        ];
    }
}
```

### 2.2 Subscription da Corporation (`corporation_subscriptions`)

Registra o contrato comercial da corporation, o modo de faturamento e a configuração global de vencimento. **O valor financeiro real, entretanto, vive nas `VenueSubscription`s.**

| Coluna | Tipo | Descrição |
|---|---|---|
| `id` | UUID PK | |
| `corporation_id` | UUID FK | → `corporations.id` |
| `plan_catalog_id` | UUID FK nullable | Pacote base padrão para novas venues |
| `affiliate_code_id` | UUID FK nullable | → `affiliate_codes.id` (código que originou a assinatura) |
| `billing_mode` | varchar | `per_venue` ou `unified` |
| `status` | varchar | `trial`, `active`, `past_due`, `suspended`, `canceled` |
| `billing_day` | tinyint | Dia do mês de vencimento (1–28) |
| `started_at` | datetime | Início da vigência |
| `ended_at` | datetime nullable | Fim da vigência |
| `trial_ends_at` | datetime nullable | Fim do trial |
| `currency` | varchar default 'BRL' | |

```php
// app/Models/Tenant/CorporationSubscription.php
class CorporationSubscription extends Model
{
    use HasFactory;
    use HasUuids;

    protected $connection = 'saas';

    protected $fillable = [
        'corporation_id', 'plan_catalog_id', 'affiliate_code_id', 'billing_mode',
        'status', 'billing_day', 'started_at', 'ended_at', 'trial_ends_at', 'currency',
    ];

    protected function casts(): array
    {
        return [
            'billing_day' => 'integer',
            'started_at' => 'datetime',
            'ended_at' => 'datetime',
            'trial_ends_at' => 'datetime',
            'billing_mode' => BillingMode::class,
            'status' => SubscriptionStatus::class,
        ];
    }

    public function corporation(): BelongsTo
    {
        return $this->belongsTo(Corporation::class);
    }
}
```

### 2.3 Subscription da Venue (`venue_subscriptions`)

Subscription de faturamento por venue. É a entidade que acumula o valor base, o valor dos módulos e o valor medido/excedente de cada estabelecimento.

| Coluna | Tipo | Descrição |
|---|---|---|
| `id` | UUID PK | |
| `venue_id` | UUID FK | → `venues.id` |
| `corporation_subscription_id` | UUID FK | → `corporation_subscriptions.id` |
| `plan_catalog_id` | UUID FK nullable | Pacote base desta venue (pode ser diferente do corporativo em casos especiais) |
| `affiliate_code_id` | UUID FK nullable | → `affiliate_codes.id` (código que originou esta venue/subscription) |
| `status` | varchar | `trial`, `active`, `past_due`, `suspended`, `canceled` |
| `base_value` | decimal(10,2) | Valor base do plano desta venue |
| `modules_value` | decimal(10,2) | Soma dos módulos ativos nesta venue |
| `metered_value` | decimal(10,2) | Valor de volume excedente medido nesta venue |
| `total_value` | decimal(10,2) | `base_value + modules_value + metered_value` |
| `started_at` | datetime | Início |
| `ended_at` | datetime nullable | Fim |
| `trial_ends_at` | datetime nullable | Fim do trial |

```php
// app/Models/Tenant/VenueSubscription.php
class VenueSubscription extends Model
{
    use HasFactory;
    use HasUuids;

    protected $connection = 'saas';

    protected $fillable = [
        'venue_id', 'corporation_subscription_id', 'plan_catalog_id', 'affiliate_code_id',
        'status', 'base_value', 'modules_value', 'metered_value', 'total_value',
        'started_at', 'ended_at', 'trial_ends_at',
    ];

    protected function casts(): array
    {
        return [
            'base_value' => 'decimal:2',
            'modules_value' => 'decimal:2',
            'metered_value' => 'decimal:2',
            'total_value' => 'decimal:2',
            'started_at' => 'datetime',
            'ended_at' => 'datetime',
            'trial_ends_at' => 'datetime',
            'status' => SubscriptionStatus::class,
        ];
    }

    public function venue(): BelongsTo
    {
        return $this->belongsTo(Venue::class);
    }

    public function corporationSubscription(): BelongsTo
    {
        return $this->belongsTo(CorporationSubscription::class);
    }
}
```

### 2.4 Módulos Contratados pela Corporate (`corporation_modules`)

Define quais módulos a corporation pode usar e o **preço unitário por venue** negociado. O preço efetivo em cada venue é esse valor; o total corporate é a soma proporcional às venues que ativaram o módulo.

| Coluna | Tipo | Descrição |
|---|---|---|
| `id` | UUID PK | |
| `corporation_id` | UUID FK | |
| `module_code` | varchar FK | Código canônico do módulo |
| `status` | varchar | `active`, `suspended`, `canceled`, `trial` |
| `custom_monthly_price` | decimal(10,2) nullable | Preço unitário por venue. Se null, usa `module_catalogs.base_monthly_price`. |
| `started_at` | datetime | Início |
| `ended_at` | datetime nullable | Fim |

```php
// app/Models/Tenant/CorporationModule.php
class CorporationModule extends Model
{
    use HasFactory;
    use HasUuids;

    protected $connection = 'saas';

    protected $table = 'corporation_modules';

    protected $fillable = [
        'corporation_id', 'module_code', 'status',
        'custom_monthly_price', 'started_at', 'ended_at',
    ];

    protected function casts(): array
    {
        return [
            'custom_monthly_price' => 'decimal:2',
            'started_at' => 'datetime',
            'ended_at' => 'datetime',
            'status' => ModuleStatus::class,
        ];
    }

    public function catalog(): BelongsTo
    {
        return $this->belongsTo(ModuleCatalog::class, 'module_code', 'code');
    }
}
```

### 2.5 Módulos Ativos por Venue (`venue_modules`)

Indica quais módulos contratados pela corporate estão efetivamente ativos em cada venue e a quantidade licenciada para aquele estabelecimento (ex.: número de garçons no Direct Garçom).

| Coluna | Tipo | Descrição |
|---|---|---|
| `id` | UUID PK | |
| `venue_id` | UUID FK | → `venues.id` |
| `module_code` | varchar | Código canônico do módulo |
| `status` | varchar | `active`, `suspended`, `canceled` |
| `quantity` | int default 1 | Quantidade licenciada para esta venue |
| `started_at` | datetime | Início |
| `ended_at` | datetime nullable | Fim |

```php
// app/Models/Tenant/VenueModule.php
class VenueModule extends Model
{
    use HasFactory;
    use HasUuids;

    protected $connection = 'saas';

    protected $fillable = [
        'venue_id', 'module_code', 'status', 'quantity', 'started_at', 'ended_at',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'started_at' => 'datetime',
            'ended_at' => 'datetime',
            'status' => ModuleStatus::class,
        ];
    }

    public function venue(): BelongsTo
    {
        return $this->belongsTo(Venue::class);
    }
}
```

### 2.6 Tier de Volume (`module_usage_tiers`)

Define faixas de preço e o **limite incluso** para módulos `metered` ou `hybrid`. Quando a venue estoura o limite (`included_quantity`), aplica-se a cobrança adicional (`overage_price_per_unit` / `overage_flat_fee`) na próxima fatura.

| Coluna | Tipo | Descrição |
|---|---|---|
| `id` | UUID PK | |
| `module_code` | varchar FK | |
| `min_quantity` | int | Início da faixa (inclusive) |
| `max_quantity` | int nullable | Fim da faixa (null = ilimitado) |
| `included_quantity` | int default 0 | Quantidade inclusa no preço base do módulo |
| `price_per_unit` | decimal(10,4) | Preço por unidade dentro da faixa (se a faixa for paga) |
| `flat_price` | decimal(10,2) nullable | Valor fixo da faixa |
| `overage_price_per_unit` | decimal(10,4) default 0 | Preço por unidade excedente ao limite |
| `overage_flat_fee` | decimal(10,2) nullable | Taxa fixa ao estourar o limite |
| `currency` | varchar default 'BRL' | |

```php
// app/Models/Tenant/ModuleUsageTier.php
class ModuleUsageTier extends Model
{
    use HasFactory;
    use HasUuids;

    protected $connection = 'saas';

    protected $fillable = [
        'module_code', 'min_quantity', 'max_quantity', 'included_quantity',
        'price_per_unit', 'flat_price', 'overage_price_per_unit',
        'overage_flat_fee', 'currency',
    ];

    protected function casts(): array
    {
        return [
            'min_quantity' => 'integer',
            'max_quantity' => 'integer',
            'included_quantity' => 'integer',
            'price_per_unit' => 'decimal:4',
            'flat_price' => 'decimal:2',
            'overage_price_per_unit' => 'decimal:4',
            'overage_flat_fee' => 'decimal:2',
        ];
    }
}
```

### 2.7 Consumo Medido por Venue (`venue_usage_records`)

Registra o volume mensal de cada **venue** por módulo. O limite e o excedente nunca são agregados no nível corporate.

| Coluna | Tipo | Descrição |
|---|---|---|
| `id` | UUID PK | |
| `venue_id` | UUID FK | → `venues.id` |
| `module_code` | varchar | |
| `period` | varchar (YYYY-MM) | Período de faturamento |
| `quantity` | int | Quantidade consumida |
| `included_quantity` | int | Quantidade dentro do limite |
| `overage_quantity` | int | Quantidade excedente |
| `tier_id` | UUID FK nullable | Tier aplicado |
| `base_calculated_price` | decimal(10,2) | Valor base/faixa calculado |
| `overage_calculated_price` | decimal(10,2) | Valor do excedente |
| `total_calculated_price` | decimal(10,2) | Soma total |

```php
// app/Models/Tenant/VenueUsageRecord.php
class VenueUsageRecord extends Model
{
    use HasFactory;
    use HasUuids;

    protected $connection = 'saas';

    protected $fillable = [
        'venue_id', 'module_code', 'period', 'quantity',
        'included_quantity', 'overage_quantity', 'tier_id',
        'base_calculated_price', 'overage_calculated_price', 'total_calculated_price',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'included_quantity' => 'integer',
            'overage_quantity' => 'integer',
            'base_calculated_price' => 'decimal:2',
            'overage_calculated_price' => 'decimal:2',
            'total_calculated_price' => 'decimal:2',
        ];
    }
}
```

### 2.8 Códigos de Afiliado (`affiliate_codes`)

Tabela de códigos fixos de afiliados. Por enquanto serve apenas como **rastreio** em múltiplos pontos do ciclo de vida comercial; relatórios e comissões são gerados diretamente no banco de dados.

| Coluna | Tipo | Descrição |
|---|---|---|
| `id` | UUID PK | |
| `code` | varchar unique | Código legível do afiliado (ex.: `JOAO2026`, `INDICA-10`) |
| `name` | varchar | Nome do afiliado/parceiro |
| `email` | varchar nullable | Contato |
| `status` | varchar | `active` ou `inactive` |
| `metadata` | json nullable | Campo livre para dados futuros (taxa de comissão, regras, etc.) |

```php
// app/Models/Tenant/AffiliateCode.php
class AffiliateCode extends Model
{
    use HasFactory;
    use HasUuids;

    protected $connection = 'saas';

    protected $fillable = [
        'code', 'name', 'email', 'status', 'metadata',
    ];

    protected function casts(): array
    {
        return [
            'status' => AffiliateCodeStatus::class,
            'metadata' => 'array',
        ];
    }
}
```

### 2.9 Matrix de Permissões por Módulo (`module_role_permissions`)

Personaliza quais roles operacionais têm acesso a quais ações dentro de cada módulo. Se não houver registro, usa `required_roles` do catálogo.

| Coluna | Tipo | Descrição |
|---|---|---|
| `id` | UUID PK | |
| `corporation_id` | UUID FK nullable | Se null, aplica a todas as corporations (default do produto). |
| `module_code` | varchar | |
| `role` | varchar | Valor de `UserRole` operacional |
| `permissions` | json | Array de abilities (`view`, `create`, `update`, `delete`, `configure`, `operate`) |

```php
// app/Models/Tenant/ModuleRolePermission.php
class ModuleRolePermission extends Model
{
    use HasFactory;
    use HasUuids;

    protected $connection = 'saas';

    protected $fillable = [
        'corporation_id', 'module_code', 'role', 'permissions',
    ];

    protected function casts(): array
    {
        return [
            'permissions' => 'array',
        ];
    }
}
```

---

## 3. Enums

```php
// app/Enums/BillingMode.php
enum BillingMode: string
{
    case PerVenue = 'per_venue';
    case Unified = 'unified';

    public function label(): string
    {
        return match ($this) {
            self::PerVenue => 'Por Estabelecimento',
            self::Unified => 'Fatura Unificada',
        };
    }
}

// app/Enums/ModuleBillingType.php
enum ModuleBillingType: string
{
    case Fixed = 'fixed';
    case Metered = 'metered';
    case Hybrid = 'hybrid';
}

// app/Enums/ModuleStatus.php
enum ModuleStatus: string
{
    case Trial = 'trial';
    case Active = 'active';
    case Suspended = 'suspended';
    case Canceled = 'canceled';
}

// app/Enums/SubscriptionStatus.php
enum SubscriptionStatus: string
{
    case Trial = 'trial';
    case Active = 'active';
    case PastDue = 'past_due';
    case Suspended = 'suspended';
    case Canceled = 'canceled';
}

// app/Enums/AffiliateCodeStatus.php
enum AffiliateCodeStatus: string
{
    case Active = 'active';
    case Inactive = 'inactive';
}

// app/Enums/ModuleCode.php — códigos canônicos
enum ModuleCode: string
{
    case Menu = 'menu';
    case Kds = 'kds';
    case Taker = 'taker';
    case DirectWaiter = 'direct_waiter';
    case Delivery = 'delivery';
    case ProductionDashboard = 'production_dashboard';
    case FinancialDashboard = 'financial_dashboard';
    case DirectPrint = 'direct_print';
    case FiscalNote = 'fiscal_note';
    case VoiceCommand = 'voice_command';

    public function dependsOn(): array
    {
        return match ($this) {
            self::Menu => [],
            self::Kds, self::Taker, self::Delivery,
            self::ProductionDashboard, self::FinancialDashboard,
            self::DirectPrint, self::FiscalNote, self::VoiceCommand => [self::Menu],
            self::DirectWaiter => [],
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::Menu => 'Cardápio',
            self::Kds => 'KDS',
            self::Taker => 'Anotar Pedido',
            self::DirectWaiter => 'Direct Garçom',
            self::Delivery => 'Delivery',
            self::ProductionDashboard => 'Dashboard de Produção',
            self::FinancialDashboard => 'Dashboard Financeiro',
            self::DirectPrint => 'Impressão Direta',
            self::FiscalNote => 'Nota Fiscal',
            self::VoiceCommand => 'Comando por Voz',
        };
    }
}
```

---

## 4. Modelos e Relacionamentos Atualizados

### `Corporation`

```php
class Corporation extends Model
{
    // ... campos existentes ...

    public function subscription(): HasOne
    {
        return $this->hasOne(CorporationSubscription::class)
            ->whereIn('status', [SubscriptionStatus::Active, SubscriptionStatus::Trial])
            ->latest('started_at');
    }

    public function modules(): HasMany
    {
        return $this->hasMany(CorporationModule::class);
    }

    public function activeModules(): HasMany
    {
        return $this->modules()
            ->whereIn('status', [ModuleStatus::Active, ModuleStatus::Trial])
            ->where(function ($query): void {
                $query->whereNull('ended_at')->orWhere('ended_at', '>=', now());
            });
    }

    public function hasActiveModule(ModuleCode $module): bool
    {
        return $this->activeModules()
            ->where('module_code', $module->value)
            ->exists();
    }

    public function isBillingUnified(): bool
    {
        return $this->subscription?->billing_mode === BillingMode::Unified;
    }
}
```

### `Venue`

```php
class Venue extends Model
{
    // ... relações existentes ...

    public function subscription(): HasOne
    {
        return $this->hasOne(VenueSubscription::class)
            ->whereIn('status', [SubscriptionStatus::Active, SubscriptionStatus::Trial])
            ->latest('started_at');
    }

    public function modules(): HasMany
    {
        return $this->hasMany(VenueModule::class);
    }

    public function activeModules(): array
    {
        return $this->modules()
            ->whereIn('status', [ModuleStatus::Active, ModuleStatus::Trial])
            ->where(function ($query): void {
                $query->whereNull('ended_at')->orWhere('ended_at', '>=', now());
            })
            ->pluck('module_code')
            ->all();
    }

    public function activeModuleCodes(): Collection
    {
        return collect($this->activeModules());
    }

    public function usageRecords(): HasMany
    {
        return $this->hasMany(VenueUsageRecord::class);
    }
}
```

### `User`

```php
class User extends Authenticatable
{
    // ...

    public function canAccessModule(ModuleCode $module, ?string $ability = null): bool
    {
        $venue = $this->currentVenue;

        if (! $venue) {
            return false;
        }

        // 1. Módulo está ativo nesta venue?
        if (! in_array($module->value, $venue->activeModules(), true)) {
            return false;
        }

        // 2. Subscription está ativa (respeitando billing_mode)?
        if (BillingStatusService::isBlocked($venue)) {
            return false;
        }

        // 3. Role permite o acesso?
        $role = $this->currentVenueRole()?->value;

        if (! $role) {
            return false;
        }

        $permission = ModuleRolePermission::query()
            ->where('module_code', $module->value)
            ->where(fn ($q) => $q->whereNull('corporation_id')->orWhere('corporation_id', $venue->corporation_id))
            ->where('role', $role)
            ->first();

        $permissions = $permission?->permissions
            ?? ModuleCatalog::where('code', $module->value)->value('required_roles')
            ?? [];

        if ($ability === null) {
            return ! empty($permissions);
        }

        return in_array($ability, $permissions, true);
    }
}
```

---

## 5. Permissionamento

### 5.1 Níveis de controle

O acesso a uma funcionalidade é decidido em quatro níveis:

1. **Tenant ativo** — `SetVenueContext` garante que o usuário pertence à venue.
2. **Subscription em dia** — `BillingStatusService` verifica se a venue (ou a corporation, no modo unificado) está adimplente.
3. **Módulo contratado** — `RequireModule` middleware garante que a venue tem o módulo ativo.
4. **Role operacional** — `RequireRole` middleware garante que o papel do usuário na venue permite a ação.

### 5.2 Middleware `RequireModule`

```php
// app/Http/Middleware/RequireModule.php
namespace App\Http\Middleware;

use App\Enums\ModuleCode;
use App\Services\Billing\BillingStatusService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequireModule
{
    public function handle(Request $request, Closure $next, string $moduleCode): Response
    {
        $venue = app('tenant');
        $module = ModuleCode::tryFrom($moduleCode);

        if (! $venue || ! $module) {
            abort(403, 'Módulo não disponível.');
        }

        // 1. Subscription em dia (respeita billing_mode)
        if (BillingStatusService::isBlocked($venue)) {
            abort(403, 'Acesso suspenso por questões de faturamento.');
        }

        // 2. Módulo ativo nesta venue
        $activeModules = $venue->activeModules();

        if (! in_array($module->value, $activeModules, true)) {
            abort(403, 'Este módulo não está contratado para esta conta.');
        }

        // 3. Dependências transitivas
        foreach ($module->dependsOn() as $dependency) {
            if (! in_array($dependency->value, $activeModules, true)) {
                abort(403, "Dependência não atendida: {$dependency->label()}.");
            }
        }

        return $next($request);
    }
}

// app/Services/Billing/BillingStatusService.php
class BillingStatusService
{
    public static function isBlocked(Venue $venue): bool
    {
        $corporation = $venue->corporation;

        if (! $corporation?->subscription) {
            return true;
        }

        // Modo unificado: bloqueio global pela corporation
        if ($corporation->isBillingUnified()) {
            return in_array($corporation->subscription->status, [
                SubscriptionStatus::Suspended,
                SubscriptionStatus::Canceled,
            ], true);
        }

        // Modo per_venue: bloqueio individual da venue
        $venueSubscription = $venue->subscription;

        if (! $venueSubscription) {
            return true;
        }

        return in_array($venueSubscription->status, [
            SubscriptionStatus::Suspended,
            SubscriptionStatus::Canceled,
        ], true);
    }
}
```
```

Registro do alias em `bootstrap/app.php`:

```php
->withMiddleware(function (Middleware $middleware): void {
    $middleware->alias([
        'tenant' => \App\Http\Middleware\SetVenueContext::class,
        'role' => \App\Http\Middleware\RequireRole::class,
        'module' => \App\Http\Middleware\RequireModule::class,
    ]);
})
```

### 5.3 Exemplo de rotas protegidas

```php
// routes/web.php

// Cardápio — módulo base, acesso apenas a gestores
Route::middleware(['tenant', 'module:menu', 'role:owner,general_manager'])
    ->prefix('menu')
    ->name('menu.')
    ->group(function () {
        // ... rotas de categorias, produtos, combos, modificadores
    });

// KDS — módulo pago
Route::middleware(['tenant', 'module:kds'])
    ->prefix('kitchen')
    ->name('kitchen.')
    ->group(function () {
        Route::get('/kds', [KdsController::class, 'index'])->name('kds');
        Route::put('/items/{item}/status', [KdsController::class, 'updateItemStatus'])->name('items.status');
    });

// Taker (Anotar Pedido)
Route::middleware(['tenant', 'module:taker', 'role:owner,general_manager,section_manager,attendant'])
    ->get('/orders/take/{attendance}', [OrderController::class, 'create'])
    ->name('orders.take');

// Direct Garçom — módulo independente, permite sinalização
Route::middleware(['tenant', 'module:direct_waiter'])
    ->prefix('direct-waiter')
    ->name('direct-waiter.')
    ->group(function () {
        Route::get('/', [DirectWaiterController::class, 'index'])->name('index');
        Route::post('/areas', [DirectWaiterAreaController::class, 'store'])->name('areas.store');
    });
```

### 5.4 Gate para actions e policies

```php
// app/Providers/AuthServiceProvider.php ou bootstrap/app.php
Gate::define('module-access', function (User $user, string $moduleCode, ?string $ability = null): bool {
    $module = ModuleCode::tryFrom($moduleCode);

    return $module && $user->canAccessModule($module, $ability);
});
```

Uso em controllers/actions:

```php
$this->authorize('module-access', ['kds', 'operate']);
```

---

## 6. Escalabilidade e Tiers de Volume

### 6.1 Métricas de volume por módulo

| Módulo | Métrica principal | Unidade |
|---|---|---|
| Menu | — | fixo |
| KDS | pedidos despachados | `order` |
| Taker | pedidos criados | `order` |
| Direct Garçom | sinais enviados | `signal` |
| Delivery | pedidos de delivery | `order` |
| Production Dashboard | — | fixo (dados internos) |
| Financial Dashboard | — | fixo |
| Direct Print | impressões | `order` |
| Fiscal Note | notas emitidas | `order` |
| Voice Command | comandos transcritos | `signal` |

### 6.2 Limite incluso e cobrança adicional

Cada tier define:

- **`included_quantity`** — quantidade incluída no preço base mensal do módulo.
- **`overage_price_per_unit`** — preço cobrado por cada unidade que ultrapassar o limite.
- **`overage_flat_fee`** — taxa fixa aplicada quando há qualquer excedente.

> O limite se aplica **por venue**. Mesmo no modo `unified`, cada venue tem seu próprio limite e seu próprio excedente. O excedente nunca é calculado agregando o volume de todas as venues.

#### Exemplo de tiers para KDS

```php
// KDS: 500 pedidos inclusos; excedente a R$ 0,10 por pedido
ModuleUsageTier::create([
    'module_code' => ModuleCode::Kds->value,
    'min_quantity' => 0,
    'max_quantity' => 500,
    'included_quantity' => 500,
    'price_per_unit' => 0.00,
    'overage_price_per_unit' => 0.10,
]);

ModuleUsageTier::create([
    'module_code' => ModuleCode::Kds->value,
    'min_quantity' => 501,
    'max_quantity' => 2000,
    'included_quantity' => 2000,
    'price_per_unit' => 0.00,
    'overage_price_per_unit' => 0.08,
]);

ModuleUsageTier::create([
    'module_code' => ModuleCode::Kds->value,
    'min_quantity' => 2001,
    'max_quantity' => null,
    'included_quantity' => null, // ilimitado
    'price_per_unit' => 0.05,
    'overage_price_per_unit' => 0.00,
]);
```

### 6.3 Tenant compartilhado vs. dedicado

A flag `corporations.is_dedicated` já existe. Para refletir isso no preço, o `PlanCatalog` pode ter `plan_type = shared|dedicated`, ou podemos adicionar um surcharge em cada `VenueSubscription`:

| Abordagem | Onde vive | Quando usar |
|---|---|---|
| `PlanCatalog.plan_type` | catálogo | pacotes pré-montados (ex.: "Plano Básico Compartilhado", "Plano Enterprise Dedicado") |
| `VenueSubscription.dedicated_surcharge` | subscription | quando o surcharge é negociado por venue |
| `is_dedicated` + regra de precificação | billing service | quando o valor dedicado é sempre X% acima do compartilhado |

**Recomendação:** manter `plan_catalogs.plan_type` como referência de infraestrutura e adicionar `VenueSubscription.dedicated_surcharge` para flexibilidade comercial.

### 6.4 Cálculo mensal por venue

```php
// app/Services/Billing/SubscriptionCalculator.php
class SubscriptionCalculator
{
    public function calculateVenue(Venue $venue, string $period): array
    {
        $subscription = $venue->subscription;

        if (! $subscription) {
            return ['base' => 0, 'modules' => 0, 'metered' => 0, 'dedicated_surcharge' => 0, 'total' => 0];
        }

        $base = (float) $subscription->base_value;
        $modulesValue = $this->calculateModules($venue);
        $metered = $this->calculateMetered($venue, $period);
        $dedicatedSurcharge = (float) ($subscription->dedicated_surcharge ?? 0);

        $total = $base + $modulesValue + $metered + $dedicatedSurcharge;

        // Persiste snapshot na subscription da venue
        $subscription->update([
            'modules_value' => $modulesValue,
            'metered_value' => $metered,
            'total_value' => $total,
        ]);

        return [
            'base' => $base,
            'modules' => $modulesValue,
            'metered' => $metered,
            'dedicated_surcharge' => $dedicatedSurcharge,
            'total' => $total,
        ];
    }

    private function calculateModules(Venue $venue): float
    {
        $total = 0;

        $venueModules = $venue->modules()
            ->whereIn('status', [ModuleStatus::Active, ModuleStatus::Trial])
            ->get();

        foreach ($venueModules as $venueModule) {
            $corporationModule = $venue->corporation?->modules()
                ->where('module_code', $venueModule->module_code)
                ->whereIn('status', [ModuleStatus::Active, ModuleStatus::Trial])
                ->first();

            if (! $corporationModule) {
                continue;
            }

            $unitPrice = $corporationModule->custom_monthly_price
                ?? $corporationModule->catalog?->base_monthly_price
                ?? 0;

            $total += (float) $unitPrice * max(1, (int) $venueModule->quantity);
        }

        return $total;
    }

    private function calculateMetered(Venue $venue, string $period): float
    {
        $total = 0;

        $records = VenueUsageRecord::query()
            ->where('venue_id', $venue->id)
            ->where('period', $period)
            ->get();

        foreach ($records as $record) {
            $tier = ModuleUsageTier::find($record->tier_id);

            if (! $tier) {
                continue;
            }

            $included = (int) ($tier->included_quantity ?? 0);
            $quantity = max(0, (int) $record->quantity - $included);

            $basePrice = $tier->flat_price !== null
                ? (float) $tier->flat_price
                : ((float) $tier->price_per_unit * (int) $record->quantity);

            $overagePrice = 0;

            if ($quantity > 0) {
                $overagePrice += (float) ($tier->overage_flat_fee ?? 0);
                $overagePrice += $quantity * (float) $tier->overage_price_per_unit;
            }

            $record->update([
                'included_quantity' => min((int) $record->quantity, $included),
                'overage_quantity' => $quantity,
                'base_calculated_price' => $basePrice,
                'overage_calculated_price' => $overagePrice,
                'total_calculated_price' => $basePrice + $overagePrice,
            ]);

            $total += $basePrice + $overagePrice;
        }

        return $total;
    }
}
```

### 6.5 Cálculo corporate (fatura unificada)

```php
public function calculateCorporation(Corporation $corporation, string $period): array
{
    $venueTotals = [];
    $grandTotal = 0;

    foreach ($corporation->venues as $venue) {
        $venueTotals[$venue->id] = $this->calculateVenue($venue, $period);
        $grandTotal += $venueTotals[$venue->id]['total'];
    }

    return [
        'venues' => $venueTotals,
        'total' => $grandTotal,
    ];
}
```

---

## 7. Frontend e Inertia.js

### 7.1 Shared props

```php
// app/Http/Middleware/HandleInertiaRequests.php
public function share(Request $request): array
{
    $venue = $request->user()?->currentVenue;

    return array_merge(parent::share($request), [
        'tenant' => $venue ? [
            'id' => $venue->id,
            'name' => $venue->name,
            'modules' => $venue->activeModules(),
        ] : null,
        'auth' => [
            'user' => $request->user(),
            'role' => $request->user()?->currentVenueRole()?->value,
        ],
    ]);
}
```

### 7.2 Helper Vue para módulos

```ts
// resources/js/composables/useModules.ts
import { usePage } from '@inertiajs/vue3';

export function useModules() {
    const modules = usePage().props.tenant?.modules ?? [];

    return {
        hasModule: (code: string): boolean => modules.includes(code),
        canOperate: (moduleCode: string): boolean => {
            // combinar com role quando necessário
            return modules.includes(moduleCode);
        },
    };
}
```

### 7.3 Exemplo de menu condicional

```vue
<script setup>
import { useModules } from '@/composables/useModules';

const { hasModule } = useModules();
</script>

<template>
  <nav>
    <NavLink href="/dashboard">Dashboard</NavLink>
    <NavLink href="/menu">Cardápio</NavLink>
    <NavLink v-if="hasModule('kds')" href="/kitchen/kds">KDS</NavLink>
    <NavLink v-if="hasModule('taker')" href="/orders/take">Anotar Pedido</NavLink>
    <NavLink v-if="hasModule('direct_waiter')" href="/direct-waiter">Direct Garçom</NavLink>
    <NavLink v-if="hasModule('delivery')" href="/delivery">Delivery</NavLink>
  </nav>
</template>
```

---

## 8. Ciclo de Vida de Módulos e Subscriptions

### 8.1 Habilitar módulo na corporation

Antes de qualquer venue usar um módulo, ele precisa estar habilitado no nível corporate com o preço unitário negociado.

```php
// app/Actions/Platform/EnableCorporateModuleAction.php
class EnableCorporateModuleAction
{
    public function execute(Corporation $corporation, ModuleCode $module, ?float $customPrice = null): CorporationModule
    {
        foreach ($module->dependsOn() as $dependency) {
            if (! $corporation->hasActiveModule($dependency)) {
                throw new \InvalidArgumentException("Dependência não atendida: {$dependency->label()}");
            }
        }

        return CorporationModule::updateOrCreate(
            [
                'corporation_id' => $corporation->id,
                'module_code' => $module->value,
            ],
            [
                'status' => ModuleStatus::Active,
                'custom_monthly_price' => $customPrice,
                'started_at' => now(),
                'ended_at' => null,
            ]
        );
    }
}
```

### 8.2 Ativar módulo em uma venue

```php
// app/Actions/Platform/ActivateVenueModuleAction.php
class ActivateVenueModuleAction
{
    public function execute(Venue $venue, ModuleCode $module, ?int $quantity = 1): VenueModule
    {
        // 1. Módulo precisa estar habilitado na corporation
        if (! $venue->corporation?->hasActiveModule($module)) {
            throw new \InvalidArgumentException('Módulo não habilitado para esta corporation.');
        }

        // 2. Dependências precisam estar ativas na venue
        $activeModules = $venue->activeModules();

        foreach ($module->dependsOn() as $dependency) {
            if (! in_array($dependency->value, $activeModules, true)) {
                throw new \InvalidArgumentException("Dependência não atendida na venue: {$dependency->label()}");
            }
        }

        // 3. Cria ou reativa
        $venueModule = VenueModule::updateOrCreate(
            [
                'venue_id' => $venue->id,
                'module_code' => $module->value,
            ],
            [
                'status' => ModuleStatus::Active,
                'quantity' => max(1, (int) $quantity),
                'started_at' => now(),
                'ended_at' => null,
            ]
        );

        // 4. Invalida cache de conexão/módulos da venue
        app(TenantConnectionResolver::class)->invalidate($venue);

        return $venueModule;
    }
}
```

### 8.3 Remover módulo de uma venue

```php
// app/Actions/Platform/DeactivateVenueModuleAction.php
class DeactivateVenueModuleAction
{
    public function execute(Venue $venue, ModuleCode $module): void
    {
        // Verifica se outros módulos ativos na venue dependem dele
        $dependents = $venue->modules()
            ->whereIn('status', [ModuleStatus::Active, ModuleStatus::Trial])
            ->get()
            ->filter(fn ($m) => in_array($module->value, ModuleCode::tryFrom($m->module_code)?->dependsOn() ?? [], true));

        if ($dependents->isNotEmpty()) {
            $labels = $dependents->map(fn ($m) => ModuleCode::tryFrom($m->module_code)?->label())->implode(', ');
            throw new \InvalidArgumentException("Desative primeiro: {$labels}");
        }

        VenueModule::query()
            ->where('venue_id', $venue->id)
            ->where('module_code', $module->value)
            ->update([
                'status' => ModuleStatus::Canceled,
                'ended_at' => now(),
            ]);

        app(TenantConnectionResolver::class)->invalidate($venue);
    }
}
```

### 8.4 Desabilitar módulo na corporation

Só é permitido quando nenhuma venue do grupo ainda usa o módulo.

```php
// app/Actions/Platform/DisableCorporateModuleAction.php
class DisableCorporateModuleAction
{
    public function execute(Corporation $corporation, ModuleCode $module): void
    {
        $activeInVenues = VenueModule::query()
            ->whereIn('venue_id', $corporation->venues()->pluck('id'))
            ->where('module_code', $module->value)
            ->whereIn('status', [ModuleStatus::Active, ModuleStatus::Trial])
            ->exists();

        if ($activeInVenues) {
            throw new \InvalidArgumentException('Desative o módulo em todas as venues primeiro.');
        }

        CorporationModule::query()
            ->where('corporation_id', $corporation->id)
            ->where('module_code', $module->value)
            ->update([
                'status' => ModuleStatus::Canceled,
                'ended_at' => now(),
            ]);
    }
}
```

---

## 9. Mapeamento dos Módulos do NeuraBar

### 9.1 Módulos básicos (sempre ativos)

| Módulo | Código | Dependências | Billing | Observação |
|---|---|---|---|---|
| Gestão de Usuários | `user_management` | — | fixed | Incluso no plano base |
| Configurações | `settings` | — | fixed | Incluso no plano base |
| Cardápio | `menu` | — | fixed | Base de todos os módulos pagos |

> **Decisão de produto:** o Cardápio pode ser gratuito e obrigatório, pois sem ele nenhum módulo operacional faz sentido.

### 9.2 Módulos pagos

| Módulo | Código | Dependências | Billing | Métrica de volume |
|---|---|---|---|---|
| Direct Garçom | `direct_waiter` | — | fixed + metered | sinais enviados |
| KDS | `kds` | `menu` | fixed + metered | pedidos despachados |
| Anotar Pedido | `taker` | `menu` | fixed + metered | pedidos criados |
| Delivery | `delivery` | `menu` | fixed + metered | pedidos delivery |
| Dashboard de Produção | `production_dashboard` | `menu` | fixed | — |
| Dashboard Financeiro | `financial_dashboard` | `menu` | fixed | — |
| Impressão Direta | `direct_print` | `menu` | fixed + metered | pedidos impressos |
| Nota Fiscal | `fiscal_note` | `menu` | fixed + metered | cupons emitidos |
| Comando por Voz | `voice_command` | `menu` | fixed + metered | comandos transcritos |

---

## 10. Fluxo de Cobrança

```
Plataforma cria/altera subscription
    │
    ├── Define billing_mode (unified | per_venue)
    ├── Define plano base por venue (PlanCatalog)
    ├── Habilita/desabilita módulos na corporation (preço unitário)
    ├── Ativa/desativa módulos por venue
    ├── Define dedicated_surcharge por venue
    └── Define tiers de volume
            │
            ▼
    Uso operacional gera eventos
    (OrderPlaced, GuestSignaled, FiscalNoteIssued, etc.)
            │
            ▼
    Jobs atualizam VenueUsageRecord (por venue, nunca por corporate)
            │
            ▼
    Rotina diária/mensal roda SubscriptionCalculator
    │
    ├── billing_mode = per_venue
    │       → gera VenueInvoice para cada venue
    │
    └── billing_mode = unified
            → gera CorporationInvoice agregando todas as VenueSubscriptions
            │
            ▼
    Integração com gateway de pagamento
```

### Tabelas de fatura

#### `venue_invoices`

| Coluna | Tipo | Descrição |
|---|---|---|
| `id` | UUID PK | |
| `venue_id` | UUID FK | |
| `corporation_invoice_id` | UUID FK nullable | Vincula à fatura unificada, quando aplicável |
| `affiliate_code_id` | UUID FK nullable | → `affiliate_codes.id` (código que originou a receita) |
| `period` | varchar | YYYY-MM |
| `status` | varchar | `open`, `paid`, `canceled`, `overdue` |
| `base_value` | decimal | Plano base da venue |
| `modules_value` | decimal | Soma dos módulos ativos |
| `metered_value` | decimal | Volume excedente |
| `dedicated_surcharge` | decimal | Surcharge de infra dedicada |
| `total_value` | decimal | |
| `paid_at` | datetime nullable | |

#### `corporation_invoices`

| Coluna | Tipo | Descrição |
|---|---|---|
| `id` | UUID PK | |
| `corporation_id` | UUID FK | |
| `affiliate_code_id` | UUID FK nullable | → `affiliate_codes.id` (código que originou a receita) |
| `period` | varchar | YYYY-MM |
| `status` | varchar | `open`, `paid`, `canceled`, `overdue` |
| `base_value` | decimal | Soma das bases |
| `modules_value` | decimal | Soma dos módulos |
| `metered_value` | decimal | Soma dos excedentes |
| `dedicated_surcharge` | decimal | Soma dos surcharges |
| `total_value` | decimal | |
| `paid_at` | datetime nullable | |

> No modo `per_venue`, cada `venue_invoices` é uma cobrança independente. No modo `unified`, a `corporation_invoices` agrupa as `venue_invoices` vinculadas, mas os bloqueios seguem a `corporation_invoices` (pagamento único).

---

## 11. Backoffice — Gestão Comercial

### Telas necessárias no `/backoffice`

1. **Subscriptions**
   - Listar corporations com status da subscription
   - Criar/editar `CorporationSubscription` (billing_mode, dia de vencimento)
   - Criar/editar `VenueSubscription` por venue
   - Visualizar faturas (unificadas e por venue)

2. **Catálogo de Módulos**
   - CRUD de `ModuleCatalog`
   - CRUD de `ModuleUsageTier` por módulo (incluído + excedente)
   - CRUD de `ModuleRolePermission` padrão

3. **Corporations / Modules**
   - Habilitar/desabilitar módulos na corporation (preço unitário)
   - Ativar/desativar módulos por venue
   - Definir `quantity` por venue (garçons, locais, etc.)
   - Definir `dedicated_surcharge` por venue

4. **Métricas de Uso**
   - Consumo por venue/módulo/período
   - Limite vs. excedente por venue
   - Projeção de fatura (unificada e por venue)

5. **Afiliados (futuro)**
   - CRUD de `AffiliateCode` (código fixo, nome, email, status)
   - Vincular `affiliate_code_id` em corporations, venues, subscriptions e faturas
   - Relatórios simples via queries diretas: novos cadastros, faturamento de indicados, etc.

---

## 12. Considerações de Segurança e Isolamento

1. **Módulos são verificados no backend** — nunca confie apenas no frontend para ocultar rotas.
2. **Bloqueio financeiro segue o `billing_mode`** — unificado bloqueia globalmente; por venue bloqueia localmente.
3. **Dependências são validadas no ato da ativação e da desativação** — evita estados inconsistentes.
4. **Banco compartilhado continua usando `TenantScope`** — dados operacionais permanecem isolados por `venue_id`.
5. **Banco dedicado não precisa de `TenantScope`** — o próprio `TenantScope` já ignora quando `operational_is_dedicated` é true.
6. **Cache de conexão deve ser invalidado ao alterar módulos** — para que o frontend e workers vejam o estado atualizado.
7. **Limite de volume é por venue** — mesmo no modo `unified`, o cálculo de excedente nunca agrega venues.
8. **Rotinas de billing rodam fora do request do cliente** — use queues para não bloquear operações.
9. **Rastreio de afiliado é opt-in e não obrigatório** — todos os pontos de vínculo (`affiliate_code_id`) são nullable para não quebrar o fluxo comercial atual.

---

## 13. Rastreio de Afiliados (base para futuro)

### 13.1 Visão geral

Por enquanto o sistema de afiliados é apenas um **rastreamento por código fixo**. Não há cálculo automático de comissão, saques ou painel de afiliado. O objetivo é permitir, no futuro, consultas no banco como:

```sql
-- Novos cadastros indicados por um afiliado no mês
SELECT COUNT(*) AS new_corporations
FROM corporations
WHERE affiliate_code_id = :affiliate_code_id
  AND DATE_TRUNC('month', created_at) = DATE_TRUNC('month', CURRENT_DATE);

-- Faturamento total gerado por indicados no mês
SELECT SUM(total_value) AS total_billed
FROM venue_invoices
WHERE affiliate_code_id = :affiliate_code_id
  AND period = TO_CHAR(CURRENT_DATE, 'YYYY-MM')
  AND status = 'paid';

-- Faturamento de uma corporation específica (corporate unificado)
SELECT SUM(total_value) AS total_billed
FROM corporation_invoices
WHERE affiliate_code_id = :affiliate_code_id
  AND period = :period
  AND status = 'paid';
```

### 13.2 Pontos de vínculo do código

O código de afiliado pode ser vinculado em qualquer um destes pontos, conforme o momento da indicação:

| Entidade | Campo | Quando usar |
|---|---|---|
| `Corporation` | `affiliate_code_id` | Cliente foi indicado no momento do cadastro da corporation |
| `Venue` | `affiliate_code_id` | Uma venue específica foi indicada |
| `CorporationSubscription` | `affiliate_code_id` | A assinatura corporate foi originada por um afiliado |
| `VenueSubscription` | `affiliate_code_id` | A assinatura da venue foi originada por um afiliado |
| `VenueInvoice` | `affiliate_code_id` | Redundância para facilitar relatórios de faturamento |
| `CorporationInvoice` | `affiliate_code_id` | Redundância para facilitar relatórios de faturamento |

### 13.3 Propagação sugerida

Quando um código é informado no cadastro inicial, ele deve ser propagado automaticamente:

```php
// Exemplo ao criar uma Corporation
$corporation = Corporation::create([
    'owner_id' => $user->id,
    'name' => $data['name'],
    'affiliate_code_id' => $data['affiliate_code_id'] ?? null,
]);

// Propaga para a primeira venue
$venue = Venue::create([
    'corporation_id' => $corporation->id,
    'name' => $data['venue_name'],
    'affiliate_code_id' => $corporation->affiliate_code_id,
]);

// Propaga para subscriptions
$corporationSubscription = CorporationSubscription::create([
    'corporation_id' => $corporation->id,
    'affiliate_code_id' => $corporation->affiliate_code_id,
    'billing_mode' => BillingMode::PerVenue,
]);

$venueSubscription = VenueSubscription::create([
    'venue_id' => $venue->id,
    'corporation_subscription_id' => $corporationSubscription->id,
    'affiliate_code_id' => $venue->affiliate_code_id,
]);
```

> A propagação automática evita que o código seja perdido ao longo do ciclo de vida. Caso a corporation mude de afiliado no futuro, as faturas antigas mantêm o código original (histórico imutável).

---

## 14. Plano de Implementação Incremental

### Fase 1 — Fundação (semana 1)
- [ ] Criar migrations: `module_catalogs`, `corporation_subscriptions`, `venue_subscriptions`, `corporation_modules`, `venue_modules`, `module_usage_tiers`, `module_role_permissions`, `venue_usage_records`, `venue_invoices`, `corporation_invoices`, `affiliate_codes`.
- [ ] Adicionar `affiliate_code_id` nas migrations de `corporations`, `venues`, `corporation_subscriptions`, `venue_subscriptions`, `venue_invoices` e `corporation_invoices`.
- [ ] Criar Enums: `BillingMode`, `ModuleCode`, `ModuleBillingType`, `ModuleStatus`, `SubscriptionStatus`, `AffiliateCodeStatus`.
- [ ] Criar Models e relacionamentos.
- [ ] Criar seeders para módulos base e pagos e para códigos de afiliado de exemplo.
- [ ] Criar middleware `RequireModule` e registrar alias `module`.

### Fase 2 — Permissionamento e Status Financeiro (semana 2)
- [ ] Adicionar `hasActiveModule()` e `activeModules()` em `Corporation` / `Venue`.
- [ ] Criar `BillingStatusService` e integrar ao `RequireModule`.
- [ ] Adicionar `canAccessModule()` em `User`.
- [ ] Criar Gate `module-access`.
- [ ] Proteger rotas existentes (`menu`, `kitchen`, `orders/take`, etc.).
- [ ] Adicionar `tenant.modules` nas shared props do Inertia.
- [ ] Criar composable `useModules()` no Vue.

### Fase 3 — Gestão Comercial no Backoffice (semana 3)
- [ ] CRUD de catálogo de módulos.
- [ ] CRUD de tiers de volume (incluído + excedente).
- [ ] Tela de subscription da corporation (billing_mode, vencimento).
- [ ] Tela de subscription por venue.
- [ ] Telas de habilitação/ativação de módulos (corporate + por venue).
- [ ] Ações `EnableCorporateModuleAction`, `ActivateVenueModuleAction`, `DeactivateVenueModuleAction`, `DisableCorporateModuleAction`.

### Fase 4 — Métricas e Billing (semana 4)
- [ ] Emitir eventos de uso: `OrderPlaced`, `GuestSignaled`, `FiscalNoteIssued`, etc.
- [ ] Jobs para incrementar `VenueUsageRecord`.
- [ ] `SubscriptionCalculator` (por venue + corporate unificado).
- [ ] Tabelas `venue_invoices` e `corporation_invoices` e rotina de fechamento mensal.

### Fase 5 — Módulos novos (semanas 5+)
- [ ] Implementar cada módulo pago pendente já protegido por `module:`.
- [ ] Adicionar dependências corretas e validações.
- [ ] Testar upgrade/downgrade de módulos e troca de `billing_mode`.

### Fase 6 — Rastreio de Afiliados (semana 6+)
- [ ] CRUD de `AffiliateCode` no backoffice.
- [ ] Permitir vincular `affiliate_code_id` na criação/edição de `Corporation`, `Venue`, `CorporationSubscription`, `VenueSubscription`, `VenueInvoice` e `CorporationInvoice`.
- [ ] Documentar queries de exemplo para relatórios de comissão (novos cadastros, faturamento de indicados, etc.).

---

## 14. Referência de Arquivos

| Arquivo | Responsabilidade |
|---|---|
| `app/Enums/BillingMode.php` | Modos de faturamento (`per_venue`, `unified`) |
| `app/Enums/ModuleCode.php` | Códigos canônicos, labels e dependências |
| `app/Enums/ModuleBillingType.php` | Tipo de cobrança |
| `app/Enums/ModuleStatus.php` | Status do módulo contratado |
| `app/Enums/SubscriptionStatus.php` | Status da subscription |
| `app/Models/Tenant/ModuleCatalog.php` | Catálogo de produtos |
| `app/Models/Tenant/CorporationSubscription.php` | Subscription da corporation (billing_mode) |
| `app/Models/Tenant/VenueSubscription.php` | Subscription de faturamento por venue |
| `app/Models/Tenant/CorporationModule.php` | Módulos habilitados na corporation (preço unitário) |
| `app/Models/Tenant/VenueModule.php` | Módulos ativos por venue |
| `app/Models/Tenant/ModuleUsageTier.php` | Faixas de preço, limite incluso e excedente |
| `app/Models/Tenant/VenueUsageRecord.php` | Consumo mensal por venue |
| `app/Models/Tenant/ModuleRolePermission.php` | Permissões por módulo/role |
| `app/Models/Tenant/VenueInvoice.php` | Fatura por venue |
| `app/Models/Tenant/CorporationInvoice.php` | Fatura unificada da corporation |
| `app/Models/Tenant/AffiliateCode.php` | Código fixo de afiliado para rastreio |
| `app/Enums/AffiliateCodeStatus.php` | Status do código de afiliado |
| `app/Http/Middleware/RequireModule.php` | Middleware de bloqueio por módulo + status financeiro |
| `app/Services/Module/ModuleAccessService.php` | Verifica acesso do usuário a módulo/ability |
| `app/Services/Billing/BillingStatusService.php` | Resolve bloqueio conforme `billing_mode` |
| `app/Services/Billing/SubscriptionCalculator.php` | Calcula fatura mensal |
| `app/Actions/Platform/EnableCorporateModuleAction.php` | Habilita módulo na corporation |
| `app/Actions/Platform/ActivateVenueModuleAction.php` | Ativa módulo em uma venue |
| `app/Actions/Platform/DeactivateVenueModuleAction.php` | Remove módulo de uma venue |
| `app/Actions/Platform/DisableCorporateModuleAction.php` | Desabilita módulo na corporation |
| `app/Providers/AuthServiceProvider.php` | Gate `module-access` |
| `app/Http/Middleware/HandleInertiaRequests.php` | Shared prop `tenant.modules` |
| `resources/js/composables/useModules.ts` | Helper Vue para módulos |
