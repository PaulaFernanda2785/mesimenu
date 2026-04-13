<?php
$panelSummary = is_array($panelSummary ?? null) ? $panelSummary : [];
$ordersByTable = is_array($ordersByTable ?? null) ? $ordersByTable : [];
?>

<div class="topbar">
    <div>
        <h1>Painel de Pedidos por Mesa</h1>
        <p>Controle operacional de pedidos ativos para atendimento, cozinha e entrega.</p>
    </div>
    <div style="display:flex;gap:8px;flex-wrap:wrap">
        <a class="btn secondary" href="<?= htmlspecialchars(base_url('/admin/kitchen')) ?>">Fila de cozinha</a>
        <a class="btn" href="<?= htmlspecialchars(base_url('/admin/orders/create')) ?>">Novo pedido</a>
    </div>
</div>

<div class="grid" style="grid-template-columns:repeat(4,minmax(180px,1fr));margin-bottom:16px">
    <div class="card">
        <strong><?= (int) ($panelSummary['active_orders'] ?? 0) ?></strong>
        <p style="margin:6px 0 0">Pedidos ativos</p>
    </div>
    <div class="card">
        <strong><?= (int) ($panelSummary['tables_in_service'] ?? 0) ?></strong>
        <p style="margin:6px 0 0">Mesas em atendimento</p>
    </div>
    <div class="card">
        <strong><?= (int) ($panelSummary['items_total'] ?? 0) ?></strong>
        <p style="margin:6px 0 0">Itens em andamento</p>
    </div>
    <div class="card">
        <strong>R$ <?= number_format((float) ($panelSummary['amount_total'] ?? 0), 2, ',', '.') ?></strong>
        <p style="margin:6px 0 0">Valor total em aberto</p>
    </div>
</div>

<div class="card" style="margin-bottom:16px">
    <div style="display:flex;gap:8px;flex-wrap:wrap">
        <span class="badge <?= htmlspecialchars(status_badge_class('order_status', 'pending')) ?>">Pendentes: <?= (int) ($panelSummary['pending'] ?? 0) ?></span>
        <span class="badge <?= htmlspecialchars(status_badge_class('order_status', 'received')) ?>">Recebidos: <?= (int) ($panelSummary['received'] ?? 0) ?></span>
        <span class="badge <?= htmlspecialchars(status_badge_class('order_status', 'preparing')) ?>">Em preparo: <?= (int) ($panelSummary['preparing'] ?? 0) ?></span>
        <span class="badge <?= htmlspecialchars(status_badge_class('order_status', 'ready')) ?>">Prontos: <?= (int) ($panelSummary['ready'] ?? 0) ?></span>
        <span class="badge <?= htmlspecialchars(status_badge_class('order_status', 'delivered')) ?>">Entregues: <?= (int) ($panelSummary['delivered'] ?? 0) ?></span>
        <span class="badge <?= htmlspecialchars(status_badge_class('order_operational_flag', 'paid_waiting_production')) ?>">
            Pagos aguardando producao: <?= (int) ($panelSummary['paid_waiting_production'] ?? 0) ?>
        </span>
    </div>
</div>

<?php if (empty($ordersByTable)): ?>
    <div class="card">
        Nenhum pedido ativo no momento.
    </div>
