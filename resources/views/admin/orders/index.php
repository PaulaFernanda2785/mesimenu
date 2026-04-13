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
    body.modal-open{overflow:hidden}
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
    .muted{color:#64748b}
    .tables-stack{display:grid;gap:14px}
    .table-panel{background:#fff;border:1px solid #e2e8f0;border-radius:12px;padding:12px;overflow:hidden}
    .table-panel-header{display:flex;justify-content:space-between;align-items:flex-start;gap:10px;flex-wrap:wrap;margin-bottom:10px}
    .table-panel-title{margin:0}
    .table-panel-subtitle{margin:6px 0 0;color:#64748b;font-size:13px}
    .orders-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:10px;align-items:stretch}
    .order-card{width:100%;min-width:0;text-align:left;background:#fff;border:2px solid #dbeafe;border-radius:12px;padding:10px;display:grid;gap:8px;cursor:pointer;transition:border-color .2s,transform .12s,box-shadow .2s,background-color .2s}
    .order-card:hover{background:#f8fafc;transform:translateY(-1px);box-shadow:0 10px 20px rgba(2,6,23,.08)}
    .order-card:focus{outline:2px solid #60a5fa;outline-offset:1px}
    .order-card.status-op-pending{border-color:#f59e0b}
    .order-card.status-op-received{border-color:#3b82f6}
    .order-card.status-op-preparing{border-color:#6366f1}
    .order-card.status-op-ready{border-color:#22c55e}
    .order-card.status-op-delivered{border-color:#06b6d4}
    .order-card.search-hit{outline:2px solid #60a5fa;outline-offset:1px}
    .order-card-top{display:flex;justify-content:space-between;gap:8px;align-items:flex-start}
    .order-card-number{font-size:15px;font-weight:700;color:#0f172a;overflow-wrap:anywhere}
    .order-card-line{font-size:12px;color:#334155}
    .order-card-customer{font-size:12px;color:#475569;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
    .order-card-badges{display:flex;gap:6px;flex-wrap:wrap}
    .order-card-hint{font-size:11px;color:#1e40af;background:#eff6ff;border:1px solid #bfdbfe;border-radius:999px;padding:2px 8px;justify-self:start}
    .empty-card{background:#fff;border:1px dashed #cbd5e1;border-radius:12px;padding:18px;color:#475569}

    .order-modal-backdrop{position:fixed;inset:0;background:rgba(15,23,42,.6);display:grid;place-items:center;padding:12px;z-index:1200}
    .order-modal-backdrop[hidden]{display:none !important}
    .order-modal{width:min(980px,calc(100vw - 24px));max-height:calc(100vh - 24px);overflow-y:auto;overflow-x:hidden;background:#fff;border:2px solid #cbd5e1;border-radius:14px;padding:16px;display:grid;gap:14px;box-sizing:border-box}
    .order-modal.status-op-pending{border-color:#f59e0b}
    .order-modal.status-op-received{border-color:#3b82f6}
    .order-modal.status-op-preparing{border-color:#6366f1}
    .order-modal.status-op-ready{border-color:#22c55e}
    .order-modal.status-op-delivered{border-color:#06b6d4}
    .order-modal *{box-sizing:border-box}
    .modal-header{display:flex;justify-content:space-between;gap:10px;align-items:flex-start;flex-wrap:wrap}
    .modal-header h3{margin:0;font-size:18px}
    .modal-header p{margin:6px 0 0;color:#64748b;font-size:13px}
    .modal-meta-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:8px;min-width:0}
    .modal-meta-item{border:1px solid #e2e8f0;border-radius:10px;background:#f8fafc;padding:8px;min-width:0}
    .modal-meta-item strong{display:block;font-size:11px;color:#64748b;text-transform:uppercase;letter-spacing:.04em;margin-bottom:3px}
    .modal-meta-item span{font-size:13px;color:#0f172a;display:block;overflow-wrap:anywhere}
    .modal-items{border:1px solid #e2e8f0;border-radius:10px;background:#f8fafc;padding:14px;display:grid;gap:12px;min-width:0}
    .modal-items-title{display:block;font-size:11px;color:#64748b;text-transform:uppercase;letter-spacing:.04em}
    .modal-items-list{display:grid;gap:12px;max-height:300px;overflow-y:auto;overflow-x:hidden;padding:4px 2px 2px 0}
    .modal-item{border:1px solid #dbeafe;border-radius:10px;background:#fff;padding:14px 12px}
    .modal-item-main{display:flex;justify-content:space-between;gap:10px;align-items:flex-start;flex-wrap:wrap}
    .modal-item-name{font-size:13px;color:#0f172a;font-weight:600;overflow-wrap:anywhere}
    .modal-item-prices{display:flex;flex-wrap:wrap;gap:6px}
    .modal-pill{display:inline-block;padding:3px 8px;border-radius:999px;background:#e2e8f0;color:#334155;font-size:11px}
    .modal-item-additionals{margin-top:10px;display:grid;gap:8px}
    .modal-item-additional{display:flex;justify-content:space-between;gap:10px;align-items:flex-start;flex-wrap:wrap;padding:10px 12px;border-radius:8px;background:#f8fafc}
    .modal-item-additional strong{font-size:12px;color:#334155;font-weight:600}
    .modal-item-note{margin-top:8px;font-size:12px;color:#475569}
    .modal-actions{display:grid;gap:12px;min-width:0}
    .modal-action-block{border:1px solid #e2e8f0;border-radius:10px;background:#fff;padding:12px;display:grid;gap:10px}
    .modal-action-block form{display:grid;gap:10px;min-width:0}
    .modal-actions-row{display:flex;gap:10px;flex-wrap:wrap;min-width:0}
    .modal-actions-row > *{min-width:0}
    .modal-actions-row input[type="text"]{flex:1 1 320px}
    .modal-actions-row select{flex:1 1 100%}
    .modal-actions-row button{flex:0 0 auto}
    .modal-actions select,.modal-actions input[type="text"]{background:#fff;max-width:100%}
    .modal-actions-row .btn{max-width:100%}
    .modal-actions-row-status-submit{align-items:flex-end}
    .order-modal-feedback{padding:10px;border-radius:8px;font-size:13px}
    .order-modal-feedback.success{background:#dcfce7;color:#166534}
    .order-modal-feedback.error{background:#fee2e2;color:#991b1b}
    .modal-footer{display:flex;justify-content:flex-end;padding-top:2px}

    @media (max-width:1160px){
        .kpi-grid{grid-template-columns:repeat(3,minmax(130px,1fr))}
        .modal-meta-grid{grid-template-columns:repeat(2,minmax(0,1fr))}
    }
    @media (max-width:740px){
        .kpi-grid{grid-template-columns:repeat(2,minmax(130px,1fr))}
        .orders-grid{grid-template-columns:1fr}
        .modal-meta-grid{grid-template-columns:1fr}
        .modal-actions-row-status-submit button{width:100%}
    }
</style>

<div class="orders-page">
    <div class="topbar">
        <div>
            <h1>Painel Operacional de Pedidos</h1>
            <p>Visualizacao por mesa e comandas. Clique em uma comanda para abrir o modal de operacao.</p>
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
            <strong class="legend-title">Cor da Borda da Comanda</strong>
            <div class="legend-row">
                <span class="badge <?= htmlspecialchars(status_badge_class('order_status', 'pending')) ?>">Pendente</span>
                <span class="badge <?= htmlspecialchars(status_badge_class('order_status', 'received')) ?>">Recebido</span>
                <span class="badge <?= htmlspecialchars(status_badge_class('order_status', 'preparing')) ?>">Em preparo</span>
                <span class="badge <?= htmlspecialchars(status_badge_class('order_status', 'ready')) ?>">Pronto</span>
                <span class="badge <?= htmlspecialchars(status_badge_class('order_status', 'delivered')) ?>">Entregue</span>
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
            <p class="search-info" style="margin:8px 0 0">Atualizacao automatica da pagina e do modal a cada 30 segundos.</p>
        </div>
    </div>

    <div class="card">
        <div class="search-row">
            <input id="orderSearch" type="text" placeholder="Buscar por mesa, numero do pedido, status, pagamento, comanda ou horario">
            <button id="clearOrderSearch" class="btn secondary" type="button">Limpar</button>
        </div>
        <div id="orderSearchInfo" class="search-info">Digite para filtrar as comandas em tempo real.</div>
    </div>

    <?php if (empty($ordersByTable)): ?>
        <div class="empty-card">Nenhum pedido ativo no momento.</div>
    <?php else: ?>
        <div class="tables-stack" id="tablesStack">
            <?php foreach ($ordersByTable as $tablePanel): ?>
                <?php $orders = is_array($tablePanel['orders'] ?? null) ? $tablePanel['orders'] : []; ?>
                <section class="table-panel" data-table-panel>
                    <div class="table-panel-header">
                        <div>
                            <h3 class="table-panel-title"><?= htmlspecialchars((string) ($tablePanel['label'] ?? 'Mesa')) ?></h3>
                            <p class="table-panel-subtitle">
                                Comandas: <?= (int) ($tablePanel['orders_count'] ?? 0) ?>
                                | Itens: <?= (int) ($tablePanel['items_total'] ?? 0) ?>
                                | Total em aberto: R$ <?= number_format((float) ($tablePanel['amount_total'] ?? 0), 2, ',', '.') ?>
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
                            <?php
                            $orderId = (int) ($order['id'] ?? 0);
                            $commandLabel = ($order['command_id'] ?? null) !== null
                                ? 'Comanda ativa #' . (int) $order['command_id']
                                : 'Pedido sem comanda';
                            $customerName = trim((string) ($order['customer_name'] ?? ''));
                            $customerLabel = $customerName !== '' ? $customerName : 'Nao informado';
                            ?>
                            <button
                                type="button"
                                class="order-card <?= htmlspecialchars($orderBorderClass) ?>"
                                data-search="<?= htmlspecialchars($searchText) ?>"
                                data-order-id="<?= $orderId ?>"
                                data-status-class="<?= htmlspecialchars($orderBorderClass) ?>"
                            >
                                <div class="order-card-top">
                                    <span class="order-card-number"><?= htmlspecialchars((string) ($order['order_number'] ?? '-')) ?></span>
                                    <span class="badge <?= htmlspecialchars(status_badge_class('order_status', $order['status'] ?? null)) ?>">
                                        <?= htmlspecialchars(status_label('order_status', $order['status'] ?? null)) ?>
                                    </span>
                                </div>
                                <span class="order-card-line">Criado em: <?= htmlspecialchars((string) ($order['created_at'] ?? '-')) ?></span>
                                <span class="order-card-line"><?= htmlspecialchars($commandLabel) ?></span>
                                <span class="order-card-customer">Cliente: <?= htmlspecialchars($customerLabel) ?></span>
                                <div class="order-card-badges">
                                    <span class="badge <?= htmlspecialchars(status_badge_class('order_payment_status', $order['payment_status'] ?? null)) ?>">
                                        <?= htmlspecialchars(status_label('order_payment_status', $order['payment_status'] ?? null)) ?>
                                    </span>
                                    <?php if (!empty($order['is_paid_waiting_production'])): ?>
                                        <span class="badge <?= htmlspecialchars(status_badge_class('order_operational_flag', 'paid_waiting_production')) ?>">
                                            <?= htmlspecialchars(status_label('order_operational_flag', 'paid_waiting_production')) ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                                <span class="order-card-hint">Abrir detalhes</span>
                            </button>

                            <template id="order-modal-template-<?= $orderId ?>">
                                <div class="modal-header">
                                    <div>
                                        <h3><?= htmlspecialchars((string) ($order['order_number'] ?? '-')) ?></h3>
                                        <p>
                                            <?= htmlspecialchars((string) ($tablePanel['label'] ?? 'Mesa')) ?>
                                            | Criado em: <?= htmlspecialchars((string) ($order['created_at'] ?? '-')) ?>
                                        </p>
                                    </div>
                                    <div class="order-card-badges">
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

                                <div class="modal-meta-grid">
                                    <div class="modal-meta-item"><strong>Comanda</strong><span><?= htmlspecialchars($commandLabel) ?></span></div>
                                    <div class="modal-meta-item"><strong>Cliente</strong><span><?= htmlspecialchars($customerLabel) ?></span></div>
                                    <div class="modal-meta-item"><strong>Itens</strong><span><?= (int) ($order['items_count'] ?? 0) ?></span></div>
                                    <div class="modal-meta-item"><strong>Total</strong><span><?= htmlspecialchars($formatMoney((float) ($order['total_amount'] ?? 0))) ?></span></div>
                                    <div class="modal-meta-item"><strong>Ultima mudanca</strong><span><?= htmlspecialchars((string) ($order['latest_status_changed_at'] ?? '-')) ?></span></div>
                                    <div class="modal-meta-item"><strong>Pagamento</strong><span><?= htmlspecialchars(status_label('order_payment_status', $order['payment_status'] ?? null)) ?></span></div>
                                    <div class="modal-meta-item"><strong>Status operacional</strong><span><?= htmlspecialchars(status_label('order_status', $order['status'] ?? null)) ?></span></div>
                                    <div class="modal-meta-item"><strong>Atualizacao</strong><span>Automatica em 30s</span></div>
                                </div>

                                <div class="modal-items">
                                    <span class="modal-items-title">Produtos e adicionais</span>
                                    <?php if (empty($orderItems)): ?>
                                        <span class="muted">Nenhum item detalhado para este pedido.</span>
                                    <?php else: ?>
                                        <div class="modal-items-list">
                                            <?php foreach ($orderItems as $item): ?>
                                                <?php
                                                $itemName = (string) ($item['name'] ?? $item['product_name_snapshot'] ?? 'Item');
                                                $itemQuantity = (int) ($item['quantity'] ?? 0);
                                                $itemUnitPrice = (float) ($item['unit_price'] ?? 0);
                                                $itemLineSubtotal = (float) ($item['line_subtotal'] ?? 0);
                                                $itemNotes = trim((string) ($item['notes'] ?? ''));
                                                $itemAdditionals = is_array($item['additionals'] ?? null) ? $item['additionals'] : [];
                                                ?>
                                                <div class="modal-item">
                                                    <div class="modal-item-main">
                                                        <span class="modal-item-name"><?= $itemQuantity ?>x <?= htmlspecialchars($itemName) ?></span>
                                                        <span class="modal-item-prices">
                                                            <span class="modal-pill">Unit: <?= htmlspecialchars($formatMoney($itemUnitPrice)) ?></span>
                                                            <span class="modal-pill">Total: <?= htmlspecialchars($formatMoney($itemLineSubtotal)) ?></span>
                                                        </span>
                                                    </div>

                                                    <?php if ($itemAdditionals !== []): ?>
                                                        <div class="modal-item-additionals">
                                                            <?php foreach ($itemAdditionals as $additional): ?>
                                                                <div class="modal-item-additional">
                                                                    <strong>
                                                                        + <?= (int) ($additional['quantity'] ?? 0) ?>x <?= htmlspecialchars((string) ($additional['name'] ?? 'Adicional')) ?>
                                                                    </strong>
                                                                    <span class="modal-item-prices">
                                                                        <span class="modal-pill">Unit: <?= htmlspecialchars($formatMoney((float) ($additional['unit_price'] ?? 0))) ?></span>
                                                                        <span class="modal-pill">Total: <?= htmlspecialchars($formatMoney((float) ($additional['line_subtotal'] ?? 0))) ?></span>
                                                                    </span>
                                                                </div>
                                                            <?php endforeach; ?>
                                                        </div>
                                                    <?php endif; ?>

                                                    <?php if ($itemNotes !== ''): ?>
                                                        <div class="modal-item-note">Obs.: <?= htmlspecialchars($itemNotes) ?></div>
                                                    <?php endif; ?>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <div class="modal-actions">
                                    <div class="order-modal-feedback" data-modal-feedback hidden></div>
                                    <div class="modal-action-block">
                                        <div class="modal-actions-row">
                                            <a class="btn secondary" href="<?= htmlspecialchars(base_url('/admin/orders/print-ticket?order_id=' . $orderId . '&return_order_id=' . $orderId)) ?>">Imprimir ticket</a>
                                        </div>
                                    </div>

                                    <?php if ($canSendKitchen && !empty($order['can_send_kitchen'])): ?>
                                        <div class="modal-action-block">
                                            <form class="js-modal-action-form" method="POST" action="<?= htmlspecialchars(base_url('/admin/orders/send-kitchen')) ?>">
                                                <input type="hidden" name="order_id" value="<?= $orderId ?>">
                                                <button class="btn secondary" type="submit">Enviar para cozinha</button>
                                            </form>
                                        </div>
                                    <?php endif; ?>

                                    <?php if ($canUpdateStatus && !empty($nextStatuses)): ?>
                                        <div class="modal-action-block">
                                            <form class="js-modal-action-form" method="POST" action="<?= htmlspecialchars(base_url('/admin/orders/status')) ?>">
                                                <input type="hidden" name="order_id" value="<?= $orderId ?>">
                                                <div class="modal-actions-row modal-actions-row-status-select">
                                                    <select name="new_status" required>
                                                        <option value="">Selecione o novo status</option>
                                                        <?php foreach ($nextStatuses as $status): ?>
                                                            <option value="<?= htmlspecialchars((string) $status) ?>"><?= htmlspecialchars(status_label('order_status', $status)) ?></option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>
                                                <div class="modal-actions-row modal-actions-row-status-submit">
                                                    <input name="status_notes" type="text" placeholder="Observacao (opcional)">
                                                    <button class="btn secondary" type="submit">Atualizar status</button>
                                                </div>
                                            </form>
                                        </div>
                                    <?php endif; ?>

                                    <?php if (!$hasOperationalActions): ?>
                                        <span class="badge status-default">Sem acoes operacionais disponiveis</span>
                                    <?php endif; ?>
                                </div>

                                <div class="modal-footer">
                                    <button type="button" class="btn secondary" data-modal-close>Fechar</button>
                                </div>
                            </template>
                        <?php endforeach; ?>
                    </div>
                </section>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<div class="order-modal-backdrop" id="orderModalRoot" hidden>
    <section class="order-modal" id="orderModalDialog" role="dialog" aria-modal="true">
        <div id="orderModalBody"></div>
    </section>
</div>

<script>
(() => {
    const cards = Array.from(document.querySelectorAll('.order-card[data-order-id][data-search]'));
    const tablePanels = Array.from(document.querySelectorAll('[data-table-panel]'));
    const searchInput = document.getElementById('orderSearch');
    const clearButton = document.getElementById('clearOrderSearch');
    const searchInfo = document.getElementById('orderSearchInfo');
    const pageRefreshMs = 30000;
    const modalRoot = document.getElementById('orderModalRoot');
    const modalDialog = document.getElementById('orderModalDialog');
    const modalBody = document.getElementById('orderModalBody');
    const modalStorageKey = 'orders.activeModalOrderId';
    const modalRestoreFlagKey = 'orders.restoreModalOnNextLoad';
    let activeOrderId = null;

    const normalize = (value) => String(value || '')
        .toLowerCase()
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '');

    const findCardByOrderId = (orderId) => cards.find((card) => Number(card.getAttribute('data-order-id') || 0) === Number(orderId || 0)) || null;

    const applyModalStatusClass = (orderId) => {
        if (!modalDialog) {
            return;
        }
        modalDialog.classList.remove('status-op-pending', 'status-op-received', 'status-op-preparing', 'status-op-ready', 'status-op-delivered');
        const card = findCardByOrderId(orderId);
        if (!card) {
            return;
        }
        const statusClass = String(card.getAttribute('data-status-class') || '').trim();
        if (statusClass !== '') {
            modalDialog.classList.add(statusClass);
        }
    };

    const saveModalState = () => {
        if (activeOrderId !== null) {
            window.sessionStorage.setItem(modalStorageKey, String(activeOrderId));
        }
    };

    const clearModalState = () => {
        window.sessionStorage.removeItem(modalStorageKey);
        window.sessionStorage.removeItem(modalRestoreFlagKey);
    };

    const closeModal = (clearStored = true) => {
        if (!modalRoot || !modalBody) {
            return;
        }
        modalRoot.hidden = true;
        modalBody.innerHTML = '';
        if (modalDialog) {
            modalDialog.classList.remove('status-op-pending', 'status-op-received', 'status-op-preparing', 'status-op-ready', 'status-op-delivered');
        }
        document.body.classList.remove('modal-open');
        activeOrderId = null;
        if (clearStored) {
            clearModalState();
        }
    };

    const setFeedback = (container, message, isError) => {
        if (!(container instanceof HTMLElement)) {
            return;
        }
        container.hidden = false;
        container.textContent = String(message || '');
        container.classList.remove('success', 'error');
        container.classList.add(isError ? 'error' : 'success');
    };

    const bindModalActions = (orderId) => {
        if (!modalBody) {
            return;
        }

        const closeButtons = Array.from(modalBody.querySelectorAll('[data-modal-close]'));
        closeButtons.forEach((button) => {
            button.addEventListener('click', () => closeModal(true));
        });

        const forms = Array.from(modalBody.querySelectorAll('.js-modal-action-form'));
        forms.forEach((form) => {
            form.addEventListener('submit', async (event) => {
                event.preventDefault();
                event.stopPropagation();
                if (typeof event.stopImmediatePropagation === 'function') {
                    event.stopImmediatePropagation();
                }
                const feedback = modalBody.querySelector('[data-modal-feedback]');
                const submitControls = Array.from(form.querySelectorAll('button[type="submit"], input[type="submit"]'));
                submitControls.forEach((control) => {
                    control.disabled = true;
                });

                try {
                    const response = await window.fetch(form.action, {
                        method: 'POST',
                        body: new window.FormData(form),
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json',
                        },
                    });

                    let payload = null;
                    try {
                        payload = await response.json();
                    } catch (jsonError) {
                        payload = null;
                    }

                    const isOk = response.ok && payload && payload.ok === true;
                    if (!isOk) {
                        const message = payload && payload.message
                            ? payload.message
                            : 'Falha ao executar a acao operacional.';
                        setFeedback(feedback, message, true);
                        return;
                    }

                    setFeedback(feedback, payload.message || 'Acao realizada com sucesso. Atualizando painel...', false);
                    activeOrderId = Number(orderId || 0) > 0 ? Number(orderId) : null;
                    saveModalState();
                    window.sessionStorage.setItem(modalRestoreFlagKey, '1');
                    window.setTimeout(() => {
                        if (document.hidden) {
                            return;
                        }
                        window.location.reload();
                    }, 500);
                } catch (error) {
                    setFeedback(feedback, 'Falha de comunicacao com o servidor.', true);
                } finally {
                    submitControls.forEach((control) => {
                        control.disabled = false;
                    });
                }
            });
        });
    };

    const openModal = (orderId, persist = true) => {
        if (!modalRoot || !modalBody) {
            return;
        }

        const numericOrderId = Number(orderId || 0);
        if (numericOrderId <= 0) {
            return;
        }

        const template = document.getElementById(`order-modal-template-${numericOrderId}`);
        if (!(template instanceof HTMLTemplateElement)) {
            return;
        }

        modalBody.innerHTML = template.innerHTML;

        modalRoot.hidden = false;
        document.body.classList.add('modal-open');
        activeOrderId = numericOrderId;
        applyModalStatusClass(numericOrderId);
        if (persist) {
            saveModalState();
        }

        bindModalActions(numericOrderId);
    };

    const applyFilter = () => {
        const rawQuery = searchInput ? searchInput.value : '';
        const tokens = normalize(rawQuery).split(/\s+/).filter(Boolean);
        let visibleCards = 0;
        let firstVisibleCard = null;

        tablePanels.forEach((panel) => {
            const panelCards = Array.from(panel.querySelectorAll('.order-card[data-search]'));
            let visibleInPanel = 0;

            panelCards.forEach((card) => {
                card.classList.remove('search-hit');
                const haystack = normalize(card.getAttribute('data-search') || '');
                const match = tokens.every((token) => haystack.includes(token));
                card.style.display = match ? '' : 'none';

                if (match) {
                    visibleCards++;
                    visibleInPanel++;
                    if (firstVisibleCard === null) {
                        firstVisibleCard = card;
                    }
                }
            });

            panel.style.display = visibleInPanel > 0 ? '' : 'none';
        });

        if (firstVisibleCard) {
            firstVisibleCard.classList.add('search-hit');
        }

        if (searchInfo) {
            if (tokens.length === 0) {
                searchInfo.textContent = 'Digite para filtrar as comandas em tempo real.';
            } else {
                searchInfo.textContent = visibleCards > 0
                    ? `Filtro ativo: ${visibleCards} comanda(s) encontrada(s).`
                    : 'Nenhuma comanda encontrada para o filtro informado.';
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

    cards.forEach((card) => {
        card.addEventListener('click', () => {
            const orderId = Number(card.getAttribute('data-order-id') || 0);
            openModal(orderId, true);
        });
    });

    if (modalRoot) {
        modalRoot.addEventListener('click', (event) => {
            if (event.target === modalRoot) {
                closeModal(true);
            }
        });
    }

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && modalRoot && !modalRoot.hidden) {
            closeModal(true);
        }
    });

    window.setInterval(() => {
        if (document.hidden) {
            return;
        }
        if (modalRoot && !modalRoot.hidden) {
            saveModalState();
            window.sessionStorage.setItem(modalRestoreFlagKey, '1');
        } else {
            clearModalState();
        }
        window.location.reload();
    }, pageRefreshMs);

    applyFilter();

    const url = new URL(window.location.href);
    const queryOpenOrderId = Number(url.searchParams.get('open_order_id') || 0);
    if (queryOpenOrderId > 0 && findCardByOrderId(queryOpenOrderId)) {
        openModal(queryOpenOrderId, true);
        url.searchParams.delete('open_order_id');
        const nextSearch = url.searchParams.toString();
        const nextUrl = `${url.pathname}${nextSearch !== '' ? '?' + nextSearch : ''}${url.hash}`;
        window.history.replaceState({}, '', nextUrl);
        return;
    }

    const shouldRestoreModal = window.sessionStorage.getItem(modalRestoreFlagKey) === '1';
    window.sessionStorage.removeItem(modalRestoreFlagKey);
    const persistedOrderId = Number(window.sessionStorage.getItem(modalStorageKey) || 0);
    if (shouldRestoreModal && persistedOrderId > 0) {
        if (findCardByOrderId(persistedOrderId)) {
            openModal(persistedOrderId, true);
        } else {
            clearModalState();
        }
    } else if (!shouldRestoreModal && persistedOrderId > 0) {
        window.sessionStorage.removeItem(modalStorageKey);
    }
})();
</script>
