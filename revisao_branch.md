# Relatório de Revisão – feat/painel-assinaturas vs feat/sistema-modulos-assinaturas

## Resumo da Task
Implementar o "Portal de Autogestão da Assinatura e Camada de Pagamento" conforme tech-plan anexado em `docs/current_feat/tech-plan_portal_autogestao_pagamento.md`. O escopo inclui: contrato de gateway de pagamento, serviço orquestrador, gateway fake, actions de assinatura, controllers Inertia, webhook público, páginas Vue, migrations, enum de métodos de pagamento, gate de permissão e testes de feature.

## Resultado da Revisão
- **Arquivos revisados:** 40
- **Total de findings:** 25 ([CRITICAL]: 3, [WARNING]: 16, [DEBT]: 6)

## Correções Aplicadas

### Segurança
- [FIXED] `SubscriptionInvoiceController::resolveInvoice()` agora escopa faturas pela corporação do usuário logado. Acesso a faturas de outras corporações retorna 403.
- [FIXED] `SubscriptionController::store()` e `destroy()` agora validam que a venue pertence à corporação do usuário atual via `ensureVenueBelongsToCurrentCorporation()`.
- [FIXED] `ProcessWebhookPaymentAction` agora rejeita requisições quando `subscription.payment.webhook_token` não está configurado, lançando `InvalidWebhookTokenException`.

### Corretude e Lógica
- [FIXED] Criado o componente `resources/js/Pages/Settings/Subscription/InvoiceShow.vue` que era referenciado pela rota `settings.subscription.invoices.show`.
- [FIXED] Corrigida referência a `corporation` não declarada em `resources/js/Pages/Settings/Subscription/Invoices.vue`.

### Padrões e Arquitetura
- [FIXED] `AppServiceProvider` agora lança `RuntimeException` em ambientes não-local quando `SUBSCRIPTION_PAYMENT_GATEWAY` não está configurado, evitando o uso acidental do `FakePaymentGateway` em produção.

### Webhook
- [FIXED] `PaymentWebhookController` agora retorna 401 apenas para token inválido/ausente e 400 para erros de payload/negócio, evitando reenvios desnecessários por parte dos gateways.

## Status Pós-Correção
- **Testes de feature:** 13 passando (`PortalSubscriptionTest`)
- **Testes de regressão:** 49 passando (`tests/Feature/Billing`)
- **Total:** 62 passando (111 assertions)
- **Pint:** passou com correções automáticas

## Findings Pendentes (não críticos)

