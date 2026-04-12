<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($title ?? 'Autenticação') ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body{font-family:Arial,sans-serif;background:#f4f6f8;margin:0;padding:0}
        .wrap{min-height:100vh;display:flex;align-items:center;justify-content:center;padding:24px}
        .card{background:#fff;width:100%;max-width:420px;border-radius:12px;box-shadow:0 8px 24px rgba(0,0,0,.08);padding:24px}
        h1{margin:0 0 8px;font-size:24px}
        p{color:#555}
        label{display:block;margin:12px 0 6px;font-weight:bold}
        input{width:100%;padding:12px;border:1px solid #d0d7de;border-radius:8px;box-sizing:border-box}
        button{margin-top:16px;width:100%;padding:12px;border:0;background:#1d4ed8;color:#fff;border-radius:8px;font-weight:bold;cursor:pointer}
        .error{background:#fee2e2;color:#991b1b;padding:10px;border-radius:8px;margin-top:12px}
    </style>
</head>
<body>
<div class="wrap">
    <div class="card">
        <?= $content ?>
    </div>
</div>
</body>
</html>
