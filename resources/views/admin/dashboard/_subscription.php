<?php
$subscriptionModule = is_array($subscriptionModule ?? null) ? $subscriptionModule : [];
$subscription = is_array($subscriptionModule['subscription'] ?? null) ? $subscriptionModule['subscription'] : [];
$subscriptionSummary = is_array($subscriptionModule['summary'] ?? null) ? $subscriptionModule['summary'] : [];
$openSubscriptionPayments = is_array($subscriptionModule['open_payments'] ?? null) ? $subscriptionModule['open_payments'] : [];
$subscriptionHistory = is_array($subscriptionModule['payment_history'] ?? null) ? $subscriptionModule['payment_history'] : [];
$subscriptionHistoryFilters = is_array($subscriptionModule['history_filters'] ?? null) ? $subscriptionModule['history_filters'] : [];
$subscriptionHistoryPagination = is_array($subscriptionModule['history_pagination'] ?? null) ? $subscriptionModule['history_pagination'] : [];
$subscriptionFeatures = is_array($subscriptionModule['features'] ?? null) ? $subscriptionModule['features'] : [];
$subscriptionBillingAccess = is_array($subscriptionModule['billing_access'] ?? null) ? $subscriptionModule['billing_access'] : [];

$currentSubscriptionQuery = is_array($_GET ?? null) ? $_GET : [];
$currentSubscriptionQuery['section'] = 'subscription';
$returnSubscriptionQuery = http_build_query($currentSubscriptionQuery);

$formatSubscriptionMoney = static fn (mixed $value): string => 'R$ ' . number_format((float) $value, 2, ',', '.');
$formatSubscriptionDate = static function (mixed $value, bool $withTime = false): string {
    $raw = trim((string) ($value ?? ''));
    if ($raw === '') {
        return '-';
    }

    $timestamp = strtotime($raw);
    if ($timestamp === false) {
        return $raw;
    }

    return date($withTime ? 'd/m/Y H:i' : 'd/m/Y', $timestamp);
};

$paymentMethodLabels = [
    '' => 'Não definido',
    'pix' => 'Pix',
    'credito' => 'Cartão de crédito',
    'debito' => 'Cartão de débito',
];

$historyStatusOptions = [
    '' => 'Todos os status',
    'pendente' => 'Pendente',
    'pago' => 'Pago',
    'vencido' => 'Vencido',
    'cancelado' => 'Cancelado',
];

$historyMethodOptions = [
    '' => 'Todos os métodos',
    'pix' => 'Pix',
    'credito' => 'Cartão de crédito',
    'debito' => 'Cartão de débito',
    'none' => 'Sem método',
];

$historySearch = trim((string) ($subscriptionHistoryFilters['search'] ?? ''));
$historyStatus = trim((string) ($subscriptionHistoryFilters['status'] ?? ''));
$historyMethod = trim((string) ($subscriptionHistoryFilters['method'] ?? ''));
$historyPage = max(1, (int) ($subscriptionHistoryPagination['page'] ?? 1));
$historyLastPage = max(1, (int) ($subscriptionHistoryPagination['last_page'] ?? 1));
$historyPages = is_array($subscriptionHistoryPagination['pages'] ?? null) ? $subscriptionHistoryPagination['pages'] : [1];

$buildSubscriptionUrl = static function (array $overrides = []) use ($historySearch, $historyStatus, $historyMethod): string {
    $params = array_merge([
        'section' => 'subscription',
        'subscription_history_search' => $historySearch,
        'subscription_history_status' => $historyStatus,
        'subscription_history_method' => $historyMethod,
    ], $overrides);

    foreach ($params as $key => $value) {
        if ($key !== 'section' && trim((string) $value) === '') {
            unset($params[$key]);
        }
    }

    return base_url('/admin/dashboard?' . http_build_query($params));
};

