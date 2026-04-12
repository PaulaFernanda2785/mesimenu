<?php
$product = is_array($product ?? null) ? $product : [];
$additionalGroup = is_array($additionalGroup ?? null) ? $additionalGroup : null;
$additionalItems = is_array($additionalItems ?? null) ? $additionalItems : [];
?>

<div class="topbar">
    <div>
        <h1>Adicionais do Produto</h1>
        <p>Configure nome, valor e limite de adicionais selecionaveis para este produto.</p>
    </div>
    <a class="btn secondary" href="<?= htmlspecialchars(base_url('/admin/products')) ?>">Voltar ao painel</a>
</div>

<div class="card" style="margin-bottom:16px">
    <div style="display:flex;gap:12px;align-items:center;flex-wrap:wrap">
        <div style="width:84px;height:84px;border-radius:10px;background:#e2e8f0;overflow:hidden;flex-shrink:0">
            <?php if (!empty($product['image_path'])): ?>
                <img src="<?= htmlspecialchars((string) $product['image_path']) ?>" alt="<?= htmlspecialchars((string) ($product['name'] ?? 'Produto')) ?>" style="width:100%;height:100%;object-fit:cover">
            <?php endif; ?>
        </div>
        <div>
            <strong><?= htmlspecialchars((string) ($product['name'] ?? 'Produto')) ?></strong><br>
            <small style="color:#64748b"><?= htmlspecialchars((string) ($product['slug'] ?? '-')) ?></small><br>
            <small style="color:#334155">Preco base: R$ <?= number_format((float) ($product['price'] ?? 0), 2, ',', '.') ?></small>
        </div>
    </div>
</div>

<div class="grid two">
    <div class="card">
        <h3 style="margin-top:0">Regras de selecao</h3>
        <form method="POST" action="<?= htmlspecialchars(base_url('/admin/products/additionals/rules')) ?>">
            <input type="hidden" name="product_id" value="<?= (int) ($product['id'] ?? 0) ?>">

            <div class="field">
                <label for="max_selection">Maximo de adicionais por item</label>
                <input
                    id="max_selection"
                    name="max_selection"
                    type="number"
                    min="1"
                    value="<?= $additionalGroup !== null && $additionalGroup['max_selection'] !== null ? (int) $additionalGroup['max_selection'] : 1 ?>"
                    required
                >
            </div>

            <div class="field">
                <label>
                    <input type="checkbox" name="is_required" <?= $additionalGroup !== null && (int) ($additionalGroup['is_required'] ?? 0) === 1 ? 'checked' : '' ?>>
                    Exigir ao menos um adicional
                </label>
            </div>

            <button class="btn" type="submit">Salvar regras</button>
        </form>
    </div>

    <div class="card">
        <h3 style="margin-top:0">Novo adicional</h3>
        <form method="POST" action="<?= htmlspecialchars(base_url('/admin/products/additionals/store')) ?>">
            <input type="hidden" name="product_id" value="<?= (int) ($product['id'] ?? 0) ?>">

            <div class="field">
                <label for="additional_name">Nome do adicional</label>
                <input id="additional_name" name="name" type="text" required>
            </div>

            <div class="grid two">
                <div class="field">
                    <label for="additional_price">Valor (R$)</label>
                    <input id="additional_price" name="price" type="number" min="0" step="0.01" required>
                </div>
                <div class="field">
                    <label for="display_order">Ordem de exibicao</label>
                    <input id="display_order" name="display_order" type="number" min="0" value="0">
                </div>
            </div>

            <div class="field">
                <label for="additional_description">Descricao (opcional)</label>
                <input id="additional_description" name="description" type="text" placeholder="Ex.: porcao extra">
            </div>

            <button class="btn" type="submit">Adicionar</button>
        </form>
    </div>
</div>

<div class="card" style="margin-top:16px">
    <h3 style="margin-top:0">Adicionais cadastrados</h3>
    <table>
        <thead>
            <tr>
                <th>Nome</th>
                <th>Valor</th>
                <th>Status</th>
                <th>Acoes</th>
            </tr>
        </thead>
        <tbody>
        <?php if (empty($additionalItems)): ?>
            <tr><td colspan="4">Nenhum adicional cadastrado para este produto.</td></tr>
        <?php else: ?>
            <?php foreach ($additionalItems as $additional): ?>
                <tr>
                    <td>
                        <strong><?= htmlspecialchars((string) ($additional['name'] ?? '-')) ?></strong>
                        <?php if (!empty($additional['description'])): ?>
                            <br><small><?= htmlspecialchars((string) $additional['description']) ?></small>
                        <?php endif; ?>
                    </td>
                    <td>R$ <?= number_format((float) ($additional['price'] ?? 0), 2, ',', '.') ?></td>
                    <td>
                        <span class="badge <?= htmlspecialchars((string) (($additional['status'] ?? '') === 'ativo' ? 'status-active' : 'status-inactive')) ?>">
                            <?= htmlspecialchars((string) (($additional['status'] ?? '') === 'ativo' ? 'Ativo' : 'Inativo')) ?>
                        </span>
                    </td>
                    <td>
                        <?php if (($additional['status'] ?? '') === 'ativo'): ?>
                            <form method="POST" action="<?= htmlspecialchars(base_url('/admin/products/additionals/remove')) ?>" onsubmit="return confirm('Remover este adicional?');">
                                <input type="hidden" name="product_id" value="<?= (int) ($product['id'] ?? 0) ?>">
                                <input type="hidden" name="additional_item_id" value="<?= (int) ($additional['id'] ?? 0) ?>">
                                <button class="btn secondary" type="submit">Remover</button>
                            </form>
                        <?php else: ?>
                            -
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
        </tbody>
    </table>
</div>
