<script setup>
import { Link } from '@inertiajs/vue3';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import CustomHead from '@/Components/CustomHead.vue';
import '../../css/welcome-landing.css';

defineProps({
    canLogin: Boolean,
    canRegister: Boolean,
});

const brands = ['Bar do Zé', 'Restaurante Bella Vista', 'Choperia Aurora', 'Pizza & Cia', 'Boteco Central'];

const features = [
    {
        icon: 'menu',
        title: 'Cardápio Digital Completo',
        bullets: [
            'Acesso por QR Code',
            'Acesso para delivery',
            'Edição simples e rápida',
            'Fotos dos produtos (opcional)',
            'Combos, modificadores e variações de tamanho',
        ],
    },
    {
        icon: 'orders',
        title: 'Pedidos em Tempo Real',
        bullets: [
            'Envio para monitor e/ou impressora',
            'Separação por setores da cozinha',
            'Pedidos lançados pelo celular',
            'Tela de gerenciamento de setores',
        ],
    },
    {
        icon: 'kds',
        title: 'KDS — Tela de Cozinha (Kitchen Display System)',
        bullets: [
            'Acompanhamento por tempo, setor e produto',
            'Setorização (ex.: confeitaria, cozinha, bebidas)',
            'Interface organizada e eficiente',
            'Atualização em tempo real para mais agilidade',
        ],
    },
    {
        icon: 'payment',
        title: 'Pagamento e Fechamento',
        bullets: [
            'Fechamento parcial',
            'Divisão de contas',
            'Taxas de couvert e de atendimento',
            'Fechamento por mesa e/ou comanda (opcional)',
        ],
    },
    {
        icon: 'qrcode',
        title: 'QR Code na Mesa',
        bullets: [
            'Acesso ao cardápio digital',
            'Solicitação de atendimento pelo cliente',
            'Rápido, fácil e econômico',
        ],
    },
    {
        icon: 'corporation',
        title: 'Multi-unidades',
        bullets: [
            'Gerencie várias unidades',
            'Painel centralizado para todas as filiais',
        ],
    },
];

const steps = [
    {
        number: '01',
        title: 'Crie sua conta',
        bullets: [
            'Cadastro gratuito em menos de 2 minutos',
            'Sem cartão de crédito',
            'Sem cobranças inesperadas',
        ],
    },
    {
        number: '02',
        title: 'Configure seu estabelecimento',
        bullets: [
            {
                text: 'Adicione o cardápio',
                children: [
                    'Itens pré-cadastrados (opcional)',
                    'Solicite o cadastro do cardápio por um terceirizado (opcional)',
                ],
            },
            'Configure as mesas (adicione quantas precisar)',
            'Configure locais de atendimento (varanda, palco, etc.)',
        ],
    },
    {
        number: '03',
        title: 'Comece a atender',
        bullets: [
            'Abra atendimentos',
            'Registre pedidos',
            'Acompanhe tudo em tempo real',
        ],
    },
];

const idealUseRoles = [
    {
        number: '01',
        title: 'Gerente no computador',
        text: 'Com o computador aberto, monitora toda a operação e realiza o fechamento das mesas.',
    },
    {
        number: '02',
        title: 'Monitor na cozinha',
        text: 'Exibe os pedidos novos; a cozinha libera os pedidos quando ficam prontos para entrega.',
    },
    {
        number: '03',
        title: 'Garçom com celular',
        text: 'Registra os pedidos pelo NeuraBar com o celular na mão.',
    },
    {
        number: '04',
        title: 'Entrega dos pedidos',
        text: 'O garçom ou o gerente leva os pedidos prontos até as mesas.',
    },
];

const plans = [
    {
        name: 'Básico',
        price: 'R$ 97',
        period: '/mês',
        description: 'Ideal para bares e restaurantes iniciando a digitalização.',
        features: ['1 unidade', 'Cardápio digital', 'Pedidos e KDS', 'Até 3 usuários', 'Suporte por e-mail'],
        cta: 'Começar grátis',
        highlighted: false,
    },
    {
        name: 'Pro',
        price: 'R$ 197',
        period: '/mês',
        description: 'Para operações que precisam de mais controle e escala.',
        features: ['1 unidade', 'Tudo do Básico', 'QR Code para mesas', 'Relatórios avançados', 'Usuários ilimitados', 'Suporte prioritário'],
        cta: 'Assinar Pro',
        highlighted: true,
    },
    {
        name: 'Enterprise',
        price: 'Sob consulta',
        period: '',
        description: 'Para grupos e redes com múltiplas unidades.',
        features: ['Unidades ilimitadas', 'Tudo do Pro', 'Painel corporativo', 'SLA garantido', 'Onboarding dedicado'],
        cta: 'Falar com vendas',
        highlighted: false,
    },
];

