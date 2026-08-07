O resultado é um código que não apenas replica um layout, mas que comunica a essência da marca: **conectividade, inteligência, eficiência, evolução e experiência**.

### Ajustes realizados com base no Branding Book:

*   **Paleta de Cores:** Substituí as cores genéricas (`#007BFF`, etc.) pela paleta oficial da NeuraBar.
    *   **Azul Intenso (`#293b4f`):** Usado como cor principal, transmitindo confiança e inovação.
    *   **Dourado Glow (`#c4b39b` e `#a28665`):** Adicionado um toque de sofisticação e exclusividade.
    *   **Off-white (`#eeeeee`):** Mantido como cor de fundo para clareza e leveza.
*   **Tipografia:** Troquei a fonte genérica pela combinação oficial da marca.
    *   **Primária (Poppins SemiBold):** Aplicada em títulos e elementos de destaque.
    *   **Secundária (Manrope Regular):** Utilizada em textos corridos, garantindo legibilidade.
*   **Identidade Visual:** Os elementos gráficos (logo, ícones) e o tom da documentação agora conversam diretamente com o manifesto e os valores da NeuraBar.
*   **Estrutura e Conteúdo:** Reforcei os princípios de conectividade e eficiência na descrição, alinhando a função do dashboard com a missão da marca.

---

### Documentação Técnica Ajustada: NeuraBar - Dashboard de Gestão

A interface administrativa (dashboard) da NeuraBar foi projetada para ser o centro de comando da operação do seu bar ou restaurante. Ela incorpora a identidade visual da marca, que une tecnologia e sofisticação, para oferecer uma experiência de gestão intuitiva, limpa e altamente eficiente. Este layout é a materialização do manifesto da NeuraBar: **conectar pessoas, processos e informações para simplificar o que realmente importa.**

### Análise da Identidade Visual

*   **Paleta de Cores (NeuraBar):**
    *   **Cor Primária (Azul Intenso):** `#293b4f`. Utilizada na logo, links ativos e elementos de destaque, transmite confiança, estabilidade e a inteligência da plataforma.
    *   **Cor de Destaque (Dourado Glow):** `#c4b39b` e `#a28665`. Presente em sutis detalhes e interações, adiciona um toque de excelência e valor percebido, elevando a percepção da marca.
    *   **Plano de Fundo:** `#eeeeee` (Off-white), criando um contraste suave e moderno com as superfícies brancas.
    *   **Superfícies (Cards/Header):** Branco puro (`#FFFFFF`) com bordas sutis em `#e2e8f0`, garantindo clareza e organização.
    *   **Texto:** Tons de cinza escuro (`#0f172a`) para títulos e cinza médio (`#64748b`) para descrições, priorizando a legibilidade.
*   **Tipografia (NeuraBar):**
    *   **Primária (Poppins SemiBold):** Utilizada em títulos, nomes de seções e navegação principal, reforçando a personalidade forte e contemporânea da marca.
    *   **Secundária (Manrope Regular):** Aplicada em textos corridos, descrições e dados, garantindo legibilidade e fluidez na leitura.
*   **Estrutura (Layout):**
    *   **Header (Cabeçalho):** Estrutura fixa contendo a marca (logotipo com o ícone "N"), informações da sessão do usuário, navegação principal horizontal (com o item "Cardápio" ativo) e botão de saída. O design limpo reforça a sensação de controle e conectividade.
    *   **Navegação Secundária (Cards):** Utiliza cartões clicáveis para alternar entre os modos de visualização do cardápio. O estado "ativo" é destacado com a cor **Dourado Glow** (`#c4b39b`) em suas variações, seguindo os princípios de **experiência** e **inteligência** da marca.
    *   **Containers de Conteúdo:** Áreas bem delimitadas com bordas arredondadas (radius de 8px a 12px) e sombras sutis, criando uma hierarquia visual clara e uma experiência de usuário fluida, em linha com o princípio de **eficiência**.

---

### Código HTML e CSS Ajustado

Abaixo está a estrutura completa em um único arquivo (HTML + CSS), incorporando todos os elementos da identidade visual da NeuraBar.

