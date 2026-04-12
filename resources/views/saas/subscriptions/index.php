<div class="topbar">
    <div>
        <h1>Assinaturas</h1>
        <p class="muted">Histórico e estado atual das assinaturas por empresa.</p>
    </div>
</div>

<div class="card">
    <table>
        <thead>
            <tr>
                <th>Empresa</th>
                <th>Plano</th>
                <th>Ciclo</th>
                <th>Valor</th>
                <th>Status</th>
                <th>Início</th>
                <th>Fim</th>
            </tr>
        </thead>
        <tbody>
        <?php if (empty($subscriptions)): ?>
            <tr><td colspan="7">Nenhuma assinatura encontrada.</td></tr>
        <?php else: ?>
            <?php foreach ($subscriptions as $subscription): ?>
                <tr>
                    <td>
                        <strong><?= htmlspecialchars((string)$subscription['company_name']) ?></strong><br>
                        <span class="muted"><?= htmlspecialchars((string)$subscription['company_slug']) ?></span>
                    </td>
                    <td><?= htmlspecialchars((string)$subscription['plan_name']) ?></td>
                    <td><?= htmlspecialchars(status_label('billing_cycle', $subscription['billing_cycle'] ?? null)) ?></td>
                    <td>R$ <?= number_format((float)$subscription['amount'], 2, ',', '.') ?></td>
                    <td><span class="badge"><?= htmlspecialchars(status_label('subscription_status', $subscription['status'] ?? null)) ?></span></td>
                    <td><?= htmlspecialchars((string)$subscription['starts_at']) ?></td>
                    <td><?= htmlspecialchars((string)($subscription['ends_at'] ?? '-')) ?></td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
        </tbody>
    </table>
</div>
