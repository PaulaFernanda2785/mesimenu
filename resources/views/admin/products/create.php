<?php
$product = is_array($product ?? null) ? $product : [];
$mode = (string) ($mode ?? 'create');
$isEdit = $mode === 'edit';
$formAction = (string) ($formAction ?? base_url('/admin/products/store'));
$submitLabel = (string) ($submitLabel ?? 'Salvar produto');
$existingImagePath = (string) ($product['image_path'] ?? '');
$hasCategories = !empty($categories);
?>

<style>
    .product-form-layout{display:grid;grid-template-columns:1.3fr 1fr;gap:16px}
    .product-form-card{background:#fff;border:1px solid #e5e7eb;border-radius:14px;padding:18px}
    .dropzone{border:2px dashed #93c5fd;border-radius:14px;padding:18px;text-align:center;background:linear-gradient(180deg,#eff6ff,#f8fafc);cursor:pointer}
    .dropzone.dragover{border-color:#1d4ed8;background:#dbeafe}
    .dropzone p{margin:6px 0;color:#475569}
    .dropzone small{color:#64748b}
    .image-preview{margin-top:12px;border-radius:12px;overflow:hidden;border:1px solid #e2e8f0;min-height:160px;background:#f8fafc;display:flex;align-items:center;justify-content:center}
    .image-preview img{width:100%;max-height:240px;object-fit:cover;display:block}
    .steps{display:flex;gap:8px;flex-wrap:wrap;margin-bottom:10px}
    .step-pill{padding:4px 10px;border-radius:999px;background:#e2e8f0;color:#334155;font-size:12px}
    @media (max-width:980px){
        .product-form-layout{grid-template-columns:1fr}
    }
</style>

<div class="topbar">
    <div>
        <h1><?= $isEdit ? 'Editar Produto' : 'Novo Produto' ?></h1>
        <p>Formulario dinamico para cadastro assertivo de produtos e imagem.</p>
    </div>
    <a class="btn secondary" href="<?= htmlspecialchars(base_url('/admin/products')) ?>">Voltar ao painel</a>
</div>

<form method="POST" action="<?= htmlspecialchars($formAction) ?>" enctype="multipart/form-data">
    <?php if ($isEdit): ?>
        <input type="hidden" name="product_id" value="<?= (int) ($product['id'] ?? 0) ?>">
    <?php endif; ?>
    <input type="file" id="image_file" name="image_file" accept="image/*" style="display:none">

    <div class="product-form-layout">
        <div class="product-form-card">
            <div class="steps">
                <span class="step-pill">1. Dados basicos</span>
                <span class="step-pill">2. Preco e disponibilidade</span>
            </div>

            <div class="grid two">
                <div class="field">
                    <label for="name">Nome do produto</label>
                    <input id="name" name="name" type="text" required value="<?= htmlspecialchars((string) ($product['name'] ?? '')) ?>">
                </div>
                <div class="field">
                    <label for="slug">Slug</label>
                    <input id="slug" name="slug" type="text" required value="<?= htmlspecialchars((string) ($product['slug'] ?? '')) ?>" placeholder="ex: burger-artesanal">
                </div>
            </div>

            <div class="grid two">
                <div class="field">
                    <label for="category_id">Categoria</label>
                    <select id="category_id" name="category_id" required>
                        <option value="">Selecione</option>
                        <?php foreach (($categories ?? []) as $category): ?>
                            <option
                                value="<?= (int) ($category['id'] ?? 0) ?>"
                                <?= (int) ($product['category_id'] ?? 0) === (int) ($category['id'] ?? 0) ? 'selected' : '' ?>
                            >
                                <?= htmlspecialchars((string) ($category['name'] ?? 'Categoria')) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (!$hasCategories): ?>
                        <small style="color:#b45309">Nenhuma categoria ativa disponivel. Cadastre categorias no painel de produtos antes de salvar.</small>
                    <?php endif; ?>
                </div>
                <div class="field">
                    <label for="sku">SKU</label>
                    <input id="sku" name="sku" type="text" value="<?= htmlspecialchars((string) ($product['sku'] ?? '')) ?>">
                </div>
            </div>

            <div class="field">
                <label for="description">Descricao</label>
                <textarea id="description" name="description" rows="4"><?= htmlspecialchars((string) ($product['description'] ?? '')) ?></textarea>
            </div>

            <div class="grid two">
                <div class="field">
                    <label for="price">Preco (R$)</label>
                    <input id="price" name="price" type="number" step="0.01" min="0" required value="<?= htmlspecialchars((string) ($product['price'] ?? '0.00')) ?>">
                </div>
                <div class="field">
                    <label for="promotional_price">Preco promocional (R$)</label>
                    <input id="promotional_price" name="promotional_price" type="number" step="0.01" min="0" value="<?= htmlspecialchars((string) ($product['promotional_price'] ?? '')) ?>">
                </div>
            </div>

            <div class="grid two">
                <div class="field">
                    <label for="display_order">Ordem de exibicao</label>
                    <input id="display_order" name="display_order" type="number" min="0" value="<?= (int) ($product['display_order'] ?? 0) ?>">
                </div>
                <div class="field">
                    <label>Status operacional</label>
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;margin-top:8px">
                        <label><input type="checkbox" name="is_active" <?= !isset($product['is_active']) || (int) $product['is_active'] === 1 ? 'checked' : '' ?>> Ativo</label>
                        <label><input type="checkbox" name="is_paused" <?= (int) ($product['is_paused'] ?? 0) === 1 ? 'checked' : '' ?>> Pausado</label>
                        <label><input type="checkbox" name="is_featured" <?= (int) ($product['is_featured'] ?? 0) === 1 ? 'checked' : '' ?>> Destaque</label>
                        <label><input type="checkbox" name="allows_notes" <?= !isset($product['allows_notes']) || (int) $product['allows_notes'] === 1 ? 'checked' : '' ?>> Permite observacao</label>
                        <label><input type="checkbox" name="has_additionals" <?= (int) ($product['has_additionals'] ?? 0) === 1 ? 'checked' : '' ?>> Possui adicionais</label>
                    </div>
                </div>
            </div>
        </div>

        <div class="product-form-card">
            <div class="steps">
                <span class="step-pill">3. Imagem do produto</span>
            </div>

            <label for="image_file" style="display:block;margin-bottom:8px;font-weight:bold">Anexar imagem</label>
            <div id="dropzone" class="dropzone" tabindex="0">
                <p><strong>Selecione, arraste ou cole (Ctrl+V) a imagem aqui</strong></p>
                <small>Formatos: JPG, PNG, WEBP ou GIF. Tamanho maximo: 5MB.</small>
            </div>

            <div class="image-preview" id="imagePreview">
                <?php if ($existingImagePath !== ''): ?>
                    <img id="previewImage" src="<?= htmlspecialchars($existingImagePath) ?>" alt="Imagem do produto">
                <?php else: ?>
                    <span id="previewPlaceholder" style="color:#64748b">Nenhuma imagem selecionada.</span>
                <?php endif; ?>
            </div>

            <?php if ($isEdit && $existingImagePath !== ''): ?>
                <label style="display:block;margin-top:10px">
                    <input type="checkbox" name="remove_image"> Remover imagem atual ao salvar
                </label>
            <?php endif; ?>

            <div style="margin-top:14px">
                <button class="btn" type="submit" <?= !$hasCategories ? 'disabled' : '' ?>><?= htmlspecialchars($submitLabel) ?></button>
            </div>

            <?php if ($isEdit): ?>
                <p style="margin-top:10px;color:#64748b">
                    Para limites e itens de adicionais, use a tela de
                    <a href="<?= htmlspecialchars(base_url('/admin/products/additionals?product_id=' . (int) ($product['id'] ?? 0))) ?>">Adicionais</a>.
                </p>
            <?php endif; ?>
        </div>
    </div>
</form>

<script>
(() => {
    const input = document.getElementById('image_file');
    const dropzone = document.getElementById('dropzone');
    const preview = document.getElementById('imagePreview');
    const nameInput = document.getElementById('name');
    const slugInput = document.getElementById('slug');

    const normalizeText = (value) => String(value || '')
        .toLowerCase()
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '');
    const slugify = (value) => normalizeText(value).replace(/[^a-z0-9]+/g, '-').replace(/(^-|-$)/g, '');

    const setPreview = (file) => {
        if (!(file instanceof File)) {
            return;
        }

        const reader = new FileReader();
        reader.onload = () => {
            preview.innerHTML = '';
            const img = document.createElement('img');
            img.id = 'previewImage';
            img.src = String(reader.result || '');
            img.alt = 'Preview da imagem';
            preview.appendChild(img);
        };
        reader.readAsDataURL(file);
    };

    const assignFile = (file) => {
        if (!(file instanceof File) || !input) {
            return;
        }

        const dt = new DataTransfer();
        dt.items.add(file);
        input.files = dt.files;
        setPreview(file);
    };

    if (nameInput && slugInput) {
        nameInput.addEventListener('input', () => {
            if (slugInput.dataset.touched === '1') {
                return;
            }
            slugInput.value = slugify(nameInput.value);
        });
        slugInput.addEventListener('input', () => {
            slugInput.dataset.touched = '1';
        });
    }

    const firstImageFileFromList = (files) => {
        for (const file of files) {
            if (file && String(file.type || '').startsWith('image/')) {
                return file;
            }
        }
        return null;
    };

    if (input) {
        input.addEventListener('change', () => {
            const file = input.files && input.files.length > 0 ? input.files[0] : null;
            if (file) {
                setPreview(file);
            }
        });
    }

    if (dropzone && input) {
        dropzone.addEventListener('click', () => input.click());
        dropzone.addEventListener('keydown', (event) => {
            if (event.key === 'Enter' || event.key === ' ') {
                event.preventDefault();
                input.click();
            }
        });

        ['dragenter', 'dragover'].forEach((eventName) => {
            dropzone.addEventListener(eventName, (event) => {
                event.preventDefault();
                dropzone.classList.add('dragover');
            });
        });
        ['dragleave', 'drop'].forEach((eventName) => {
            dropzone.addEventListener(eventName, (event) => {
                event.preventDefault();
                dropzone.classList.remove('dragover');
            });
        });

        dropzone.addEventListener('drop', (event) => {
            const file = firstImageFileFromList(event.dataTransfer ? event.dataTransfer.files : []);
            if (file) {
                assignFile(file);
            }
        });
    }

    document.addEventListener('paste', (event) => {
        const clipboard = event.clipboardData;
        if (!clipboard) {
            return;
        }

        const file = firstImageFileFromList(clipboard.files || []);
        if (file) {
            assignFile(file);
        }
    });
})();
</script>
