# User ↔ Venue Architecture

**Versão:** 1.0 · **Data:** 27 de maio de 2026

> Referência técnica do modelo de identidade e acesso do NeuraBar: como usuários se relacionam com Corporations, Venues e roles operacionais.

---

## Visão Geral

```
PlatformUser  (guard: platform)
      │
      │  administra
      ▼
Corporation  ─── owner_id ──►  User
      │
      │  has many
      ▼
    Venue  ◄────── user_venue (pivot) ──────►  User
                   role: UserRole (operacional)
```

Um `User` pode pertencer a **múltiplas Venues** através da tabela pivot `user_venue`. Cada entrada no pivot carrega a `role` daquele usuário naquela venue específica. A venue ativa no momento é rastreada por `users.current_venue_id`.

`PlatformUser` é uma entidade **completamente separada** (guard `platform`, tabela `platform_users`) usada pela equipe interna do NeuraBar. Não compartilha tabela, guard ou enum com os usuários SaaS.

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

## Fluxo de Registro (novo Owner)

1. Usuário preenche o formulário de registro com nome, email e senha.
2. `CreateNewUser` cria o `User` (sem venue ainda).
3. `CreateUserOwnerDefinitions` (disparado por evento) cria em sequência:
   - `Corporation` com `owner_id = $user->id`
   - `Venue` associada à corporation
   - Entrada em `user_venue` com `role = UserRole::Owner`
   - Atualiza `users.current_venue_id` para a nova venue
4. Fortify autentica o usuário e redireciona para `/dashboard`.

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

O middleware `SetVenueContext` (alias `tenant`) é responsável por injetar a venue no container:

```
Request chega
    │
    ├── Usuário não autenticado → passa adiante (Fortify cuida do redirect)
    │
    ├── users.current_venue_id = null → redirect para /no-venue
    │
    ├── user_venue não tem entrada para esta venue → abort(403)
    │
    └── OK → app()->instance('tenant', $venue)
             → request->merge(['_venue' => $venue])
             → passa adiante
```

**Rotas que NÃO usam o middleware `tenant`:**
- `/login`, `/register` e rotas Fortify
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

## Platform Users

`PlatformUser` é uma entidade separada para a equipe interna do NeuraBar:

- Tabela: `platform_users`
- Guard: `platform`
- Roles: `UserRole::SuperAdmin`, `Finance`, `Registration`, `ReadOnly`
- Login: `/_platform/login` (prefixo configurável em `config/platform.php`)
- **Nunca** interage com `user_venue` ou `current_venue_id`

O middleware `RequirePlatformRole` (alias `platform_role`) protege as rotas do painel de plataforma.

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
