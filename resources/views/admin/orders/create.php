<?php
$commands = is_array($commands ?? null) ? $commands : [];
$products = is_array($products ?? null) ? $products : [];

$productsJson = json_encode($products, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT);
if (!is_string($productsJson)) {
    $productsJson = '[]';
}

$totalCommands = count($commands);
$totalProducts = count($products);
$productsWithAdditionals = count(array_filter($products, static fn (array $product): bool => (int) ($product['has_additionals'] ?? 0) === 1));
$canCreateOrder = $totalCommands > 0 && $totalProducts > 0;
?>

<style>
    .order-create-page{display:grid;gap:16px}
    .kpi-grid{display:grid;grid-template-columns:repeat(4,minmax(140px,1fr));gap:12px}
    .kpi-item{background:#fff;border:1px solid #e5e7eb;border-radius:12px;padding:14px}
    .kpi-item strong{display:block;font-size:24px;line-height:1.1}
    .kpi-item span{color:#64748b;font-size:12px}
    .order-form-layout{display:grid;grid-template-columns:1.35fr 1fr;gap:16px}
    .order-form-card{background:#fff;border:1px solid #e5e7eb;border-radius:14px;padding:18px}
    .steps{display:flex;gap:8px;flex-wrap:wrap;margin-bottom:10px}
    .step-pill{padding:4px 10px;border-radius:999px;background:#e2e8f0;color:#334155;font-size:12px}
    .warning-strip{border:1px solid #fcd34d;background:#fffbeb;border-radius:10px;padding:10px;color:#92400e}
    .items-header{display:flex;justify-content:space-between;align-items:center;gap:8px;flex-wrap:wrap;margin-top:8px}
    .items-table-wrap{border:1px solid #e2e8f0;border-radius:12px;overflow:auto;background:#f8fafc}
    .items-table{width:100%;border-collapse:separate;border-spacing:0;margin:0;min-width:820px}
    .items-table th{background:#e2e8f0;color:#334155;font-size:12px;text-transform:uppercase;letter-spacing:.03em}
    .items-table th,.items-table td{border-bottom:1px solid #e2e8f0;padding:10px;vertical-align:top}
    .items-table tbody tr:last-child td{border-bottom:0}
    .additionals-container{max-height:170px;overflow:auto;border:1px solid #e2e8f0;border-radius:8px;padding:8px;background:#fff}
    .additional-choice{display:block;font-weight:normal;margin-bottom:4px;color:#334155;font-size:13px}
    .additional-hint{margin-bottom:6px;font-size:12px;color:#64748b}
    .line-total{display:inline-block;padding:4px 8px;border-radius:999px;background:#dbeafe;color:#1e3a8a;font-size:12px;white-space:nowrap}
    .summary-list{display:grid;gap:8px;margin-top:10px}
    .summary-item{border:1px solid #e2e8f0;border-radius:10px;padding:10px;background:#f8fafc}
    .summary-item strong{display:block;font-size:11px;color:#64748b;text-transform:uppercase;letter-spacing:.04em;margin-bottom:3px}
    .summary-item span{font-size:14px;color:#0f172a}
    .total-highlight{border-color:#bfdbfe;background:#eff6ff}
    .total-highlight span{font-size:18px;font-weight:700;color:#1d4ed8}
    .actions-row{display:flex;gap:8px;flex-wrap:wrap;margin-top:14px}
    @media (max-width:1080px){
        .kpi-grid{grid-template-columns:repeat(2,minmax(130px,1fr))}
        .order-form-layout{grid-template-columns:1fr}
        .items-table{min-width:720px}
    }
</style>

<div class="order-create-page">
    <div class="topbar">
        <div>
            <h1>Novo Pedido</h1>
            <p>Cadastro no padrao moderno com resumo operacional em tempo real.</p>
        </div>
        <a class="btn secondary" href="<?= htmlspecialchars(base_url('/admin/orders')) ?>">Voltar</a>
    </div>

    <div class="kpi-grid">
        <div class="kpi-item"><strong><?= $totalCommands ?></strong><span>Comandas abertas</span></div>
        <div class="kpi-item"><strong><?= $totalProducts ?></strong><span>Produtos disponiveis</span></div>
        <div class="kpi-item"><strong><?= $productsWithAdditionals ?></strong><span>Produtos com adicionais</span></div>
        <div class="kpi-item"><strong><?= $canCreateOrder ? 'OK' : 'Pendente' ?></strong><span>Pronto para criar pedido</span></div>
    </div>

    <?php if (!$canCreateOrder): ?>
        <div class="warning-strip">
            <?php if ($totalCommands <= 0): ?>
                Nenhuma comanda aberta encontrada. Abra uma comanda antes de criar o pedido.
            <?php endif; ?>
            <?php if ($totalProducts <= 0): ?>
                Nenhum produto disponivel. Cadastre/ative produtos para continuar.
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="<?= htmlspecialchars(base_url('/admin/orders/store')) ?>" id="orderCreateForm">
        <?= form_security_fields('orders.store') ?>

        <div class="order-form-layout">
            <div class="order-form-card">
                <div class="steps">
                    <span class="step-pill">1. Comanda e dados gerais</span>
                    <span class="step-pill">2. Itens e adicionais</span>
                </div>

                <div class="grid two">
                    <div class="field">
                        <label for="command_id">Comanda aberta</label>
                        <select id="command_id" name="command_id" required <?= $canCreateOrder ? '' : 'disabled' ?>>
                            <option value="">Selecione</option>
                            <?php foreach ($commands as $command): ?>
                                <option value="<?= (int) $command['id'] ?>">
                                    <?= $command['table_number'] !== null ? 'Mesa ' . (int) $command['table_number'] : 'Comanda sem mesa' ?>
                                    <?= !empty($command['customer_name']) ? '- ' . htmlspecialchars((string) $command['customer_name']) : '' ?>
                                    <?= !empty($command['opened_at']) ? '- Aberta em ' . htmlspecialchars((string) $command['opened_at']) : '' ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="field">
                        <label for="notes">Observacoes gerais</label>
                        <input id="notes" name="notes" type="text" placeholder="Opcional" <?= $canCreateOrder ? '' : 'disabled' ?>>
                    </div>
                </div>

                <div class="grid two">
                    <div class="field">
                        <label for="discount_amount">Desconto (R$)</label>
                        <input id="discount_amount" name="discount_amount" type="number" step="0.01" min="0" value="0.00" <?= $canCreateOrder ? '' : 'disabled' ?>>
                    </div>

                    <div class="field">
                        <label for="delivery_fee">Taxa de entrega (R$)</label>
                        <input id="delivery_fee" name="delivery_fee" type="number" step="0.01" min="0" value="0.00" <?= $canCreateOrder ? '' : 'disabled' ?>>
                    </div>
                </div>

                <div class="items-header">
                    <div>
                        <h3 style="margin:0">Itens do pedido</h3>
                        <small style="color:#64748b">Selecione produto, quantidade, adicionais e observacao por linha.</small>
                    </div>
                    <button class="btn secondary" id="addItemBtn" type="button" <?= $canCreateOrder ? '' : 'disabled' ?>>Adicionar item</button>
                </div>

                <div class="items-table-wrap" style="margin-top:10px">
                    <table class="items-table" id="itemsTable">
                        <thead>
                            <tr>
                                <th style="width:28%">Produto</th>
                                <th style="width:9%">Qtd</th>
                                <th style="width:34%">Adicionais</th>
                                <th style="width:17%">Observacao</th>
                                <th style="width:8%">Total linha</th>
                                <th style="width:4%">Acao</th>
                            </tr>
                        </thead>
                        <tbody id="itemsBody"></tbody>
                    </table>
                </div>
            </div>

            <div class="order-form-card">
                <div class="steps">
                    <span class="step-pill">3. Resumo operacional</span>
                </div>

                <div class="summary-list">
                    <div class="summary-item">
                        <strong>Comanda selecionada</strong>
                        <span id="summaryCommand">Nao selecionada</span>
                    </div>
                    <div class="summary-item">
                        <strong>Itens totais</strong>
                        <span id="summaryItems">0</span>
                    </div>
                    <div class="summary-item">
                        <strong>Subtotal produtos</strong>
                        <span id="summaryBase">R$ 0,00</span>
                    </div>
                    <div class="summary-item">
                        <strong>Subtotal adicionais</strong>
                        <span id="summaryAdditionals">R$ 0,00</span>
                    </div>
                    <div class="summary-item">
                        <strong>Desconto</strong>
                        <span id="summaryDiscount">R$ 0,00</span>
                    </div>
                    <div class="summary-item">
                        <strong>Taxa de entrega</strong>
                        <span id="summaryDelivery">R$ 0,00</span>
                    </div>
                    <div class="summary-item total-highlight">
                        <strong>Total previsto</strong>
                        <span id="summaryTotal">R$ 0,00</span>
                    </div>
                    <div class="summary-item">
                        <strong>Observacao geral</strong>
                        <span id="summaryNotes">Sem observacoes.</span>
                    </div>
                </div>

                <div class="actions-row">
                    <button class="btn" id="submitOrderBtn" type="submit" <?= $canCreateOrder ? '' : 'disabled' ?>>Criar pedido</button>
                    <a class="btn secondary" href="<?= htmlspecialchars(base_url('/admin/orders')) ?>">Cancelar</a>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
(() => {
    const products = <?= $productsJson ?>;
    const canCreateOrder = <?= $canCreateOrder ? 'true' : 'false' ?>;
    const productsById = {};
    const tbody = document.getElementById('itemsBody');
    const addItemBtn = document.getElementById('addItemBtn');
    const commandSelect = document.getElementById('command_id');
    const notesInput = document.getElementById('notes');
    const discountInput = document.getElementById('discount_amount');
    const deliveryInput = document.getElementById('delivery_fee');
    const summaryCommand = document.getElementById('summaryCommand');
    const summaryItems = document.getElementById('summaryItems');
    const summaryBase = document.getElementById('summaryBase');
    const summaryAdditionals = document.getElementById('summaryAdditionals');
    const summaryDiscount = document.getElementById('summaryDiscount');
    const summaryDelivery = document.getElementById('summaryDelivery');
    const summaryTotal = document.getElementById('summaryTotal');
    const summaryNotes = document.getElementById('summaryNotes');
    const form = document.getElementById('orderCreateForm');

    const money = (value) => `R$ ${Number(value || 0).toFixed(2).replace('.', ',')}`;
    const parseMoney = (value) => {
        const parsed = Number(String(value || '').replace(',', '.'));
        return Number.isFinite(parsed) && parsed > 0 ? parsed : 0;
    };
    const escapeHtml = (value) => String(value)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');

    const effectivePrice = (product) => {
        if (!product || typeof product !== 'object') {
            return 0;
        }
        if (product.promotional_price !== null && product.promotional_price !== undefined && product.promotional_price !== '') {
            return Number(product.promotional_price || 0);
        }
        return Number(product.price || 0);
    };

    products.forEach((product) => {
        const productId = Number(product.id || 0);
        if (productId > 0) {
            productsById[productId] = product;
        }
    });

    const additionalPrice = (product, additionalId) => {
        if (!product || !Array.isArray(product.additionals)) {
            return 0;
        }
        const found = product.additionals.find((item) => Number(item.id || 0) === Number(additionalId || 0));
        return found ? Number(found.price || 0) : 0;
    };

    const productOptions = () => {
        let options = '<option value="">Selecione</option>';
        products.forEach((product) => {
            const productId = Number(product.id || 0);
            if (productId <= 0) {
                return;
            }
            const safeName = escapeHtml(product.name || 'Produto');
            const category = product.category_name ? ` (${escapeHtml(product.category_name)})` : '';
            options += `<option value="${productId}">${safeName}${category} - ${money(effectivePrice(product))}</option>`;
        });
        return options;
    };

    const checkedAdditionalIds = (container) => {
        const ids = [];
        container.querySelectorAll('input[type="checkbox"][data-additional-id]').forEach((checkbox) => {
            if (checkbox.checked) {
                ids.push(String(Number(checkbox.getAttribute('data-additional-id') || 0)));
            }
        });
        return ids.filter((id) => Number(id) > 0);
    };

    const syncAdditionalHidden = (row) => {
        const hidden = row.querySelector('input[name="additional_item_ids[]"]');
        const container = row.querySelector('.additionals-container');
        if (!hidden || !container) {
            return;
        }
        hidden.value = checkedAdditionalIds(container).join(',');
    };

    const lineTotals = (row) => {
        const select = row.querySelector('select[name="product_id[]"]');
        const qtyInput = row.querySelector('input[name="quantity[]"]');
        const hidden = row.querySelector('input[name="additional_item_ids[]"]');
        const productId = Number(select ? select.value || 0 : 0);
        const quantity = Math.max(1, Number(qtyInput ? qtyInput.value || 1 : 1));
        const product = productsById[productId] || null;
        const base = product ? effectivePrice(product) * quantity : 0;
        let additionals = 0;

        if (product && hidden) {
            String(hidden.value || '').split(',').map((id) => Number(id || 0)).filter((id) => id > 0).forEach((id) => {
                additionals += additionalPrice(product, id) * quantity;
            });
        }

        const lineBadge = row.querySelector('.item-line-total');
        if (lineBadge) {
            lineBadge.textContent = money(base + additionals);
        }

        return { quantity: product ? quantity : 0, base, additionals };
    };

    const refreshSummary = () => {
        let totalItems = 0;
        let totalBase = 0;
        let totalAdditionals = 0;

        Array.from(tbody.querySelectorAll('tr')).forEach((row) => {
            const line = lineTotals(row);
            totalItems += line.quantity;
            totalBase += line.base;
            totalAdditionals += line.additionals;
        });

        const discount = parseMoney(discountInput ? discountInput.value : 0);
        const delivery = parseMoney(deliveryInput ? deliveryInput.value : 0);
        const total = Math.max(0, (totalBase + totalAdditionals) - discount + delivery);

        if (summaryItems) summaryItems.textContent = String(totalItems);
        if (summaryBase) summaryBase.textContent = money(totalBase);
        if (summaryAdditionals) summaryAdditionals.textContent = money(totalAdditionals);
        if (summaryDiscount) summaryDiscount.textContent = money(discount);
        if (summaryDelivery) summaryDelivery.textContent = money(delivery);
        if (summaryTotal) summaryTotal.textContent = money(total);

        if (summaryCommand && commandSelect) {
            const label = commandSelect.options[commandSelect.selectedIndex]?.textContent || '';
            summaryCommand.textContent = commandSelect.value ? String(label).trim() : 'Nao selecionada';
        }

        if (summaryNotes && notesInput) {
            const notes = String(notesInput.value || '').trim();
            summaryNotes.textContent = notes !== '' ? notes : 'Sem observacoes.';
        }
    };

    const enforceMaxSelection = (container, maxSelection) => {
        const selected = checkedAdditionalIds(container);
        if (maxSelection !== null && selected.length > maxSelection) {
            const lastChecked = container.querySelector('input[type="checkbox"][data-last-change="1"]');
            if (lastChecked) {
                lastChecked.checked = false;
                lastChecked.removeAttribute('data-last-change');
            }
            alert(`Este produto permite no maximo ${maxSelection} adicional(is) por item.`);
        }
    };

    const renderAdditionals = (row) => {
        const productSelect = row.querySelector('select[name="product_id[]"]');
        const additionalsContainer = row.querySelector('.additionals-container');
        const hidden = row.querySelector('input[name="additional_item_ids[]"]');
        if (!productSelect || !additionalsContainer || !hidden) {
            return;
        }

        hidden.value = '';
        const productId = Number(productSelect.value || 0);
        const product = productsById[productId] || null;

        if (!product || !Array.isArray(product.additionals) || product.additionals.length === 0) {
            additionalsContainer.innerHTML = '<span style="color:#64748b">Sem adicionais disponiveis.</span>';
            refreshSummary();
            return;
        }

        const maxSelection = product.additionals_max_selection !== null ? Number(product.additionals_max_selection) : null;
        const minSelection = product.additionals_min_selection !== null ? Number(product.additionals_min_selection) : 0;
        const isRequired = Boolean(product.additionals_is_required);

        let html = '';
        if (maxSelection !== null) {
            html += `<div class="additional-hint">Maximo por item: ${maxSelection}</div>`;
        }
        if (isRequired) {
            html += `<div class="additional-hint" style="color:#92400e">Selecao obrigatoria${minSelection > 0 ? ` (min ${minSelection})` : ''}.</div>`;
        }

        product.additionals.forEach((additional) => {
            const additionalId = Number(additional.id || 0);
            const additionalName = escapeHtml(additional.name || 'Adicional');
            const additionalPriceText = money(Number(additional.price || 0));
            html += `
                <label class="additional-choice">
                    <input type="checkbox" data-additional-id="${additionalId}">
                    ${additionalName} - ${additionalPriceText}
                </label>
            `;
        });

        additionalsContainer.innerHTML = html;

        additionalsContainer.querySelectorAll('input[type="checkbox"][data-additional-id]').forEach((checkbox) => {
            checkbox.addEventListener('change', () => {
                additionalsContainer.querySelectorAll('input[type="checkbox"][data-additional-id]').forEach((item) => item.removeAttribute('data-last-change'));
                checkbox.setAttribute('data-last-change', '1');
                enforceMaxSelection(additionalsContainer, maxSelection);
                syncAdditionalHidden(row);
                refreshSummary();
            });
        });

        refreshSummary();
    };

    const addRow = () => {
        if (!canCreateOrder || products.length === 0) {
            return;
        }

        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td>
                <select name="product_id[]" required>${productOptions()}</select>
            </td>
            <td>
                <input name="quantity[]" type="number" min="1" step="1" value="1" required>
            </td>
            <td>
                <div class="additionals-container">
                    <span style="color:#64748b">Selecione um produto para ver os adicionais.</span>
                </div>
                <input type="hidden" name="additional_item_ids[]" value="">
            </td>
            <td>
                <input name="item_notes[]" type="text" placeholder="Opcional">
            </td>
            <td>
                <span class="line-total item-line-total">R$ 0,00</span>
            </td>
            <td>
                <button class="btn secondary remove-item" type="button">Remover</button>
            </td>
        `;
        tbody.appendChild(tr);

        const select = tr.querySelector('select[name="product_id[]"]');
        const quantity = tr.querySelector('input[name="quantity[]"]');

        if (select) {
            select.addEventListener('change', () => {
                renderAdditionals(tr);
                refreshSummary();
            });
        }

        if (quantity) {
            quantity.addEventListener('input', refreshSummary);
        }

        refreshSummary();
    };

    if (addItemBtn) {
        addItemBtn.addEventListener('click', addRow);
    }

    if (tbody) {
        tbody.addEventListener('click', (event) => {
            const target = event.target;
            if (!(target instanceof HTMLElement) || !target.classList.contains('remove-item')) {
                return;
            }
            const row = target.closest('tr');
            if (row) {
                row.remove();
            }
            if (tbody.children.length === 0 && products.length > 0) {
                addRow();
            }
            refreshSummary();
        });
    }

    [commandSelect, notesInput, discountInput, deliveryInput].forEach((input) => {
        if (input) {
            input.addEventListener('input', refreshSummary);
            input.addEventListener('change', refreshSummary);
        }
    });

    if (form) {
        form.addEventListener('submit', (event) => {
            const rows = Array.from(tbody.querySelectorAll('tr'));
            for (const row of rows) {
                const select = row.querySelector('select[name="product_id[]"]');
                const hidden = row.querySelector('input[name="additional_item_ids[]"]');
                const productId = Number(select ? select.value || 0 : 0);
                const product = productsById[productId] || null;
                if (!product || !hidden) {
                    continue;
                }

                const selectedCount = String(hidden.value || '').split(',').map((id) => Number(id || 0)).filter((id) => id > 0).length;
                const isRequired = Boolean(product.additionals_is_required);
                const minSelection = product.additionals_min_selection !== null ? Number(product.additionals_min_selection) : (isRequired ? 1 : 0);
                if (minSelection > 0 && selectedCount < minSelection) {
                    event.preventDefault();
                    alert(`O produto ${product.name || 'selecionado'} exige ao menos ${minSelection} adicional(is).`);
                    return;
                }
            }
        });
    }

    if (canCreateOrder && products.length > 0 && tbody.children.length === 0) {
        addRow();
    }
    refreshSummary();
})();
</script>
