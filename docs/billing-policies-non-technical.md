# Políticas e Funcionamento do Sistema de Cobrança do NeuraBar

**Versão:** 1.0 · **Data:** 18 de julho de 2026

> Este documento explica, em linguagem simples e acessível, como funcionam as cobranças do NeuraBar para clientes, gestores comerciais e equipe de suporte. Não é necessário conhecer programação para entendê-lo.

---

## 1. O que é o NeuraBar

O NeuraBar é um sistema de gestão para bares, restaurantes e grupos gastronômicos. Uma **Corporação** pode representar um único dono de restaurante ou um grupo que administra vários estabelecimentos. Cada estabelecimento é chamado de **Venue**.

A cobrança é sempre feita com base em duas coisas:

1. **Quantas venues o cliente possui.**
2. **Quais módulos estão ativos em cada venue.**

O cliente só paga pelas venues que usa e pelos módulos que ativou.

---

## 2. Estrutura Básica da Cobrança

### 2.1 Plano Base

Todo cliente precisa de um plano base. O plano base cobre:

- Acesso ao sistema.
- Cadastro e gestão de usuários.
- Configurações gerais.
- O módulo de **Cardápio**, que é obrigatório e serve de base para vários outros recursos.

O valor do plano base é cobrado **por venue**. Se uma corporação tem 3 venues, ela paga o plano base 3 vezes — uma para cada estabelecimento.

### 2.2 Módulos Adicionais

Além do plano base, o cliente pode contratar módulos extras conforme a necessidade de cada venue. Cada módulo tem uma função específica:

| Módulo | Para que serve |
|---|---|
| **Cardápio** | Cadastro de produtos, categorias, combos e modificadores. É obrigatório e incluso no plano base. |
| **KDS** | Tela de cozinha para acompanhar e despachar pedidos. |
| **Anotar Pedido** | Permite que atendentes registrem pedidos rapidamente. |
| **Direct Garçom** | Sistema para o cliente chamar o garçom diretamente da mesa. |
| **Delivery** | Gerenciamento de pedidos de delivery. |
| **Dashboard de Produção** | Painel com indicadores de produção da cozinha. |
| **Dashboard Financeiro** | Painel com indicadores financeiros do estabelecimento. |
| **Impressão Direta** | Impressão automática de pedidos e comandas. |
| **Nota Fiscal** | Emissão de notas fiscais e cupons fiscais. |
| **Comando por Voz** | Permite fazer pedidos por comandos de voz. |

Importante: a contratação de módulos é feita em duas etapas:

1. **Habilitação na corporação:** a corporação contrata o módulo e define o preço unitário por venue.
2. **Ativação na venue:** cada estabelecimento decide se vai usar ou não o módulo contratado.

Isso significa que uma corporação pode contratar o módulo KDS, mas só ativá-lo nas venues que realmente precisam dele.

---

## 3. Preço por Venue e Flexibilidade Comercial

### 3.1 Cada Venue Pode Ter um Custo Diferente

O preço do plano base e dos módulos pode variar de uma venue para outra, dependendo do tipo de contrato negociado. Por exemplo:

- Uma venue de rua pode ter um plano base mais barato.
- Uma venue em um shopping pode ter um plano base mais caro.
- Uma venue com infraestrutura dedicada (servidor próprio) pode ter um acréscimo chamado **surcharge de dedicado**.

### 3.2 Preço Negociado por Corporação

A corporação negocia preços personalizados para cada módulo. Quando não há preço negociado, o sistema usa o preço padrão do catálogo.

Exemplo:

- Preço de catálogo do KDS: R$ 50,00 por venue.
- Preço negociado para a Corporação X: R$ 40,00 por venue.
- Se a Corporação X ativar o KDS em 2 venues, ela pagará R$ 80,00 de KDS naquele mês.

---

## 4. Formas de Faturamento

O cliente pode escolher entre duas formas de receber as faturas:

### 4.1 Fatura Unificada

Todas as venues são agrupadas em **uma única fatura** no nome da corporação.

- **Vantagem:** praticidade para o financeiro da corporação.
- **Implicação:** se a fatura não for paga, **todas as venues são bloqueadas** ao mesmo tempo.

### 4.2 Fatura por Venue

Cada venue recebe sua **própria fatura**.

- **Vantagem:** cada estabelecimento paga pelo que usa.
- **Implicação:** se uma venue não pagar, **apenas ela é bloqueada**. As demais continuam funcionando normalmente.

