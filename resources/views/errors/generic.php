<h1>Erro <?= htmlspecialchars((string)($status ?? 500)) ?></h1>
<p><?= htmlspecialchars($message ?? 'Ocorreu um erro.') ?></p>
<p><a href="/login">Voltar</a></p>