$buildSubscriptionReceiptUrl = static function (int $paymentId) use ($historySearch, $historyStatus, $historyMethod, $historyPage): string {
    $params = [
        'section' => 'subscription',
        'subscription_history_search' => $historySearch,
        'subscription_history_status' => $historyStatus,
        'subscription_history_method' => $historyMethod,
        'subscription_history_page' => $historyPage,
        'subscription_payment_id' => $paymentId,
    ];

    foreach ($params as $key => $value) {
        if (!in_array($key, ['section', 'subscription_payment_id', 'subscription_history_page'], true) && trim((string) $value) === '') {
            unset($params[$key]);
        }
    }

    return base_url('/admin/dashboard/subscription/receipt?' . http_build_query($params));
};

$currentCharge = is_array($openSubscriptionPayments[0] ?? null) ? $openSubscriptionPayments[0] : [];
$hasCurrentCharge = $currentCharge !== [];
$currentChargeId = (int) ($currentCharge['id'] ?? 0);
$currentPixImage = trim((string) ($currentCharge['pix_qr_image_base64'] ?? ''));
$currentPixPayload = trim((string) ($currentCharge['pix_qr_payload'] ?? ''));
$currentPixTicketUrl = trim((string) ($currentCharge['pix_ticket_url'] ?? ''));
$currentPixFallbackImageUrl = $currentPixImage === '' && $currentPixPayload !== ''
    ? 'https://api.qrserver.com/v1/create-qr-code/?size=240x240&data=' . rawurlencode($currentPixPayload)
    : '';
$currentGatewayPaymentId = trim((string) ($currentCharge['gateway_payment_id'] ?? ''));
$shouldAutoPollPixStatus = $currentChargeId > 0
    && $currentGatewayPaymentId !== ''
    && in_array((string) ($currentCharge['status'] ?? ''), ['pendente', 'vencido'], true);
$nextDueDate = $subscriptionSummary['next_due_date'] ?? ($subscriptionBillingAccess['next_due_date'] ?? null);
$historyTotal = (int) ($subscriptionHistoryPagination['total'] ?? 0);
$historyFrom = (int) ($subscriptionHistoryPagination['from'] ?? 0);
$historyTo = (int) ($subscriptionHistoryPagination['to'] ?? 0);
?>

