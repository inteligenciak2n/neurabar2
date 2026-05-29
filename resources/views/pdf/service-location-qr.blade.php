<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8" />
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: DejaVu Sans, sans-serif;
            background: #ffffff;
            color: #1a2e3b;
        }

        .page {
            width: 100%;
            max-width: 400px;
            margin: 40px auto;
            padding: 32px;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            text-align: center;
        }

        .logo {
            width: 80px;
            height: 80px;
            object-fit: contain;
            border-radius: 8px;
            margin-bottom: 16px;
        }

        .venue-name {
            font-size: 22px;
            font-weight: 700;
            color: #1a2e3b;
            margin-bottom: 4px;
        }

        .location-name {
            font-size: 16px;
            color: #64748b;
            margin-bottom: 4px;
        }

        .location-type {
            display: inline-block;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #0ea5e9;
            background: #e0f2fe;
            padding: 2px 10px;
            border-radius: 999px;
            margin-bottom: 24px;
        }

        .qr-wrapper {
            margin: 0 auto 20px;
            width: 200px;
            height: 200px;
        }

        .qr-wrapper img {
            width: 200px;
            height: 200px;
        }

        .instruction {
            font-size: 13px;
            color: #475569;
            line-height: 1.6;
            margin-bottom: 16px;
        }

        .url {
            font-size: 10px;
            color: #94a3b8;
            word-break: break-all;
        }

        .divider {
            border: none;
            border-top: 1px solid #e2e8f0;
            margin: 20px 0;
        }

        .powered {
            font-size: 10px;
            color: #cbd5e1;
        }
    </style>
</head>
<body>
    <div class="page">
        @if ($logoBase64)
            <img class="logo" src="{{ $logoBase64 }}" alt="{{ $venueName }}" />
        @endif

        <div class="venue-name">{{ $venueName }}</div>
        <div class="location-name">{{ $locationName }}</div>
        <div class="location-type">{{ $locationType }}</div>

        <div class="qr-wrapper">
            <img src="{{ $qrBase64 }}" alt="QR Code" />
        </div>

        <p class="instruction">
            Escaneie o código acima com a câmera do seu celular para chamar o atendente, ver o cardápio ou acompanhar seu pedido.
        </p>

        <hr class="divider" />

        <p class="url">{{ $hubUrl }}</p>

        <p class="powered" style="margin-top: 12px;">Powered by NeuraBar</p>
    </div>
</body>
</html>
