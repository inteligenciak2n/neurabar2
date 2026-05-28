<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Convite para colaborar</title>
    <style>
        body { font-family: sans-serif; background: #f4f4f5; margin: 0; padding: 40px 20px; }
        .card { background: #fff; max-width: 500px; margin: 0 auto; padding: 40px; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,.08); }
        .btn { display: inline-block; background: #f59e0b; color: #000; padding: 14px 28px; border-radius: 8px; text-decoration: none; font-weight: 600; margin-top: 24px; }
        .footer { color: #71717a; font-size: 13px; margin-top: 32px; }
    </style>
</head>
<body>
    <div class="card">
        <h2>Você foi convidado!</h2>
        <p>Você recebeu um convite para colaborar em <strong>{{ $venueName }}</strong> com o papel de <strong>{{ $role }}</strong>.</p>
        <p>Clique no botão abaixo para aceitar o convite. Este link expira em <strong>{{ $expiresAt }}</strong>.</p>
        <a href="{{ $acceptUrl }}" class="btn">Aceitar convite</a>
        <div class="footer">
            <p>Se você não esperava este convite, pode ignorar este e-mail com segurança.</p>
            <p>Ou copie e cole o link no navegador:<br>{{ $acceptUrl }}</p>
        </div>
    </div>
</body>
</html>