<?php else: ?>
    <?php foreach ($ordersByTable as $tablePanel): ?>
        <?php
        $orders = is_array($tablePanel['orders'] ?? null) ? $tablePanel['orders'] : [];
        ?>
        <div class="card" style="margin-bottom:16px">
            <div style="display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap">
                <div>
                    <h3 style="margin:0"><?= htmlspecialchars((string) ($tablePanel['label'] ?? 'Mesa')) ?></h3>
                    <p style="margin:6px 0 0;color:#64748b">
                        Pedidos: <?= (int) ($tablePanel['orders_count'] ?? 0) ?>
                        | Itens: <?= (int) ($tablePanel['items_total'] ?? 0) ?>
                        | Total: R$ <?= number_format((float) ($tablePanel['amount_total'] ?? 0), 2, ',', '.') ?>
                    </p>
                </div>
            </div>

            <table>
                <thead>
                    <tr>
                        <th>Pedido</th>
                        <th>Cliente</th>
                        <th>Itens</th>
                        <th>Status</th>
                        <th>Pagamento</th>
                        <th>Ultima mudanca</th>
                        <th>Acoes</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($orders as $order): ?>
                    <tr>
                        <td>
                            <strong><?= htmlspecialchars((string) ($order['order_number'] ?? '-')) ?></strong><br>
                            <small><?= htmlspecialchars((string) ($order['created_at'] ?? '-')) ?></small>
                            <?php if ($order['command_id'] !== null): ?>
                                <br><small>Comanda ativa</small>
                            <?php endif; ?>
                        </td>
                        <td><?= htmlspecialchars((string) ($order['customer_name'] ?? '-')) ?></td>
                        <td><?= (int) ($order['items_count'] ?? 0) ?></td>
                        <td>
                            <span class="badge <?= htmlspecialchars(status_badge_class('order_status', $order['status'] ?? null)) ?>">
                                <?= htmlspecialchars(status_label('order_status', $order['status'] ?? null)) ?>
                            </span>
                        </td>
                        <td>
                            <span class="badge <?= htmlspecialchars(status_badge_class('order_payment_status', $order['payment_status'] ?? null)) ?>">
                                <?= htmlspecialchars(status_label('order_payment_status', $order['payment_status'] ?? null)) ?>
                            </span>
                            <?php if (!empty($order['is_paid_waiting_production'])): ?>
                                <br>
                                <span class="badge <?= htmlspecialchars(status_badge_class('order_operational_flag', 'paid_waiting_production')) ?>" style="margin-top:4px">
                                    <?= htmlspecialchars(status_label('order_operational_flag', 'paid_waiting_production')) ?>
                                </span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?= htmlspecialchars((string) ($order['latest_status_changed_at'] ?? '-')) ?>
                            <?php if (!empty($order['latest_status_changed_by'])): ?>
                                <br><small>por <?= htmlspecialchars((string) $order['latest_status_changed_by']) ?></small>
                            <?php endif; ?>
                        </td>
                        <td>
                            <a class="btn secondary" href="<?= htmlspecialchars(base_url('/admin/orders/print-ticket?order_id=' . (int) $order['id'])) ?>" style="margin-bottom:6px">
                                Imprimir ticket
                            </a>

                            <?php if (!empty($canSendKitchen) && !empty($order['can_send_kitchen'])): ?>
                                <form method="POST" action="<?= htmlspecialchars(base_url('/admin/orders/send-kitchen')) ?>" style="margin-bottom:6px">
                                    <input type="hidden" name="order_id" value="<?= (int) $order['id'] ?>">
                                    <button class="btn secondary" type="submit">Enviar para cozinha</button>
                                </form>
                            <?php endif; ?>

                            <?php
                            $nextStatuses = $order['next_statuses'] ?? [];
                            if (!is_array($nextStatuses)) {
                                $nextStatuses = [];
                            }
                            if (empty($canCancelOrder)) {
                                $nextStatuses = array_values(array_filter(
                                    $nextStatuses,
                                    static fn (mixed $status): bool => (string) $status !== 'canceled'
                                ));
                            }
                            if (!empty($order['can_send_kitchen'])) {
                                $nextStatuses = array_values(array_filter(
                                    $nextStatuses,
                                    static fn (mixed $status): bool => (string) $status !== 'received'
                                ));
                            }
                            ?>

                            <?php if (!empty($canUpdateStatus) && !empty($nextStatuses)): ?>
                                <form method="POST" action="<?= htmlspecialchars(base_url('/admin/orders/status')) ?>">
                                    <input type="hidden" name="order_id" value="<?= (int) $order['id'] ?>">
                                    <select name="new_status" required>
                                        <option value="">Selecione</option>
                                        <?php foreach ($nextStatuses as $status): ?>
                                            <option value="<?= htmlspecialchars((string) $status) ?>">
                                                <?= htmlspecialchars(status_label('order_status', $status)) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <input name="status_notes" type="text" placeholder="Observacao (opcional)" style="margin-top:6px">
                                    <button class="btn secondary" type="submit" style="margin-top:6px">Atualizar status</button>
                                </form>
                            <?php elseif (empty($canSendKitchen) || empty($order['can_send_kitchen'])): ?>
                                -
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endforeach; ?>
<?php endif; ?>
