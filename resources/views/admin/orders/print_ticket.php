<?php
$context = is_array($context ?? null) ? $context : [];
$order = is_array($context['order'] ?? null) ? $context['order'] : [];
$items = is_array($context['items'] ?? null) ? $context['items'] : [];
$generatedAt = (string) ($context['generated_at'] ?? date('Y-m-d H:i:s'));

$companyName = trim((string) ($order['company_name'] ?? 'Empresa'));
$companyLogoPath = trim((string) ($order['company_logo_path'] ?? ''));
$companyLogoUrl = $companyLogoPath !== '' ? asset_url($companyLogoPath) : '';

$orderNumber = (string) ($order['order_number'] ?? '-');
$tableNumber = $order['table_number'] !== null ? (int) $order['table_number'] : null;
$commandId = $order['command_id'] !== null ? (int) $order['command_id'] : null;
$customerName = trim((string) ($order['customer_name'] ?? ''));
$createdAt = (string) ($order['created_at'] ?? '-');
$statusValue = (string) ($order['status'] ?? '');
$paymentStatusValue = (string) ($order['payment_status'] ?? '');
$notes = trim((string) ($order['notes'] ?? ''));
$returnOrderId = isset($_GET['return_order_id']) ? (int) $_GET['return_order_id'] : 0;
$backToOrdersUrl = $returnOrderId > 0
    ? base_url('/admin/orders?open_order_id=' . $returnOrderId)
    : base_url('/admin/orders');

$subtotal = (float) ($order['subtotal_amount'] ?? 0);
$discount = (float) ($order['discount_amount'] ?? 0);
$deliveryFee = (float) ($order['delivery_fee'] ?? 0);
$total = (float) ($order['total_amount'] ?? 0);
?>

