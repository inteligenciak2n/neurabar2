<script setup>
import { Head, Link } from '@inertiajs/vue3';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';

defineProps({
    canLogin: Boolean,
    canRegister: Boolean,
});

const features = [
    {
        icon: 'menu',
        title: 'Cardápio Digital Completo',
        description: 'Crie menus com categorias, produtos, variações de preço e modificadores. Atualize em tempo real sem reimprimir nada.',
    },
    {
        icon: 'orders',
        title: 'Pedidos em Tempo Real',
        description: 'Atendentes anotam pedidos pelo tablet ou celular. Tudo cai automaticamente na cozinha via WebSocket.',
    },
    {
        icon: 'kds',
        title: 'KDS — Tela de Cozinha',
        description: 'A cozinha acompanha cada pedido em tempo real, com status de preparo e alertas de prioridade.',
    },
    {
        icon: 'payment',
        title: 'Pagamentos e Fechamento',
        description: 'Divida a conta, registre pagamentos por método e feche atendimentos com total controle financeiro.',
    },
    {
        icon: 'qrcode',
        title: 'QR Code para a Mesa',
        description: 'O cliente escaneia, visualiza o cardápio, faz pedidos e chama o garçom — tudo pelo próprio celular.',
    },
    {
        icon: 'corporation',
        title: 'Multi-unidades',
        description: 'Gerencie várias filiais de uma única conta. Cada venue com seu cardápio, equipe e configurações independentes.',
    },
];

