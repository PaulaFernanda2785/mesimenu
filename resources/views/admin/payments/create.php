<div class="topbar">
    <div>
        <h1>Registrar Pagamento</h1>
        <p>Vincule um pagamento a um pedido da empresa autenticada.</p>
    </div>
    <a class="btn secondary" href="<?= htmlspecialchars(base_url('/admin/payments')) ?>">Voltar</a>
</div>

<?php if (empty($hasOpenCashRegister)): ?>
    <div class="card">
        <p>Nao ha caixa aberto no momento.</p>
        <p>Abra um caixa antes de registrar pagamentos.</p>
        <a class="btn" href="<?= htmlspecialchars(base_url('/admin/cash-registers')) ?>">Ir para Caixa</a>
    </div>
<?php else: ?>
    <div class="card">
        <form method="POST" action="<?= htmlspecialchars(base_url('/admin/payments/store')) ?>">
            <div class="field">
                <label for="order_id">Pedido</label>
                <select id="order_id" name="order_id" required>
                    <option value="">Selecione</option>
                    <?php foreach (($orders ?? []) as $order): ?>
                        <option value="<?= (int) $order['id'] ?>">
                            <?= htmlspecialchars((string) $order['order_number']) ?>
                            <?= $order['table_number'] !== null ? ' - Mesa ' . (int) $order['table_number'] : '' ?>
                            <?= !empty($order['customer_name']) ? ' - ' . htmlspecialchars((string) $order['customer_name']) : '' ?>
                            <?= ' - Saldo: R$ ' . number_format((float) ($order['remaining_amount'] ?? 0), 2, ',', '.') ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="grid two">
                <div class="field">
                    <label for="payment_method_id">Metodo de pagamento</label>
                    <select id="payment_method_id" name="payment_method_id" required>
                        <option value="">Selecione</option>
                        <?php foreach (($paymentMethods ?? []) as $method): ?>
                            <option value="<?= (int) $method['id'] ?>">
                                <?= htmlspecialchars((string) $method['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="field">
                    <label for="amount">Valor (R$)</label>
                    <input id="amount" name="amount" type="number" min="0.01" step="0.01" required>
                </div>
            </div>

            <div class="field">
                <label for="transaction_reference">Referencia da transacao</label>
                <input id="transaction_reference" name="transaction_reference" type="text" placeholder="Opcional">
            </div>

            <button class="btn" type="submit">Registrar pagamento</button>
        </form>
    </div>
<?php endif; ?>