A escolha da forma de faturamento é feita no momento da contratação e pode ser alterada posteriormente.

---

## 5. Período de Trial e Período de Cortesia

### 5.1 Trial (Teste Gratuito)

Quando uma corporação é criada, ela entra automaticamente em um período de teste gratuito. Durante esse período, o cliente pode usar o sistema sem pagar.

- O tempo padrão do trial é de **14 dias**, mas pode ser configurado comercialmente.
- Ao final do trial, se não houver pagamento, a conta entra em **status de inadimplência com cortesia**.

### 5.2 Grace Period (Período de Cortesia)

O grace period é um prazo extra concedido após o vencimento do trial ou de uma fatura. O padrão é de **3 dias**, mas pode ser negociado.

Durante o grace period:

- O sistema continua funcionando normalmente.
- O cliente tem tempo de regularizar o pagamento.
- O acesso **não é bloqueado**.

Se o pagamento não for feito dentro do grace period, a conta passa para o status **suspenso** e o acesso é bloqueado.

### 5.3 Status de uma Assinatura

A assinatura pode estar em um dos seguintes status:

| Status | Significado | O sistema funciona? |
|---|---|---|
| **Trial** | Período de teste gratuito. | Sim. |
| **Ativa** | Assinatura paga e em dia. | Sim. |
| **Inadimplente com cortesia** | Pagamento atrasado, mas dentro do grace period. | Sim. |
| **Suspensa** | Grace period expirou sem pagamento. | Não. |
| **Cancelada** | Assinatura cancelada. | Não. |

---

## 6. Cálculo da Fatura

### 6.1 O que Compõe a Fatura de uma Venue

A fatura de cada venue é formada por quatro partes:

1. **Valor base:** plano base da venue.
2. **Valor dos módulos:** soma dos módulos ativos naquela venue.
3. **Valor medido/excedente:** cobrança por uso acima do limite contratado.
4. **Surcharge dedicado:** acréscimo para venues com infraestrutura dedicada.

O valor total é a soma dessas partes, menos eventuais descontos.

### 6.2 Cobrança por Volume Excedente

Alguns módulos têm um limite de uso incluso no preço. Se a venue ultrapassar esse limite, é cobrado um valor adicional por cada unidade excedente.

Exemplo prático — módulo KDS:

- Plano do KDS inclui 500 pedidos despachados por mês.
- Se a venue despachar 700 pedidos, ela pagará pelo excedente de 200 pedidos.
- Se o preço do excedente for R$ 0,10 por pedido, o valor medido será R$ 20,00.

Esse limite é sempre calculado **por venue**. Mesmo no faturamento unificado, o excedente de cada venue é calculado separadamente e depois somado.

### 6.3 Unidades de Medida por Módulo

Cada módulo mede o volume de uma forma:

| Módulo | O que é medido | Unidade |
|---|---|---|
| KDS | Pedidos despachados | pedidos |
| Anotar Pedido | Pedidos criados | pedidos |
| Direct Garçom | Sinais enviados pelos clientes | sinais |
| Delivery | Pedidos de delivery | pedidos |
| Impressão Direta | Pedidos impressos | pedidos |
| Nota Fiscal | Cupons emitidos | pedidos |
| Comando por Voz | Comandos de voz transcritos | comandos |

Módulos como Cardápio, Dashboard de Produção e Dashboard Financeiro não têm medição de volume — têm preço fixo.

### 6.4 Período de Medição

O uso é medido mês a mês, sempre pelo **mês calendário** (de 1º a último dia do mês), independentemente do dia de vencimento da fatura.

Exemplo:

- Se a fatura vence no dia 20 de julho, ela cobre todo o período de 1º a 31 de julho.
- O dia de vencimento só define quando o pagamento deve ser feito, não o período de uso cobrado.

### 6.5 Ativação e Desativação no Meio do Mês

Na fase atual do sistema:

- Se um módulo for ativado no meio do mês, ele é cobrado pelo mês inteiro na fatura seguinte.
- Se um módulo for desativado no meio do mês, **não há crédito proporcional**. Ele permanece ativo e cobrado até o final do ciclo.

Isso simplifica o controle financeiro. No futuro, pode ser implementado um cálculo proporcional por dia.

---

## 7. Descontos

### 7.1 Desconto por Volume de Módulos