<style>
    .ticket-page{display:grid;gap:12px}
    .ticket-actions{display:flex;gap:8px;flex-wrap:wrap}
    .ticket-paper{width:80mm;max-width:100%;margin:0 auto;background:#fff;border:1px solid #d1d5db;padding:8px 8px 10px;font-family:"Courier New",Courier,monospace;font-size:12px;color:#0f172a;line-height:1.25}
    .ticket-center{text-align:center}
    .ticket-logo{width:56px;height:56px;object-fit:cover;border-radius:8px;border:1px solid #e5e7eb}
    .ticket-title{font-size:14px;font-weight:700;margin-top:4px}
    .ticket-divider{border-top:1px dashed #64748b;margin:6px 0}
    .ticket-row{display:flex;justify-content:space-between;gap:8px}
    .ticket-muted{color:#475569}
    .ticket-item{padding:4px 0;border-bottom:1px dotted #cbd5e1}
    .ticket-item-name{font-weight:700}
    .ticket-item-meta{font-size:11px;color:#334155}
    .ticket-additional{padding-left:10px;font-size:11px;color:#334155}
    .ticket-total{font-size:14px;font-weight:700}
    .screen-only{display:block}

    @page { size: 80mm auto; margin: 4mm; }

    @media print {
        body{background:#fff}
        .shell{display:block !important}
        .shell > aside{display:none !important}
        main{padding:0 !important}
        .screen-only{display:none !important}
        .ticket-paper{border:none;width:100%;padding:0}
    }
</style>

<div class="ticket-page">
    <div class="screen-only">
        <h1 style="margin:0">Ticket do pedido</h1>
        <p style="margin:6px 0 8px;color:#64748b">Modelo otimizado para impressora termica 80mm.</p>
        <div class="ticket-actions">
            <a class="btn secondary" href="<?= htmlspecialchars($backToOrdersUrl) ?>">Voltar</a>
            <button class="btn" type="button" onclick="window.print()">Imprimir ticket</button>
        </div>
    </div>

    <section class="ticket-paper">
        <div class="ticket-center">
            <?php if ($companyLogoUrl !== ''): ?>
                <img class="ticket-logo" src="<?= htmlspecialchars($companyLogoUrl) ?>" alt="Logo da empresa">
            <?php endif; ?>
            <div class="ticket-title"><?= htmlspecialchars($companyName) ?></div>
            <div class="ticket-muted">Comanda360.com.br</div>
        </div>

        <div class="ticket-divider"></div>

        <div class="ticket-row"><span>Pedido</span><strong><?= htmlspecialchars($orderNumber) ?></strong></div>
        <div class="ticket-row"><span>Mesa</span><span><?= $tableNumber !== null ? 'Mesa ' . $tableNumber : '-' ?></span></div>
        <div class="ticket-row"><span>Comanda</span><span><?= $commandId !== null ? '#' . $commandId : '-' ?></span></div>
        <div class="ticket-row"><span>Cliente</span><span><?= htmlspecialchars($customerName !== '' ? $customerName : '-') ?></span></div>
        <div class="ticket-row"><span>Data</span><span><?= htmlspecialchars($createdAt) ?></span></div>
        <div class="ticket-row"><span>Status</span><span><?= htmlspecialchars(status_label('order_status', $statusValue)) ?></span></div>
        <div class="ticket-row"><span>Pagamento</span><span><?= htmlspecialchars(status_label('order_payment_status', $paymentStatusValue)) ?></span></div>

        <div class="ticket-divider"></div>

        <?php if (empty($items)): ?>
            <div class="ticket-muted">Sem itens para este pedido.</div>
        <?php else: ?>
            <?php foreach ($items as $item): ?>
                <?php
                $itemAdditionals = is_array($item['additionals'] ?? null) ? $item['additionals'] : [];
                $itemName = (string) ($item['name'] ?? 'Produto');
                $itemQty = (int) ($item['quantity'] ?? 0);
                $itemSubtotal = (float) ($item['line_subtotal'] ?? 0);
                $itemUnitPrice = (float) ($item['unit_price'] ?? 0);
                $itemNotes = trim((string) ($item['notes'] ?? ''));
                ?>
                <div class="ticket-item">
                    <div class="ticket-row">
                        <span class="ticket-item-name"><?= $itemQty ?>x <?= htmlspecialchars($itemName) ?></span>
                        <strong>R$ <?= number_format($itemSubtotal, 2, ',', '.') ?></strong>
                    </div>
                    <div class="ticket-item-meta">Unitario: R$ <?= number_format($itemUnitPrice, 2, ',', '.') ?></div>
                    <?php if ($itemNotes !== ''): ?>
                        <div class="ticket-item-meta">Obs.: <?= htmlspecialchars($itemNotes) ?></div>
                    <?php endif; ?>
                    <?php foreach ($itemAdditionals as $additional): ?>
                        <div class="ticket-additional">
                            + <?= (int) ($additional['quantity'] ?? 0) ?>x <?= htmlspecialchars((string) ($additional['name'] ?? 'Adicional')) ?>
                            (R$ <?= number_format((float) ($additional['line_subtotal'] ?? 0), 2, ',', '.') ?>)
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>

        <div class="ticket-divider"></div>

        <div class="ticket-row"><span>Subtotal</span><span>R$ <?= number_format($subtotal, 2, ',', '.') ?></span></div>
        <div class="ticket-row"><span>Desconto</span><span>R$ <?= number_format($discount, 2, ',', '.') ?></span></div>
        <div class="ticket-row"><span>Taxa entrega</span><span>R$ <?= number_format($deliveryFee, 2, ',', '.') ?></span></div>
        <div class="ticket-row ticket-total"><span>Total</span><span>R$ <?= number_format($total, 2, ',', '.') ?></span></div>

        <?php if ($notes !== ''): ?>
            <div class="ticket-divider"></div>
            <div><strong>Obs. do pedido</strong></div>
            <div><?= htmlspecialchars($notes) ?></div>
        <?php endif; ?>

        <div class="ticket-divider"></div>
        <div class="ticket-center ticket-muted">Gerado em <?= htmlspecialchars($generatedAt) ?></div>
    </section>
</div>
