<?php
$companyName = trim((string) ($company['name'] ?? ''));
$companyTitle = trim((string) ($company['title'] ?? ''));
$companyDescription = trim((string) ($company['description'] ?? ''));
$footerText = trim((string) ($company['footer_text'] ?? ''));
$logoPath = trim((string) ($company['logo_path'] ?? ''));
$bannerPath = trim((string) ($company['banner_path'] ?? ''));
$primaryColor = trim((string) ($company['primary_color'] ?? '#1d4ed8'));
$secondaryColor = trim((string) ($company['secondary_color'] ?? '#0f172a'));
$accentColor = trim((string) ($company['accent_color'] ?? '#0ea5e9'));
?>

<section class="dash-section<?= $activeSection === 'branding' ? ' active' : '' ?>" data-section="branding">
    <div class="brand-grid">
        <div class="card">
            <h3 style="margin-top:0">Personalizacao do estabelecimento</h3>
            <p class="ticket-note">Defina identidade visual por empresa: cores, nome, descricao, logo e banner.</p>
            <form method="POST" action="<?= htmlspecialchars(base_url('/admin/dashboard/theme')) ?>" enctype="multipart/form-data">
                <?= form_security_fields('dashboard.theme.update') ?>
                <div class="field"><label for="company_name">Nome da empresa</label><input id="company_name" name="company_name" type="text" required value="<?= htmlspecialchars($companyName) ?>"></div>
                <div class="field"><label for="title">Titulo acima do Comanda360</label><input id="title" name="title" type="text" value="<?= htmlspecialchars($companyTitle !== '' ? $companyTitle : $companyName) ?>"></div>
                <div class="field"><label for="description">Descricao da empresa</label><textarea id="description" name="description" rows="4"><?= htmlspecialchars($companyDescription) ?></textarea></div>
                <div class="field"><label for="footer_text">Texto de rodape</label><input id="footer_text" name="footer_text" type="text" value="<?= htmlspecialchars($footerText) ?>"></div>
                <div class="grid two">
                    <div class="field"><label for="primary_color">Cor dos botoes</label><input id="primary_color" name="primary_color" type="color" value="<?= htmlspecialchars($primaryColor) ?>"></div>
                    <div class="field"><label for="secondary_color">Cor do menu lateral</label><input id="secondary_color" name="secondary_color" type="color" value="<?= htmlspecialchars($secondaryColor) ?>"></div>
                </div>
                <div class="field"><label for="accent_color">Cor de destaque (cabecalho/rodape)</label><input id="accent_color" name="accent_color" type="color" value="<?= htmlspecialchars($accentColor) ?>"></div>
                <div class="field"><label for="logo_file">Logo (menu lateral e tickets)</label><input id="logo_file" name="logo_file" type="file" accept="image/*"><label style="margin-top:8px;font-weight:normal"><input type="checkbox" name="remove_logo" value="1"> Remover logo atual</label></div>
                <div class="field"><label for="banner_file">Banner do cabecalho</label><input id="banner_file" name="banner_file" type="file" accept="image/*"><label style="margin-top:8px;font-weight:normal"><input type="checkbox" name="remove_banner" value="1"> Remover banner atual</label></div>
                <button class="btn" type="submit">Salvar personalizacao</button>
            </form>
        </div>

        <div class="brand-preview">
            <div class="card brand-media">
                <h4 style="margin:0 0 8px">Preview da logo</h4>
                <?php if ($logoPath !== ''): ?>
                    <img src="<?= htmlspecialchars(asset_url($logoPath)) ?>" alt="Logo atual">
                <?php else: ?>
                    <div class="empty-state">Nenhuma logo cadastrada.</div>
                <?php endif; ?>
                <small>A logo sera exibida no menu lateral e nos tickets gerados.</small>
            </div>
            <div class="card brand-media">
                <h4 style="margin:0 0 8px">Preview do banner</h4>
                <?php if ($bannerPath !== ''): ?>
                    <img src="<?= htmlspecialchars(asset_url($bannerPath)) ?>" alt="Banner atual">
                <?php else: ?>
                    <div class="empty-state">Nenhum banner cadastrado.</div>
                <?php endif; ?>
                <small>O banner aparece no cabecalho com usuario logado e perfil.</small>
            </div>
        </div>
    </div>
</section>