Corporações que contratam vários módulos pagos podem receber desconto automático sobre o valor total dos módulos:

| Quantidade de módulos pagos ativos | Desconto sobre o valor dos módulos |
|---|---|
| 1 a 2 | 0% |
| 3 a 5 | 10% |
| 6 ou mais | 20% |

Esse desconto pode ser negociado individualmente por corporação.

### 7.2 Outros Descontos

Descontos por negociação comercial, pacotes especiais ou promoções são aplicados diretamente na fatura, aparecendo como um valor de desconto.

---

## 8. Faturas e Pagamentos

### 8.1 Faturas por Venue

No modo de faturamento por venue, cada estabelecimento recebe uma fatura individual com:

- Período cobrado.
- Data de vencimento.
- Valor base, módulos, excedente e descontos.
- Status: aberta, paga, vencida ou cancelada.

### 8.2 Fatura Unificada

No modo unificado, a corporação recebe uma única fatura que agrupa os valores de todas as venues. Mesmo assim, o sistema mantém o detalhamento por venue para controle interno.

### 8.3 Tentativas de Pagamento

Toda tentativa de cobrança é registrada, seja por cartão, boleto, PIX ou outro meio. Isso garante rastreabilidade e evita cobranças duplicadas.

### 8.4 Faturas Finalizadas

Depois que uma fatura é paga ou fica vencida, ela é marcada como **finalizada**. Faturas finalizadas não podem ser recalculadas, garantindo a integridade dos registros financeiros.

---

## 9. Bloqueios e Suspensão

### 9.1 Quando o Acesso é Bloqueado

O acesso ao sistema é bloqueado apenas quando a assinatura está:

- **Suspensa** — após expiração do grace period.
- **Cancelada** — por solicitação do cliente ou da plataforma.

Enquanto a assinatura estiver em **trial**, **ativa** ou **inadimplente com cortesia**, o sistema continua funcionando.

### 9.2 Regra de Bloqueio por Forma de Faturamento

- **Fatura unificada:** a inadimplência bloqueia **todas as venues** da corporação ao mesmo tempo.
- **Fatura por venue:** a inadimplência bloqueia **apenas a venue inadimplente**. As outras continuam funcionando.

### 9.3 Rotinas Automáticas

O sistema executa rotinas diárias para:

- Verificar trials que expiraram e movê-los para inadimplência com cortesia.
- Verificar grace periods que expiraram e suspender assinaturas.
- Marcar faturas vencidas como atrasadas.

---

## 10. Rastreamento de Indicações (Afiliados)

O NeuraBar permite associar um código de afiliado a corporações, venues, assinaturas e faturas. Por enquanto, esse código serve apenas para **rastreamento** e relatórios futuros.

### 10.1 Como Funciona

- Um parceiro ou indicador recebe um código fixo, como `JOAO2026`.
- Quando um novo cliente se cadastra usando esse código, o código é propagado automaticamente para a corporação, venues, assinaturas e faturas.
- No futuro, será possível gerar relatórios de quantos clientes um afiliado indicou e quanto de faturamento ele gerou.

### 10.2 Importante

- O código de afiliado é opcional.
- Faturas antigas mantêm o código original mesmo que a corporação mude de afiliado no futuro.
- Ainda não há cálculo automático de comissão — isso é previsto para uma fase posterior.

---

## 11. Exemplos Práticos

### Exemplo 1 — Uma Única Venue

A Corporação A tem 1 venue e contratou:

- Plano base: R$ 100,00
- KDS: R$ 50,00
- Anotar Pedido: R$ 40,00

No mês, a venue despachou 450 pedidos no KDS (limite incluso de 500) e criou 300 pedidos no Anotar Pedido (limite incluso de 300).

**Fatura:**

- Plano base: R$ 100,00
- KDS: R$ 50,00
- Anotar Pedido: R$ 40,00
- Excedente: R$ 0,00
- **Total: R$ 190,00**

### Exemplo 2 — Múltiplas Venues com Fatura Unificada

A Corporação B tem 3 venues e contratou:

- Plano base por venue: R$ 100,00
- KDS: R$ 50,00 por venue

Ela ativou o KDS em 2 das 3 venues. No mês, as duas venues usaram dentro do limite.

**Fatura unificada:**

- Plano base: R$ 300,00 (3 × R$ 100,00)
- KDS: R$ 100,00 (2 × R$ 50,00)
- Excedente: R$ 0,00
- **Total: R$ 400,00**

