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
- [FIXED] `SubscribeModuleAction` não redefine mais `started_at` ao reativar um módulo já existente.
- [FIXED] `FakePaymentGateway::fakeCharge()` não atualiza mais a fatura diretamente; a transição de status fica centralizada em `PaymentSaasService`.
- [FIXED] `PaymentSaasService::recordAttempt()` substituiu `updateOrCreate` por `create` para evitar sobrescrita de tentativas.
- [FIXED] `CancelSubscriptionAction` agora define `status = canceled` e foi exposta via rota/controller/UI.
- [FIXED] `SubscriptionController::index()` refatorado em métodos privados menores e com queries lazy (`venues()->pluck` / `venues()->get`).

### Padrões e Arquitetura
- [FIXED] `AppServiceProvider` agora lança `RuntimeException` em ambientes não-local quando `SUBSCRIPTION_PAYMENT_GATEWAY` não está configurado, evitando o uso acidental do `FakePaymentGateway` em produção.
- [FIXED] `SavePaymentMethodAction` removida; `SubscriptionPaymentMethodController` chama `PaymentSaasService` diretamente.

### Webhook
- [FIXED] `PaymentWebhookController` agora retorna 401 apenas para token inválido/ausente e 400 para erros de payload/negócio, evitando reenvios desnecessários por parte dos gateways.

## Status Pós-Correção
- **Testes de feature:** 16 passando (`PortalSubscriptionTest`)
- **Testes de regressão:** 49 passando (`tests/Feature/Billing`)
- **Total:** 65 passando (120 assertions)
- **Pint:** passou

## Findings Pendentes (não críticos)

### Segurança
- [WARNING] [app/Services/Subscription/FakePaymentGateway.php](app/Services/Subscription/FakePaymentGateway.php#L99-L120) — `handleWebhook()` não valida assinatura ou origem do payload. É fake, mas o contrato `PaymentGatewayContract` não prevê mecanismo de validação para implementações reais.
- [WARNING] [resources/js/Pages/Settings/Subscription/PaymentMethods.vue](resources/js/Pages/Settings/Subscription/PaymentMethods.vue#L78-L110) — o formulário envia número do cartão, CVV e CPF do titular em texto plano para o backend. Aumenta escopo PCI; em produção deve-se tokenizar client-side antes de enviar.
- [WARNING] [app/Services/Subscription/PaymentSaasService.php](app/Services/Subscription/PaymentSaasService.php#L18-L44) — dados sensíveis de cartão (`$cardData`) trafegam pelo serviço sem sanitização/redação. Instrumentações futuras de log/evento podem vazar PII.

### Padrões e Arquitetura
- [WARNING] [app/Contracts/Subscription/PaymentGatewayContract.php](app/Contracts/Subscription/PaymentGatewayContract.php#L14-L50) — contrato retorna arrays genéricos sem DTOs tipados. Dificulta refatoração e aumenta risco de usar chaves inexistentes.
- [DEBT] [config/subscription.php](config/subscription.php#L17) — `webhook_token` é opcional. Configuração de segurança não deveria permitir autenticação desabilitada.

## Score de Qualidade
- **Pontuação:** 7/10 (todos os blockers críticos resolvidos; a maioria dos warnings de corretude e arquitetura tratados)
- **Pontuação de Dívida Técnica:** 6/10 (DTOs e tokenização de cartão ainda pendentes)
- **Parecer Final:** APROVADO COM PENDÊNCIAS — os blockers críticos foram corrigidos, a lógica de pagamento foi centralizada, o cancelamento foi exposto e todos os testes passam. Recomenda-se tratar os findings de PCI/tokenização e DTOs antes de produção.
