<?php
$stockPanel = is_array($stockPanel ?? null) ? $stockPanel : [];
$summary = is_array($stockPanel['summary'] ?? null) ? $stockPanel['summary'] : [];
$items = is_array($stockPanel['items'] ?? null) ? $stockPanel['items'] : [];
$movements = is_array($stockPanel['movements'] ?? null) ? $stockPanel['movements'] : [];
$filters = is_array($stockPanel['filters'] ?? null) ? $stockPanel['filters'] : [];
$itemPagination = is_array($stockPanel['item_pagination'] ?? null) ? $stockPanel['item_pagination'] : [];
$movementPagination = is_array($stockPanel['movement_pagination'] ?? null) ? $stockPanel['movement_pagination'] : [];
$products = is_array($stockPanel['products'] ?? null) ? $stockPanel['products'] : [];
$unitOptions = is_array($stockPanel['unit_options'] ?? null) ? $stockPanel['unit_options'] : [];
$referenceTypeOptions = is_array($stockPanel['reference_type_options'] ?? null) ? $stockPanel['reference_type_options'] : [];
$canManageStock = (bool) ($canManageStock ?? false);

$stockSearch = trim((string) ($filters['search'] ?? ''));
$stockStatus = trim((string) ($filters['status'] ?? ''));
$stockAlert = trim((string) ($filters['alert'] ?? ''));
$stockMovementType = trim((string) ($filters['movement_type'] ?? ''));

$statusOptions = [
    '' => 'Todos os status',
    'ativo' => 'Ativos',
    'inativo' => 'Inativos',
];

$alertOptions = [
    '' => 'Todos os alertas',
    'low' => 'Estoque baixo',
    'out' => 'Sem saldo',
];

$movementTypeOptions = [
    '' => 'Todos os tipos',
    'entry' => 'Entradas',
    'exit' => 'Saidas',
    'adjustment' => 'Ajustes',
];

$referenceTypeLabels = [
    'manual' => 'Manual',
    'purchase' => 'Compra',
    'consumption' => 'Consumo',
    'inventory_count' => 'Inventario',
    'waste' => 'Perda',
    'production' => 'Producao',
];

$currentQuery = is_array($_GET ?? null) ? $_GET : [];
$returnQuery = http_build_query($currentQuery);

$buildStockUrl = static function (array $overrides = []) use ($currentQuery): string {
    $params = array_merge($currentQuery, $overrides);

    foreach ($params as $key => $value) {
        if (in_array($key, ['stock_search', 'stock_status', 'stock_alert', 'stock_page', 'stock_movement_type', 'stock_movement_page'], true)
            && trim((string) $value) === '') {
            unset($params[$key]);
        }
    }

    $query = http_build_query($params);
    return base_url('/admin/stock' . ($query !== '' ? '?' . $query : ''));
};