Se a Corporação B não pagar, as 3 venues são bloqueadas.

### Exemplo 3 — Múltiplas Venues com Fatura por Venue

A Corporação C tem 3 venues com faturamento por venue.

- Venue 1: plano base R$ 100,00 + KDS R$ 50,00 → total R$ 150,00
- Venue 2: plano base R$ 100,00 + KDS R$ 50,00 → total R$ 150,00
- Venue 3: apenas plano base R$ 100,00 → total R$ 100,00

Se a Venue 2 não pagar, **apenas a Venue 2 é bloqueada**. As venues 1 e 3 continuam funcionando.

### Exemplo 4 — Volume Excedente

A Corporação D tem 1 venue com KDS contratado.

- Plano base: R$ 100,00
- KDS: R$ 50,00 (inclui 500 pedidos)

No mês, a venue despachou 700 pedidos. O excedente foi de 200 pedidos, cobrados a R$ 0,10 cada.

**Fatura:**

- Plano base: R$ 100,00
- KDS: R$ 50,00
- Excedente: R$ 20,00
- **Total: R$ 170,00**

---

## 12. Resumo das Regras Mais Importantes

1. **Cobrança proporcional:** o cliente paga de acordo com a quantidade de venues e módulos ativos.
2. **Preço negociado:** cada corporação pode ter preços personalizados para módulos.
3. **Dois modos de faturamento:** unificado (uma fatura para todas as venues) ou por venue (faturas separadas).
4. **Bloqueio conforme o modo:** no unificado, inadimplência bloqueia tudo; no por venue, bloqueia só a venue.
5. **Trial e grace period:** o cliente tem tempo para testar e regularizar pagamentos antes de ser bloqueado.
6. **Volume por venue:** limites de uso são sempre individuais por venue, nunca somados entre estabelecimentos.
7. **Faturas finalizadas:** depois de pagas ou vencidas, faturas não são recalculadas.
8. **Afiliados:** código de indicação é rastreado, mas ainda não gera comissão automática.
9. **Ativação no meio do mês:** módulos ativados no meio do mês são cobrados integralmente no ciclo seguinte.
10. **Desativação no meio do mês:** não há crédito proporcional; o módulo continua ativo até o fim do ciclo.

---

## 13. Perguntas Frequentes

### P: Posso ativar um módulo em apenas uma das minhas venues?

**R:** Sim. Desde que o módulo esteja habilitado na corporação, você pode escolher em quais venues ele será ativado.

### P: E se eu ultrapassar o limite de uso do módulo?

**R:** Você pagará um valor adicional apenas pelas unidades excedentes. O limite é individual por venue.

### P: O que acontece se eu não pagar uma fatura?

**R:** Você entra no período de cortesia (grace period). Se não pagar dentro desse prazo, o acesso é suspenso.

### P: Posso mudar de fatura por venue para fatura unificada?

**R:** Sim, essa configuração pode ser alterada pela equipe comercial da NeuraBar.

### P: O Cardápio é sempre gratuito?

**R:** O Cardápio faz parte do plano base e é obrigatório. Ele não é cobrado como módulo adicional.

### P: E se eu cancelar um módulo no meio do mês?

**R:** O módulo permanece ativo e cobrado até o final do mês. A partir do mês seguinte, ele não aparece mais na fatura.

---

## 14. Glossário

| Termo | Significado |
|---|---|
| **Corporação** | Empresa ou grupo que administra uma ou mais venues. |
| **Venue** | Estabelecimento comercial, como um restaurante ou bar. |
| **Módulo** | Funcionalidade específica do sistema, como KDS ou Delivery. |
| **Plano base** | Pacote obrigatório que inclui acesso ao sistema e o módulo Cardápio. |
| **Trial** | Período de teste gratuito no início da assinatura. |
| **Grace period** | Prazo extra de cortesia após o vencimento, antes do bloqueio. |
| **Fatura unificada** | Uma única fatura para todas as venues da corporação. |
| **Fatura por venue** | Fatura individual para cada estabelecimento. |
| **Volume excedente** | Uso acima do limite incluso em um módulo. |
| **Surcharge dedicado** | Acréscimo para venues com infraestrutura dedicada. |
| **Afiliado** | Parceiro que indica novos clientes por meio de um código de rastreamento. |