const reasons = [
    {
        number: '01',
        title: 'Menos erros e retrabalho',
        bullets: [
            'Menos erros nos pedidos',
            'Menos pedidos esquecidos',
            'Gerencie pedidos em tempo real por atendente',
            'Garçons não perdem tempo indo à cozinha levar pedidos',
        ],
    },
    {
        number: '02',
        title: 'Atendimento mais ágil',
        bullets: [
            'Empilhamento automático de pedidos',
            'KDS (Kitchen Display System): monitor de pedidos por setor',
            'Visualize a demanda de produção e aloque a equipe com facilidade',
        ],
    },
    {
        number: '03',
        title: 'Delivery próprio',
        bullets: [
            'Cardápio exclusivo para delivery',
            'Sem taxa por pedido',
            'Com poucos pedidos, a economia nas comissões pode superar o valor da mensalidade',
        ],
    },
    {
        number: '04',
        title: 'Relatórios',
        bullets: [
            'Pedidos do dia',
            'Faturamento',
            'Atendimentos por garçom',
            'Demanda por setor (cozinha, drinks, etc.)',
            'Resultados visíveis e previsíveis, com menos dor de cabeça',
        ],
    },
    {
        number: '05',
        title: 'Comece sem risco',
        bullets: [
            '14 dias grátis para testar',
            'Sem cartão de crédito no período de teste',
            'Sem cobranças inesperadas',
        ],
    },
    {
        number: '06',
        title: 'Garçom vendedor',
        bullets: [
            'Transforme seu garçom em vendedor dos melhores pratos da casa',
            'Mais tempo disponível junto aos clientes',
            'Oriente novos clientes sobre o que pedir',
            'Valorize o atendimento aos clientes da casa',
            'De mesa em mesa, sem ir à cozinha — pedidos pelo celular, mais vendas e entregas',
        ],
    },
];

const testimonials = [
    { quote: 'Depois do NeuraBar, nossa cozinha parou de perder pedidos. O KDS mudou tudo.', author: 'Carlos M.', role: 'Dono, Choperia Aurora' },
    { quote: 'Em 20 minutos configurei o cardápio completo. A equipe adorou e os clientes também.', author: 'Ana Paula S.', role: 'Gerente, Restaurante Bella Vista' },
    { quote: 'O QR Code na mesa reduziu erros nos pedidos e melhorou muito a experiência do cliente.', author: 'Rafael T.', role: 'Proprietário, Bar do Zé' },
];

const faqs = [
    { question: 'O NeuraBar tem período de teste gratuito?', answer: 'Sim, 14 dias grátis sem necessidade de cartão de crédito. Você pode testar todas as funcionalidades do plano escolhido sem qualquer compromisso.' },
    { question: 'Funciona no celular do garçom?', answer: 'Sim, a interface se adapta a qualquer tamanho de tela. Funciona em qualquer smartphone, tablet ou computador moderno com acesso à internet.' },
    { question: 'Preciso comprar tablet, monitor ou algo do tipo?', answer: 'Não precisa. Inicialmente, basta um computador ou notebook e um celular para enviar os pedidos. Recomendamos um monitor de TV ou de computador na cozinha ou nos setores de produção.' },
    { question: 'Preciso instalar algum aplicativo?', answer: 'Não. O NeuraBar é 100% baseado em web. Basta abrir o navegador e acessar — sem instalação, sem atualizações manuais.' },
    { question: 'Posso gerenciar mais de um estabelecimento?', answer: 'Sim, no plano Enterprise você gerencia unidades ilimitadas a partir de um único painel corporativo, com equipes e cardápios independentes por filial.' },
    { question: 'Como funciona o suporte técnico?', answer: 'Oferecemos suporte via e-mail em todos os planos. O plano Pro inclui atendimento prioritário e o Enterprise conta com onboarding dedicado e prazo de resposta garantido em contrato.' },
    { question: 'Posso cancelar a qualquer momento?', answer: 'Sim, sem multas ou taxas de cancelamento. Você pode cancelar pelo painel de configurações da sua conta quando quiser.' },
];