<section class="dash-section<?= $activeSection === 'subscription' ? ' active' : '' ?>" data-section="subscription">
    <style>
        .sp-shell{display:grid;gap:14px}
        .sp-hero{border:1px solid rgba(255,255,255,.22);border-radius:18px;background:linear-gradient(135deg,var(--theme-main-card,#0f172a) 0%,#0f766e 58%,#34d399 100%);color:#fff;padding:20px}
        .sp-hero h2{margin:0 0 8px;color:#fff}
        .sp-hero p{margin:0;max-width:860px;color:#d1fae5;line-height:1.55}
        .sp-kpis{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:10px}
        .sp-kpi{border:1px solid #dbeafe;border-radius:14px;background:linear-gradient(180deg,#fff 0%,#f8fafc 100%);padding:14px}
        .sp-kpi span{display:block;font-size:11px;text-transform:uppercase;letter-spacing:.05em;color:#64748b}
        .sp-kpi strong{display:block;margin-top:6px;font-size:21px;color:#0f172a}
        .sp-kpi small{display:block;margin-top:4px;color:#475569;line-height:1.4}
        .sp-layout{display:grid;grid-template-columns:minmax(0,1.7fr) minmax(260px,.7fr);gap:14px;align-items:start}
        .sp-column{display:grid;gap:14px}
        .sp-head{display:flex;justify-content:space-between;align-items:flex-start;gap:12px;flex-wrap:wrap}
        .sp-head h3{margin:0;color:#0f172a}
        .sp-note{margin:4px 0 0;font-size:13px;line-height:1.5;color:#475569}
        .sp-flow{display:grid;gap:10px;margin-top:14px}
        .sp-step{display:grid;grid-template-columns:38px minmax(0,1fr);gap:12px;align-items:flex-start;padding:12px 0;border-bottom:1px dashed #dbe4ee}
        .sp-step:last-child{border-bottom:none;padding-bottom:0}
        .sp-step-badge{width:38px;height:38px;border-radius:999px;display:grid;place-items:center;background:#ccfbf1;color:#0f766e;font-weight:800}
        .sp-step strong{display:block;color:#0f172a}
        .sp-step p{margin:4px 0 0;font-size:13px;line-height:1.5;color:#475569}
        .sp-charge{border:1px solid #a7f3d0;border-radius:16px;background:linear-gradient(180deg,#fff 0%,#f0fdfa 100%);padding:16px;display:grid;gap:14px}
        .sp-charge-head{display:flex;justify-content:space-between;align-items:flex-start;gap:12px;flex-wrap:wrap}
        .sp-charge-head strong{display:block;font-size:19px;color:#0f172a}
        .sp-meta{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:10px}
        .sp-meta-card{border:1px solid #d1fae5;border-radius:12px;background:#fff;padding:12px}
        .sp-meta-card span{display:block;font-size:11px;text-transform:uppercase;letter-spacing:.05em;color:#64748b}
        .sp-meta-card strong{display:block;margin-top:4px;font-size:13px;line-height:1.4;color:#0f172a}
        .sp-actions{display:flex;gap:8px;flex-wrap:wrap;align-items:center}
        .sp-qr{display:grid;grid-template-columns:200px minmax(0,1fr);gap:14px;align-items:start}
        .sp-qr-box{border:1px solid #d1fae5;border-radius:14px;background:#fff;padding:12px;display:grid;place-items:center;min-height:200px}
        .sp-qr-box img{display:block;max-width:100%;height:auto}
        .sp-stack{display:grid;gap:10px}
        .sp-card-grid{display:grid;gap:10px}
        .sp-row{display:flex;justify-content:space-between;gap:12px;padding:9px 0;border-bottom:1px dashed #dbe4ee}
        .sp-row:last-child{border-bottom:none}
        .sp-row span{font-size:12px;color:#64748b}
        .sp-row strong{font-size:13px;color:#0f172a;text-align:right}
        .sp-feature-list{display:flex;gap:8px;flex-wrap:wrap;margin-top:14px}
        .sp-feature{padding:7px 11px;border-radius:999px;background:#dbeafe;color:#1d4ed8;font-size:12px;font-weight:700}
        .sp-history-toolbar{display:grid;grid-template-columns:minmax(0,1.5fr) repeat(2,minmax(0,1fr)) auto;gap:10px;align-items:end;margin-top:14px}
        .sp-history-toolbar .field{margin:0}
        .sp-history-wrap{overflow:auto;-webkit-overflow-scrolling:touch;margin-top:14px}
        .sp-history-table{width:100%;border-collapse:collapse}
        .sp-history-table th,.sp-history-table td{padding:11px 10px;border-bottom:1px solid #e2e8f0;font-size:13px;text-align:left;vertical-align:top}
        .sp-history-table th{font-size:11px;text-transform:uppercase;letter-spacing:.05em;color:#64748b}
        .sp-history-actions{display:flex;gap:8px;flex-wrap:wrap;align-items:center}
        .sp-pagination{display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;margin-top:14px}
        .sp-pagination-controls{display:flex;gap:8px;flex-wrap:wrap}
        .sp-page-btn{border:1px solid #cbd5e1;background:#fff;color:#0f172a;border-radius:9px;padding:7px 10px;text-decoration:none;min-width:36px;text-align:center}
        .sp-page-btn.is-active{background:#0f766e;border-color:#0f766e;color:#fff}
        .sp-page-btn.is-disabled{pointer-events:none;opacity:.45}

        @media (max-width:1120px){.sp-layout{grid-template-columns:1fr}}
        @media (max-width:900px){.sp-kpis{grid-template-columns:repeat(2,minmax(0,1fr))}}
        @media (max-width:760px){
            .sp-kpis,.sp-meta,.sp-history-toolbar,.sp-qr{grid-template-columns:1fr}
            .sp-history-wrap{overflow:visible}
            .sp-history-table,.sp-history-table tbody,.sp-history-table tr,.sp-history-table td{display:block;width:100%}
            .sp-history-table thead{display:none}
            .sp-history-table{border-collapse:separate;border-spacing:0 10px}
            .sp-history-table tbody{display:grid;gap:10px}
            .sp-history-table tr{border:1px solid #dbe4ee;border-radius:12px;background:#f8fafc;padding:4px 0}
            .sp-history-table td{display:grid;grid-template-columns:112px minmax(0,1fr);gap:10px;padding:8px 12px;border-bottom:1px dashed #dbe4ee}
            .sp-history-table td:last-child{border-bottom:none}
            .sp-history-table td::before{content:attr(data-label);font-size:11px;font-weight:700;letter-spacing:.04em;text-transform:uppercase;color:#64748b}
        }
    </style>

    <div class="sp-shell">
        <div class="sp-hero">
            <h2>Assinatura e cobrança</h2>
            <p>Centralize aqui o pagamento da assinatura, a conferência do histórico financeiro e o acompanhamento do que está contratado. O fluxo principal permanece simples para a empresa: gerar o PIX, pagar e aguardar a confirmação automática.</p>
        </div>

        <?php if ($subscription === []): ?>
            <div class="card">
                <div class="empty-state">
                    Nenhuma assinatura ativa foi localizada para esta empresa. Vincule um plano no ambiente SaaS antes de liberar esta área.
                </div>
            </div>
        <?php else: ?>
            <div class="sp-kpis">
                <div class="sp-kpi">
                    <span>Plano atual</span>
                    <strong><?= htmlspecialchars((string) ($subscription['plan_name'] ?? '-')) ?></strong>
                    <small><?= htmlspecialchars(status_label('billing_cycle', (string) ($subscription['billing_cycle'] ?? ''))) ?></small>
                </div>
                <div class="sp-kpi">
                    <span>Próximo vencimento</span>
                    <strong><?= htmlspecialchars($formatSubscriptionDate($nextDueDate)) ?></strong>
                    <small>Status da assinatura: <?= htmlspecialchars(status_label('subscription_status', (string) ($subscription['status'] ?? ''))) ?></small>
                </div>
                <div class="sp-kpi">
                    <span>Cobranças em aberto</span>
                    <strong><?= htmlspecialchars((string) ($subscriptionSummary['open_count'] ?? 0)) ?></strong>
                    <small><?= htmlspecialchars($formatSubscriptionMoney($subscriptionSummary['open_amount'] ?? 0)) ?></small>
                </div>
                <div class="sp-kpi">
                    <span>Cobranças em atraso</span>
                    <strong><?= htmlspecialchars((string) ($subscriptionSummary['overdue_count'] ?? 0)) ?></strong>
                    <small>O bloqueio local considera 3 dias de tolerância.</small>
                </div>
            </div>

            <div class="sp-layout">
                <div class="sp-column">
                    <div class="card">
                        <div class="sp-head">
                            <div>
                                <h3>Fluxo de pagamento</h3>
                                <p class="sp-note">A rotina operacional da empresa deve ser objetiva. Gere o PIX da cobrança atual, realize o pagamento no banco e deixe o sistema confirmar o retorno automaticamente.</p>
                            </div>
                        </div>

                        <div class="sp-flow">
                            <div class="sp-step">
                                <div class="sp-step-badge">1</div>
                                <div>
                                    <strong>Gerar o PIX da cobrança atual</strong>
                                    <p>O sistema busca o QR Code e o código copia e cola da cobrança mais recente em aberto.</p>
                                </div>
                            </div>
                            <div class="sp-step">
                                <div class="sp-step-badge">2</div>
                                <div>
                                    <strong>Efetuar o pagamento</strong>
                                    <p>Pague pelo aplicativo do banco usando o QR Code ou o código copia e cola. Não há necessidade de cartão ou autorização recorrente.</p>
                                </div>
                            </div>
                            <div class="sp-step">
                                <div class="sp-step-badge">3</div>
                                <div>
                                    <strong>Aguardar a confirmação</strong>
                                    <p>Após o pagamento, a tela acompanha a confirmação no gateway. A sincronização manual permanece disponível apenas como apoio.</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <?php if ($hasCurrentCharge): ?>
                        <div class="sp-charge">
                            <div class="sp-charge-head">
                                <div>
                                    <strong>Cobrança atual</strong>
                                    <p class="sp-note">
                                        Referência <?= htmlspecialchars(sprintf('%02d/%04d', (int) ($currentCharge['reference_month'] ?? 0), (int) ($currentCharge['reference_year'] ?? 0))) ?>
                                        • vencimento em <?= htmlspecialchars($formatSubscriptionDate($currentCharge['due_date'] ?? null)) ?>
                                    </p>
                                </div>
                                <span class="badge <?= htmlspecialchars(status_badge_class('subscription_payment_status', (string) ($currentCharge['status'] ?? ''))) ?>">
                                    <?= htmlspecialchars(status_label('subscription_payment_status', (string) ($currentCharge['status'] ?? ''))) ?>
                                </span>
                            </div>

                            <div class="sp-meta">
                                <div class="sp-meta-card">
                                    <span>Valor</span>
                                    <strong><?= htmlspecialchars($formatSubscriptionMoney($currentCharge['amount'] ?? 0)) ?></strong>
                                </div>
                                <div class="sp-meta-card">
                                    <span>Método atual</span>
                                    <strong><?= htmlspecialchars($paymentMethodLabels[(string) ($currentCharge['payment_method'] ?? '')] ?? 'Pix') ?></strong>
                                </div>
                                <div class="sp-meta-card">
                                    <span>Fluxo</span>
                                    <strong><?= htmlspecialchars($currentGatewayPaymentId !== '' ? 'Automático via gateway' : 'Manual sem vínculo com gateway') ?></strong>
                                </div>
                            </div>

                            <div class="sp-actions">
                                <form method="POST" action="<?= htmlspecialchars(base_url('/admin/dashboard/subscription/pix/generate')) ?>">
                                    <?= form_security_fields('dashboard.subscription.pix.generate.' . $currentChargeId) ?>
                                    <input type="hidden" name="subscription_payment_id" value="<?= htmlspecialchars((string) $currentChargeId) ?>">
                                    <input type="hidden" name="return_query" value="<?= htmlspecialchars($returnSubscriptionQuery) ?>">
                                    <button class="btn" type="submit"><?= $currentPixImage !== '' ? 'Atualizar QR Code PIX' : 'Gerar QR Code PIX' ?></button>
                                </form>

                                <?php if ($currentPixTicketUrl !== ''): ?>
                                    <a class="btn secondary" href="<?= htmlspecialchars($currentPixTicketUrl) ?>" target="_blank" rel="noopener">Abrir tela do PIX</a>
                                <?php endif; ?>

                                <form method="POST" action="<?= htmlspecialchars(base_url('/admin/dashboard/subscription/gateway/sync')) ?>">
                                    <?= form_security_fields('dashboard.subscription.gateway.sync') ?>
                                    <input type="hidden" name="return_query" value="<?= htmlspecialchars($returnSubscriptionQuery) ?>">
                                    <button class="btn secondary" type="submit">Sincronizar agora</button>
                                </form>
                            </div>

                            <?php if ($shouldAutoPollPixStatus): ?>
                                <div class="sp-note" id="pix-auto-status-note">
                                    Aguardando a confirmação automática do pagamento no gateway. Esta tela consulta o status sozinha a cada 20 segundos.
                                </div>
                            <?php endif; ?>

                            <?php if ($currentPixImage !== '' || $currentPixPayload !== ''): ?>
                                <div class="sp-qr">
                                    <div class="sp-qr-box">
                                        <?php if ($currentPixImage !== ''): ?>
                                            <img src="data:image/png;base64,<?= htmlspecialchars($currentPixImage) ?>" alt="QR Code PIX">
                                        <?php elseif ($currentPixFallbackImageUrl !== ''): ?>
                                            <img src="<?= htmlspecialchars($currentPixFallbackImageUrl) ?>" alt="QR Code PIX">
                                        <?php else: ?>
                                            <span class="sp-note">O QR Code real ainda não foi retornado pelo gateway.</span>
                                        <?php endif; ?>
                                    </div>

                                    <div class="sp-stack">
                                        <div class="field">
                                            <label for="subscription_pix_copy_paste">PIX copia e cola</label>
                                            <textarea id="subscription_pix_copy_paste" readonly><?= htmlspecialchars($currentPixPayload !== '' ? $currentPixPayload : (string) ($currentCharge['pix_code'] ?? '')) ?></textarea>
                                        </div>
                                        <div class="sp-note">
                                            Depois do pagamento, aguarde alguns instantes para a confirmação automática. Se o status não mudar, use a sincronização manual. Persistindo a divergência, acione a equipe técnica.
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                        <div class="card">
                            <div class="empty-state">
                                Não existe cobrança em aberto neste momento. Se a assinatura já foi regularizada, aguarde o próximo ciclo.
                            </div>
                        </div>
                    <?php endif; ?>

                    <div class="card">
                        <div class="sp-head">
                            <div>
                                <h3>Histórico financeiro</h3>
                                <p class="sp-note">Audite as faturas já registradas, filtre o histórico e acesse o recibo sempre que o pagamento estiver quitado.</p>
                            </div>
                        </div>

                        <form method="GET" action="<?= htmlspecialchars(base_url('/admin/dashboard')) ?>">
                            <input type="hidden" name="section" value="subscription">
                            <div class="sp-history-toolbar">
                                <div class="field">
                                    <label for="subscription_history_search">Busca</label>
                                    <input id="subscription_history_search" name="subscription_history_search" type="text" value="<?= htmlspecialchars($historySearch) ?>" placeholder="Referência, gateway, Pix ou origem">
                                </div>
                                <div class="field">
                                    <label for="subscription_history_status">Status</label>
                                    <select id="subscription_history_status" name="subscription_history_status">
                                        <?php foreach ($historyStatusOptions as $value => $label): ?>
                                            <option value="<?= htmlspecialchars($value) ?>" <?= $historyStatus === (string) $value ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="field">
                                    <label for="subscription_history_method">Método</label>
                                    <select id="subscription_history_method" name="subscription_history_method">
                                        <?php foreach ($historyMethodOptions as $value => $label): ?>
                                            <option value="<?= htmlspecialchars($value) ?>" <?= $historyMethod === (string) $value ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="sp-actions">
                                    <button class="btn" type="submit">Filtrar</button>
                                    <a class="btn secondary" href="<?= htmlspecialchars(base_url('/admin/dashboard?section=subscription')) ?>">Limpar</a>
                                </div>
                            </div>
                        </form>

                        <?php if ($subscriptionHistory === []): ?>
                            <div class="empty-state" style="margin-top:14px">
                                Ainda não existe histórico financeiro registrado para esta assinatura.
                            </div>
                        <?php else: ?>
                            <div class="sp-history-wrap">
                                <table class="sp-history-table">
                                    <thead>
                                        <tr>
                                            <th>Referência</th>
                                            <th>Vencimento</th>
                                            <th>Valor</th>
                                            <th>Status</th>
                                            <th>Método</th>
                                            <th>Pago em</th>
                                            <th>Referência externa</th>
                                            <th>Ações</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($subscriptionHistory as $payment): ?>
                                            <?php $paymentStatus = (string) ($payment['status'] ?? ''); ?>
                                            <tr>
                                                <td data-label="Referência"><?= htmlspecialchars(sprintf('%02d/%04d', (int) ($payment['reference_month'] ?? 0), (int) ($payment['reference_year'] ?? 0))) ?></td>
                                                <td data-label="Vencimento"><?= htmlspecialchars($formatSubscriptionDate($payment['due_date'] ?? null)) ?></td>
                                                <td data-label="Valor"><?= htmlspecialchars($formatSubscriptionMoney($payment['amount'] ?? 0)) ?></td>
                                                <td data-label="Status">
                                                    <span class="badge <?= htmlspecialchars(status_badge_class('subscription_payment_status', $paymentStatus)) ?>">
                                                        <?= htmlspecialchars(status_label('subscription_payment_status', $paymentStatus)) ?>
                                                    </span>
                                                </td>
                                                <td data-label="Método"><?= htmlspecialchars($paymentMethodLabels[(string) ($payment['payment_method'] ?? '')] ?? 'Não definido') ?></td>
                                                <td data-label="Pago em"><?= htmlspecialchars($formatSubscriptionDate($payment['paid_at'] ?? null, true)) ?></td>
                                                <td data-label="Referência externa"><?= htmlspecialchars((string) ($payment['transaction_reference'] ?? '-')) ?></td>
                                                <td data-label="Ações">
                                                    <?php if ($paymentStatus === 'pago'): ?>
                                                        <div class="sp-history-actions">
                                                            <a class="btn secondary small" href="<?= htmlspecialchars($buildSubscriptionReceiptUrl((int) ($payment['id'] ?? 0))) ?>">Recibo</a>
                                                        </div>
                                                    <?php else: ?>
                                                        <span class="sp-note">Sem ação</span>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>

                            <div class="sp-pagination">
                                <div class="sp-note">
                                    <?php if ($historyTotal > 0): ?>
                                        Exibindo <?= htmlspecialchars((string) $historyFrom) ?> a <?= htmlspecialchars((string) $historyTo) ?> de <?= htmlspecialchars((string) $historyTotal) ?> registros
                                    <?php else: ?>
                                        Nenhum registro encontrado
                                    <?php endif; ?>
                                </div>
                                <div class="sp-pagination-controls">
                                    <a class="sp-page-btn<?= $historyPage <= 1 ? ' is-disabled' : '' ?>" href="<?= htmlspecialchars($buildSubscriptionUrl(['subscription_history_page' => max(1, $historyPage - 1)])) ?>">Anterior</a>
                                    <?php foreach ($historyPages as $pageNumber): ?>
                                        <a class="sp-page-btn<?= $pageNumber === $historyPage ? ' is-active' : '' ?>" href="<?= htmlspecialchars($buildSubscriptionUrl(['subscription_history_page' => $pageNumber])) ?>"><?= htmlspecialchars((string) $pageNumber) ?></a>
                                    <?php endforeach; ?>
                                    <a class="sp-page-btn<?= $historyPage >= $historyLastPage ? ' is-disabled' : '' ?>" href="<?= htmlspecialchars($buildSubscriptionUrl(['subscription_history_page' => min($historyLastPage, $historyPage + 1)])) ?>">Próxima</a>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="sp-column">
                    <div class="card">
                        <div class="sp-head">
                            <div>
                                <h3>Resumo da assinatura</h3>
                                <p class="sp-note">Informações essenciais para leitura rápida do contrato e da situação de cobrança.</p>
                            </div>
                        </div>

                        <div class="sp-card-grid" style="margin-top:14px">
                            <div class="sp-row">
                                <span>Status da assinatura</span>
                                <strong><?= htmlspecialchars(status_label('subscription_status', (string) ($subscription['status'] ?? ''))) ?></strong>
                            </div>
                            <div class="sp-row">
                                <span>Status da empresa</span>
                                <strong><?= htmlspecialchars(status_label('company_subscription_status', (string) ($subscription['company_subscription_status'] ?? ''))) ?></strong>
                            </div>
                            <div class="sp-row">
                                <span>Próximo vencimento</span>
                                <strong><?= htmlspecialchars($formatSubscriptionDate($nextDueDate)) ?></strong>
                            </div>
                            <div class="sp-row">
                                <span>Pagamento preferencial</span>
                                <strong><?= htmlspecialchars($paymentMethodLabels[(string) ($subscription['preferred_payment_method'] ?? '')] ?? 'Não definido') ?></strong>
                            </div>
                            <div class="sp-row">
                                <span>Empresa bloqueada</span>
                                <strong><?= !empty($subscriptionBillingAccess['is_blocked']) ? 'Sim' : 'Não' ?></strong>
                            </div>
                        </div>
                    </div>

                    <div class="card">
                        <div class="sp-head">
                            <div>
                                <h3>Recursos contratados</h3>
                                <p class="sp-note">Resumo comercial do que está liberado para esta empresa no plano atual.</p>
                            </div>
                        </div>

                        <?php if ($subscriptionFeatures === []): ?>
                            <div class="empty-state" style="margin-top:14px">
                                Nenhum recurso resumido foi encontrado para este plano.
                            </div>
                        <?php else: ?>
                            <div class="sp-feature-list">
                                <?php foreach ($subscriptionFeatures as $feature): ?>
                                    <span class="sp-feature"><?= htmlspecialchars((string) $feature) ?></span>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="card">
                        <div class="sp-head">
                            <div>
                                <h3>Regra operacional</h3>
                                <p class="sp-note">O ambiente da empresa deve consumir o fluxo mais simples possível, enquanto o SaaS trata exceções e divergências.</p>
                            </div>
                        </div>

                        <div class="sp-flow">
                            <div class="sp-step">
                                <div class="sp-step-badge">A</div>
                                <div>
                                    <strong>Confirmação automática</strong>
                                    <p>A cobrança é acompanhada pelo gateway e pelo servidor para evitar baixa manual em operações normais.</p>
                                </div>
                            </div>
                            <div class="sp-step">
                                <div class="sp-step-badge">B</div>
                                <div>
                                    <strong>SaaS trata exceções</strong>
                                    <p>O administrador central acompanha atrasos, divergências e regularizações fora do fluxo padrão.</p>
                                </div>
                            </div>
                            <div class="sp-step">
                                <div class="sp-step-badge">C</div>
                                <div>
                                    <strong>Empresa acompanha o histórico</strong>
                                    <p>A consulta de recibos e faturas fica disponível para conferência, auditoria e comprovação do pagamento.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <?php if ($shouldAutoPollPixStatus): ?>
        <script>
            (function () {
                const paymentId = <?= json_encode($currentChargeId) ?>;
                const endpoint = <?= json_encode(base_url('/admin/dashboard/subscription/pix/status?subscription_payment_id=' . $currentChargeId)) ?>;
                const note = document.getElementById('pix-auto-status-note');
                let polling = false;

                const poll = async () => {
                    if (polling) {
                        return;
                    }

                    polling = true;
                    try {
                        const response = await fetch(endpoint, {
                            method: 'GET',
                            credentials: 'same-origin',
                            headers: {
                                'Accept': 'application/json'
                            }
                        });

                        const payload = await response.json();
                        if (!payload || payload.ok !== true) {
                            if (note && payload && payload.message) {
                                note.textContent = payload.message;
                            }
                            return;
                        }

                        if ((payload.payment_id || 0) !== paymentId) {
                            return;
                        }

                        if (note && payload.status_label) {
                            note.textContent = 'Status atual: ' + payload.status_label + (payload.gateway_status ? ' no gateway: ' + payload.gateway_status : '');
                        }

                        if (payload.status === 'pago') {
                            window.location.reload();
                        }
                    } catch (error) {
                        if (note) {
                            note.textContent = 'Falha ao consultar automaticamente o pagamento. Tente atualizar a tela em alguns instantes.';
                        }
                    } finally {
                        polling = false;
                    }
                };

                window.setInterval(poll, 20000);
                window.setTimeout(poll, 8000);
            })();
        </script>
    <?php endif; ?>
</section>
