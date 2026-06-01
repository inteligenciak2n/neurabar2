<?php

namespace Database\Seeders;

use App\Models\Support\TicketCategory;
use App\Models\Support\Tutorial;
use App\Models\Support\TutorialCategory;
use Illuminate\Database\Seeder;

class SupportDatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedTicketCategories();
        $this->seedTutorials();
    }

    private function seedTicketCategories(): void
    {
        $categories = [
            [
                'name' => 'Financeiro',
                'description' => 'Dúvidas sobre cobranças, faturas e pagamentos.',
                'icon' => '💳',
                'active' => true,
            ],
            [
                'name' => 'Técnico',
                'description' => 'Problemas de funcionamento, erros e lentidão.',
                'icon' => '🔧',
                'active' => true,
            ],
            [
                'name' => 'Conta e Acesso',
                'description' => 'Problemas de login, senha, permissões e usuários.',
                'icon' => '🔑',
                'active' => true,
            ],
            [
                'name' => 'Cardápio e Produtos',
                'description' => 'Dúvidas sobre cadastro de produtos, categorias e modificadores.',
                'icon' => '🍽️',
                'active' => true,
            ],
            [
                'name' => 'Pedidos e Atendimento',
                'description' => 'Dúvidas sobre pedidos, mesas, KDS e fluxo de atendimento.',
                'icon' => '📋',
                'active' => true,
            ],
            [
                'name' => 'Configurações da Venue',
                'description' => 'Ajuda com configurações gerais, locais de atendimento e QR codes.',
                'icon' => '⚙️',
                'active' => true,
            ],
            [
                'name' => 'Outros',
                'description' => 'Assuntos que não se encaixam nas categorias acima.',
                'icon' => '💬',
                'active' => true,
            ],
        ];

        foreach ($categories as $data) {
            TicketCategory::firstOrCreate(['name' => $data['name']], $data);
        }
    }

    private function seedTutorials(): void
    {
        $categories = [
            [
                'name' => 'Primeiros Passos',
                'description' => 'Guias essenciais para começar a usar o NeuraBar.',
                'icon' => '🚀',
                'position' => 1,
                'active' => true,
                'tutorials' => [
                    [
                        'title' => 'Como configurar sua venue',
                        'summary' => 'Aprenda a configurar as informações básicas da sua venue, como nome, endereço e horário de funcionamento.',
                        'body' => "# Como configurar sua venue\n\nEste tutorial mostra como preencher as configurações iniciais da sua venue no NeuraBar.\n\n## Acessando as configurações\n\nNo menu lateral, clique em **Configurações** > **Venue**.\n\n## Informações básicas\n\nPreencha os campos:\n- **Nome** da venue\n- **Endereço** completo\n- **Telefone** de contato\n\n## Salvando\n\nClique em **Salvar** ao final. As alterações entram em vigor imediatamente.",
                        'position' => 1,
                        'published' => true,
                    ],
                    [
                        'title' => 'Criando seu primeiro cardápio',
                        'summary' => 'Passo a passo para criar menus, categorias e produtos do zero.',
                        'body' => "# Criando seu primeiro cardápio\n\nVeja como estruturar seu cardápio no NeuraBar em poucos minutos.\n\n## 1. Criar um Menu\n\nAcesse **Cardápio** > **Menus** e clique em **Novo Menu**. Dê um nome (ex: \"Cardápio Principal\") e salve.\n\n## 2. Adicionar Categorias\n\nDentro do menu, crie categorias como \"Entradas\", \"Pratos Principais\", \"Bebidas\".\n\n## 3. Adicionar Produtos\n\nEm cada categoria, clique em **Novo Produto** e preencha:\n- Nome\n- Preço\n- Descrição (opcional)\n- Estação de preparo (KDS)\n\n## Dica\n\nUse a opção **Ativo/Inativo** para controlar quais produtos aparecem no atendimento.",
                        'position' => 2,
                        'published' => true,
                    ],
                    [
                        'title' => 'Convidando usuários para sua equipe',
                        'summary' => 'Como adicionar atendentes, gerentes e outros membros da equipe.',
                        'body' => "# Convidando usuários para sua equipe\n\nO NeuraBar permite que você tenha múltiplos usuários operando na mesma venue com diferentes níveis de acesso.\n\n## Níveis de acesso\n\n| Role | Permissões |\n|---|---|\n| **Owner** | Acesso total |\n| **Gerente Geral** | Configurações, cardápio, pedidos |\n| **Gerente de Seção** | Pedidos e atendimento |\n| **Atendente** | Apenas pedidos e mesas |\n\n## Como convidar\n\n1. Acesse **Configurações** > **Usuários**\n2. Clique em **Novo Usuário**\n3. Informe nome, email e selecione a role\n4. Se o email já tiver conta no NeuraBar, um convite será enviado automaticamente",
                        'position' => 3,
                        'published' => true,
                    ],
                ],
            ],
            [
                'name' => 'Pedidos e Atendimento',
                'description' => 'Tudo sobre o fluxo de pedidos, mesas e KDS.',
                'icon' => '📋',
                'position' => 2,
                'active' => true,
                'tutorials' => [
                    [
                        'title' => 'Como registrar um pedido',
                        'summary' => 'Aprenda a abrir um atendimento e registrar pedidos com modificadores e variações.',
                        'body' => "# Como registrar um pedido\n\n## Abrindo um atendimento\n\n1. Acesse **Atendimento** no menu lateral\n2. Clique no local desejado (mesa, balcão, etc.)\n3. Um novo atendimento é aberto automaticamente\n\n## Adicionando itens\n\n- Navegue pelo cardápio ou use a busca\n- Clique no produto para adicioná-lo\n- Se houver **variações** (ex: tamanho), selecione antes de confirmar\n- Se houver **modificadores** obrigatórios (ex: ponto da carne), selecione a opção desejada\n\n## Finalizando\n\nClique em **Enviar Pedido**. O KDS da cozinha receberá o pedido automaticamente.",
                        'position' => 1,
                        'published' => true,
                    ],
                    [
                        'title' => 'Usando o KDS (Kitchen Display System)',
                        'summary' => 'Como gerenciar a produção da cozinha usando o painel KDS.',
                        'body' => "# Usando o KDS\n\nO KDS (Kitchen Display System) é o painel da cozinha que exibe os pedidos em tempo real.\n\n## Acessando\n\nAcesse **Cozinha** > **KDS** no menu lateral. A tela é atualizada automaticamente via WebSocket.\n\n## Avançando o status\n\nCada item exibe seu status de preparo. Clique no botão de avançar para mover para o próximo status (ex: Recebido → Em Preparo → Pronto).\n\n## Status final\n\nQuando o status for marcado como **final** (configurável em Configurações > Preparo), o item some do KDS automaticamente.",
                        'position' => 2,
                        'published' => true,
                    ],
                ],
            ],
            [
                'name' => 'Configurações Avançadas',
                'description' => 'QR codes, integrações e recursos avançados da plataforma.',
                'icon' => '⚙️',
                'position' => 3,
                'active' => true,
                'tutorials' => [
                    [
                        'title' => 'Gerando QR codes para mesas',
                        'summary' => 'Como criar locais de atendimento e gerar QR codes para que clientes chamem o atendente.',
                        'body' => "# Gerando QR codes para mesas\n\nCom o QR code, o cliente final pode chamar o atendente diretamente pelo celular.\n\n## Criando locais de atendimento\n\n1. Acesse **Configurações** > **Locais de Atendimento**\n2. Clique em **Novo Local**\n3. Defina o nome (ex: \"Mesa 7\"), tipo e canal de atendimento padrão\n\n## Gerando o QR code\n\n- Clique em **Gerar QR** ao lado do local\n- Faça o download do PDF com o QR code para impressão\n\n## Como funciona\n\nO cliente escaneia o QR code e acessa uma página que permite chamar o atendente. A sinalização chega em tempo real para os atendentes logados.",
                        'position' => 1,
                        'published' => true,
                    ],
                ],
            ],
        ];

        foreach ($categories as $categoryData) {
            $tutorials = $categoryData['tutorials'];
            unset($categoryData['tutorials']);

            /** @var TutorialCategory $category */
            $category = TutorialCategory::firstOrCreate(['name' => $categoryData['name']], $categoryData);

            foreach ($tutorials as $tutorialData) {
                $slug = Tutorial::generateSlug($tutorialData['title']);
                Tutorial::firstOrCreate(
                    ['slug' => $slug],
                    array_merge($tutorialData, [
                        'category_id' => $category->id,
                        'slug' => $slug,
                        'published_at' => $tutorialData['published'] ? now() : null,
                    ])
                );
            }
        }
    }
}