const starPath = 'M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z';

const checkPath = 'M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z';
</script>

<template>
    <CustomHead title="NeuraBar — Gestão inteligente para bares e restaurantes" />

    <div class="welcome-landing">
        <!-- NAVBAR -->
        <header class="navbar" role="banner">
            <div class="container">
                <div class="navbar__inner">
                    <Link href="/" class="navbar__brand" aria-label="NeuraBar - Página inicial">
                        <div style="width: 40px; height: 40px; margin-right: 0.75rem">
                            <ApplicationLogo class="h-full w-full" />
                        </div>
                        NeuraBar
                    </Link>

                    <nav class="navbar__nav" aria-label="Navegação principal">
                        <a href="#funcionalidades">Funcionalidades</a>
                        <a href="#como-funciona">Como funciona</a>
                        <a href="#planos">Planos</a>
                        <a href="#faq">FAQ</a>
                    </nav>

                    <div class="navbar__cta">
                        <Link v-if="canLogin" :href="route('login')" class="navbar__login">Entrar</Link>
                        <Link v-if="canRegister" :href="route('register')" class="navbar__signup">Começar grátis</Link>
                    </div>
                </div>
            </div>
        </header>

        <!-- HERO -->
        <section class="hero" aria-labelledby="hero-heading">
            <div class="hero__bg" aria-hidden="true">
                <div class="hero__bg-circle1"></div>
                <div class="hero__bg-circle2"></div>
            </div>

            <div class="container">
                <div class="hero__content">
                    <div class="hero__badge" aria-hidden="true">
                        <div class="hero__badge-dot"></div>
                        <span>Sistema para Bares &amp; Restaurantes</span>
                    </div>

                    <h1 class="hero__title" id="hero-heading">
                        Agilize seu atendimento<br>para <span class="hero__title-blue">Bares e Restaurantes</span><br>com
                        <span class="gradient"> Inteligência</span>
                    </h1>

                    <p class="hero__sub">Tudo que você precisa para agilizar sua operação:</p>

                    <ul class="hero__list">
                        <li><strong>Cardápio digital:</strong> mais uma opção para clientes no salão ou no delivery</li>
                        <li><strong>Empilhamento de pedidos:</strong> envio para monitores ou impressoras</li>
                        <li><strong>Setorização de trabalho:</strong> monte seus setores (cozinha, bar, churrasqueira, etc.)</li>
                        <li><strong>Controle de pagamentos:</strong> divisão de contas e muito mais</li>
                        <li><strong>Sistema simplificado:</strong> fácil, ágil e barato</li>
                    </ul>

                    <div class="hero__actions">
                        <Link v-if="canRegister" :href="route('register')" class="btn-primary">
                            Começar grátis agora
                            <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13 7l5 5m0 0l-5 5m5-5H6" />
                            </svg>
                        </Link>
                        <a href="#funcionalidades" class="btn-outline">Ver funcionalidades</a>
                    </div>

                    <p class="hero__fine-print">Sem cartão de crédito · Configuração em minutos · Cancele quando quiser</p>
                </div>

                <div class="hero__mockup" aria-hidden="true">
                    <div class="mockup-shell">
                        <div class="mockup-bar">
                            <div class="mockup-dot red"></div>
                            <div class="mockup-dot yellow"></div>
                            <div class="mockup-dot green"></div>
                            <div class="mockup-url">app.neurabar.com/dashboard</div>
                        </div>
                        <div class="mockup-body">
                            <div class="mockup-sidebar">
                                <div class="mockup-nav-item active">
                                    <div class="mockup-nav-dot primary"></div>
                                    <div class="mockup-nav-bar" style="width: 4rem"></div>
                                </div>
                                <div class="mockup-nav-item"><div class="mockup-nav-dot muted"></div><div class="mockup-nav-bar" style="width: 3.5rem"></div></div>
                                <div class="mockup-nav-item"><div class="mockup-nav-dot muted"></div><div class="mockup-nav-bar" style="width: 5rem"></div></div>
                                <div class="mockup-nav-item"><div class="mockup-nav-dot muted"></div><div class="mockup-nav-bar" style="width: 4.5rem"></div></div>
                                <div class="mockup-nav-item"><div class="mockup-nav-dot muted"></div><div class="mockup-nav-bar" style="width: 3.5rem"></div></div>
                            </div>
                            <div class="mockup-main">
                                <div class="mockup-stats">
                                    <div class="mockup-stat-card">
                                        <div class="mockup-stat-label">Pedidos hoje</div>
                                        <div class="mockup-stat-value primary">127</div>
                                    </div>
                                    <div class="mockup-stat-card">
                                        <div class="mockup-stat-label">Faturamento</div>
                                        <div class="mockup-stat-value accent">R$ 8.840</div>
                                    </div>
                                    <div class="mockup-stat-card">
                                        <div class="mockup-stat-label">Mesas abertas</div>
                                        <div class="mockup-stat-value gold">12</div>
                                    </div>
                                </div>
                                <div class="mockup-orders">
                                    <div class="mockup-orders-title"></div>
                                    <div class="mockup-order-row"><div class="mockup-order-dot" style="background: var(--primary)"></div><div class="mockup-order-bar"></div><div class="mockup-order-amount"></div><div class="mockup-badge"></div></div>
                                    <div class="mockup-order-row"><div class="mockup-order-dot" style="background: #4ade80"></div><div class="mockup-order-bar"></div><div class="mockup-order-amount"></div><div class="mockup-badge"></div></div>
                                    <div class="mockup-order-row"><div class="mockup-order-dot" style="background: var(--warm-gold)"></div><div class="mockup-order-bar"></div><div class="mockup-order-amount"></div><div class="mockup-badge"></div></div>
                                    <div class="mockup-order-row"><div class="mockup-order-dot" style="background: rgba(100,116,139,0.3)"></div><div class="mockup-order-bar"></div><div class="mockup-order-amount"></div><div class="mockup-badge"></div></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="hero__glow" aria-hidden="true"></div>
                </div>
            </div>
        </section>

        <!-- SOCIAL PROOF -->
        <section class="social-proof" aria-label="Clientes">
            <div class="container">
                <p class="social-proof__label">Confiado por estabelecimentos de todo o Brasil</p>
                <div class="social-proof__logos" aria-hidden="true">
                    <span v-for="brand in brands" :key="brand">{{ brand }}</span>
                </div>
            </div>
        </section>

        <!-- FEATURES -->
        <section id="funcionalidades" class="features" aria-labelledby="features-heading">
            <div class="container">
                <div class="text-center">
                    <p class="section-label">Funcionalidades</p>
                    <h2 class="section-title mt-2" id="features-heading">Mais agilidade e economia para seu estabelecimento</h2>
                    <p class="section-sub mt-4">Cardápio digital com opção de delivery.<br>Empilhamento de pedidos e KDS (Kitchen Display System).</p>
                </div>

                <div class="features__grid" role="list">
                    <article v-for="feature in features" :key="feature.title" class="feature-card" role="listitem">
                        <div class="feature-icon" aria-hidden="true">
                            <svg v-if="feature.icon === 'menu'" width="24" height="24" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
                            </svg>
                            <svg v-else-if="feature.icon === 'orders'" width="24" height="24" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                            </svg>
                            <svg v-else-if="feature.icon === 'kds'" width="24" height="24" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                            </svg>
                            <svg v-else-if="feature.icon === 'payment'" width="24" height="24" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                            </svg>
                            <svg v-else-if="feature.icon === 'qrcode'" width="24" height="24" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12v.01M12 4h.01M4 4h4v4H4V4zm12 0h4v4h-4V4zM4 16h4v4H4v-4z" />
                            </svg>
                            <svg v-else-if="feature.icon === 'corporation'" width="24" height="24" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="feature-title">{{ feature.title }}</h3>
                            <p v-if="feature.description" class="feature-desc">{{ feature.description }}</p>
                            <ul v-if="feature.bullets" class="feature-list">
                                <li v-for="bullet in feature.bullets" :key="bullet">{{ bullet }}</li>
                            </ul>
                        </div>
                    </article>
                </div>
            </div>
        </section>

        <!-- HOW IT WORKS -->
        <section id="como-funciona" class="how" aria-labelledby="how-heading">
            <div class="container">
                <div class="text-center">
                    <p class="section-label">Como funciona</p>
                    <h2 class="section-title mt-2" id="how-heading">Do cadastro ao primeiro atendimento em minutos</h2>
                </div>

                <div class="how__grid" role="list">
                    <div v-for="step in steps" :key="step.number" class="step" role="listitem">
                        <div class="step__number" aria-hidden="true">{{ step.number }}</div>
                        <h3 class="step__title">{{ step.title }}</h3>
                        <p v-if="step.description" class="step__desc">{{ step.description }}</p>
                        <ul v-if="step.bullets" class="step__list">
                            <template v-for="(bullet, index) in step.bullets" :key="index">
                                <li v-if="typeof bullet === 'string'">{{ bullet }}</li>
                                <li v-else class="step__list-item--group">
                                    {{ bullet.text }}
                                    <ul v-if="bullet.children" class="step__list--nested">
                                        <li v-for="child in bullet.children" :key="child">{{ child }}</li>
                                    </ul>
                                </li>
                            </template>
                        </ul>
                    </div>
                </div>
            </div>
        </section>

        <!-- IDEAL USE -->
        <section id="uso-ideal" class="ideal-use" aria-labelledby="ideal-use-heading">
            <div class="container">
                <div class="text-center">
                    <p class="section-label">Operação enxuta</p>
                    <h2 class="section-title mt-2" id="ideal-use-heading">Como foi idealizado para usar o programa?</h2>
                    <p class="section-sub mt-4">Para funcionar do modo mais enxuto possível</p>
                </div>

                <div class="ideal-use__grid" role="list">
                    <article v-for="role in idealUseRoles" :key="role.number" class="ideal-use-card" role="listitem">
                        <span class="ideal-use-card__number" aria-hidden="true">{{ role.number }}</span>
                        <h3 class="ideal-use-card__title">{{ role.title }}</h3>
                        <p class="ideal-use-card__text">{{ role.text }}</p>
                    </article>
                </div>
            </div>
        </section>

        <!-- PRICING -->
        <section id="planos" class="pricing" aria-labelledby="pricing-heading">
            <div class="container">
                <div class="text-center">
                    <p class="section-label">Planos</p>
                    <h2 class="section-title mt-2" id="pricing-heading">Preço simples e transparente</h2>
                    <p class="section-sub mt-4">Comece grátis por 14 dias. Sem taxa de setup, sem surpresas na fatura.</p>
                </div>

                <div class="pricing__grid" aria-label="Planos de preço">
                    <article
                        v-for="plan in plans"
                        :key="plan.name"
                        :class="['plan-card', { highlighted: plan.highlighted }]"
                        role="listitem"
                    >
                        <div v-if="plan.highlighted" class="plan-badge" aria-label="Mais popular">Mais popular</div>
                        <div>
                            <h3 class="plan-name">{{ plan.name }}</h3>
                            <p class="plan-desc">{{ plan.description }}</p>
                        </div>
                        <div class="plan-price-wrap">
                            <span class="plan-price">{{ plan.price }}</span>
                            <span v-if="plan.period" class="plan-period">{{ plan.period }}</span>
                        </div>
                        <ul class="plan-features" :aria-label="`Incluso no plano ${plan.name}`">
                            <li v-for="feat in plan.features" :key="feat">
                                <svg width="16" height="16" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                    <path fill-rule="evenodd" :d="checkPath" clip-rule="evenodd" />
                                </svg>
                                {{ feat }}
                            </li>
                        </ul>
                        <Link
                            v-if="canRegister"
                            :href="route('register')"
                            :class="['plan-cta', plan.highlighted ? 'highlighted' : 'default']"
                        >
                            {{ plan.cta }}
                        </Link>
                    </article>
                </div>
            </div>
        </section>

        <!-- REASONS -->
        <section id="motivos" class="reasons" aria-labelledby="reasons-heading">
            <div class="container">
                <div class="text-center">
                    <p class="section-label">Por que investir</p>
                    <h2 class="section-title" id="reasons-heading">6 motivos que compensam o investimento</h2>
                </div>

                <div class="reasons__grid" role="list">
                    <article v-for="reason in reasons" :key="reason.number" class="reason-card" role="listitem">
                        <span class="reason-card__number" aria-hidden="true">{{ reason.number }}</span>
                        <h3 class="reason-card__title">{{ reason.title }}</h3>
                        <ul v-if="reason.bullets" class="reason-card__list">
                            <li v-for="bullet in reason.bullets" :key="bullet">{{ bullet }}</li>
                        </ul>
                        <p v-else-if="reason.text" class="reason-card__text">{{ reason.text }}</p>
                    </article>
                </div>
            </div>
        </section>

        <!-- TESTIMONIALS -->
        <section class="testimonials" aria-labelledby="testimonials-heading">
            <div class="container">
                <div class="text-center">
                    <h2 class="section-title" id="testimonials-heading">O que nossos clientes dizem</h2>
                </div>

                <div class="testimonials__grid" role="list">
                    <article v-for="testimonial in testimonials" :key="testimonial.author" class="testimonial-card" role="listitem">
                        <div class="testimonial-stars" aria-label="5 estrelas">
                            <svg v-for="s in 5" :key="s" viewBox="0 0 20 20"><path :d="starPath" /></svg>
                        </div>
                        <blockquote class="testimonial-quote">"{{ testimonial.quote }}"</blockquote>
                        <footer>
                            <p class="testimonial-author">{{ testimonial.author }}</p>
                            <p class="testimonial-role">{{ testimonial.role }}</p>
                        </footer>
                    </article>
                </div>
            </div>
        </section>

        <!-- FAQ -->
        <section id="faq" class="faq" aria-labelledby="faq-heading">
            <div class="container">
                <div class="text-center">
                    <p class="section-label">Dúvidas frequentes</p>
                    <h2 class="section-title mt-2" id="faq-heading">Perguntas frequentes</h2>
                </div>
                <div class="faq__list">
                    <details v-for="faq in faqs" :key="faq.question">
                        <summary>{{ faq.question }}</summary>
                        <p class="faq-answer">{{ faq.answer }}</p>
                    </details>
                </div>
            </div>
        </section>

        <!-- FINAL CTA -->
        <section class="cta-section" aria-labelledby="cta-heading">
            <div class="container text-center">
                <h2 class="section-title" id="cta-heading">Pronto para modernizar seu estabelecimento?</h2>
                <p class="section-sub mt-4">Junte-se a centenas de bares e restaurantes que já gerenciam seus negócios com o NeuraBar.</p>
                <div class="cta-actions">
                    <Link v-if="canRegister" :href="route('register')" class="btn-primary">
                        Criar conta gratuitamente
                        <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13 7l5 5m0 0l-5 5m5-5H6" />
                        </svg>
                    </Link>
                    <p class="cta-fine">14 dias grátis · Sem cartão de crédito</p>
                </div>
            </div>
        </section>

        <!-- FOOTER -->
        <footer class="footer" role="contentinfo">
            <div class="container">
                <div class="footer__grid">
                    <div>
                        <div class="footer__brand" aria-label="NeuraBar">
                            <div style="width: 40px; height: 40px; margin-right: 0.75rem">
                                <ApplicationLogo class="h-full w-full" />
                            </div>
                            NeuraBar
                        </div>
                        <p class="footer__tagline">Plataforma SaaS de gestão inteligente para bares e restaurantes de pequeno e médio porte.</p>
                    </div>

                    <div>
                        <p class="footer__heading">Produto</p>
                        <ul class="footer__links">
                            <li><a href="#funcionalidades">Funcionalidades</a></li>
                            <li><a href="#planos">Planos</a></li>
                            <li><a href="#como-funciona">Como funciona</a></li>
                            <li><a href="#faq">FAQ</a></li>
                        </ul>
                    </div>

                    <div>
                        <p class="footer__heading">Conta</p>
                        <ul class="footer__links">
                            <li><Link v-if="canLogin" :href="route('login')">Entrar</Link></li>
                            <li><Link v-if="canRegister" :href="route('register')">Criar conta</Link></li>
                        </ul>
                    </div>
                </div>

                <div class="footer__bottom">
                    <p>&copy; {{ new Date().getFullYear() }} NeuraBar. Todos os direitos reservados.</p>
                </div>
            </div>
        </footer>
    </div>
</template>