const steps = [
    {
        number: '01',
        title: 'Crie sua conta',
        description: 'Cadastro gratuito em menos de 2 minutos. Sem cartão de crédito.',
    },
    {
        number: '02',
        title: 'Configure seu estabelecimento',
        description: 'Adicione seu cardápio, equipe, mesas e locais de atendimento.',
    },
    {
        number: '03',
        title: 'Comece a atender',
        description: 'Abra atendimentos, anote pedidos e acompanhe tudo em tempo real.',
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
</script>

<template>
    <Head title="NeuraBar — Gestão inteligente para bares e restaurantes" />

    <div class="min-h-screen bg-muted font-body text-foreground antialiased">

        <!-- ==================== NAVBAR ==================== -->
        <header class="sticky top-0 z-50 border-b border-border bg-surface/90 backdrop-blur-sm">
            <div class="mx-auto flex h-16 max-w-7xl items-center justify-between px-4 sm:px-6 lg:px-8">
                <!-- Logo -->
                <Link href="/" class="flex items-center gap-2.5">
                    <ApplicationLogo class="h-8 w-8" />
                    <span class="font-heading text-xl font-bold text-ocean-deep">NeuraBar</span>
                </Link>

                <!-- Nav links desktop -->
                <nav class="hidden items-center gap-8 md:flex">
                    <a href="#funcionalidades" class="text-sm font-medium text-muted-foreground transition-colors hover:text-foreground">Funcionalidades</a>
                    <a href="#como-funciona" class="text-sm font-medium text-muted-foreground transition-colors hover:text-foreground">Como funciona</a>
                    <a href="#planos" class="text-sm font-medium text-muted-foreground transition-colors hover:text-foreground">Planos</a>
                </nav>

                <!-- CTA buttons -->
                <div class="flex items-center gap-3">
                    <template v-if="canLogin">
                        <Link
                            :href="route('login')"
                            class="hidden text-sm font-medium text-muted-foreground transition-colors hover:text-foreground sm:block"
                        >
                            Entrar
                        </Link>
                    </template>
                    <template v-if="canRegister">
                        <Link
                            :href="route('register')"
                            class="rounded-md bg-primary px-4 py-2 text-sm font-semibold text-white shadow-sm transition-colors hover:bg-primary-hover focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2"
                        >
                            Começar grátis
                        </Link>
                    </template>
                </div>
            </div>
        </header>

        <!-- ==================== HERO ==================== -->
        <section class="relative overflow-hidden bg-ocean-deep py-20 lg:py-32">
            <!-- Background decorative elements -->
            <div class="pointer-events-none absolute inset-0 overflow-hidden">
                <div class="absolute -right-64 -top-64 h-[600px] w-[600px] rounded-full bg-primary/10 blur-3xl"></div>
                <div class="absolute -bottom-32 -left-32 h-[400px] w-[400px] rounded-full bg-accent/10 blur-3xl"></div>
                <div class="absolute left-1/2 top-1/2 h-[800px] w-[800px] -translate-x-1/2 -translate-y-1/2 rounded-full border border-white/5"></div>
            </div>

            <div class="relative mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="mx-auto max-w-3xl text-center">
                    <!-- Badge -->
                    <div class="mb-6 inline-flex items-center gap-2 rounded-full border border-primary/30 bg-primary/10 px-4 py-1.5">
                        <span class="h-1.5 w-1.5 animate-pulse rounded-full bg-primary"></span>
                        <span class="text-xs font-medium text-primary">SaaS para Bares &amp; Restaurantes</span>
                    </div>

                    <h1 class="font-heading text-4xl font-bold leading-tight tracking-tight text-white sm:text-5xl lg:text-6xl">
                        Gerencie seu bar ou restaurante com
                        <span class="bg-gradient-to-r from-primary to-accent bg-clip-text text-transparent"> inteligência</span>
                    </h1>

                    <p class="mt-6 text-lg leading-relaxed text-white/70">
                        Cardápio digital, pedidos em tempo real, KDS para cozinha, controle de pagamentos e muito mais — tudo em uma plataforma moderna e fácil de usar.
                    </p>

                    <div class="mt-10 flex flex-col items-center justify-center gap-4 sm:flex-row">
                        <template v-if="canRegister">
                            <Link
                                :href="route('register')"
                                class="inline-flex w-full items-center justify-center rounded-lg bg-primary px-8 py-3.5 text-base font-semibold text-white shadow-ocean transition-all hover:bg-primary-hover hover:shadow-lg sm:w-auto"
                            >
                                Começar grátis agora
                                <svg class="ml-2 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M13 7l5 5m0 0l-5 5m5-5H6" />
                                </svg>
                            </Link>
                        </template>
                        <a
                            href="#funcionalidades"
                            class="inline-flex w-full items-center justify-center rounded-lg border border-white/20 bg-white/5 px-8 py-3.5 text-base font-semibold text-white transition-all hover:bg-white/10 sm:w-auto"
                        >
                            Ver funcionalidades
                        </a>
                    </div>

                    <p class="mt-4 text-sm text-white/40">Sem cartão de crédito · Configuração em minutos · Cancele quando quiser</p>
                </div>

                <!-- Hero visual — dashboard mockup -->
                <div class="relative mx-auto mt-16 max-w-4xl">
                    <div class="overflow-hidden rounded-xl border border-white/10 bg-surface shadow-ocean">
                        <!-- Mock browser bar -->
                        <div class="flex items-center gap-2 border-b border-border bg-muted px-4 py-3">
                            <div class="h-3 w-3 rounded-full bg-red-400/70"></div>
                            <div class="h-3 w-3 rounded-full bg-yellow-400/70"></div>
                            <div class="h-3 w-3 rounded-full bg-green-400/70"></div>
                            <div class="mx-auto flex h-6 w-64 items-center rounded-md bg-border px-3">
                                <span class="text-xs text-muted-foreground">app.neurabar.com/dashboard</span>
                            </div>
                        </div>
                        <!-- Mock dashboard content -->
                        <div class="grid grid-cols-3 gap-4 p-6 sm:grid-cols-4">
                            <!-- Sidebar -->
                            <div class="hidden flex-col gap-2 sm:flex">
                                <div class="flex items-center gap-2 rounded-lg bg-primary/10 px-3 py-2">
                                    <div class="h-2 w-2 rounded-full bg-primary"></div>
                                    <div class="h-2 w-16 rounded bg-primary/40"></div>
                                </div>
                                <div v-for="i in 5" :key="i" class="flex items-center gap-2 rounded-lg px-3 py-2">
                                    <div class="h-2 w-2 rounded-full bg-muted-foreground/30"></div>
                                    <div class="h-2 rounded bg-muted-foreground/20" :class="['w-20', 'w-16', 'w-24', 'w-14', 'w-20'][i-1]"></div>
                                </div>
                            </div>
                            <!-- Main content area -->
                            <div class="col-span-3 flex flex-col gap-4">
                                <!-- Stats row -->
                                <div class="grid grid-cols-3 gap-3">
                                    <div v-for="(stat, i) in [{label:'Pedidos hoje', value:'127', color:'text-primary'},{label:'Faturamento', value:'R$ 8.840', color:'text-accent'},{label:'Mesas abertas', value:'12', color:'text-warm-gold'}]" :key="i" class="rounded-lg border border-border bg-surface p-3">
                                        <div class="text-xs text-muted-foreground">{{ stat.label }}</div>
                                        <div class="mt-1 font-heading text-lg font-bold" :class="stat.color">{{ stat.value }}</div>
                                    </div>
                                </div>
                                <!-- Orders list skeleton -->
                                <div class="rounded-lg border border-border bg-surface p-4">
                                    <div class="mb-3 h-3 w-32 rounded bg-muted-foreground/20"></div>
                                    <div v-for="j in 4" :key="j" class="flex items-center gap-3 py-2">
                                        <div class="h-2 w-2 rounded-full" :class="['bg-primary', 'bg-green-400', 'bg-warm-gold', 'bg-muted-foreground/30'][j-1]"></div>
                                        <div class="h-2 flex-1 rounded bg-muted-foreground/15"></div>
                                        <div class="h-2 w-12 rounded bg-muted-foreground/15"></div>
                                        <div class="h-5 w-16 rounded bg-muted px-2 py-0.5 text-xs"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- Glow effect below mockup -->
                    <div class="pointer-events-none absolute -bottom-10 left-1/2 h-20 w-2/3 -translate-x-1/2 rounded-full bg-primary/20 blur-2xl"></div>
                </div>
            </div>
        </section>

        <!-- ==================== SOCIAL PROOF ==================== -->
        <section class="border-b border-border bg-surface py-10">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <p class="text-center text-sm font-medium text-muted-foreground">Confiado por estabelecimentos de todo o Brasil</p>
                <div class="mt-6 flex flex-wrap items-center justify-center gap-8 opacity-50 grayscale">
                    <div v-for="brand in ['Bar do Zé', 'Restaurante Bella Vista', 'Choperia Aurora', 'Pizza &amp; Cia', 'Boteco Central']" :key="brand" class="font-heading text-sm font-bold text-foreground">
                        {{ brand }}
                    </div>
                </div>
            </div>
        </section>

        <!-- ==================== FEATURES ==================== -->
        <section id="funcionalidades" class="py-20 lg:py-28">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="mx-auto max-w-2xl text-center">
                    <span class="text-sm font-semibold uppercase tracking-widest text-primary">Funcionalidades</span>
                    <h2 class="mt-2 font-heading text-3xl font-bold tracking-tight text-ocean-deep sm:text-4xl">
                        Tudo que seu estabelecimento precisa
                    </h2>
                    <p class="mt-4 text-base text-muted-foreground">
                        Uma plataforma completa, do cardápio ao fechamento. Sem integrações complicadas, sem mensalidades surpresa.
                    </p>
                </div>

                <div class="mt-16 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                    <div
                        v-for="feature in features"
                        :key="feature.title"
                        class="group relative flex flex-col gap-4 rounded-xl border border-border bg-surface p-6 shadow-card transition-all hover:-translate-y-1 hover:border-primary/30 hover:shadow-ocean"
                    >
                        <!-- Icon wrapper -->
                        <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-primary/10 text-primary transition-colors group-hover:bg-primary group-hover:text-white">
                            <!-- Menu icon -->
                            <template v-if="feature.icon === 'menu'">
                                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
                                </svg>
                            </template>
                            <!-- Orders icon -->
                            <template v-else-if="feature.icon === 'orders'">
                                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                                </svg>
                            </template>
                            <!-- KDS icon -->
                            <template v-else-if="feature.icon === 'kds'">
                                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                </svg>
                            </template>
                            <!-- Payment icon -->
                            <template v-else-if="feature.icon === 'payment'">
                                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                                </svg>
                            </template>
                            <!-- QR Code icon -->
                            <template v-else-if="feature.icon === 'qrcode'">
                                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12v.01M12 4h.01M4 4h4v4H4V4zm12 0h4v4h-4V4zM4 16h4v4H4v-4z" />
                                </svg>
                            </template>
                            <!-- Corporation icon -->
                            <template v-else-if="feature.icon === 'corporation'">
                                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                </svg>
                            </template>
                        </div>

                        <div>
                            <h3 class="font-heading text-base font-semibold text-ocean-deep">{{ feature.title }}</h3>
                            <p class="mt-2 text-sm leading-relaxed text-muted-foreground">{{ feature.description }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- ==================== HOW IT WORKS ==================== -->
        <section id="como-funciona" class="bg-ocean-deep py-20 lg:py-28">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="mx-auto max-w-2xl text-center">
                    <span class="text-sm font-semibold uppercase tracking-widest text-primary">Como funciona</span>
                    <h2 class="mt-2 font-heading text-3xl font-bold tracking-tight text-white sm:text-4xl">
                        Do cadastro ao primeiro atendimento em minutos
                    </h2>
                </div>

                <div class="mt-16 grid gap-8 md:grid-cols-3">
                    <div
                        v-for="(step, index) in steps"
                        :key="step.number"
                        class="relative flex flex-col items-center text-center"
                    >
                        <!-- Connector line -->
                        <div
                            v-if="index < steps.length - 1"
                            class="absolute left-1/2 top-8 hidden h-0.5 w-full bg-gradient-to-r from-primary/50 to-transparent md:block"
                            style="transform: translateX(50%);"
                        ></div>

                        <div class="relative z-10 flex h-16 w-16 items-center justify-center rounded-2xl border border-primary/30 bg-primary/10">
                            <span class="font-heading text-xl font-bold text-primary">{{ step.number }}</span>
                        </div>
                        <h3 class="mt-4 font-heading text-lg font-semibold text-white">{{ step.title }}</h3>
                        <p class="mt-2 text-sm leading-relaxed text-white/60">{{ step.description }}</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- ==================== PRICING ==================== -->
        <section id="planos" class="py-20 lg:py-28">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="mx-auto max-w-2xl text-center">
                    <span class="text-sm font-semibold uppercase tracking-widest text-primary">Planos</span>
                    <h2 class="mt-2 font-heading text-3xl font-bold tracking-tight text-ocean-deep sm:text-4xl">
                        Preço simples e transparente
                    </h2>
                    <p class="mt-4 text-base text-muted-foreground">
                        Comece grátis por 14 dias. Sem taxa de setup, sem surpresas na fatura.
                    </p>
                </div>

                <div class="mt-16 grid gap-6 lg:grid-cols-3">
                    <div
                        v-for="plan in plans"
                        :key="plan.name"
                        :class="[
                            'relative flex flex-col rounded-xl border p-8 transition-all',
                            plan.highlighted
                                ? 'border-primary bg-ocean-deep shadow-ocean ring-2 ring-primary/50'
                                : 'border-border bg-surface shadow-card hover:border-primary/30 hover:shadow-ocean',
                        ]"
                    >
                        <div v-if="plan.highlighted" class="absolute -top-3 left-1/2 -translate-x-1/2">
                            <span class="rounded-full bg-primary px-4 py-1 text-xs font-semibold text-white shadow-sm">Mais popular</span>
                        </div>

                        <div>
                            <h3 :class="['font-heading text-lg font-bold', plan.highlighted ? 'text-white' : 'text-ocean-deep']">
                                {{ plan.name }}
                            </h3>
                            <p :class="['mt-1 text-sm', plan.highlighted ? 'text-white/60' : 'text-muted-foreground']">
                                {{ plan.description }}
                            </p>
                        </div>

                        <div class="mt-6 flex items-end gap-1">
                            <span :class="['font-heading text-4xl font-bold', plan.highlighted ? 'text-white' : 'text-ocean-deep']">
                                {{ plan.price }}
                            </span>
                            <span v-if="plan.period" :class="['mb-1 text-sm', plan.highlighted ? 'text-white/50' : 'text-muted-foreground']">
                                {{ plan.period }}
                            </span>
                        </div>

                        <ul class="mt-8 flex flex-col gap-3">
                            <li v-for="feat in plan.features" :key="feat" class="flex items-center gap-2.5">
                                <svg class="h-4 w-4 shrink-0 text-primary" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                </svg>
                                <span :class="['text-sm', plan.highlighted ? 'text-white/80' : 'text-muted-foreground']">{{ feat }}</span>
                            </li>
                        </ul>

                        <div class="mt-8">
                            <Link
                                v-if="canRegister"
                                :href="route('register')"
                                :class="[
                                    'block w-full rounded-lg py-3 text-center text-sm font-semibold transition-all',
                                    plan.highlighted
                                        ? 'bg-primary text-white hover:bg-primary-hover'
                                        : 'border border-border bg-muted text-ocean-deep hover:border-primary/50 hover:bg-primary/5',
                                ]"
                            >
                                {{ plan.cta }}
                            </Link>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- ==================== TESTIMONIALS ==================== -->
        <section class="bg-surface py-20 lg:py-24">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="mx-auto max-w-2xl text-center">
                    <h2 class="font-heading text-3xl font-bold tracking-tight text-ocean-deep sm:text-4xl">
                        O que nossos clientes dizem
                    </h2>
                </div>
                <div class="mt-16 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                    <div
                        v-for="testimonial in [
                            {quote: 'Depois do NeuraBar, nossa cozinha parou de perder pedidos. O KDS mudou tudo.', author: 'Carlos M.', role: 'Dono, Choperia Aurora'},
                            {quote: 'Em 20 minutos configurei o cardápio completo. A equipe adorou e os clientes também.', author: 'Ana Paula S.', role: 'Gerente, Restaurante Bella Vista'},
                            {quote: 'O QR code na mesa reduziu erros nos pedidos e melhorou muito a experiência do cliente.', author: 'Rafael T.', role: 'Proprietário, Bar do Zé'},
                        ]"
                        :key="testimonial.author"
                        class="flex flex-col gap-4 rounded-xl border border-border bg-muted p-6"
                    >
                        <div class="flex gap-1">
                            <svg v-for="s in 5" :key="s" class="h-4 w-4 text-warm-gold" viewBox="0 0 20 20" fill="currentColor">
                                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                            </svg>
                        </div>
                        <p class="text-sm leading-relaxed text-muted-foreground">"{{ testimonial.quote }}"</p>
                        <div class="mt-auto">
                            <p class="text-sm font-semibold text-ocean-deep">{{ testimonial.author }}</p>
                            <p class="text-xs text-muted-foreground">{{ testimonial.role }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- ==================== FINAL CTA ==================== -->
        <section class="bg-gradient-to-br from-ocean-deep via-ocean-deep to-primary/30 py-20 lg:py-28">
            <div class="mx-auto max-w-3xl px-4 text-center sm:px-6 lg:px-8">
                <h2 class="font-heading text-3xl font-bold tracking-tight text-white sm:text-4xl">
                    Pronto para modernizar seu estabelecimento?
                </h2>
                <p class="mt-4 text-lg text-white/60">
                    Junte-se a centenas de bares e restaurantes que já gerenciam seus negócios com o NeuraBar.
                </p>
                <div class="mt-10 flex flex-col items-center gap-4 sm:flex-row sm:justify-center">
                    <template v-if="canRegister">
                        <Link
                            :href="route('register')"
                            class="inline-flex w-full items-center justify-center rounded-lg bg-primary px-8 py-4 text-base font-semibold text-white shadow-ocean transition-all hover:bg-primary-hover sm:w-auto"
                        >
                            Criar conta gratuitamente
                            <svg class="ml-2 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13 7l5 5m0 0l-5 5m5-5H6" />
                            </svg>
                        </Link>
                    </template>
                    <p class="text-sm text-white/40">14 dias grátis · Sem cartão de crédito</p>
                </div>
            </div>
        </section>

        <!-- ==================== FOOTER ==================== -->
        <footer class="border-t border-border bg-ocean-deep py-12">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="grid gap-8 sm:grid-cols-2 lg:grid-cols-4">
                    <!-- Brand -->
                    <div class="lg:col-span-2">
                        <div class="flex items-center gap-2.5">
                            <ApplicationLogo class="h-8 w-8" />
                            <span class="font-heading text-xl font-bold text-white">NeuraBar</span>
                        </div>
                        <p class="mt-3 max-w-xs text-sm text-white/50">
                            Plataforma SaaS de gestão inteligente para bares e restaurantes de pequeno e médio porte.
                        </p>
                    </div>

                    <!-- Product -->
                    <div>
                        <h4 class="text-sm font-semibold text-white">Produto</h4>
                        <ul class="mt-4 flex flex-col gap-2">
                            <li v-for="link in ['Funcionalidades', 'Planos', 'Como funciona']" :key="link">
                                <a href="#" class="text-sm text-white/50 transition-colors hover:text-white">{{ link }}</a>
                            </li>
                        </ul>
                    </div>

                    <!-- Account -->
                    <div>
                        <h4 class="text-sm font-semibold text-white">Conta</h4>
                        <ul class="mt-4 flex flex-col gap-2">
                            <li>
                                <Link v-if="canLogin" :href="route('login')" class="text-sm text-white/50 transition-colors hover:text-white">
                                    Entrar
                                </Link>
                            </li>
                            <li>
                                <Link v-if="canRegister" :href="route('register')" class="text-sm text-white/50 transition-colors hover:text-white">
                                    Criar conta
                                </Link>
                            </li>
                        </ul>
                    </div>
                </div>

                <div class="mt-10 border-t border-white/10 pt-8 text-center">
                    <p class="text-xs text-white/30">
                        &copy; {{ new Date().getFullYear() }} NeuraBar. Todos os direitos reservados.
                    </p>
                </div>
            </div>
        </footer>
    </div>
</template>
