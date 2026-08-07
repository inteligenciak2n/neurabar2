<template>
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 500 500" width="500" height="500">
  <defs>
    <linearGradient id="bgGrad" x1="0%" y1="0%" x2="100%" y2="100%">
      <stop offset="0%" style="stop-color:#293b4f;stop-opacity:1" />
      <stop offset="100%" style="stop-color:#1e2a3a;stop-opacity:1" />
    </linearGradient>
    <linearGradient id="neonGrad" x1="0%" y1="0%" x2="100%" y2="100%">
      <stop offset="0%" style="stop-color:#c4b39b;stop-opacity:1" />
      <stop offset="100%" style="stop-color:#a28665;stop-opacity:1" />
    </linearGradient>
    <linearGradient id="nGrad" x1="0%" y1="0%" x2="100%" y2="100%">
      <stop offset="0%" style="stop-color:#FFFFFF;stop-opacity:1" />
      <stop offset="100%" style="stop-color:#eeeeee;stop-opacity:1" />
    </linearGradient>
    <filter id="glow">
      <feGaussianBlur stdDeviation="8" result="coloredBlur"/>
      <feMerge>
        <feMergeNode in="coloredBlur"/>
        <feMergeNode in="SourceGraphic"/>
      </feMerge>
    </filter>
    <filter id="shadow">
      <feDropShadow dx="0" dy="4" stdDeviation="8" flood-color="#000" flood-opacity="0.3"/>
    </filter>
  </defs>

  <!-- Fundo quadrado com cantos arredondados -->
  <rect x="25" y="25" width="450" height="450" rx="80" ry="80" fill="url(#bgGrad)" filter="url(#shadow)"/>

  <!-- Borda neon -->
  <rect x="25" y="25" width="450" height="450" rx="80" ry="80" fill="none" stroke="url(#neonGrad)" stroke-width="4" filter="url(#glow)" opacity="0.8"/>

  <!-- Círculo de fundo para a letra N -->
  <circle cx="250" cy="250" r="120" fill="none" stroke="url(#neonGrad)" stroke-width="2" opacity="0.3"/>

  <!-- Traços decorativos de neurônios/redes -->
  <g opacity="0.15" stroke="#c4b39b" stroke-width="1.5" fill="none">
    <circle cx="250" cy="250" r="160"/>
    <circle cx="250" cy="250" r="180"/>
    <line x1="250" y1="90" x2="250" y2="410"/>
    <line x1="90" y1="250" x2="410" y2="250"/>
    <circle cx="250" cy="160" r="3" fill="#c4b39b"/>
    <circle cx="250" cy="340" r="3" fill="#c4b39b"/>
    <circle cx="160" cy="250" r="3" fill="#c4b39b"/>
    <circle cx="340" cy="250" r="3" fill="#c4b39b"/>
  </g>

  <!-- Letra N estilizada -->
  <g filter="url(#glow)">
    <path d="
      M 170 310
      L 170 170
      L 220 170
      L 280 270
      L 280 170
      L 330 170
      L 330 330
      L 280 330
      L 220 230
      L 220 330
      Z
    " fill="url(#nGrad)" stroke="none" opacity="0.95"/>
  </g>

  <!-- Pequeno detalhe de conexão tipo "nó neural" -->
  <g opacity="0.4">
    <circle cx="170" cy="170" r="5" fill="#c4b39b"/>
    <circle cx="330" cy="170" r="5" fill="#c4b39b"/>
    <circle cx="170" cy="330" r="5" fill="#c4b39b"/>
    <circle cx="330" cy="330" r="5" fill="#c4b39b"/>
    <circle cx="220" cy="230" r="4" fill="#c4b39b" opacity="0.6"/>
    <circle cx="280" cy="270" r="4" fill="#c4b39b" opacity="0.6"/>
  </g>

  <!-- Pontos de conexão nos cantos do N -->
  <g opacity="0.6">
    <circle cx="190" cy="180" r="2.5" fill="#a28665"/>
    <circle cx="220" cy="200" r="2" fill="#a28665"/>
    <circle cx="250" cy="250" r="3" fill="#c4b39b"/>
    <circle cx="310" cy="300" r="2" fill="#a28665"/>
    <circle cx="280" cy="310" r="2.5" fill="#a28665"/>
  </g>

</svg>
</template>
