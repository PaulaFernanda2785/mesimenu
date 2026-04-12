<div class="topbar">
    <div>
        <h1>Pagamentos</h1>
        <p>Registros de pagamentos da empresa autenticada.</p>
    </div>
    <a class="btn" href="<?= htmlspecialchars(base_url('/admin/payments/create')) ?>">Registrar pagamento</a>
</div>

<div class="card">
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Pedido</th>
                <th>Metodo</th>
                <th>Status</th>
                <th>Valor</th>
                <th>Referencia</th>
                <th>Recebido por</th>
                <th>Pago em</th>
            </tr>
        </thead>
        <tbody>
        <?php if (empty($payments)): ?>
            <tr><td colspan="8">Nenhum pagamento encontrado.</td></tr>
        <?php else: ?>
            <?php foreach ($payments as $payment): ?>
                <tr>
                    <td><?= (int) $payment['id'] ?></td>
                    <td>
                        <?php if ($payment['order_id'] !== null): ?>
                            <?= htmlspecialchars((string) ($payment['order_number'] ?? ('#' . $payment['order_id']))) ?>
                        <?php else: ?>
                            -
                        <?php endif; ?>
                    </td>
                    <td><?= htmlspecialchars((string) ($payment['payment_method_name'] ?? '-')) ?></td>
                    <td><span class="badge"><?= htmlspecialchars(status_label('payment_status', $payment['status'] ?? null)) ?></span></td>
                    <td>R$ <?= number_format((float) $payment['amount'], 2, ',', '.') ?></td>
                    <td><?= htmlspecialchars((string) ($payment['transaction_reference'] ?? '-')) ?></td>
                    <td><?= htmlspecialchars((string) ($payment['received_by_user_name'] ?? '-')) ?></td>
                    <td><?= htmlspecialchars((string) ($payment['paid_at'] ?? '-')) ?></td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
        </tbody>
    </table>
</div>
