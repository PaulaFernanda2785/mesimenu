<div class="topbar">
    <div>
        <h1>Cobrancas de Assinaturas</h1>
        <p class="muted">Gestao financeira das assinaturas SaaS.</p>
    </div>
    <a class="btn" href="<?= htmlspecialchars(base_url('/saas/subscription-payments/create')) ?>">Nova cobranca</a>
</div>

<div class="grid" style="margin-bottom:16px">
    <div class="kpi">
        <span>Total de cobrancas</span>
        <strong><?= (int) ($summary['total_charges'] ?? 0) ?></strong>
    </div>
    <div class="kpi">
        <span>Pendentes</span>
        <strong><?= (int) ($summary['pending_charges'] ?? 0) ?></strong>
    </div>
    <div class="kpi">
        <span>Pagas</span>
        <strong><?= (int) ($summary['paid_charges'] ?? 0) ?></strong>
    </div>
    <div class="kpi">
        <span>Receita recebida</span>
        <strong>R$ <?= number_format((float) ($summary['total_paid_amount'] ?? 0), 2, ',', '.') ?></strong>
    </div>
</div>

<div class="card">
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Empresa</th>
                <th>Assinatura</th>
                <th>Referencia</th>
                <th>Vencimento</th>
                <th>Valor</th>
                <th>Status</th>
                <th>Acoes</th>
            </tr>
        </thead>
        <tbody>
        <?php if (empty($subscriptionPayments)): ?>
            <tr><td colspan="8">Nenhuma cobranca encontrada.</td></tr>
        <?php else: ?>
            <?php foreach ($subscriptionPayments as $charge): ?>
                <tr>
                    <td><?= (int) $charge['id'] ?></td>
                    <td>
                        <strong><?= htmlspecialchars((string) $charge['company_name']) ?></strong><br>
                        <span class="muted"><?= htmlspecialchars((string) $charge['company_slug']) ?></span>
                    </td>
                    <td>
                        <?= htmlspecialchars((string) $charge['plan_name']) ?><br>
                        <span class="muted"><?= htmlspecialchars(status_label('billing_cycle', $charge['billing_cycle'] ?? null)) ?></span>
                    </td>
                    <td>
                        <?= str_pad((string) (int) $charge['reference_month'], 2, '0', STR_PAD_LEFT) ?>/<?= (int) $charge['reference_year'] ?>
                    </td>
                    <td><?= htmlspecialchars((string) $charge['due_date']) ?></td>
                    <td>R$ <?= number_format((float) $charge['amount'], 2, ',', '.') ?></td>
                    <td><span class="badge"><?= htmlspecialchars(status_label('subscription_payment_status', $charge['status'] ?? null)) ?></span></td>
                    <td>
                        <?php if ((string) $charge['status'] !== 'pago' && (string) $charge['status'] !== 'cancelado'): ?>
                            <form method="POST" action="<?= htmlspecialchars(base_url('/saas/subscription-payments/mark-paid')) ?>" style="margin-bottom:6px">
                                <input type="hidden" name="subscription_payment_id" value="<?= (int) $charge['id'] ?>">
                                <input type="text" name="payment_method" placeholder="Metodo (opcional)">
                                <input type="text" name="transaction_reference" placeholder="Referencia (opcional)" style="margin-top:6px">
                                <button class="btn secondary" type="submit" style="margin-top:6px">Marcar pago</button>
                            </form>

                            <form method="POST" action="<?= htmlspecialchars(base_url('/saas/subscription-payments/mark-overdue')) ?>" style="display:inline-block">
                                <input type="hidden" name="subscription_payment_id" value="<?= (int) $charge['id'] ?>">
                                <button class="btn secondary" type="submit">Marcar vencido</button>
                            </form>

                            <form method="POST" action="<?= htmlspecialchars(base_url('/saas/subscription-payments/cancel')) ?>" style="display:inline-block">
                                <input type="hidden" name="subscription_payment_id" value="<?= (int) $charge['id'] ?>">
                                <button class="btn secondary" type="submit">Cancelar</button>
                            </form>
                        <?php else: ?>
                            -
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
        </tbody>
    </table>
</div>
