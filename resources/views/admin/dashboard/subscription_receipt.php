<?php
$context = is_array($context ?? null) ? $context : [];
$payment = is_array($context['payment'] ?? null) ? $context['payment'] : [];
$paymentDetails = is_array($context['payment_details'] ?? null) ? $context['payment_details'] : [];
$saasSignature = is_array($context['saas_signature'] ?? null) ? $context['saas_signature'] : [];
$backUrl = trim((string) ($backUrl ?? base_url('/admin/dashboard?section=subscription')));

$companyName = trim((string) ($payment['company_name'] ?? 'Empresa'));
$companyLegalName = trim((string) ($payment['company_legal_name'] ?? ''));
$companyDocument = trim((string) ($payment['company_document_number'] ?? ''));
$companyEmail = trim((string) ($payment['company_email'] ?? ''));
$companyPhone = trim((string) ($payment['company_phone'] ?? ($payment['company_whatsapp'] ?? '')));
$companyLogoPath = trim((string) ($payment['company_logo_path'] ?? ''));
$companyLogoUrl = $companyLogoPath !== '' ? company_image_url($companyLogoPath) : '';
$saasLogoUrl = base_url('/img/logo-mesimenu.png');

$receiptNumber = trim((string) ($context['receipt_number'] ?? ''));
$referenceLabel = trim((string) ($context['reference_label'] ?? ''));
$paymentStatusLabel = trim((string) ($context['status_label'] ?? 'Pago'));
$paymentMethodLabel = trim((string) ($context['payment_method_label'] ?? 'Nao definido'));
$billingCycleLabel = trim((string) ($context['billing_cycle_label'] ?? '-'));
$chargeOriginLabel = trim((string) ($context['charge_origin_label'] ?? '-'));
$generatedAt = trim((string) ($context['generated_at'] ?? date('Y-m-d H:i:s')));

