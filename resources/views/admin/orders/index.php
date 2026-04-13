<?php
$panelSummary = is_array($panelSummary ?? null) ? $panelSummary : [];
$ordersByTable = is_array($ordersByTable ?? null) ? $ordersByTable : [];
$canUpdateStatus = !empty($canUpdateStatus);
$canCancelOrder = !empty($canCancelOrder);
$canSendKitchen = !empty($canSendKitchen);

$operationalSummary = [
    'pending' => (int) ($panelSummary['pending'] ?? 0),
    'received' => (int) ($panelSummary['received'] ?? 0),
    'preparing' => (int) ($panelSummary['preparing'] ?? 0),
    'ready' => (int) ($panelSummary['ready'] ?? 0),
    'delivered' => (int) ($panelSummary['delivered'] ?? 0),
];
$paymentSummary = ['pending' => 0, 'partial' => 0, 'paid' => 0];
$ordersWithCommand = 0;
$ordersWithoutCommand = 0;
$ordersWaitingKitchen = 0;
$formatMoney = static fn ($value): string => 'R$ ' . number_format((float) $value, 2, ',', '.');

foreach ($ordersByTable as $tablePanel) {
    if (!is_array($tablePanel)) {
        continue;
    }
    $orders = is_array($tablePanel['orders'] ?? null) ? $tablePanel['orders'] : [];
    foreach ($orders as $order) {
        if (!is_array($order)) {
            continue;
        }
        if (($order['command_id'] ?? null) !== null) {
            $ordersWithCommand++;
        } else {
            $ordersWithoutCommand++;
        }
        if (!empty($order['can_send_kitchen'])) {
            $ordersWaitingKitchen++;
        }
        $paymentStatus = (string) ($order['payment_status'] ?? '');
        if (isset($paymentSummary[$paymentStatus])) {
            $paymentSummary[$paymentStatus]++;
        }
    }
}
?>