$formatDate = static function (mixed $value, bool $withTime = true): string {
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

$formatQty = static function (mixed $value): string {
    return number_format((float) $value, 3, ',', '.');
};

$stockAlertMeta = static function (array $item): array {
    $alert = (string) ($item['stock_alert'] ?? 'normal');

    return match ($alert) {
        'out' => ['label' => 'Sem saldo', 'class' => 'badge status-overdue', 'note' => 'Sem disponibilidade operacional.'],
        'low' => ['label' => 'Estoque baixo', 'class' => 'badge status-pending', 'note' => 'Abaixo do minimo definido.'],
        default => ['label' => 'Controlado', 'class' => 'badge status-active', 'note' => 'Saldo acima do minimo.'],
    };
};
?>

<style>
    .stock-page{display:grid;gap:16px}
    .stock-hero{border:1px solid #bfdbfe;background:linear-gradient(118deg,#0f172a 0%,#1d4ed8 44%,#0f766e 100%);color:#fff;border-radius:18px;padding:20px;position:relative;overflow:hidden}
    .stock-hero:before{content:"";position:absolute;top:-50px;right:-30px;width:210px;height:210px;border-radius:999px;background:rgba(255,255,255,.1)}
    .stock-hero:after{content:"";position:absolute;bottom:-76px;left:-34px;width:180px;height:180px;border-radius:999px;background:rgba(255,255,255,.08)}
    .stock-hero-body{position:relative;z-index:1;display:flex;justify-content:space-between;gap:12px;align-items:flex-start;flex-wrap:wrap}
    .stock-hero h1{margin:0 0 8px;font-size:28px}
    .stock-hero p{margin:0;max-width:860px;color:#dbeafe;line-height:1.55}
    .stock-pills{display:flex;gap:8px;flex-wrap:wrap}
    .stock-pill{border:1px solid rgba(255,255,255,.24);background:rgba(15,23,42,.35);padding:6px 10px;border-radius:999px;font-size:12px;font-weight:700}

    .stock-layout{display:grid;grid-template-columns:minmax(0,1.55fr) minmax(320px,.95fr);gap:16px;align-items:start}
    .stock-main,.stock-side{display:grid;gap:16px}
    .stock-head{display:flex;justify-content:space-between;align-items:flex-start;gap:10px;flex-wrap:wrap}
    .stock-head h2,.stock-head h3{margin:0}
    .stock-note{margin:4px 0 0;color:#64748b;font-size:13px;line-height:1.45}
    .stock-badges{display:flex;gap:8px;flex-wrap:wrap}

    .stock-kpis{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:12px}
    .stock-kpi{border:1px solid #dbeafe;border-radius:14px;background:linear-gradient(180deg,#fff,#eff6ff);padding:14px}
    .stock-kpi span{display:block;font-size:11px;text-transform:uppercase;letter-spacing:.04em;color:#64748b}
    .stock-kpi strong{display:block;margin-top:6px;font-size:24px;color:#0f172a}
    .stock-kpi small{display:block;margin-top:4px;color:#475569}

    .stock-filter-grid{display:grid;grid-template-columns:1.4fr 1fr 1fr auto;gap:10px;align-items:end}
    .stock-filter-grid .field{margin:0}
    .stock-filter-actions{display:flex;gap:8px;flex-wrap:wrap}

    .stock-list{display:grid;gap:12px}
    .stock-card{border:1px solid #dbeafe;border-radius:14px;background:linear-gradient(180deg,#fff,#f8fafc);padding:14px;display:grid;gap:12px}
    .stock-card-top{display:flex;justify-content:space-between;align-items:flex-start;gap:10px;flex-wrap:wrap}
    .stock-title{display:grid;gap:4px}
    .stock-title strong{font-size:16px;color:#0f172a}
    .stock-title small{font-size:12px;color:#64748b;line-height:1.4}
    .stock-info{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:8px}
    .stock-box{border:1px solid #e2e8f0;background:#fff;border-radius:10px;padding:10px}
    .stock-box span{display:block;font-size:11px;text-transform:uppercase;color:#64748b}
    .stock-box strong{display:block;margin-top:4px;font-size:13px;color:#0f172a}
    .stock-actions{display:flex;gap:8px;flex-wrap:wrap}

    .stock-details{border-top:1px dashed #cbd5e1;padding-top:12px}
    .stock-details summary{display:flex;justify-content:space-between;align-items:center;gap:10px;cursor:pointer;list-style:none;font-weight:700;color:#0f172a}
    .stock-details summary::-webkit-details-marker{display:none}
    .stock-details-toggle{font-size:11px;color:#1d4ed8;background:#dbeafe;border:1px solid #bfdbfe;border-radius:999px;padding:4px 9px;font-weight:700}
    .stock-details-body{display:grid;gap:12px;margin-top:12px}
    .stock-form-grid{display:grid;grid-template-columns:1fr 1fr;gap:10px}
    .stock-form-grid .field{margin:0}
    .stock-form-grid .field.full{grid-column:1 / -1}
    .stock-form-note{font-size:12px;color:#64748b;line-height:1.45}

    .stock-summary-grid{display:grid;gap:8px}
    .stock-summary-item{display:flex;justify-content:space-between;align-items:center;gap:10px;padding:10px;border:1px solid #e2e8f0;background:#f8fafc;border-radius:10px}
    .stock-summary-item strong{color:#0f172a}
    .stock-summary-item span{padding:4px 8px;border-radius:999px;background:#dbeafe;color:#1e3a8a;font-size:11px;font-weight:700}

    .stock-empty{padding:14px;border:1px dashed #cbd5e1;border-radius:14px;background:#f8fafc;color:#64748b;font-size:13px;line-height:1.5}
    .stock-pagination{display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap}
    .stock-pagination-controls{display:flex;gap:8px;align-items:center;flex-wrap:wrap}
    .stock-page-btn{display:inline-block;padding:8px 11px;border:1px solid #cbd5e1;border-radius:8px;background:#fff;color:#0f172a;text-decoration:none}
    .stock-page-btn.is-active{background:#1d4ed8;border-color:#1d4ed8;color:#fff}
    .stock-page-ellipsis{color:#64748b;padding:0 2px}

    .stock-movement-list{display:grid;gap:10px}
    .stock-movement{border:1px solid #dbeafe;border-radius:12px;background:linear-gradient(180deg,#fff,#f8fafc);padding:12px;display:grid;gap:8px}
    .stock-movement-top{display:flex;justify-content:space-between;align-items:flex-start;gap:10px;flex-wrap:wrap}
    .stock-movement-meta{display:grid;gap:4px}
    .stock-movement-meta strong{color:#0f172a}
    .stock-movement-meta small{color:#64748b;font-size:12px;line-height:1.4}

    @media (max-width:1180px){
        .stock-layout{grid-template-columns:1fr}
    }
    @media (max-width:980px){
        .stock-kpis,.stock-filter-grid,.stock-info,.stock-form-grid{grid-template-columns:1fr 1fr}
    }
    @media (max-width:760px){
        .stock-kpis,.stock-filter-grid,.stock-info,.stock-form-grid{grid-template-columns:1fr}
        .stock-hero h1{font-size:24px}
    }
</style>

<div class="stock-page">
    <div class="stock-hero">
        <div class="stock-hero-body">
            <div>
                <h1>Estoque</h1>
                <p>Controle operacional de insumos e itens vinculados ao negocio. A tela combina cadastro, nivel de saldo, alerta de reposicao e movimentacao auditavel para evitar estoque “decorativo” sem historico real.</p>
            </div>
            <div class="stock-pills">
                <span class="stock-pill">Itens: <?= htmlspecialchars((string) ($summary['total_items'] ?? 0)) ?></span>
                <span class="stock-pill">Baixo: <?= htmlspecialchars((string) ($summary['low_stock_items'] ?? 0)) ?></span>
                <span class="stock-pill">Sem saldo: <?= htmlspecialchars((string) ($summary['out_of_stock_items'] ?? 0)) ?></span>
                <span class="stock-pill">Movimentos: <?= htmlspecialchars((string) ($summary['total_movements'] ?? 0)) ?></span>
            </div>
        </div>
    </div>

    <div class="stock-layout">
        <div class="stock-main">
            <section class="card">
                <div class="stock-head">
                    <div>
                        <h2>Painel do estoque</h2>
                        <p class="stock-note">Use os filtros para localizar item, status operacional e situacao de alerta. O objetivo aqui nao e listar tudo, e destacar onde a operacao corre risco por falta de saldo ou manutencao ruim do cadastro.</p>
                    </div>
                    <div class="stock-badges">
                        <?php if (!empty($summary['last_item_update_at'])): ?>
                            <span class="badge">Ultima atualizacao: <?= htmlspecialchars($formatDate($summary['last_item_update_at'])) ?></span>
                        <?php endif; ?>
                        <span class="badge">Produtos vinculados: <?= htmlspecialchars((string) ($summary['linked_products'] ?? 0)) ?></span>
                    </div>
                </div>

                <div class="stock-kpis" style="margin-top:16px">
                    <div class="stock-kpi">
                        <span>Itens ativos</span>
                        <strong><?= htmlspecialchars((string) ($summary['active_items'] ?? 0)) ?></strong>
                        <small>Base operacional em uso</small>
                    </div>
                    <div class="stock-kpi">
                        <span>Estoque baixo</span>
                        <strong><?= htmlspecialchars((string) ($summary['low_stock_items'] ?? 0)) ?></strong>
                        <small>Reposicao perto do limite</small>
                    </div>
                    <div class="stock-kpi">
                        <span>Sem saldo</span>
                        <strong><?= htmlspecialchars((string) ($summary['out_of_stock_items'] ?? 0)) ?></strong>
                        <small>Impacto direto na operacao</small>
                    </div>
                    <div class="stock-kpi">
                        <span>Ajustes</span>
                        <strong><?= htmlspecialchars((string) ($summary['adjustment_count'] ?? 0)) ?></strong>
                        <small>Correcoes de inventario</small>
                    </div>
                </div>

                <form method="GET" action="<?= htmlspecialchars(base_url('/admin/stock')) ?>" style="margin-top:16px">
                    <div class="stock-filter-grid">
                        <div class="field">
                            <label for="stock_search">Busca</label>
                            <input id="stock_search" name="stock_search" type="text" value="<?= htmlspecialchars($stockSearch) ?>" placeholder="Item, SKU, produto ou ID">
                        </div>
                        <div class="field">
                            <label for="stock_status">Status</label>
                            <select id="stock_status" name="stock_status">
                                <?php foreach ($statusOptions as $value => $label): ?>
                                    <option value="<?= htmlspecialchars($value) ?>" <?= $stockStatus === $value ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="field">
                            <label for="stock_alert">Alerta</label>
                            <select id="stock_alert" name="stock_alert">
                                <?php foreach ($alertOptions as $value => $label): ?>
                                    <option value="<?= htmlspecialchars($value) ?>" <?= $stockAlert === $value ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="stock-filter-actions">
                            <input type="hidden" name="stock_page" value="1">
                            <input type="hidden" name="stock_movement_page" value="1">
                            <button class="btn" type="submit">Aplicar</button>
                            <a class="btn secondary" href="<?= htmlspecialchars(base_url('/admin/stock')) ?>">Limpar</a>
                        </div>
                    </div>
                </form>

                <div class="stock-list" style="margin-top:16px">
                    <?php if ($items === []): ?>
                        <div class="stock-empty">Nenhum item de estoque encontrado para os filtros aplicados.</div>
                    <?php endif; ?>

                    <?php foreach ($items as $item): ?>
                        <?php $alertMeta = $stockAlertMeta($item); ?>
                        <article class="stock-card">
                            <div class="stock-card-top">
                                <div class="stock-title">
                                    <strong>#<?= (int) ($item['id'] ?? 0) ?> - <?= htmlspecialchars((string) ($item['name'] ?? 'Item')) ?></strong>
                                    <small>
                                        SKU: <?= htmlspecialchars((string) ($item['sku'] ?? 'Nao informado')) ?>
                                        · Produto: <?= htmlspecialchars((string) ($item['product_name'] ?? 'Sem vinculo')) ?>
                                    </small>
                                </div>
                                <div class="stock-badges">
                                    <span class="<?= htmlspecialchars($alertMeta['class']) ?>"><?= htmlspecialchars($alertMeta['label']) ?></span>
                                    <span class="badge <?= htmlspecialchars(status_badge_class('stock_item_status', $item['status'] ?? null)) ?>"><?= htmlspecialchars(status_label('stock_item_status', $item['status'] ?? null)) ?></span>
                                </div>
                            </div>

                            <div class="stock-info">
                                <div class="stock-box">
                                    <span>Saldo atual</span>
                                    <strong><?= htmlspecialchars($formatQty($item['current_quantity'] ?? 0)) ?> <?= htmlspecialchars((string) ($item['unit_of_measure'] ?? 'un')) ?></strong>
                                </div>
                                <div class="stock-box">
                                    <span>Estoque minimo</span>
                                    <strong>
                                        <?= ($item['minimum_quantity'] ?? null) !== null
                                            ? htmlspecialchars($formatQty($item['minimum_quantity'])) . ' ' . htmlspecialchars((string) ($item['unit_of_measure'] ?? 'un'))
                                            : 'Nao definido' ?>
                                    </strong>
                                </div>
                                <div class="stock-box">
                                    <span>Alerta</span>
                                    <strong><?= htmlspecialchars($alertMeta['note']) ?></strong>
                                </div>
                                <div class="stock-box">
                                    <span>Ultima atualizacao</span>
                                    <strong><?= htmlspecialchars($formatDate($item['updated_at'] ?? $item['created_at'] ?? null)) ?></strong>
                                </div>
                            </div>

                            <?php if ($canManageStock): ?>
                                <details class="stock-details">
                                    <summary>
                                        <span>Editar e movimentar</span>
                                        <span class="stock-details-toggle">Expandir / recolher</span>
                                    </summary>

                                    <div class="stock-details-body">
                                        <form method="POST" action="<?= htmlspecialchars(base_url('/admin/stock/items/update')) ?>">
                                            <?= form_security_fields('stock.items.update.' . (int) ($item['id'] ?? 0)) ?>
                                            <input type="hidden" name="stock_item_id" value="<?= (int) ($item['id'] ?? 0) ?>">
                                            <input type="hidden" name="return_query" value="<?= htmlspecialchars($returnQuery) ?>">

                                            <div class="stock-form-grid">
                                                <div class="field">
                                                    <label for="stock_name_<?= (int) ($item['id'] ?? 0) ?>">Nome</label>
                                                    <input id="stock_name_<?= (int) ($item['id'] ?? 0) ?>" name="name" type="text" required value="<?= htmlspecialchars((string) ($item['name'] ?? '')) ?>">
                                                </div>
                                                <div class="field">
                                                    <label for="stock_sku_<?= (int) ($item['id'] ?? 0) ?>">SKU</label>
                                                    <input id="stock_sku_<?= (int) ($item['id'] ?? 0) ?>" name="sku" type="text" value="<?= htmlspecialchars((string) ($item['sku'] ?? '')) ?>">
                                                </div>
                                                <div class="field">
                                                    <label for="stock_product_<?= (int) ($item['id'] ?? 0) ?>">Produto vinculado</label>
                                                    <select id="stock_product_<?= (int) ($item['id'] ?? 0) ?>" name="product_id">
                                                        <option value="">Sem vinculo</option>
                                                        <?php foreach ($products as $product): ?>
                                                            <?php $productId = (int) ($product['id'] ?? 0); ?>
                                                            <option value="<?= $productId ?>" <?= (int) ($item['product_id'] ?? 0) === $productId ? 'selected' : '' ?>>
                                                                <?= htmlspecialchars((string) ($product['name'] ?? 'Produto')) ?>
                                                            </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>
                                                <div class="field">
                                                    <label for="stock_unit_<?= (int) ($item['id'] ?? 0) ?>">Unidade</label>
                                                    <select id="stock_unit_<?= (int) ($item['id'] ?? 0) ?>" name="unit_of_measure" required>
                                                        <?php foreach ($unitOptions as $unitOption): ?>
                                                            <option value="<?= htmlspecialchars((string) $unitOption) ?>" <?= (string) ($item['unit_of_measure'] ?? 'un') === (string) $unitOption ? 'selected' : '' ?>>
                                                                <?= htmlspecialchars(strtoupper((string) $unitOption)) ?>
                                                            </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>
                                                <div class="field">
                                                    <label for="stock_minimum_<?= (int) ($item['id'] ?? 0) ?>">Estoque minimo</label>
                                                    <input id="stock_minimum_<?= (int) ($item['id'] ?? 0) ?>" name="minimum_quantity" type="number" min="0" step="0.001" value="<?= ($item['minimum_quantity'] ?? null) !== null ? htmlspecialchars(number_format((float) $item['minimum_quantity'], 3, '.', '')) : '' ?>">
                                                </div>
                                                <div class="field">
                                                    <label for="stock_status_edit_<?= (int) ($item['id'] ?? 0) ?>">Status</label>
                                                    <select id="stock_status_edit_<?= (int) ($item['id'] ?? 0) ?>" name="status" required>
                                                        <option value="ativo" <?= (string) ($item['status'] ?? '') === 'ativo' ? 'selected' : '' ?>>Ativo</option>
                                                        <option value="inativo" <?= (string) ($item['status'] ?? '') === 'inativo' ? 'selected' : '' ?>>Inativo</option>
                                                    </select>
                                                </div>
                                            </div>

                                            <div class="stock-actions" style="margin-top:12px">
                                                <button class="btn" type="submit">Salvar item</button>
                                                <span class="stock-form-note">Saldo atual nao e alterado aqui. Use a movimentacao abaixo para manter trilha auditavel.</span>
                                            </div>
                                        </form>

                                        <form method="POST" action="<?= htmlspecialchars(base_url('/admin/stock/movements/store')) ?>">
                                            <?= form_security_fields('stock.movements.store.' . (int) ($item['id'] ?? 0)) ?>
                                            <input type="hidden" name="stock_item_id" value="<?= (int) ($item['id'] ?? 0) ?>">
                                            <input type="hidden" name="return_query" value="<?= htmlspecialchars($returnQuery) ?>">

                                            <div class="stock-form-grid">
                                                <div class="field">
                                                    <label for="stock_movement_type_<?= (int) ($item['id'] ?? 0) ?>">Tipo de movimentacao</label>
                                                    <select id="stock_movement_type_<?= (int) ($item['id'] ?? 0) ?>" name="movement_type" required>
                                                        <option value="entry">Entrada</option>
                                                        <option value="exit">Saida</option>
                                                        <option value="adjustment">Ajuste</option>
                                                    </select>
                                                </div>
                                                <div class="field">
                                                    <label for="stock_quantity_<?= (int) ($item['id'] ?? 0) ?>">Quantidade</label>
                                                    <input id="stock_quantity_<?= (int) ($item['id'] ?? 0) ?>" name="quantity" type="number" min="0.001" step="0.001" placeholder="Use para entrada ou saida">
                                                </div>
                                                <div class="field">
                                                    <label for="stock_target_<?= (int) ($item['id'] ?? 0) ?>">Saldo alvo</label>
                                                    <input id="stock_target_<?= (int) ($item['id'] ?? 0) ?>" name="target_quantity" type="number" min="0" step="0.001" placeholder="Use apenas em ajuste">
                                                </div>
                                                <div class="field">
                                                    <label for="stock_reference_type_<?= (int) ($item['id'] ?? 0) ?>">Origem</label>
                                                    <select id="stock_reference_type_<?= (int) ($item['id'] ?? 0) ?>" name="reference_type">
                                                        <?php foreach ($referenceTypeOptions as $referenceType): ?>
                                                            <option value="<?= htmlspecialchars((string) $referenceType) ?>">
                                                                <?= htmlspecialchars($referenceTypeLabels[(string) $referenceType] ?? (string) $referenceType) ?>
                                                            </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>
                                                <div class="field full">
                                                    <label for="stock_reason_<?= (int) ($item['id'] ?? 0) ?>">Motivo</label>
                                                    <input id="stock_reason_<?= (int) ($item['id'] ?? 0) ?>" name="reason" type="text" placeholder="Compra, consumo interno, inventario, perda, producao...">
                                                </div>
                                            </div>

                                            <div class="stock-actions" style="margin-top:12px">
                                                <button class="btn" type="submit">Registrar movimento</button>
                                                <span class="stock-form-note">Entrada e saida usam quantidade. Ajuste usa saldo alvo. Misturar os dois enfraquece a confiabilidade do historico.</span>
                                            </div>
                                        </form>
                                    </div>
                                </details>
                            <?php endif; ?>
                        </article>
                    <?php endforeach; ?>
                </div>

                <?php if ((int) ($itemPagination['total'] ?? 0) > 0): ?>
                    <div class="stock-pagination" style="margin-top:14px">
                        <div class="stock-note">
                            Exibindo <?= htmlspecialchars((string) ($itemPagination['from'] ?? 0)) ?> a <?= htmlspecialchars((string) ($itemPagination['to'] ?? 0)) ?> de <?= htmlspecialchars((string) ($itemPagination['total'] ?? 0)) ?> itens.
                        </div>
                        <?php if ((int) ($itemPagination['last_page'] ?? 1) > 1): ?>
                            <div class="stock-pagination-controls">
                                <?php if ((int) ($itemPagination['page'] ?? 1) > 1): ?>
                                    <a class="stock-page-btn" href="<?= htmlspecialchars($buildStockUrl(['stock_page' => ((int) $itemPagination['page']) - 1])) ?>">Anterior</a>
                                <?php endif; ?>
                                <?php
                                $lastRenderedPage = 0;
                                foreach (is_array($itemPagination['pages'] ?? null) ? $itemPagination['pages'] : [] as $pageNumber):
                                    $pageNumber = (int) $pageNumber;
                                    if ($lastRenderedPage > 0 && $pageNumber - $lastRenderedPage > 1): ?>
                                        <span class="stock-page-ellipsis">...</span>
                                    <?php endif; ?>
                                    <a class="stock-page-btn<?= $pageNumber === (int) ($itemPagination['page'] ?? 1) ? ' is-active' : '' ?>" href="<?= htmlspecialchars($buildStockUrl(['stock_page' => $pageNumber])) ?>"><?= $pageNumber ?></a>
                                    <?php $lastRenderedPage = $pageNumber; ?>
                                <?php endforeach; ?>
                                <?php if ((int) ($itemPagination['page'] ?? 1) < (int) ($itemPagination['last_page'] ?? 1)): ?>
                                    <a class="stock-page-btn" href="<?= htmlspecialchars($buildStockUrl(['stock_page' => ((int) $itemPagination['page']) + 1])) ?>">Proxima</a>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </section>
        </div>

        <aside class="stock-side">
            <section class="card">
                <div class="stock-head">
                    <div>
                        <h3>Resumo operacional</h3>
                        <p class="stock-note">Leitura curta para identificar onde o estoque pressiona compra, producao ou consumo.</p>
                    </div>
                </div>
                <div class="stock-summary-grid">
                    <div class="stock-summary-item"><strong>Total de itens</strong><span><?= htmlspecialchars((string) ($summary['total_items'] ?? 0)) ?></span></div>
                    <div class="stock-summary-item"><strong>Itens ativos</strong><span><?= htmlspecialchars((string) ($summary['active_items'] ?? 0)) ?></span></div>
                    <div class="stock-summary-item"><strong>Itens inativos</strong><span><?= htmlspecialchars((string) ($summary['inactive_items'] ?? 0)) ?></span></div>
                    <div class="stock-summary-item"><strong>Sem saldo</strong><span><?= htmlspecialchars((string) ($summary['out_of_stock_items'] ?? 0)) ?></span></div>
                    <div class="stock-summary-item"><strong>Estoque baixo</strong><span><?= htmlspecialchars((string) ($summary['low_stock_items'] ?? 0)) ?></span></div>
                    <div class="stock-summary-item"><strong>Entradas</strong><span><?= htmlspecialchars((string) ($summary['entry_count'] ?? 0)) ?></span></div>
                    <div class="stock-summary-item"><strong>Saidas</strong><span><?= htmlspecialchars((string) ($summary['exit_count'] ?? 0)) ?></span></div>
                    <div class="stock-summary-item"><strong>Ultimo movimento</strong><span><?= htmlspecialchars($formatDate($summary['last_moved_at'] ?? null)) ?></span></div>
                </div>
            </section>

            <?php if ($canManageStock): ?>
                <section class="card">
                    <div class="stock-head">
                        <div>
                            <h3>Novo item</h3>
                            <p class="stock-note">Cadastre apenas o que realmente precisa de controle. Estoque inchado e sem rotina de movimento vira lixo de cadastro.</p>
                        </div>
                    </div>

                    <form method="POST" action="<?= htmlspecialchars(base_url('/admin/stock/items/store')) ?>">
                        <?= form_security_fields('stock.items.store') ?>

                        <div class="stock-form-grid">
                            <div class="field">
                                <label for="new_stock_name">Nome</label>
                                <input id="new_stock_name" name="name" type="text" required>
                            </div>
                            <div class="field">
                                <label for="new_stock_sku">SKU</label>
                                <input id="new_stock_sku" name="sku" type="text">
                            </div>
                            <div class="field">
                                <label for="new_stock_product">Produto vinculado</label>
                                <select id="new_stock_product" name="product_id">
                                    <option value="">Sem vinculo</option>
                                    <?php foreach ($products as $product): ?>
                                        <option value="<?= (int) ($product['id'] ?? 0) ?>"><?= htmlspecialchars((string) ($product['name'] ?? 'Produto')) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="field">
                                <label for="new_stock_unit">Unidade</label>
                                <select id="new_stock_unit" name="unit_of_measure" required>
                                    <?php foreach ($unitOptions as $unitOption): ?>
                                        <option value="<?= htmlspecialchars((string) $unitOption) ?>"><?= htmlspecialchars(strtoupper((string) $unitOption)) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="field">
                                <label for="new_stock_initial">Saldo inicial</label>
                                <input id="new_stock_initial" name="initial_quantity" type="number" min="0" step="0.001" value="0.000">
                            </div>
                            <div class="field">
                                <label for="new_stock_minimum">Estoque minimo</label>
                                <input id="new_stock_minimum" name="minimum_quantity" type="number" min="0" step="0.001">
                            </div>
                            <div class="field">
                                <label for="new_stock_status">Status</label>
                                <select id="new_stock_status" name="status" required>
                                    <option value="ativo">Ativo</option>
                                    <option value="inativo">Inativo</option>
                                </select>
                            </div>
                        </div>

                        <div class="stock-actions" style="margin-top:12px">
                            <button class="btn" type="submit">Cadastrar item</button>
                            <span class="stock-form-note">Se houver saldo inicial, o sistema registra a entrada automaticamente para nao nascer sem historico.</span>
                        </div>
                    </form>
                </section>
            <?php endif; ?>

            <section class="card">
                <div class="stock-head">
                    <div>
                        <h3>Movimentacoes</h3>
                        <p class="stock-note">A trilha de estoque mostra o que entrou, saiu ou foi ajustado. Sem esse historico, saldo vira opiniao.</p>
                    </div>
                </div>

                <form method="GET" action="<?= htmlspecialchars(base_url('/admin/stock')) ?>" style="margin-top:16px">
                    <?php foreach ($currentQuery as $queryKey => $queryValue): ?>
                        <?php if (!in_array((string) $queryKey, ['stock_search', 'stock_status', 'stock_alert', 'stock_page', 'stock_movement_type', 'stock_movement_page'], true)): ?>
                            <input type="hidden" name="<?= htmlspecialchars((string) $queryKey) ?>" value="<?= htmlspecialchars((string) $queryValue) ?>">
                        <?php endif; ?>
                    <?php endforeach; ?>
                    <div class="stock-form-grid">
                        <div class="field">
                            <label for="stock_movement_type">Tipo</label>
                            <select id="stock_movement_type" name="stock_movement_type">
                                <?php foreach ($movementTypeOptions as $value => $label): ?>
                                    <option value="<?= htmlspecialchars($value) ?>" <?= $stockMovementType === $value ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="field">
                            <label for="stock_movement_search_side">Busca compartilhada</label>
                            <input id="stock_movement_search_side" name="stock_search" type="text" value="<?= htmlspecialchars($stockSearch) ?>" placeholder="Item, SKU ou ID">
                        </div>
                    </div>
                    <div class="stock-actions">
                        <input type="hidden" name="stock_page" value="1">
                        <input type="hidden" name="stock_movement_page" value="1">
                        <button class="btn" type="submit">Filtrar movimentos</button>
                    </div>
                </form>

                <div class="stock-movement-list" style="margin-top:16px">
                    <?php if ($movements === []): ?>
                        <div class="stock-empty">Nenhuma movimentacao encontrada para os filtros atuais.</div>
                    <?php endif; ?>

                    <?php foreach ($movements as $movement): ?>
                        <article class="stock-movement">
                            <div class="stock-movement-top">
                                <div class="stock-movement-meta">
                                    <strong>#<?= (int) ($movement['id'] ?? 0) ?> · <?= htmlspecialchars((string) ($movement['stock_item_name'] ?? 'Item')) ?></strong>
                                    <small>
                                        <?= htmlspecialchars(status_label('stock_movement_type', $movement['type'] ?? null)) ?>
                                        · <?= htmlspecialchars($formatQty($movement['quantity'] ?? 0)) ?> <?= htmlspecialchars((string) ($movement['unit_of_measure'] ?? 'un')) ?>
                                    </small>
                                </div>
                                <div class="stock-badges">
                                    <span class="badge <?= htmlspecialchars(status_badge_class('stock_movement_type', $movement['type'] ?? null)) ?>"><?= htmlspecialchars(status_label('stock_movement_type', $movement['type'] ?? null)) ?></span>
                                </div>
                            </div>
                            <div class="stock-summary-grid">
                                <div class="stock-summary-item"><strong>Origem</strong><span><?= htmlspecialchars($referenceTypeLabels[(string) ($movement['reference_type'] ?? 'manual')] ?? (string) ($movement['reference_type'] ?? 'manual')) ?></span></div>
                                <div class="stock-summary-item"><strong>Responsavel</strong><span><?= htmlspecialchars((string) ($movement['moved_by_user_name'] ?? 'Sistema')) ?></span></div>
                                <div class="stock-summary-item"><strong>Data</strong><span><?= htmlspecialchars($formatDate($movement['moved_at'] ?? null)) ?></span></div>
                            </div>
                            <?php if (trim((string) ($movement['reason'] ?? '')) !== ''): ?>
                                <div class="stock-form-note"><?= htmlspecialchars((string) ($movement['reason'] ?? '')) ?></div>
                            <?php endif; ?>
                        </article>
                    <?php endforeach; ?>
                </div>

                <?php if ((int) ($movementPagination['total'] ?? 0) > 0): ?>
                    <div class="stock-pagination" style="margin-top:14px">
                        <div class="stock-note">
                            Exibindo <?= htmlspecialchars((string) ($movementPagination['from'] ?? 0)) ?> a <?= htmlspecialchars((string) ($movementPagination['to'] ?? 0)) ?> de <?= htmlspecialchars((string) ($movementPagination['total'] ?? 0)) ?> movimentos.
                        </div>
                        <?php if ((int) ($movementPagination['last_page'] ?? 1) > 1): ?>
                            <div class="stock-pagination-controls">
                                <?php if ((int) ($movementPagination['page'] ?? 1) > 1): ?>
                                    <a class="stock-page-btn" href="<?= htmlspecialchars($buildStockUrl(['stock_movement_page' => ((int) $movementPagination['page']) - 1])) ?>">Anterior</a>
                                <?php endif; ?>
                                <?php
                                $lastRenderedMovementPage = 0;
                                foreach (is_array($movementPagination['pages'] ?? null) ? $movementPagination['pages'] : [] as $pageNumber):
                                    $pageNumber = (int) $pageNumber;
                                    if ($lastRenderedMovementPage > 0 && $pageNumber - $lastRenderedMovementPage > 1): ?>
                                        <span class="stock-page-ellipsis">...</span>
                                    <?php endif; ?>
                                    <a class="stock-page-btn<?= $pageNumber === (int) ($movementPagination['page'] ?? 1) ? ' is-active' : '' ?>" href="<?= htmlspecialchars($buildStockUrl(['stock_movement_page' => $pageNumber])) ?>"><?= $pageNumber ?></a>
                                    <?php $lastRenderedMovementPage = $pageNumber; ?>
                                <?php endforeach; ?>
                                <?php if ((int) ($movementPagination['page'] ?? 1) < (int) ($movementPagination['last_page'] ?? 1)): ?>
                                    <a class="stock-page-btn" href="<?= htmlspecialchars($buildStockUrl(['stock_movement_page' => ((int) $movementPagination['page']) + 1])) ?>">Proxima</a>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </section>
        </aside>
    </div>
</div>
