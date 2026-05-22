Analisando o layout fornecido, podemos identificar claramente uma interface administrativa (dashboard) moderna, limpa e altamente funcional. A identidade visual é marcada pelo minimalismo, uso de espaços em branco (whitespace) para separar os elementos, e um esquema de cores focado na legibilidade e no direcionamento da atenção do usuário.

### Análise da Identidade Visual

* **Paleta de Cores:**
* **Cor Primária (Destaque):** Um tom de azul vibrante (aproximadamente `#007BFF` ou `#0284C7`), usado na logo, links ativos, bordas de seleção e botões de ação primária.
* **Plano de Fundo (Background):** Cinza muito claro/off-white (aproximadamente `#F9FAFB`), criando contraste suave com os cartões brancos.
* **Superfícies (Cards/Header):** Branco puro (`#FFFFFF`) com bordas sutis.
* **Texto:** Tons de cinza escuro para títulos (`#1F2937`) e cinza médio para descrições e links inativos (`#6B7280`).


* **Tipografia:** Fonte sem serifa, limpa e moderna (como *Inter*, *Roboto* ou *Segoe UI*).
* **Estrutura (Layout):**
* **Header (Cabeçalho):** Fixo ou no topo, contendo a marcação da empresa, informações da sessão do usuário, navegação principal horizontal e botão de saída.
* **Navegação Secundária (Cards):** Utiliza um formato de cartões clicáveis horizontais para alternar entre os modos de visualização. O estado "ativo" recebe um fundo azul claro e borda azul.
* **Containers de Conteúdo:** Áreas bem delimitadas com bordas arredondadas (radius de aprox. 8px) e sombras extremamente sutis (ou apenas bordas) para não sobrecarregar a interface.



---

### Código HTML e CSS

Abaixo está a estrutura completa em um único arquivo (HTML + CSS) que replica fielmente essa identidade visual usando Flexbox para o posicionamento e CSS Variables para facilitar a manutenção das cores.