<style>
    .orders-page{display:grid;gap:16px}
    .kpi-grid{display:grid;grid-template-columns:repeat(6,minmax(135px,1fr));gap:12px}
    .kpi-item{background:#fff;border:1px solid #e5e7eb;border-radius:12px;padding:14px}
    .kpi-item strong{display:block;font-size:24px;line-height:1.1}
    .kpi-item span{color:#64748b;font-size:12px}
    .legend-panel{display:grid;gap:10px}
    .legend-group{border:1px solid #e2e8f0;border-radius:10px;padding:10px;background:#f8fafc}
    .legend-title{display:block;font-size:12px;color:#475569;text-transform:uppercase;letter-spacing:.05em;margin-bottom:6px}
    .legend-row{display:flex;gap:8px;flex-wrap:wrap}
    .search-row{display:grid;grid-template-columns:1fr auto;gap:8px}
    .search-info{color:#64748b;font-size:12px;margin-top:6px}
    .tables-stack{display:grid;gap:14px}
    .table-panel{background:#fff;border:1px solid #e2e8f0;border-radius:12px;padding:12px}
    .table-panel-header{display:flex;justify-content:space-between;align-items:flex-start;gap:10px;flex-wrap:wrap;margin-bottom:10px}
    .table-panel-title{margin:0}
    .table-panel-subtitle{margin:6px 0 0;color:#64748b}
    .orders-grid{display:grid;grid-template-columns:repeat(2,minmax(320px,1fr));gap:10px}
    .order-card{background:#fff;border:2px solid #dbeafe;border-radius:12px;padding:12px;display:grid;gap:10px}
    .order-card.status-op-pending{border-color:#f59e0b}
    .order-card.status-op-received{border-color:#3b82f6}
    .order-card.status-op-preparing{border-color:#6366f1}
    .order-card.status-op-ready{border-color:#22c55e}
    .order-card.status-op-delivered{border-color:#06b6d4}
    .order-card.search-hit{outline:2px solid #60a5fa;outline-offset:1px}
    .order-head{display:flex;justify-content:space-between;align-items:flex-start;gap:8px;flex-wrap:wrap}
    .order-head p{margin:4px 0 0;color:#334155;font-size:13px}
    .order-badges{display:flex;gap:6px;flex-wrap:wrap}
    .order-meta{display:grid;grid-template-columns:1fr 1fr;gap:8px}
    .order-meta-item{border:1px solid #e2e8f0;border-radius:9px;background:#f8fafc;padding:8px}
    .order-meta-item strong{display:block;font-size:11px;color:#64748b;text-transform:uppercase;letter-spacing:.04em;margin-bottom:3px}
    .order-meta-item span{font-size:13px;color:#0f172a}
    .order-items{border:1px solid #e2e8f0;border-radius:10px;background:#f8fafc;padding:10px;display:grid;gap:8px}
    .order-items-title{display:block;font-size:11px;color:#64748b;text-transform:uppercase;letter-spacing:.04em;margin-bottom:5px}
    .order-items-list{display:grid;gap:8px;max-height:240px;overflow:auto;padding-right:2px}
    .order-item-entry{border:1px solid #dbeafe;border-radius:10px;background:#fff;padding:8px}
    .order-item-main{display:flex;align-items:flex-start;justify-content:space-between;gap:8px}
    .order-item-name{font-size:13px;color:#0f172a;font-weight:600}
    .order-item-prices{display:flex;flex-wrap:wrap;justify-content:flex-end;gap:6px}
    .order-price-pill{display:inline-block;padding:3px 8px;border-radius:999px;background:#e2e8f0;color:#334155;font-size:11px}
    .order-item-additionals{margin-top:6px;display:grid;gap:4px}
    .order-item-additional{display:flex;align-items:flex-start;justify-content:space-between;gap:8px;padding:6px;border-radius:8px;background:#f8fafc}
    .order-item-additional strong{font-size:12px;color:#334155;font-weight:600}
    .order-item-note{margin-top:6px;font-size:12px;color:#475569}
    .order-actions{display:grid;gap:8px}
    .order-actions .row{display:flex;gap:8px;flex-wrap:wrap}
    .order-actions select,.order-actions input[type="text"]{background:#fff}
    .muted{color:#64748b}
    .empty-card{background:#fff;border:1px dashed #cbd5e1;border-radius:12px;padding:18px;color:#475569}
    @media (max-width:1160px){.kpi-grid{grid-template-columns:repeat(3,minmax(130px,1fr))}.orders-grid{grid-template-columns:1fr}}
    @media (max-width:700px){.kpi-grid{grid-template-columns:repeat(2,minmax(130px,1fr))}.order-meta{grid-template-columns:1fr}}
</style>

<div class="orders-page">
    <div class="topbar">
        <div>
            <h1>Pedidos</h1>
            <p>Painel operacional no padrao visual atualizado para cozinha, atendimento e entrega.</p>
        </div>
        <div style="display:flex;gap:8px;flex-wrap:wrap">
            <a class="btn secondary" href="<?= htmlspecialchars(base_url('/admin/kitchen')) ?>">Fila de cozinha</a>
            <a class="btn" href="<?= htmlspecialchars(base_url('/admin/orders/create')) ?>">Novo pedido</a>
        </div>
    </div>

    <div class="kpi-grid">
        <div class="kpi-item"><strong><?= (int) ($panelSummary['active_orders'] ?? 0) ?></strong><span>Pedidos ativos</span></div>
        <div class="kpi-item"><strong><?= (int) ($panelSummary['tables_in_service'] ?? 0) ?></strong><span>Mesas em atendimento</span></div>
        <div class="kpi-item"><strong><?= (int) ($panelSummary['items_total'] ?? 0) ?></strong><span>Itens em andamento</span></div>
        <div class="kpi-item"><strong>R$ <?= number_format((float) ($panelSummary['amount_total'] ?? 0), 2, ',', '.') ?></strong><span>Total em aberto</span></div>
        <div class="kpi-item"><strong><?= $ordersWithCommand ?></strong><span>Com comanda</span></div>
        <div class="kpi-item"><strong><?= $ordersWaitingKitchen ?></strong><span>Aguardando cozinha</span></div>
    </div>

    <div class="card legend-panel">
        <div class="legend-group">
            <strong class="legend-title">Cor da Borda do Pedido</strong>
            <div class="legend-row">
                <span class="badge <?= htmlspecialchars(status_badge_class('order_status', 'pending')) ?>">Pedido pendente</span>
                <span class="badge <?= htmlspecialchars(status_badge_class('order_status', 'received')) ?>">Pedido recebido</span>
                <span class="badge <?= htmlspecialchars(status_badge_class('order_status', 'preparing')) ?>">Pedido em preparo</span>
                <span class="badge <?= htmlspecialchars(status_badge_class('order_status', 'ready')) ?>">Pedido pronto</span>
                <span class="badge <?= htmlspecialchars(status_badge_class('order_status', 'delivered')) ?>">Pedido entregue</span>
            </div>
        </div>
        <div class="legend-group">
            <strong class="legend-title">Contagem Operacional</strong>
            <div class="legend-row">
                <span class="badge <?= htmlspecialchars(status_badge_class('order_status', 'pending')) ?>">Pendentes: <?= (int) ($operationalSummary['pending'] ?? 0) ?></span>
                <span class="badge <?= htmlspecialchars(status_badge_class('order_status', 'received')) ?>">Recebidos: <?= (int) ($operationalSummary['received'] ?? 0) ?></span>
                <span class="badge <?= htmlspecialchars(status_badge_class('order_status', 'preparing')) ?>">Em preparo: <?= (int) ($operationalSummary['preparing'] ?? 0) ?></span>
                <span class="badge <?= htmlspecialchars(status_badge_class('order_status', 'ready')) ?>">Prontos: <?= (int) ($operationalSummary['ready'] ?? 0) ?></span>
                <span class="badge <?= htmlspecialchars(status_badge_class('order_status', 'delivered')) ?>">Entregues: <?= (int) ($operationalSummary['delivered'] ?? 0) ?></span>
            </div>
        </div>
        <div class="legend-group">
            <strong class="legend-title">Pagamentos</strong>
            <div class="legend-row">
                <span class="badge <?= htmlspecialchars(status_badge_class('order_payment_status', 'pending')) ?>">Pendente: <?= (int) ($paymentSummary['pending'] ?? 0) ?></span>
                <span class="badge <?= htmlspecialchars(status_badge_class('order_payment_status', 'partial')) ?>">Parcial: <?= (int) ($paymentSummary['partial'] ?? 0) ?></span>
                <span class="badge <?= htmlspecialchars(status_badge_class('order_payment_status', 'paid')) ?>">Pago: <?= (int) ($paymentSummary['paid'] ?? 0) ?></span>
                <span class="badge status-default">Sem comanda: <?= $ordersWithoutCommand ?></span>
                <span class="badge <?= htmlspecialchars(status_badge_class('order_operational_flag', 'paid_waiting_production')) ?>">Pagos aguardando producao: <?= (int) ($panelSummary['paid_waiting_production'] ?? 0) ?></span>
            </div>
            <p class="search-info" style="margin:8px 0 0">Atualizacao automatica da pagina a cada 30 segundos.</p>
        </div>
    </div>

    <div class="card">
        <div class="search-row">
            <input id="orderSearch" type="text" placeholder="Buscar pedido por mesa, numero, cliente, status, pagamento, valor ou horario">
            <button id="clearOrderSearch" class="btn secondary" type="button">Limpar</button>
        </div>
        <div id="orderSearchInfo" class="search-info">Digite para filtrar os pedidos em tempo real.</div>
    </div>

    <?php if (empty($ordersByTable)): ?>
        <div class="empty-card">Nenhum pedido ativo no momento.</div>
    <?php else: ?>
        <div class="tables-stack">
            <?php foreach ($ordersByTable as $tablePanel): ?>
                <?php $orders = is_array($tablePanel['orders'] ?? null) ? $tablePanel['orders'] : []; ?>
                <section class="table-panel">
                    <div class="table-panel-header">
                        <div>
                            <h3 class="table-panel-title"><?= htmlspecialchars((string) ($tablePanel['label'] ?? 'Mesa')) ?></h3>
                            <p class="table-panel-subtitle">
                                Pedidos: <?= (int) ($tablePanel['orders_count'] ?? 0) ?>
                                | Itens: <?= (int) ($tablePanel['items_total'] ?? 0) ?>
                                | Total: R$ <?= number_format((float) ($tablePanel['amount_total'] ?? 0), 2, ',', '.') ?>
                            </p>
                        </div>
                    </div>

                    <div class="orders-grid">
                        <?php foreach ($orders as $order): ?>
                            <?php
                            $nextStatuses = $order['next_statuses'] ?? [];
                            if (!is_array($nextStatuses)) {
                                $nextStatuses = [];
                            }
                            if (!$canCancelOrder) {
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

                            $statusValue = (string) ($order['status'] ?? 'pending');
                            $orderBorderClass = in_array($statusValue, ['pending', 'received', 'preparing', 'ready', 'delivered'], true)
                                ? 'status-op-' . $statusValue
                                : 'status-op-pending';

                            $orderItems = is_array($order['items'] ?? null) ? $order['items'] : [];
                            $orderItemsSearchParts = [];
                            foreach ($orderItems as $searchItem) {
                                if (!is_array($searchItem)) {
                                    continue;
                                }
                                $orderItemsSearchParts[] = (string) ($searchItem['name'] ?? $searchItem['product_name_snapshot'] ?? '');
                                $orderItemsSearchParts[] = (string) ($searchItem['notes'] ?? '');
                                $searchAdditionals = is_array($searchItem['additionals'] ?? null) ? $searchItem['additionals'] : [];
                                foreach ($searchAdditionals as $searchAdditional) {
                                    if (!is_array($searchAdditional)) {
                                        continue;
                                    }
                                    $orderItemsSearchParts[] = (string) ($searchAdditional['name'] ?? '');
                                }
                            }
                            $hasOperationalActions = ($canSendKitchen && !empty($order['can_send_kitchen'])) || ($canUpdateStatus && !empty($nextStatuses));

                            $searchText = strtolower(trim(implode(' ', [
                                (string) ($tablePanel['label'] ?? ''),
                                (string) ($order['order_number'] ?? ''),
                                (string) ($order['customer_name'] ?? ''),
                                (string) ($order['status'] ?? ''),
                                (string) status_label('order_status', $order['status'] ?? null),
                                (string) ($order['payment_status'] ?? ''),
                                (string) status_label('order_payment_status', $order['payment_status'] ?? null),
                                (string) ($order['created_at'] ?? ''),
                                (string) ($order['latest_status_changed_at'] ?? ''),
                                number_format((float) ($order['total_amount'] ?? 0), 2, '.', ''),
                                implode(' ', $orderItemsSearchParts),
                            ])));
                            ?>
                            <article class="order-card <?= htmlspecialchars($orderBorderClass) ?>" data-search="<?= htmlspecialchars($searchText) ?>">
                                <div class="order-head">
                                    <div>
                                        <strong><?= htmlspecialchars((string) ($order['order_number'] ?? '-')) ?></strong>
                                        <p>
                                            Criado em: <?= htmlspecialchars((string) ($order['created_at'] ?? '-')) ?><br>
                                            <?= ($order['command_id'] ?? null) !== null ? 'Comanda ativa' : 'Pedido sem comanda' ?>
                                        </p>
                                    </div>
                                    <div class="order-badges">
                                        <span class="badge <?= htmlspecialchars(status_badge_class('order_status', $order['status'] ?? null)) ?>">
                                            <?= htmlspecialchars(status_label('order_status', $order['status'] ?? null)) ?>
                                        </span>
                                        <span class="badge <?= htmlspecialchars(status_badge_class('order_payment_status', $order['payment_status'] ?? null)) ?>">
                                            <?= htmlspecialchars(status_label('order_payment_status', $order['payment_status'] ?? null)) ?>
                                        </span>
                                        <?php if (!empty($order['is_paid_waiting_production'])): ?>
                                            <span class="badge <?= htmlspecialchars(status_badge_class('order_operational_flag', 'paid_waiting_production')) ?>">
                                                <?= htmlspecialchars(status_label('order_operational_flag', 'paid_waiting_production')) ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <div class="order-meta">
                                    <div class="order-meta-item"><strong>Cliente</strong><span><?= htmlspecialchars((string) ($order['customer_name'] ?? '-')) ?></span></div>
                                    <div class="order-meta-item"><strong>Itens</strong><span><?= (int) ($order['items_count'] ?? 0) ?></span></div>
                                    <div class="order-meta-item"><strong>Total</strong><span>R$ <?= number_format((float) ($order['total_amount'] ?? 0), 2, ',', '.') ?></span></div>
                                    <div class="order-meta-item"><strong>Ultima mudanca</strong><span><?= htmlspecialchars((string) ($order['latest_status_changed_at'] ?? '-')) ?></span></div>
                                </div>

                                <div class="order-items">
                                    <span class="order-items-title">Produtos selecionados</span>
                                    <?php if (empty($orderItems)): ?>
                                        <span class="muted">Nenhum item detalhado para este pedido.</span>
                                    <?php else: ?>
                                        <div class="order-items-list">
                                            <?php foreach ($orderItems as $item): ?>
                                                <?php
                                                $itemName = (string) ($item['name'] ?? $item['product_name_snapshot'] ?? 'Item');
                                                $itemQuantity = (int) ($item['quantity'] ?? 0);
                                                $itemUnitPrice = (float) ($item['unit_price'] ?? 0);
                                                $itemLineSubtotal = (float) ($item['line_subtotal'] ?? 0);
                                                $itemNotes = trim((string) ($item['notes'] ?? ''));
                                                $itemAdditionals = is_array($item['additionals'] ?? null) ? $item['additionals'] : [];
                                                ?>
                                                <div class="order-item-entry">
                                                    <div class="order-item-main">
                                                        <span class="order-item-name"><?= $itemQuantity ?>x <?= htmlspecialchars($itemName) ?></span>
                                                        <span class="order-item-prices">
                                                            <span class="order-price-pill">Unit: <?= htmlspecialchars($formatMoney($itemUnitPrice)) ?></span>
                                                            <span class="order-price-pill">Total: <?= htmlspecialchars($formatMoney($itemLineSubtotal)) ?></span>
                                                        </span>
                                                    </div>

                                                    <?php if ($itemAdditionals !== []): ?>
                                                        <div class="order-item-additionals">
                                                            <?php foreach ($itemAdditionals as $additional): ?>
                                                                <div class="order-item-additional">
                                                                    <strong>
                                                                        + <?= (int) ($additional['quantity'] ?? 0) ?>x <?= htmlspecialchars((string) ($additional['name'] ?? 'Adicional')) ?>
                                                                    </strong>
                                                                    <span class="order-item-prices">
                                                                        <span class="order-price-pill">Unit: <?= htmlspecialchars($formatMoney((float) ($additional['unit_price'] ?? 0))) ?></span>
                                                                        <span class="order-price-pill">Total: <?= htmlspecialchars($formatMoney((float) ($additional['line_subtotal'] ?? 0))) ?></span>
                                                                    </span>
                                                                </div>
                                                            <?php endforeach; ?>
                                                        </div>
                                                    <?php endif; ?>

                                                    <?php if ($itemNotes !== ''): ?>
                                                        <div class="order-item-note">Obs.: <?= htmlspecialchars($itemNotes) ?></div>
                                                    <?php endif; ?>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <div class="order-actions">
                                    <a class="btn secondary" href="<?= htmlspecialchars(base_url('/admin/orders/print-ticket?order_id=' . (int) $order['id'])) ?>">Imprimir ticket</a>

                                    <?php if ($canSendKitchen && !empty($order['can_send_kitchen'])): ?>
                                        <form method="POST" action="<?= htmlspecialchars(base_url('/admin/orders/send-kitchen')) ?>">
                                            <input type="hidden" name="order_id" value="<?= (int) $order['id'] ?>">
                                            <button class="btn secondary" type="submit">Enviar para cozinha</button>
                                        </form>
                                    <?php endif; ?>

                                    <?php if ($canUpdateStatus && !empty($nextStatuses)): ?>
                                        <form method="POST" action="<?= htmlspecialchars(base_url('/admin/orders/status')) ?>">
                                            <input type="hidden" name="order_id" value="<?= (int) $order['id'] ?>">
                                            <div class="row">
                                                <select name="new_status" required>
                                                    <option value="">Selecione o novo status</option>
                                                    <?php foreach ($nextStatuses as $status): ?>
                                                        <option value="<?= htmlspecialchars((string) $status) ?>"><?= htmlspecialchars(status_label('order_status', $status)) ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                            <div class="row">
                                                <input name="status_notes" type="text" placeholder="Observacao (opcional)">
                                                <button class="btn secondary" type="submit">Atualizar status</button>
                                            </div>
                                        </form>
                                    <?php endif; ?>

                                    <?php if (!$hasOperationalActions): ?>
                                        <span class="badge status-default">Sem acoes operacionais disponiveis</span>
                                    <?php endif; ?>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                </section>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<script>
(() => {
    const cards = Array.from(document.querySelectorAll('.order-card[data-search]'));
    const searchInput = document.getElementById('orderSearch');
    const clearButton = document.getElementById('clearOrderSearch');
    const searchInfo = document.getElementById('orderSearchInfo');
    const pageRefreshMs = 30000;

    const normalize = (value) => String(value || '')
        .toLowerCase()
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '');

    const applyFilter = () => {
        const rawQuery = searchInput ? searchInput.value : '';
        const tokens = normalize(rawQuery).split(/\s+/).filter(Boolean);
        let visibleCount = 0;
        let firstVisibleCard = null;

        cards.forEach((card) => {
            card.classList.remove('search-hit');
            const haystack = normalize(card.getAttribute('data-search') || '');
            const match = tokens.every((token) => haystack.includes(token));
            card.style.display = match ? '' : 'none';
            if (match) {
                visibleCount++;
                if (firstVisibleCard === null) {
                    firstVisibleCard = card;
                }
            }
        });

        if (firstVisibleCard) {
            firstVisibleCard.classList.add('search-hit');
        }

        if (searchInfo) {
            if (tokens.length === 0) {
                searchInfo.textContent = 'Digite para filtrar os pedidos em tempo real.';
            } else {
                searchInfo.textContent = visibleCount > 0
                    ? `Filtro ativo: ${visibleCount} pedido(s) encontrado(s).`
                    : 'Nenhum pedido encontrado para o filtro informado.';
            }
        }
    };

    if (searchInput) {
        searchInput.addEventListener('input', applyFilter);
    }

    if (clearButton && searchInput) {
        clearButton.addEventListener('click', () => {
            searchInput.value = '';
            applyFilter();
            searchInput.focus();
        });
    }

    window.setTimeout(() => {
        if (document.hidden) {
            return;
        }
        window.location.reload();
    }, pageRefreshMs);

    applyFilter();
})();
</script>