$formatMoney = static fn (mixed $value): string => 'R$ ' . number_format((float) $value, 2, ',', '.');
$formatDate = static function (mixed $value, bool $withTime = false): string {
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

$proofReference = trim((string) ($payment['transaction_reference'] ?? ''));
$gatewayPaymentId = trim((string) ($payment['gateway_payment_id'] ?? ''));
$gatewayStatus = trim((string) ($payment['gateway_status'] ?? ''));
$gatewayProvider = trim((string) ($payment['gateway_provider'] ?? ''));
$gatewaySyncedAt = trim((string) ($payment['gateway_last_synced_at'] ?? ''));
$subscriptionLabel = trim((string) ($payment['plan_name'] ?? 'Plano'));
$subscriptionPeriod = $subscriptionLabel !== '' ? ($subscriptionLabel . ' - ' . $billingCycleLabel) : $billingCycleLabel;
$modeLabel = trim((string) ($paymentDetails['mode'] ?? ''));
$sourceLabel = trim((string) ($paymentDetails['source'] ?? ''));
$paidAt = $formatDate($payment['paid_at'] ?? null, true);
$dueDate = $formatDate($payment['due_date'] ?? null);
$amountLabel = $formatMoney($payment['amount'] ?? 0);

$signatureMode = trim((string) ($saasSignature['mode'] ?? 'institutional'));
$signatureName = trim((string) ($saasSignature['name'] ?? 'MesiMenu SaaS'));
$signatureEmail = trim((string) ($saasSignature['email'] ?? ''));
$signatureRole = trim((string) ($saasSignature['role_name'] ?? 'Administrador SaaS'));
$signatureSignedAt = $formatDate($saasSignature['signed_at'] ?? null, true);
$signatureType = trim((string) ($saasSignature['type'] ?? 'system_issued'));
$signatureHashBase = implode('|', [
    $receiptNumber,
    (string) ($payment['id'] ?? 0),
    $signatureName,
    $signatureEmail,
    (string) ($saasSignature['signed_at'] ?? $generatedAt),
    $signatureType,
]);
$signatureHash = strtoupper(substr(sha1($signatureHashBase), 0, 16));
?>

<style>
    .receipt-page{display:grid;gap:16px}
    .receipt-screen-only{display:block}
    .receipt-topbar{display:flex;justify-content:space-between;align-items:flex-start;gap:12px;flex-wrap:wrap}
    .receipt-actions{display:flex;gap:8px;flex-wrap:wrap}
    .receipt-layout{display:grid;grid-template-columns:minmax(240px,280px) minmax(0,1fr);gap:16px;align-items:start}
    .receipt-sidebar{display:grid;gap:12px}
    .receipt-summary-grid{display:grid;grid-template-columns:1fr;gap:8px}
    .receipt-summary-card{border:1px solid #e2e8f0;border-radius:12px;background:#f8fafc;padding:10px}
    .receipt-summary-card strong{display:block;margin-bottom:4px;font-size:11px;color:#64748b;text-transform:uppercase;letter-spacing:.05em}
    .receipt-summary-card span{display:block;font-size:13px;color:#0f172a;word-break:break-word}
    .receipt-summary-note{margin:0;font-size:12px;line-height:1.55;color:#475569}

    .receipt-stage-card{padding:16px !important}
    .receipt-stage-head{display:flex;justify-content:space-between;align-items:center;gap:10px;flex-wrap:wrap;margin-bottom:10px}
    .receipt-stage-head strong{font-size:15px}
    .receipt-stage-head span{font-size:12px;color:#64748b}
    .receipt-stage{display:grid;place-items:center;padding:14px;border:1px solid #e2e8f0;border-radius:16px;background:linear-gradient(180deg,#f8fafc 0%,#f1f5f9 100%)}
    .receipt-paper{
        width:min(100%,186mm);
        background:#fff;
        border:1px solid #d1d5db;
        border-radius:14px;
        box-shadow:0 12px 28px rgba(15,23,42,.08);
        padding:11mm 11mm 9mm;
        display:grid;
        gap:10px;
    }

    .receipt-header{display:grid;grid-template-columns:minmax(0,1.2fr) minmax(225px,.8fr);gap:12px;align-items:start}
    .receipt-brand{display:grid;gap:10px}
    .receipt-brand-block{display:flex;align-items:center;gap:12px;padding:12px;border:1px solid #dbeafe;border-radius:14px;background:linear-gradient(135deg,#eff6ff 0%,#dbeafe 100%)}
    .receipt-brand-block img{width:76px;height:auto;display:block}
    .receipt-brand-copy strong{display:block;font-size:17px;color:#0f172a}
    .receipt-brand-copy span{display:block;margin-top:3px;font-size:11px;color:#475569;text-transform:uppercase;letter-spacing:.08em}

    .receipt-company{display:flex;align-items:flex-start;gap:12px}
    .receipt-company-logo{width:64px;height:64px;border-radius:14px;object-fit:cover;border:1px solid #e5e7eb;background:#fff;flex:0 0 auto}
    .receipt-company-copy h1{margin:0;font-size:24px;line-height:1.05;color:#0f172a}
    .receipt-company-copy p{margin:5px 0 0;font-size:12px;line-height:1.5;color:#475569}

    .receipt-docbox{border:1px solid #dbe2ea;border-radius:14px;background:#f8fafc;padding:14px;display:grid;gap:8px}
    .receipt-docbox-badge{display:inline-flex;width:max-content;padding:5px 10px;border-radius:999px;background:#dcfce7;color:#166534;font-size:12px;font-weight:700}
    .receipt-docbox h2{margin:0;font-size:28px;line-height:1;color:#0f172a}
    .receipt-docbox p{margin:0;font-size:12px;line-height:1.45;color:#475569}

    .receipt-kpis{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:8px}
    .receipt-kpi{border:1px solid #e2e8f0;border-radius:10px;background:#f8fafc;padding:10px}
    .receipt-kpi strong{display:block;margin-bottom:4px;font-size:10px;color:#64748b;text-transform:uppercase;letter-spacing:.05em}
    .receipt-kpi span{display:block;font-size:13px;color:#0f172a;word-break:break-word}

    .receipt-proof{border:1px solid #bfdbfe;border-radius:12px;background:#eff6ff;padding:12px;color:#1e3a8a;font-size:12px;line-height:1.55}
    .receipt-proof strong{color:#1e3a8a}

    .receipt-columns{display:grid;grid-template-columns:1fr 1fr;gap:10px}
    .receipt-block{border:1px solid #e2e8f0;border-radius:12px;background:#fff;padding:12px}
    .receipt-block h3{margin:0 0 8px;font-size:13px;color:#0f172a}
    .receipt-rows{display:grid;gap:5px}
    .receipt-row{display:flex;justify-content:space-between;gap:12px;padding:5px 0;border-bottom:1px dashed #dbe4ee}
    .receipt-row:last-child{border-bottom:none}
    .receipt-row span{font-size:11px;color:#64748b}
    .receipt-row strong{font-size:12px;color:#0f172a;text-align:right;word-break:break-word}

    .receipt-footer{border-top:1px solid #e2e8f0;padding-top:9px;display:grid;gap:8px}
    .receipt-footer p{margin:0;font-size:10.5px;line-height:1.45;color:#475569}
    .receipt-signatures{display:grid;grid-template-columns:1.15fr .85fr;gap:14px}
    .receipt-signature{padding-top:14px;border-top:1px solid #cbd5e1;font-size:10.5px;color:#475569}
    .receipt-signature strong{display:block;margin-top:4px;font-size:12px;color:#0f172a}
    .receipt-signature-panel{border:1px solid #dbeafe;border-radius:12px;background:linear-gradient(180deg,#f8fbff 0%,#eef6ff 100%);padding:12px}
    .receipt-signature-panel h4{margin:0 0 8px;font-size:12px;color:#1d4ed8;text-transform:uppercase;letter-spacing:.06em}
    .receipt-signature-panel p{margin:0;font-size:11px;line-height:1.5;color:#334155}
    .receipt-signature-mark{margin-top:10px;padding-top:10px;border-top:1px dashed #bfdbfe;font-size:12px;color:#0f172a}
    .receipt-signature-mark strong{display:block;font-size:16px;letter-spacing:.08em;color:#0f172a}
    .receipt-signature-meta{display:grid;gap:4px;margin-top:8px}
    .receipt-signature-meta span{font-size:10.5px;color:#475569}

    @page{size:A4 portrait;margin:8mm}

    @media (max-width:980px){
        .receipt-layout{grid-template-columns:1fr}
        .receipt-header,.receipt-columns,.receipt-signatures{grid-template-columns:1fr}
        .receipt-kpis{grid-template-columns:1fr 1fr}
    }

    @media print{
        html,body{margin:0 !important;padding:0 !important;background:#fff !important}
        body{background:#fff !important}
        .shell{display:block !important}
        .shell > aside{display:none !important}
        .shell-main{display:block !important;min-height:auto !important}
        .shell-header,.shell-footer,.flash{display:none !important}
        main{padding:0 !important;overflow:visible !important}
        .card{border:none !important;box-shadow:none !important;background:#fff !important}
        .receipt-screen-only{display:none !important}
        .receipt-page,.receipt-layout{display:block}
        .receipt-stage-card{padding:0 !important}
        .receipt-stage{padding:0 !important;border:0 !important;background:#fff !important}
        .receipt-paper{
            width:100%;
            border:none;
            border-radius:0;
            box-shadow:none;
            padding:0;
            gap:8px;
        }
        .receipt-header{grid-template-columns:1.18fr .82fr}
        .receipt-kpis{grid-template-columns:repeat(4,minmax(0,1fr))}
        .receipt-columns{grid-template-columns:1fr 1fr}
        .receipt-brand-block,.receipt-docbox,.receipt-kpi,.receipt-proof,.receipt-block,.receipt-signature-panel{break-inside:avoid;page-break-inside:avoid}
        .receipt-company-copy h1{font-size:22px}
        .receipt-docbox h2{font-size:26px}
        .receipt-row{padding:4px 0}
        .receipt-footer p,.receipt-signature,.receipt-signature-panel p,.receipt-signature-meta span{font-size:10px}
    }
</style>

<div class="receipt-page">
    <div class="topbar receipt-screen-only receipt-topbar">
        <div>
            <h1 style="margin:0">Recibo da assinatura</h1>
            <p style="margin:6px 0 0;color:#64748b">Previa organizada para conferencia e impressao em folha unica.</p>
        </div>
        <div class="receipt-actions">
            <a class="btn secondary" href="<?= htmlspecialchars($backUrl) ?>">Voltar para assinatura</a>
            <button class="btn" type="button" onclick="window.print()">Imprimir recibo</button>
        </div>
    </div>

    <div class="receipt-layout">
        <aside class="card receipt-sidebar receipt-screen-only">
            <h3 style="margin:0">Resumo do recibo</h3>
            <div>
                <span class="badge <?= htmlspecialchars(status_badge_class('subscription_payment_status', (string) ($payment['status'] ?? 'pago'))) ?>">
                    <?= htmlspecialchars($paymentStatusLabel) ?>
                </span>
            </div>

            <div class="receipt-summary-grid">
                <div class="receipt-summary-card"><strong>Recibo</strong><span><?= htmlspecialchars($receiptNumber) ?></span></div>
                <div class="receipt-summary-card"><strong>Referencia</strong><span><?= htmlspecialchars($referenceLabel) ?></span></div>
                <div class="receipt-summary-card"><strong>Valor</strong><span><?= htmlspecialchars($amountLabel) ?></span></div>
                <div class="receipt-summary-card"><strong>Pago em</strong><span><?= htmlspecialchars($paidAt) ?></span></div>
                <div class="receipt-summary-card"><strong>Metodo</strong><span><?= htmlspecialchars($paymentMethodLabel) ?></span></div>
                <div class="receipt-summary-card"><strong>Plano</strong><span><?= htmlspecialchars($subscriptionPeriod) ?></span></div>
            </div>

            <p class="receipt-summary-note">
                O modelo impresso foi condensado para caber em uma unica folha A4, preservando comprovacao, rastreabilidade e identificacao do responsavel emissor.
            </p>
        </aside>

        <section class="card receipt-stage-card">
            <div class="receipt-stage-head receipt-screen-only">
                <strong>Previa do recibo</strong>
                <span>Este e o documento final enviado para impressao.</span>
            </div>

            <div class="receipt-stage">
                <section class="receipt-paper">
                    <header class="receipt-header">
                        <div class="receipt-brand">
                            <div class="receipt-brand-block" aria-label="MesiMenu SaaS">
                                <img src="<?= htmlspecialchars($saasLogoUrl) ?>" alt="Logo do MesiMenu SaaS">
                                <div class="receipt-brand-copy">
                                    <strong>MesiMenu SaaS</strong>
                                    <span>Plataforma de gestao e cobranca</span>
                                </div>
                            </div>

                            <div class="receipt-company">
                                <?php if ($companyLogoUrl !== ''): ?>
                                    <img class="receipt-company-logo" src="<?= htmlspecialchars($companyLogoUrl) ?>" alt="Logo da empresa">
                                <?php endif; ?>
                                <div class="receipt-company-copy">
                                    <h1><?= htmlspecialchars($companyName) ?></h1>
                                    <p>
                                        <?= htmlspecialchars($companyLegalName !== '' ? $companyLegalName : $companyName) ?><br>
                                        Documento: <?= htmlspecialchars($companyDocument !== '' ? $companyDocument : '-') ?><br>
                                        E-mail: <?= htmlspecialchars($companyEmail !== '' ? $companyEmail : '-') ?><?php if ($companyPhone !== ''): ?> | Telefone: <?= htmlspecialchars($companyPhone) ?><?php endif; ?>
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div class="receipt-docbox">
                            <div class="receipt-docbox-badge"><?= htmlspecialchars($paymentStatusLabel) ?></div>
                            <h2>Recibo</h2>
                            <p>No <?= htmlspecialchars($receiptNumber) ?></p>
                            <p>Emitido em <?= htmlspecialchars($formatDate($generatedAt, true)) ?></p>
                        </div>
                    </header>

                    <section class="receipt-kpis">
                        <div class="receipt-kpi"><strong>Referencia da fatura</strong><span><?= htmlspecialchars($referenceLabel) ?></span></div>
                        <div class="receipt-kpi"><strong>Valor liquidado</strong><span><?= htmlspecialchars($amountLabel) ?></span></div>
                        <div class="receipt-kpi"><strong>Data do pagamento</strong><span><?= htmlspecialchars($paidAt) ?></span></div>
                        <div class="receipt-kpi"><strong>Vencimento</strong><span><?= htmlspecialchars($dueDate) ?></span></div>
                    </section>

                    <section class="receipt-proof">
                        Declaramos, para fins de comprovacao, que a empresa <strong><?= htmlspecialchars($companyName) ?></strong>
                        quitou a fatura da assinatura referente ao periodo <strong><?= htmlspecialchars($referenceLabel) ?></strong>,
                        no valor de <strong><?= htmlspecialchars($amountLabel) ?></strong>,
                        vinculada ao plano <strong><?= htmlspecialchars($subscriptionPeriod) ?></strong>.
                    </section>

                    <section class="receipt-columns">
                        <div class="receipt-block">
                            <h3>Dados da liquidacao</h3>
                            <div class="receipt-rows">
                                <div class="receipt-row"><span>Metodo</span><strong><?= htmlspecialchars($paymentMethodLabel) ?></strong></div>
                                <div class="receipt-row"><span>Origem da cobranca</span><strong><?= htmlspecialchars($chargeOriginLabel) ?></strong></div>
                                <div class="receipt-row"><span>Referencia externa</span><strong><?= htmlspecialchars($proofReference !== '' ? $proofReference : '-') ?></strong></div>
                                <div class="receipt-row"><span>Modo registrado</span><strong><?= htmlspecialchars($modeLabel !== '' ? $modeLabel : '-') ?></strong></div>
                                <div class="receipt-row"><span>Origem registrada</span><strong><?= htmlspecialchars($sourceLabel !== '' ? $sourceLabel : '-') ?></strong></div>
                            </div>
                        </div>

                        <div class="receipt-block">
                            <h3>Rastreabilidade tecnica</h3>
                            <div class="receipt-rows">
                                <div class="receipt-row"><span>ID do pagamento no gateway</span><strong><?= htmlspecialchars($gatewayPaymentId !== '' ? $gatewayPaymentId : '-') ?></strong></div>
                                <div class="receipt-row"><span>Status do gateway</span><strong><?= htmlspecialchars($gatewayStatus !== '' ? $gatewayStatus : '-') ?></strong></div>
                                <div class="receipt-row"><span>Provedor</span><strong><?= htmlspecialchars($gatewayProvider !== '' ? $gatewayProvider : '-') ?></strong></div>
                                <div class="receipt-row"><span>Ultima sincronizacao</span><strong><?= htmlspecialchars($formatDate($gatewaySyncedAt, true)) ?></strong></div>
                                <div class="receipt-row"><span>Assinatura</span><strong><?= htmlspecialchars($subscriptionLabel !== '' ? $subscriptionLabel : '-') ?></strong></div>
                            </div>
                        </div>
                    </section>

                    <footer class="receipt-footer">
                        <p>Este recibo foi gerado automaticamente pelo MesiMenu SaaS com base no historico financeiro da assinatura vinculada a empresa.</p>
                        <p>Para auditoria e comprovacao, preserve este documento juntamente com a referencia externa do pagamento e o identificador do gateway quando houver integracao ativa.</p>

                        <div class="receipt-signatures">
                            <div class="receipt-signature-panel">
                                <h4>Assinatura eletronica do administrador SaaS</h4>
                                <p>
                                    <?php if ($signatureMode === 'named'): ?>
                                        Emissao vinculada ao operador SaaS responsavel pela baixa ou validacao manual deste pagamento.
                                    <?php else: ?>
                                        Pagamento sem operador SaaS nominal registrado no historico. A emissao abaixo representa a assinatura institucional do MesiMenu SaaS.
                                    <?php endif; ?>
                                </p>
                                <div class="receipt-signature-mark">
                                    <strong><?= htmlspecialchars($signatureName) ?></strong>
                                    <?= htmlspecialchars($signatureRole) ?>
                                </div>
                                <div class="receipt-signature-meta">
                                    <span>E-mail: <?= htmlspecialchars($signatureEmail !== '' ? $signatureEmail : 'nao informado') ?></span>
                                    <span>Assinado em: <?= htmlspecialchars($signatureSignedAt !== '-' ? $signatureSignedAt : $formatDate($generatedAt, true)) ?></span>
                                    <span>Hash da assinatura: <?= htmlspecialchars($signatureHash) ?></span>
                                </div>
                            </div>

                            <div class="receipt-signature">
                                Emitente
                                <strong>MesiMenu SaaS</strong>
                            </div>
                        </div>
                    </footer>
                </section>
            </div>
        </section>
    </div>
</div>