```html
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Neura - Dashboard de Cardápio</title>
    <style>
        /* Variáveis de Cores e Estilo (Identidade Visual) */
        :root {
            --primary-blue: #0ea5e9; /* Azul principal */
            --primary-blue-light: #e0f2fe; /* Fundo do card ativo */
            --bg-color: #f8fafc; /* Fundo da página */
            --surface-color: #ffffff; /* Fundo de cartões e header */
            --text-main: #0f172a; /* Texto escuro (títulos) */
            --text-muted: #64748b; /* Texto secundário */
            --border-color: #e2e8f0; /* Bordas sutis */
            --radius-md: 8px;
            --radius-lg: 12px;
            --font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }

        /* Reset e Base */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: var(--font-family);
            background-color: var(--bg-color);
            color: var(--text-main);
            -webkit-font-smoothing: antialiased;
        }

        /* Tipografia Base */
        h1 { font-size: 24px; font-weight: 700; margin-bottom: 4px; }
        h2 { font-size: 18px; font-weight: 600; margin-bottom: 4px; }
        h3 { font-size: 16px; font-weight: 600; margin-bottom: 16px; }
        p { color: var(--text-muted); font-size: 14px; }

        /* Cabeçalho (Header) */
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

        .logo-icon {
            background-color: var(--primary-blue);
            color: white;
            width: 32px;
            height: 32px;
            border-radius: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 18px;
        }

        .brand-name {
            font-weight: 700;
            font-size: 18px;
        }

        .user-info {
            display: flex;
            flex-direction: column;
            font-size: 12px;
            color: var(--text-muted);
            border-left: 1px solid var(--border-color);
            padding-left: 16px;
        }

        .user-info strong { color: var(--text-main); }

        /* Navegação Principal */
        .main-nav {
            display: flex;
            gap: 24px;
        }

        .main-nav a {
            text-decoration: none;
            color: var(--text-muted);
            font-size: 14px;
            font-weight: 500;
            transition: color 0.2s;
        }

        .main-nav a:hover { color: var(--text-main); }
        .main-nav a.active { color: var(--primary-blue); }

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
        }

        /* Container Principal */
        .main-content {
            max-width: 1200px;
            margin: 0 auto;
            padding: 32px 24px;
        }

        /* Cartões de Modo (Tabs horizontais) */
        .mode-cards {
            display: flex;
            gap: 16px;
            margin-top: 24px;
            margin-bottom: 40px;
            overflow-x: auto;
            padding-bottom: 8px; /* Para scrollbar se houver */
        }

        .mode-card {
            background-color: var(--surface-color);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-md);
            padding: 16px;
            min-width: 220px;
            cursor: pointer;
            transition: all 0.2s;
        }

        .mode-card.active {
            border-color: var(--primary-blue);
            background-color: var(--primary-blue-light);
        }

        .mode-card.active .card-title { color: var(--primary-blue); }

        .card-icon {
            margin-bottom: 12px;
            font-size: 18px; /* Placeholder para ícone real */
        }
        
        .mode-card.active .card-icon { color: var(--primary-blue); }

        .card-title {
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

        /* Seção Resumo */
        .section-header {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 16px;
        }

        .loading-box {
            background-color: var(--bg-color); /* Fundo um pouco mais escuro para destaque da borda */
            border: 1px solid var(--border-color);
            border-radius: var(--radius-lg);
            padding: 48px;
            text-align: center;
            color: var(--text-muted);
            font-size: 14px;
            margin-bottom: 48px;
        }

        /* Seção Cardápio Compacto */
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
        }

        .btn-primary {
            background-color: var(--primary-blue);
            color: white;
            border: none;
            padding: 10px 16px;
            border-radius: var(--radius-md);
            font-weight: 500;
            font-size: 14px;
            cursor: pointer;
            transition: background-color 0.2s;
        }

        .btn-primary:hover { background-color: #0284c7; }

        /* Tabela Simplificada */
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
            font-weight: 500;
            font-size: 14px;
        }

    </style>
</head>
<body>

    <header class="top-header">
        <div class="header-left">
            <div class="brand-area">
                <div class="logo-icon">N</div>
                <div class="brand-name">Neura</div>
            </div>
            <div class="user-info">
                <span>logado como: <strong>Dono</strong></span>
                <span>Bar do Zé Id=1</span>
            </div>
        </div>

        <nav class="main-nav">
            <a href="#">Painel</a>
            <a href="#">Chama garçom</a>
            <a href="#" class="active">Cardápio</a>
            <a href="#">Atendimento</a>
            <a href="#">Anotar pedidos</a>
            <a href="#">Tela de preparo</a>
            <a href="#">Gerenciamento de atendimentos</a>
        </nav>

        <button class="btn-logout">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg>
            Sair
        </button>
    </header>

    <main class="main-content">
        <header class="page-title">
            <h1>Cardápio</h1>
            <p>Gerencie o cardápio em diferentes modos</p>
        </header>

        <div class="mode-cards">
            <div class="mode-card active">
                <div class="card-icon">📊</div>
                <div class="card-title">Resumo categ...</div>
                <div class="card-desc"></div>
            </div>
            <div class="mode-card">
                <div class="card-icon">🔲</div>
                <div class="card-title">Cardápio comp...</div>
                <div class="card-desc">Ative e desative itens rapidamente</div>
            </div>
            <div class="mode-card">
                <div class="card-icon">⚙️</div>
                <div class="card-title">Cardápio comp...</div>
                <div class="card-desc">Adicione itens, altere variações, preços e...</div>
            </div>
            <div class="mode-card">
                <div class="card-icon">📱</div>
                <div class="card-title">Cardápio digital</div>
                <div class="card-desc">Edite as fotos dos pratos e visualize com...</div>
            </div>
            <div class="mode-card">
                <div class="card-icon">👁️</div>
                <div class="card-title">Versão clientes</div>
                <div class="card-desc">Veja como o cliente vê o cardápio no celular</div>
            </div>
        </div>

        <section>
            <div class="section-header">
                <h2>📊 Resumo por categoria</h2>
            </div>
            <p style="margin-bottom: 16px;">Quantidade de itens ativos e inativos por categoria</p>
            
            <div class="loading-box">
                Carregando resumo...
            </div>
        </section>

        <section class="compact-menu-container">
            <div class="compact-header">
                <div>
                    <h2>Cardápio Compacto</h2>
                    <p>Gerencie os itens do seu cardápio</p>
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
                            <th>Ativo</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        </tbody>
                </table>
            </div>
        </section>
    </main>

</body>
</html>

```

### Dicas de Implementação

* **Ícones:** No código acima, usei emojis como marcadores de espaço (placeholders). Para manter o design extremamente simples e alinhado com o estilo ícone e minimalista que sistemas modernos exigem, recomendo a substituição por uma biblioteca de ícones SVG limpos, como o **Phosphor Icons** ou **Lucide Icons**.
* **Fontes:** O uso do Google Fonts (`<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">`) na tag `<head>` deixará a interface idêntica ao design original.
* **Escalabilidade:** A estrutura foca na reutilização. As classes `.btn-primary` ou `.surface-color` podem ser reaproveitadas em todo o desenvolvimento do sistema para manter a consistência estética do painel.