### Segurança
- [WARNING] [app/Services/Subscription/FakePaymentGateway.php](app/Services/Subscription/FakePaymentGateway.php#L99-L120) — `handleWebhook()` não valida assinatura ou origem do payload. É fake, mas o contrato `PaymentGatewayContract` não prevê mecanismo de validação para implementações reais.
- [WARNING] [resources/js/Pages/Settings/Subscription/PaymentMethods.vue](resources/js/Pages/Settings/Subscription/PaymentMethods.vue#L78-L110) — o formulário envia número do cartão, CVV e CPF do titular em texto plano para o backend. Aumenta escopo PCI; em produção deve-se tokenizar client-side antes de enviar.
- [WARNING] [app/Services/Subscription/PaymentSaasService.php](app/Services/Subscription/PaymentSaasService.php#L18-L44) — dados sensíveis de cartão (`$cardData`) trafegam pelo serviço sem sanitização/redação. Instrumentações futuras de log/evento podem vazar PII.

### Corretude e Lógica
- [WARNING] [app/Actions/Subscription/SubscribeModuleAction.php](app/Actions/Subscription/SubscribeModuleAction.php#L36-L39) — reativação de um módulo já ativo redefine `started_at = now()`, o que pode distorcer cálculos de pró-rata/faturamento.
- [WARNING] [app/Services/Subscription/PaymentSaasService.php](app/Services/Subscription/PaymentSaasService.php#L118-L137) — `recordAttempt()` usa `updateOrCreate` com chave `gateway_payment_id`. Colisões (ainda que improváveis com `md5(now())`) sobrescrevem tentativas anteriores.
- [WARNING] [app/Services/Subscription/FakePaymentGateway.php](app/Services/Subscription/FakePaymentGateway.php#L126-L145) — `fakeCharge()` atualiza a fatura para `Paid` diretamente, enquanto `PaymentSaasService::handleWebhook()` também atualiza faturas. Lógica duplicada e risco de transições inconsistentes entre camadas.
- [WARNING] [app/Actions/Subscription/CancelSubscriptionAction.php](app/Actions/Subscription/CancelSubscriptionAction.php) — a action existe e está implementada, mas não está exposta em nenhuma rota, controller ou página Vue. Confirmado por `route:list`.

### Performance
- [DEBT] [app/Http/Controllers/Settings/SubscriptionController.php](app/Http/Controllers/Settings/SubscriptionController.php#L41-L45) — `$corporation->venues->pluck('id')` carrega todas as venues da corporação em memória. Sem limitação, corporações com muitas unidades podem ter degradação.
- [DEBT] [app/Http/Controllers/Settings/SubscriptionController.php](app/Http/Controllers/Settings/SubscriptionController.php#L33-L37) — listagem de módulos disponíveis não possui paginação. Para catálogos grandes, a resposta Inertia cresce sem controle.

### Complexidade e Manutenibilidade
- [WARNING] [app/Http/Controllers/Settings/SubscriptionController.php](app/Http/Controllers/Settings/SubscriptionController.php#L25-L61) — `index()` possui ~66 linhas e acumula múltiplas responsabilidades (subscription, módulos, venues, status de billing).
- [WARNING] [app/Http/Controllers/Settings/SubscriptionInvoiceController.php](app/Http/Controllers/Settings/SubscriptionInvoiceController.php#L25-L51) — `index()` possui ~57 linhas e gerencia duas paginações independentes.
- [DEBT] [app/Actions/Subscription/SavePaymentMethodAction.php](app/Actions/Subscription/SavePaymentMethodAction.php#L10-L15) — action é apenas wrapper do `PaymentSaasService`. Poderia ser eliminada e o controller chamar o serviço diretamente.

### Padrões e Arquitetura
- [WARNING] [app/Contracts/Subscription/PaymentGatewayContract.php](app/Contracts/Subscription/PaymentGatewayContract.php#L14-L50) — contrato retorna arrays genéricos sem DTOs tipados. Dificulta refatoração e aumenta risco de usar chaves inexistentes.
- [DEBT] [config/subscription.php](config/subscription.php#L17) — `webhook_token` é opcional. Configuração de segurança não deveria permitir autenticação desabilitada.

## Findings Resolvidos

### Segurança (antigos CRITICAL)
- [RESOLVED] [app/Http/Controllers/Settings/SubscriptionInvoiceController.php] — IDOR em faturas corrigido.
- [RESOLVED] [app/Http/Controllers/Settings/SubscriptionController.php] — IDOR em venues/módulos corrigido.
- [RESOLVED] [app/Actions/Subscription/ProcessWebhookPaymentAction.php] — webhook sem token agora rejeita requisições.
- [WARNING] [app/Http/Controllers/Api/PaymentWebhookController.php](app/Http/Controllers/Api/PaymentWebhookController.php#L29-L33) — retorna HTTP 401 para `InvalidArgumentException` (ex: fatura não encontrada, gateway não suportado). Código semântico incorreto; gateways podem interpretar como falha de autenticação e reenviar o evento.
- [WARNING] [app/Services/Subscription/FakePaymentGateway.php](app/Services/Subscription/FakePaymentGateway.php#L99-L120) — `handleWebhook()` não valida assinatura ou origem do payload. É fake, mas o contrato `PaymentGatewayContract` não prevê mecanismo de validação para implementações reais.
- [WARNING] [resources/js/Pages/Settings/Subscription/PaymentMethods.vue](resources/js/Pages/Settings/Subscription/PaymentMethods.vue#L78-L110) — o formulário envia número do cartão, CVV e CPF do titular em texto plano para o backend. Aumenta escopo PCI; em produção deve-se tokenizar client-side antes de enviar.
- [WARNING] [app/Services/Subscription/PaymentSaasService.php](app/Services/Subscription/PaymentSaasService.php#L18-L44) — dados sensíveis de cartão (`$cardData`) trafegam pelo serviço sem sanitização/redação. Instrumentações futuras de log/evento podem vazar PII.

### Corretude e Lógica
- [CRITICAL] [app/Http/Controllers/Settings/SubscriptionInvoiceController.php](app/Http/Controllers/Settings/SubscriptionInvoiceController.php#L76-L86) — `show()` renderiza o componente Inertia `Settings/Subscription/InvoiceShow`, mas o arquivo `resources/js/Pages/Settings/Subscription/InvoiceShow.vue` não existe. A rota `settings.subscription.invoices.show` falha.
- [WARNING] [resources/js/Pages/Settings/Subscription/Invoices.vue](resources/js/Pages/Settings/Subscription/Invoices.vue#L84) — expressão `invoice.venue?.name ?? corporation?.name ?? '-'` referencia `corporation`, variável não declarada nas props. A corporação pagadora não é exibida.
- [WARNING] [app/Actions/Subscription/SubscribeModuleAction.php](app/Actions/Subscription/SubscribeModuleAction.php#L36-L39) — reativação de um módulo já ativo redefine `started_at = now()`, o que pode distorcer cálculos de pró-rata/faturamento.
- [WARNING] [app/Services/Subscription/PaymentSaasService.php](app/Services/Subscription/PaymentSaasService.php#L118-L137) — `recordAttempt()` usa `updateOrCreate` com chave `gateway_payment_id`. Colisões (ainda que improváveis com `md5(now())`) sobrescrevem tentativas anteriores.
- [WARNING] [app/Services/Subscription/FakePaymentGateway.php](app/Services/Subscription/FakePaymentGateway.php#L126-L145) — `fakeCharge()` atualiza a fatura para `Paid` diretamente, enquanto `PaymentSaasService::handleWebhook()` também atualiza faturas. Lógica duplicada e risco de transições inconsistentes entre camadas.
- [WARNING] [app/Actions/Subscription/CancelSubscriptionAction.php](app/Actions/Subscription/CancelSubscriptionAction.php) — a action existe e está implementada, mas não está exposta em nenhuma rota, controller ou página Vue. Confirmado por `route:list`.

### Performance
- [DEBT] [app/Http/Controllers/Settings/SubscriptionController.php](app/Http/Controllers/Settings/SubscriptionController.php#L41-L45) — `$corporation->venues->pluck('id')` carrega todas as venues da corporação em memória. Sem limitação, corporações com muitas unidades podem ter degradação.
- [DEBT] [app/Http/Controllers/Settings/SubscriptionController.php](app/Http/Controllers/Settings/SubscriptionController.php#L33-L37) — listagem de módulos disponíveis não possui paginação. Para catálogos grandes, a resposta Inertia cresce sem controle.

### Complexidade e Manutenibilidade
- [WARNING] [app/Http/Controllers/Settings/SubscriptionController.php](app/Http/Controllers/Settings/SubscriptionController.php#L25-L61) — `index()` possui ~66 linhas e acumula múltiplas responsabilidades (subscription, módulos, venues, status de billing).
- [WARNING] [app/Http/Controllers/Settings/SubscriptionInvoiceController.php](app/Http/Controllers/Settings/SubscriptionInvoiceController.php#L25-L51) — `index()` possui ~57 linhas e gerencia duas paginações independentes.
- [DEBT] [app/Actions/Subscription/SavePaymentMethodAction.php](app/Actions/Subscription/SavePaymentMethodAction.php#L10-L15) — action é apenas wrapper do `PaymentSaasService`. Poderia ser eliminada e o controller chamar o serviço diretamente.

### Padrões e Arquitetura
- [WARNING] [app/Providers/AppServiceProvider.php](app/Providers/AppServiceProvider.php#L28-L31) — `FakePaymentGateway` é fallback padrão quando `SUBSCRIPTION_PAYMENT_GATEWAY` não está configurado. Risco de produção processar cobranças reais através do gateway fake.
- [WARNING] [app/Contracts/Subscription/PaymentGatewayContract.php](app/Contracts/Subscription/PaymentGatewayContract.php#L14-L50) — contrato retorna arrays genéricos sem DTOs tipados. Dificulta refatoração e aumenta risco de usar chaves inexistentes.
- [DEBT] [config/subscription.php](config/subscription.php#L17) — `webhook_token` é opcional. Configuração de segurança não deveria permitir autenticação desabilitada.

### Cobertura de Testes
- [RESOLVED] [tests/Feature/Subscription/PortalSubscriptionTest.php](tests/Feature/Subscription/PortalSubscriptionTest.php) — cenários IDOR cobertos: acesso a faturas/venues de outras corporações agora testado.
- [WARNING] [tests/Feature/Subscription/PortalSubscriptionTest.php](tests/Feature/Subscription/PortalSubscriptionTest.php) — não testa falha de pagamento com cartão (`simulate_failure`).
- [RESOLVED] [tests/Feature/Subscription/PortalSubscriptionTest.php](tests/Feature/Subscription/PortalSubscriptionTest.php) — cenário de webhook sem token configurado agora testado.
- [RESOLVED] [tests/Feature/Subscription/PortalSubscriptionTest.php](tests/Feature/Subscription/PortalSubscriptionTest.php) — rota `settings.subscription.invoices.show` agora possui componente e é funcional.
- [DEBT] [tests/Feature/Subscription/PortalSubscriptionTest.php](tests/Feature/Subscription/PortalSubscriptionTest.php) — faltam casos de borda como fatura já finalizada, cartão expirado e cancelamento.

## Score de Qualidade
- **Pontuação:** 6/10 (todos os blockers críticos resolvidos; findings menores de segurança, arquitetura e dívida técnica pendentes)
- **Pontuação de Dívida Técnica:** 5/10 (controllers ainda acima do limite, arrays sem DTOs, action wrapper, duplicação de update de fatura)
- **Parecer Final:** APROVADO COM PENDÊNCIAS — os 3 blockers críticos foram corrigidos e todos os testes passam. Recomenda-se tratar os findings pendentes em follow-up técnico antes de escalar para produção.
