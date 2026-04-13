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
    .order-form-layout{display:grid;grid-template-columns:minmax(0,1.8fr) minmax(300px,.7fr);gap:16px}
    .order-form-card{background:#fff;border:1px solid #e5e7eb;border-radius:14px;padding:18px}
    .steps{display:flex;gap:8px;flex-wrap:wrap;margin-bottom:10px}
    .step-pill{padding:4px 10px;border-radius:999px;background:#e2e8f0;color:#334155;font-size:12px}
    .warning-strip{border:1px solid #fcd34d;background:#fffbeb;border-radius:10px;padding:10px;color:#92400e}
    .items-header{display:flex;justify-content:space-between;align-items:center;gap:8px;flex-wrap:wrap;margin-top:8px}
    .items-table-wrap{border:1px solid #e2e8f0;border-radius:12px;overflow:auto;background:#f8fafc}
    .items-table{width:100%;border-collapse:separate;border-spacing:0;margin:0;min-width:760px}
    .items-table th{background:#e2e8f0;color:#334155;font-size:12px;text-transform:uppercase;letter-spacing:.03em}
    .items-table th,.items-table td{border-bottom:1px solid #e2e8f0;padding:10px;vertical-align:top}
    .items-table tbody tr:last-child td{border-bottom:0}
    .product-picker{display:grid;gap:6px;position:relative}
    .product-suggestions{position:relative;border:1px solid #dbeafe;border-radius:10px;background:#fff;max-height:170px;overflow:auto}
    .product-suggestions[hidden]{display:none}
    .product-suggestion{width:100%;border:0;border-bottom:1px solid #e2e8f0;background:#fff;text-align:left;padding:8px;display:grid;gap:3px;cursor:pointer}
    .product-suggestion:last-child{border-bottom:0}
    .product-suggestion:hover,.product-suggestion:focus{background:#eff6ff;outline:none}
    .product-suggestion-name{font-size:12px;color:#0f172a;font-weight:600}
    .product-suggestion-meta{font-size:11px;color:#64748b}
    .product-selected-meta{font-size:11px;color:#1e40af}
    .notes-textarea{width:100%;resize:vertical;min-height:48px;max-height:180px;line-height:1.3}
    .notes-textarea.compact{min-height:44px;max-height:130px}
    .additionals-container{border:1px solid #cbd5e1;border-radius:10px;padding:8px;background:#fff}
    .additionals-shell{display:grid;gap:8px}
    .additionals-meta{display:flex;align-items:center;justify-content:space-between;gap:8px;flex-wrap:wrap}
    .additionals-rules{display:flex;gap:6px;flex-wrap:wrap}
    .additional-rule-chip{display:inline-block;padding:3px 8px;border-radius:999px;background:#e2e8f0;color:#334155;font-size:11px}
    .additional-rule-chip.required{background:#fef3c7;color:#92400e}
    .additionals-counter{font-size:11px;color:#1e40af;background:#dbeafe;padding:3px 8px;border-radius:999px}
    .additionals-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(130px,1fr));gap:8px;max-height:175px;overflow-y:auto;overflow-x:hidden;padding-right:2px}
    .additional-card{position:relative;display:grid;gap:4px;padding:8px;border:1px solid #dbeafe;border-radius:10px;background:#f8fafc;cursor:pointer;transition:all .15s ease}
    .additional-card:hover{border-color:#93c5fd;background:#eff6ff}
    .additional-card.is-selected{border-color:#1d4ed8;background:#dbeafe;box-shadow:inset 0 0 0 1px #1d4ed8}
    .additional-card input{position:absolute;opacity:0;pointer-events:none}
    .additional-card-name{font-size:12px;color:#0f172a;font-weight:600;line-height:1.25;word-break:break-word}
    .additional-card-price{font-size:11px;color:#475569}
    .additionals-placeholder{display:block;padding:8px 10px;border-radius:8px;background:#f8fafc;color:#64748b;font-size:12px}
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
        .items-table{min-width:680px}
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
                        <textarea id="notes" name="notes" class="notes-textarea" rows="2" placeholder="Opcional" <?= $canCreateOrder ? '' : 'disabled' ?>></textarea>
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
                                <th style="width:24%">Produto</th>
                                <th style="width:9%">Qtd</th>
                                <th style="width:40%">Adicionais</th>
                                <th style="width:15%">Observacao</th>
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
    const normalizeSearchText = (value) => String(value || '')
        .toLowerCase()
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '')
        .trim();

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
    const productSearchIndex = products
        .map((product) => {
            const productId = Number(product.id || 0);
            if (productId <= 0) {
                return null;
            }

            const name = String(product.name || 'Produto');
            const category = String(product.category_name || '');
            const label = category !== '' ? `${name} (${category})` : name;
            const searchText = normalizeSearchText(`${name} ${category}`);
            const searchName = normalizeSearchText(name);
            return { productId, label, searchText, searchName };
        })
        .filter(Boolean);

    const additionalPrice = (product, additionalId) => {
        if (!product || !Array.isArray(product.additionals)) {
            return 0;
        }
        const found = product.additionals.find((item) => Number(item.id || 0) === Number(additionalId || 0));
        return found ? Number(found.price || 0) : 0;
    };

    const isSubsequence = (needle, haystack) => {
        if (!needle || !haystack) {
            return false;
        }
        let index = 0;
        for (let i = 0; i < haystack.length && index < needle.length; i++) {
            if (haystack[i] === needle[index]) {
                index++;
            }
        }
        return index === needle.length;
    };

    const findProductMatches = (queryRaw) => {
        const query = normalizeSearchText(queryRaw);
        if (query === '') {
            return productSearchIndex.slice(0, 10).map((entry) => productsById[entry.productId]).filter(Boolean);
        }

        const tokens = query.split(/\s+/).filter(Boolean);
        const scored = [];

        productSearchIndex.forEach((entry) => {
            if (!entry) {
                return;
            }

            let score = 0;
            const startsWithName = entry.searchName.startsWith(query);
            const includesName = entry.searchName.includes(query);
            const includesAllTokens = tokens.every((token) => entry.searchText.includes(token));
            const fuzzyByLetters = isSubsequence(query, entry.searchText.replace(/\s+/g, ''));

            if (startsWithName) score += 120;
            if (includesName) score += 70;
            if (includesAllTokens) score += 50;
            if (fuzzyByLetters) score += 25;

            if (score <= 0) {
                return;
            }

            scored.push({
                productId: entry.productId,
                score,
            });
        });

        scored.sort((left, right) => {
            if (right.score !== left.score) {
                return right.score - left.score;
            }
            const leftName = String(productsById[left.productId]?.name || '');
            const rightName = String(productsById[right.productId]?.name || '');
            return leftName.localeCompare(rightName, 'pt-BR');
        });

        return scored.slice(0, 12)
            .map((entry) => productsById[entry.productId])
            .filter(Boolean);
    };

    const productDisplayLabel = (product) => {
        if (!product) {
            return '';
        }
        const safeName = String(product.name || 'Produto');
        const category = String(product.category_name || '');
        return category !== '' ? `${safeName} (${category})` : safeName;
    };

    const setupProductPicker = (row) => {
        const picker = row.querySelector('.product-picker');
        const searchInput = row.querySelector('.product-search-input');
        const hiddenInput = row.querySelector('input[name="product_id[]"]');
        const suggestions = row.querySelector('.product-suggestions');
        const selectedMeta = row.querySelector('.product-selected-meta');

        if (!picker || !searchInput || !hiddenInput || !suggestions || !selectedMeta) {
            return;
        }

        const closeSuggestions = () => {
            suggestions.hidden = true;
        };

        const setSelectedProduct = (product) => {
            if (!product) {
                hiddenInput.value = '';
                searchInput.value = '';
                selectedMeta.textContent = '';
                renderAdditionals(row);
                refreshSummary();
                return;
            }

            const productId = Number(product.id || 0);
            if (productId <= 0) {
                return;
            }

            hiddenInput.value = String(productId);
            searchInput.value = productDisplayLabel(product);
            selectedMeta.textContent = `Selecionado: ${productDisplayLabel(product)} - ${money(effectivePrice(product))}`;
            closeSuggestions();
            renderAdditionals(row);
            refreshSummary();
        };

        const renderSuggestions = (query) => {
            const matches = findProductMatches(query);
            if (matches.length === 0) {
                suggestions.innerHTML = '<div class="product-suggestion"><span class="product-suggestion-meta">Nenhum produto encontrado.</span></div>';
                suggestions.hidden = false;
                return;
            }

            suggestions.innerHTML = matches.map((product) => {
                const productId = Number(product.id || 0);
                const label = escapeHtml(productDisplayLabel(product));
                const category = String(product.category_name || '').trim();
                const categoryText = category !== '' ? `Categoria: ${escapeHtml(category)} | ` : '';
                const meta = `${categoryText}Preco: ${escapeHtml(money(effectivePrice(product)))}`;
                return `
                    <button class="product-suggestion" type="button" data-product-id="${productId}">
                        <span class="product-suggestion-name">${label}</span>
                        <span class="product-suggestion-meta">${meta}</span>
                    </button>
                `;
            }).join('');
            suggestions.hidden = false;
        };

        searchInput.addEventListener('input', () => {
            const query = String(searchInput.value || '').trim();
            hiddenInput.value = '';
            selectedMeta.textContent = '';
            renderAdditionals(row);
            refreshSummary();

            if (query === '') {
                closeSuggestions();
                return;
            }
            renderSuggestions(query);
        });

        searchInput.addEventListener('focus', () => {
            const query = String(searchInput.value || '').trim();
            if (query !== '') {
                renderSuggestions(query);
            }
        });

        searchInput.addEventListener('blur', () => {
            window.setTimeout(closeSuggestions, 120);
        });

        suggestions.addEventListener('mousedown', (event) => {
            event.preventDefault();
        });

        suggestions.addEventListener('click', (event) => {
            const target = event.target instanceof HTMLElement ? event.target.closest('[data-product-id]') : null;
            if (!(target instanceof HTMLElement)) {
                return;
            }

            const productId = Number(target.getAttribute('data-product-id') || 0);
            const product = productsById[productId] || null;
            if (!product) {
                return;
            }
            setSelectedProduct(product);
        });

        const initialProductId = Number(hiddenInput.value || 0);
        if (initialProductId > 0 && productsById[initialProductId]) {
            setSelectedProduct(productsById[initialProductId]);
        }
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

    const syncAdditionalSelectionUi = (container) => {
        const selectedCount = checkedAdditionalIds(container).length;
        container.querySelectorAll('.additional-card').forEach((card) => {
            const checkbox = card.querySelector('input[type="checkbox"][data-additional-id]');
            if (checkbox && checkbox.checked) {
                card.classList.add('is-selected');
            } else {
                card.classList.remove('is-selected');
            }
        });

        const counter = container.querySelector('[data-selected-count]');
        if (counter) {
            counter.textContent = `${selectedCount} selecionado(s)`;
        }
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
        const productIdInput = row.querySelector('input[name="product_id[]"]');
        const qtyInput = row.querySelector('input[name="quantity[]"]');
        const hidden = row.querySelector('input[name="additional_item_ids[]"]');
        const productId = Number(productIdInput ? productIdInput.value || 0 : 0);
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
        syncAdditionalSelectionUi(container);
    };

    const renderAdditionals = (row) => {
        const productInput = row.querySelector('input[name="product_id[]"]');
        const additionalsContainer = row.querySelector('.additionals-container');
        const hidden = row.querySelector('input[name="additional_item_ids[]"]');
        if (!productInput || !additionalsContainer || !hidden) {
            return;
        }

        hidden.value = '';
        const productId = Number(productInput.value || 0);
        const product = productsById[productId] || null;

        if (!product || !Array.isArray(product.additionals) || product.additionals.length === 0) {
            additionalsContainer.innerHTML = '<span class="additionals-placeholder">Sem adicionais disponiveis.</span>';
            refreshSummary();
            return;
        }

        const maxSelection = product.additionals_max_selection !== null ? Number(product.additionals_max_selection) : null;
        const minSelection = product.additionals_min_selection !== null ? Number(product.additionals_min_selection) : 0;
        const isRequired = Boolean(product.additionals_is_required);

        let rulesHtml = '';
        const maxLabel = maxSelection !== null ? String(maxSelection) : 'Sem limite';
        rulesHtml += `<span class="additional-rule-chip">Max: ${maxLabel}</span>`;
        rulesHtml += `<span class="additional-rule-chip">Min: ${minSelection}</span>`;
        if (isRequired) {
            rulesHtml += '<span class="additional-rule-chip required">Obrigatorio</span>';
        }

        let cardsHtml = '';
        product.additionals.forEach((additional) => {
            const additionalId = Number(additional.id || 0);
            const additionalName = escapeHtml(additional.name || 'Adicional');
            const additionalPriceText = money(Number(additional.price || 0));
            cardsHtml += `
                <label class="additional-card">
                    <input type="checkbox" data-additional-id="${additionalId}">
                    <span class="additional-card-name">${additionalName}</span>
                    <span class="additional-card-price">+ ${additionalPriceText}</span>
                </label>
            `;
        });

        additionalsContainer.innerHTML = `
            <div class="additionals-shell">
                <div class="additionals-meta">
                    <div class="additionals-rules">${rulesHtml}</div>
                    <span class="additionals-counter" data-selected-count>0 selecionado(s)</span>
                </div>
                <div class="additionals-grid">${cardsHtml}</div>
            </div>
        `;

        additionalsContainer.querySelectorAll('input[type="checkbox"][data-additional-id]').forEach((checkbox) => {
            checkbox.addEventListener('change', () => {
                additionalsContainer.querySelectorAll('input[type="checkbox"][data-additional-id]').forEach((item) => item.removeAttribute('data-last-change'));
                checkbox.setAttribute('data-last-change', '1');
                enforceMaxSelection(additionalsContainer, maxSelection);
                syncAdditionalHidden(row);
                syncAdditionalSelectionUi(additionalsContainer);
                refreshSummary();
            });
        });

        syncAdditionalSelectionUi(additionalsContainer);
        refreshSummary();
    };

    const addRow = () => {
        if (!canCreateOrder || products.length === 0) {
            return;
        }

        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td>
                <div class="product-picker">
                    <input class="product-search-input" type="text" placeholder="Digite nome/categoria do produto" autocomplete="off">
                    <input name="product_id[]" type="hidden" value="">
                    <small class="product-selected-meta"></small>
                    <div class="product-suggestions" hidden></div>
                </div>
            </td>
            <td>
                <input name="quantity[]" type="number" min="1" step="1" value="1" required>
            </td>
            <td>
                <div class="additionals-container">
                    <span class="additionals-placeholder">Selecione um produto para ver os adicionais.</span>
                </div>
                <input type="hidden" name="additional_item_ids[]" value="">
            </td>
            <td>
                <textarea name="item_notes[]" class="notes-textarea compact" rows="2" placeholder="Opcional"></textarea>
            </td>
            <td>
                <span class="line-total item-line-total">R$ 0,00</span>
            </td>
            <td>
                <button class="btn secondary remove-item" type="button">Remover</button>
            </td>
        `;
        tbody.appendChild(tr);

        const quantity = tr.querySelector('input[name="quantity[]"]');
        setupProductPicker(tr);

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
                const productIdInput = row.querySelector('input[name="product_id[]"]');
                const hidden = row.querySelector('input[name="additional_item_ids[]"]');
                const productId = Number(productIdInput ? productIdInput.value || 0 : 0);
                if (productId <= 0) {
                    event.preventDefault();
                    alert('Selecione um produto valido em todas as linhas do pedido.');
                    return;
                }
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
