<div class="card">
    <h1><?= htmlspecialchars($title ?? 'Dashboard') ?></h1>
    <p>Usuário autenticado com sucesso.</p>

    <p><strong>Nome:</strong> <?= htmlspecialchars($user['name'] ?? '-') ?></p>
    <p><strong>E-mail:</strong> <?= htmlspecialchars($user['email'] ?? '-') ?></p>
    <p><strong>Perfil:</strong> <?= htmlspecialchars($user['role_name'] ?? '-') ?></p>
    <p><strong>Empresa ID:</strong> <?= htmlspecialchars((string)($user['company_id'] ?? 'N/A')) ?></p>

    <p>
        <a href="/admin/products">Produtos</a> |
        <a href="/admin/tables">Mesas</a> |
        <a href="/admin/commands">Comandas</a>
    </p>
</div>