```html
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NeuraBar - Dashboard de Gestão</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500&family=Poppins:wght@600;700&display=swap" rel="stylesheet">
    <style>
        /* ============================================================
                   VARIÁVEIS DE ESTILO (IDENTIDADE NEURABAR)
                   ============================================================ */
        :root {
            /* Cores Primárias - Azul Intenso */
            --primary-blue: #293b4f;
            --primary-blue-dark: #1e2a3a;
            
            /* Cores de Destaque - Dourado Glow */
            --gold-light: #c4b39b;
            --gold-medium: #a28665;
            
            /* Cores Neutras - Off-white e Superfícies */
            --bg-color: #eeeeee;
            --surface-color: #ffffff;
            --border-color: #e2e8f0;
            
            /* Cores de Texto */
            --text-main: #0f172a;
            --text-muted: #64748b;
            
            /* Tipografia */
            --font-primary: 'Poppins', 'Inter', -apple-system, sans-serif;
            --font-secondary: 'Manrope', 'Inter', -apple-system, sans-serif;
            
            /* Espaçamento e Bordas */
            --radius-md: 8px;
            --radius-lg: 12px;
        }

        /* ============================================================
                   RESET E BASE
                   ============================================================ */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: var(--font-secondary);
            background-color: var(--bg-color);
            color: var(--text-main);
            -webkit-font-smoothing: antialiased;
        }

        /* ============================================================
                   TIPOGRAFIA BASE
                   ============================================================ */
        h1, h2, h3, h4 {
            font-family: var(--font-primary);
            font-weight: 700;
        }

        h1 { 
            font-size: 24px; 
            margin-bottom: 4px; 
        }
        h2 { 
            font-size: 18px; 
            font-weight: 600; 
            margin-bottom: 4px; 
        }
        h3 { 
            font-size: 16px; 
            font-weight: 600; 
            margin-bottom: 16px; 
        }
        p { 
            color: var(--text-muted); 
            font-size: 14px; 
            line-height: 1.6; 
        }

        /* ============================================================
                   CABEÇALHO (HEADER) - TOPO
                   ============================================================ */
        .top-header {
            background-color: var(--surface-color);
            border-bottom: 1px solid var(--border-color);
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 24px;
            height: 64px;
        }

        .header-left {
            display: flex;
            align-items: center;
            gap: 24px;
        }

        .brand-area {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        /* Símbolo/Ícone da Marca "N" - Representa a Conectividade */
        .logo-icon {
            background-color: var(--primary-blue);
            color: var(--gold-light);
            width: 32px;
            height: 32px;
            border-radius: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: var(--font-primary);
            font-weight: 700;
            font-size: 18px;
        }

        .brand-name {
            font-family: var(--font-primary);
            font-weight: 700;
            font-size: 18px;
            color: var(--text-main);
        }

        .user-info {
            display: flex;
            flex-direction: column;
            font-size: 12px;
            color: var(--text-muted);
            border-left: 1px solid var(--border-color);
            padding-left: 16px;
        }

        .user-info strong { 
            color: var(--text-main); 
            font-weight: 600;
        }

        /* ============================================================
                   NAVEGAÇÃO PRINCIPAL
                   ============================================================ */
        .main-nav {
            display: flex;
            gap: 24px;
            font-family: var(--font-primary);
        }

        .main-nav a {
            text-decoration: none;
            color: var(--text-muted);
            font-size: 14px;
            font-weight: 600;
            transition: color 0.2s;
            position: relative;
        }

        .main-nav a:hover { 
            color: var(--text-main); 
        }
        .main-nav a.active { 
            color: var(--primary-blue); 
        }
        /* Indicador sutil de página ativa */
        .main-nav a.active::after {
            content: '';
            position: absolute;
            bottom: -4px;
            left: 0;
            width: 100%;
            height: 2px;
            background-color: var(--gold-medium);
            border-radius: 2px;
        }

        .btn-logout {
            background: none;
            border: 1px solid var(--border-color);
            padding: 6px 12px;
            border-radius: var(--radius-md);
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 14px;
            color: var(--text-main);
            font-weight: 500;
            font-family: var(--font-secondary);
            transition: all 0.2s;
        }
        .btn-logout:hover {
            background-color: var(--bg-color);
        }

        /* ============================================================
                   CONTAINER PRINCIPAL
                   ============================================================ */
        .main-content {
            max-width: 1280px;
            margin: 0 auto;
            padding: 32px 24px;
        }

        /* ============================================================
                   CARTÕES DE MODO (TABS HORIZONTAIS)
                   ============================================================ */
        .mode-cards {
            display: flex;
            gap: 16px;
            margin-top: 24px;
            margin-bottom: 40px;
            overflow-x: auto;
            padding-bottom: 8px;
        }

        .mode-card {
            background-color: var(--surface-color);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-md);
            padding: 16px;
            min-width: 200px;
            cursor: pointer;
            transition: all 0.25s ease;
            flex-shrink: 0;
        }

        .mode-card:hover {
            border-color: var(--gold-medium);
        }

        .mode-card.active {
            border-color: var(--gold-medium);
            background-color: #f8f5f0; /* Off-white com um toque quente */
        }

        .mode-card.active .card-title { 
            color: var(--primary-blue); 
        }

        .card-icon {
            margin-bottom: 12px;
            font-size: 20px; 
            color: var(--text-muted);
            transition: color 0.2s;
        }
        
        .mode-card.active .card-icon { 
            color: var(--gold-medium); 
        }

        .card-title {
            font-family: var(--font-primary);
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 4px;
            color: var(--text-main);
        }

        .card-desc {
            font-size: 12px;
            color: var(--text-muted);
            line-height: 1.4;
        }

        /* ============================================================
                   SEÇÃO RESUMO
                   ============================================================ */
        .section-header {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 16px;
        }

        .loading-box {
            background-color: var(--surface-color);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-lg);
            padding: 48px;
            text-align: center;
            color: var(--text-muted);
            font-size: 14px;
            margin-bottom: 48px;
        }

        /* ============================================================
                   SEÇÃO CARDÁPIO COMPACTO
                   ============================================================ */
        .compact-menu-container {
            background-color: var(--surface-color);
            border-radius: var(--radius-lg);
            border: 1px solid var(--border-color);
            padding: 24px;
        }

        .compact-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 32px;
            flex-wrap: wrap;
            gap: 12px;
        }

        /* Botão Primário com a identidade NeuraBar */
        .btn-primary {
            background-color: var(--primary-blue);
            color: white;
            border: none;
            padding: 10px 18px;
            border-radius: var(--radius-md);
            font-family: var(--font-primary);
            font-weight: 600;
            font-size: 14px;
            cursor: pointer;
            transition: background-color 0.2s;
        }

        .btn-primary:hover { 
            background-color: var(--primary-blue-dark); 
        }

        /* ============================================================
                   TABELA DE DADOS (SIMPLIFICADA)
                   ============================================================ */
        .data-table {
            width: 100%;
            border-collapse: collapse;
        }

        .data-table th {
            text-align: left;
            padding: 12px;
            border-top: 1px solid var(--border-color);
            border-bottom: 1px solid var(--border-color);
            color: var(--text-muted);
            font-weight: 600;
            font-size: 13px;
            font-family: var(--font-primary);
        }

        .data-table td {
            padding: 12px;
            border-bottom: 1px solid var(--border-color);
            font-size: 14px;
        }

        .data-table tr:last-child td {
            border-bottom: none;
        }

        /* Estilo para o status "Ativo" */
        .status-badge {
            background-color: #e6f7e6;
            color: #1a7a3a;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        .status-badge.inactive {
            background-color: #f3f3f3;
            color: #888;
        }

        /* ============================================================
                   RESPONSIVIDADE
                   ============================================================ */
        @media (max-width: 768px) {
            .top-header {
                flex-wrap: wrap;
                height: auto;
                padding: 12px 16px;
                gap: 12px;
            }
            .header-left {
                flex-wrap: wrap;
                gap: 12px;
            }
            .main-nav {
                flex-wrap: wrap;
                gap: 12px 16px;
                padding: 8px 0;
            }
            .main-content {
                padding: 16px;
            }
            .mode-cards {
                gap: 12px;
            }
            .mode-card {
                min-width: 160px;
                padding: 12px;
            }
            .compact-header {
                flex-direction: column;
                align-items: stretch;
            }
            .data-table {
                font-size: 13px;
            }
            .data-table th, .data-table td {
                padding: 8px;
            }
        }

        @media (max-width: 480px) {
            .brand-name {
                font-size: 16px;
            }
            .user-info {
                font-size: 11px;
                padding-left: 10px;
            }
            .main-nav a {
                font-size: 13px;
            }
            .mode-card {
                min-width: 140px;
                padding: 10px;
            }
        }
    </style>
</head>
<body>

    <header class="top-header">
        <div class="header-left">
            <div class="brand-area">
                <!-- Ícone "N" representando a marca -->
                <div class="logo-icon">N</div>
                <div class="brand-name">NeuraBar</div>
            </div>
            <div class="user-info">
                <span>logado como: <strong>Dono</strong></span>
                <span>Bar do Zé · ID: 1</span>
            </div>
        </div>

        <nav class="main-nav">
            <a href="#">Painel</a>
            <a href="#">Chama garçom</a>
            <a href="#" class="active">Cardápio</a>
            <a href="#">Atendimento</a>
            <a href="#">Anotar pedidos</a>
            <a href="#">Tela de preparo</a>
            <a href="#">Gerenciamento</a>
        </nav>

        <button class="btn-logout">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg>
            Sair
        </button>
    </header>

    <main class="main-content">
        <header class="page-title">
            <h1>Cardápio</h1>
            <p>Gerencie seu cardápio com inteligência e eficiência.</p>
        </header>

        <!-- ==========================================================
        CARTÕES DE MODO - NAVEGAÇÃO SECUNDÁRIA
        ========================================================== -->
        <div class="mode-cards">
            <div class="mode-card active">
                <div class="card-icon">📊</div>
                <div class="card-title">Resumo por categoria</div>
                <div class="card-desc">Visão geral de itens ativos e inativos</div>
            </div>
            <div class="mode-card">
                <div class="card-icon">🔲</div>
                <div class="card-title">Cardápio compacto</div>
                <div class="card-desc">Ative e desative itens rapidamente</div>
            </div>
            <div class="mode-card">
                <div class="card-icon">⚙️</div>
                <div class="card-title">Cardápio completo</div>
                <div class="card-desc">Adicione itens, variações e preços</div>
            </div>
            <div class="mode-card">
                <div class="card-icon">📱</div>
                <div class="card-title">Cardápio digital</div>
                <div class="card-desc">Edite fotos e visualize o layout</div>
            </div>
            <div class="mode-card">
                <div class="card-icon">👁️</div>
                <div class="card-title">Versão clientes</div>
                <div class="card-desc">Veja como o cliente enxerga o cardápio</div>
            </div>
        </div>

        <!-- ==========================================================
        SEÇÃO: RESUMO POR CATEGORIA
        ========================================================== -->
        <section>
            <div class="section-header">
                <h2>📊 Resumo por categoria</h2>
            </div>
            <p style="margin-bottom: 16px;">Quantidade de itens ativos e inativos por categoria.</p>
            
            <div class="loading-box">
                Carregando resumo...
            </div>
        </section>

        <!-- ==========================================================
        SEÇÃO: CARDÁPIO COMPACTO
        ========================================================== -->
        <section class="compact-menu-container">
            <div class="compact-header">
                <div>
                    <h2>Cardápio Compacto</h2>
                    <p>Gerencie os itens do seu cardápio com agilidade.</p>
                </div>
                <button class="btn-primary">+ Adicionar produto</button>
            </div>

            <div class="category-block">
                <h3>Sobremesas</h3>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Produto</th>
                            <th>Variações</th>
                            <th>Preço</th>
                            <th>Status</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Linha de exemplo -->
                        <tr>
                            <td><strong>Pudim de Leite</strong></td>
                            <td>Leite condensado</td>
                            <td>R$ 12,00</td>
                            <td><span class="status-badge">Ativo</span></td>
                            <td>✏️ 🗑️</td>
                        </tr>
                        <tr>
                            <td><strong>Torta de Limão</strong></td>
                            <td>Merengue</td>
                            <td>R$ 15,00</td>
                            <td><span class="status-badge inactive">Inativo</span></td>
                            <td>✏️ 🗑️</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>
    </main>

</body>
</html>
```

### Dicas de Implementação para Manter a Consistência da Marca

*   **Ícones:** Substitua os emojis por uma biblioteca de ícones SVG como **Phosphor Icons** ou **Lucide Icons** para manter o design profissional e alinhado com a linguagem visual da NeuraBar.
*   **Tipografia:** Certifique-se de que as fontes **Poppins** e **Manrope** estão carregadas corretamente no seu projeto. A hierarquia tipográfica é fundamental para a identidade da marca.
*   **Componentes:** A estrutura do código foi pensada para ser reutilizável. Classes como `.btn-primary`, `.mode-card` e `.status-badge` devem ser usadas em todo o sistema para garantir uma experiência consistente e coesa.
*   **Filosofia:** Lembre-se dos princípios da NeuraBar (Conectividade, Inteligência, Eficiência, Evolução, Experiência) ao desenvolver novas funcionalidades. Cada elemento deve comunicar essa essência.