# Relatório de Revisão – Naka-24-10-2026 vs main

## Resumo da Task

Revisão do fluxo de comandas e pedido por QR da mesa, incluindo resolução automática de atendimento, seleção de comanda, criação de sessão por QR e pedidos sucessivos. O escopo abrange o controller marcado e as dependências diretamente envolvidas.

## Resultado da Revisão

- **Arquivos revisados:** 8
- **Total de findings:** 2 ([CRITICAL]: 0, [WARNING]: 2, [DEBT]: 0)

## Findings por Dimensão

### Segurança

- Sem findings adicionais de segurança no escopo revisado.

### Corretude e Lógica

- [WARNING] `app/Services/GuestTokenService.php:133` — `ensureSession()` reutiliza qualquer sessão ativa do mesmo estabelecimento sem verificar se o cookie pertence ao mesmo `service_location_id` e `attendance_channel_id`. Como o cookie é válido por 24 horas e usa path `/`, ao abrir o QR de outra mesa do mesmo estabelecimento o cliente mantém a sessão anterior; o controller exibe a mesa do novo QR, mas os pedidos continuam sendo resolvidos pelo local persistido na sessão antiga. **Sugestão:** reutilizar a sessão somente quando local e canal coincidirem com o QR; caso contrário, criar uma sessão nova ou atualizar o contexto descartando vínculos incompatíveis. Adicionar teste sequencial “Mesa 1 → QR da Mesa 2 → pedido na Mesa 2”.
- [WARNING] `app/Actions/Guest/ResolveGuestTableOrderAttendanceAction.php:35` — o resolvedor recalcula as comandas abertas da mesa sem primeiro respeitar `GuestSession::attendance_id`. Uma sessão que já selecionou a comanda A pode pedir a escolha novamente quando a comanda B abre, ou ser movida silenciosamente para B se A fechar e B se tornar a única comanda aberta. O payload `attendance_id` também permite trocar a sessão por outra comanda da mesa depois da primeira escolha. **Sugestão:** se a sessão já estiver vinculada, retornar e continuar usando esse atendimento enquanto ele estiver aberto, falhando explicitamente se tiver sido fechado, antes de listar ou aceitar novas escolhas. Cobrir pedido sucessivo com a abertura/fechamento de outras comandas na mesma mesa.

### Performance

- Sem findings de performance no escopo revisado.

### Complexidade e Manutenibilidade

- Sem findings de complexidade e manutenibilidade.

### Padrões e Arquitetura

- Sem findings adicionais de padrões e arquitetura.

### Cobertura de Testes

- Sem finding adicional; as lacunas de regressão estão detalhadas nos dois findings de corretude. Os testes atuais cobrem apenas a criação inicial da sessão e o primeiro pedido.

## Score de Qualidade

- **Pontuação:** 8/10
- **Pontuação de Dívida Técnica:** 3/10 (lacunas em invariantes de sessão e regressão não coberta).
- **Parecer Final:** APROVADO COM RESSALVAS

## Verificação Executada

- `vendor/bin/sail artisan test --compact tests/Feature/Guest/GuestHubRedirectTest.php tests/Feature/Guest/GuestTableOrderTabTest.php tests/Feature/Guest/GuestOrderTabCodeTest.php`
- Resultado: 21 testes e 81 asserções aprovados.
- A linha comparativa local está em `main...HEAD` com `0` commits atrás e `4` à frente, já incluindo o merge atual da `main`.
