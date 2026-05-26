
## Dynamic Translation System

### Architecture
Sistema de tradução dinâmica que registra automaticamente strings não traduzidas e as organiza por componente Vue.

### Backend Components

#### TranslationService (`app/Services/TranslationService.php`)
- `getAllTranslations($locale)`: Carrega todas as traduções do idioma
- `setTranslations($request, $route)`: Registra nova string para tradução
- `getCustomLocale()`: Detecta idioma do usuário (session → cookie → app default)
- Idiomas suportados: `en`, `es`, `pt`

#### Storage Structure
```
resources/translations/
├── en/
│   ├── Dashboard.json
│   └── AppLayout.json
├── es/
└── pt/
    ├── Dashboard.json    # {"Dashboard": "Dashboard", "Welcome": "Bem-vindo"}
    └── AppLayout.json
```

### Frontend Integration

#### Shared Props (HandleInertiaRequests)
```php
'language' => TranslationService::getLanguagesDefinitions($request),
// Disponível em: $page.props.language.lang
```

#### Composable: useTranslate (`resources/js/Composables/useTranslate.js`)

**Uso Básico:**
```javascript
import { useTranslate } from '@/Composables/useTranslate'

const __ = useTranslate()

// Em template:
<h1>{{ __('Welcome to Dashboard') }}</h1>

// Com bindings (substituição de variáveis):
<p>{{ __('Hello :username', { username: user.name }) }}</p>
// Output: "Hello João"
```

#### Global Plugin (autoGlobalInject.js)
O composable `__()` é injetado globalmente, disponível em todos os componentes sem import quando aplicado ao template:

```vue
<template>
    <div>
        <h1>{{ __('Dashboard') }}</h1>
        <p>{{ __('Welcome back, :name', { name: $page.props.auth.user.name }) }}</p>
    </div>
</template>

<script setup>
//É necessario importar apenas se usado no script
import { useTranslate } from '@/Composables/useTranslate'
const __ = useTranslate()
</script>
```

### How It Works

1. **Primeira Renderização**: String não traduzida é exibida como está
2. **Auto-registro**: `axios.post(route('api.set.translations'))` registra a string automaticamente
3. **Próxima Carga**: String traduzida é carregada de `resources/translations/{locale}/{ComponentName}.json`
4. **Organização por Componente**: Cada arquivo JSON corresponde ao nome do componente Vue

### Translation Routes
```php
// routes/api.php
Route::get('/api/available-languages', [TranslationsController::class, 'availableLanguages']);
Route::post('/api/set/locale', [TranslationsController::class, 'setLocale']);
Route::post('/api/set/translations/{page}', [TranslationsController::class, 'setTranslations']);
```

### Adding Translations to New Components

```vue
<template>
    <AppLayout>
        <h1>{{ __('Account Settings') }}</h1>
        <p>{{ __('Manage your account preferences') }}</p>
        
        <!-- Com variáveis dinâmicas -->
        <span>{{ __('Created at :date', { date: moment(account.created_at).format('DD/MM/YYYY') }) }}</span>
    </AppLayout>
</template>

<script setup>
// __() já disponível globalmente, não precisa importar
import moment from 'moment'

const props = defineProps({
    account: Object
})
</script>
```

**Resultado**: Arquivo `resources/translations/pt/AccountSettings.json` será criado automaticamente:
```json
{
    "Account Settings": "Configurações da Conta",
    "Manage your account preferences": "Gerencie as preferências da sua conta",
    "Created at :date": "Criado em :date"
}
```

### Switching Languages
```javascript
// Trocar idioma via API
axios.post(route('api.set.locale'), { locale: 'en' })
    .then(() => window.location.reload())
```

### Best Practices

1. **Use strings descritivas em inglês** como chave base
2. **Sempre use bindings** para valores dinâmicos (`:variableName`)
3. **Não traduza manualmente no código** - deixe o sistema registrar
4. **Revise arquivos JSON** periodicamente para melhorar traduções
5. **Componentes reutilizáveis** devem ter traduções genéricas
