<?php
$appShellTheme = is_array($appShellTheme ?? null) ? $appShellTheme : [];
$user = is_array($user ?? null) ? $user : [];

$themePrimary = (string) ($appShellTheme['primary_color'] ?? '#1d4ed8');
$themeSecondary = (string) ($appShellTheme['secondary_color'] ?? '#0f172a');
$themeAccent = (string) ($appShellTheme['accent_color'] ?? '#0ea5e9');

$companyName = trim((string) ($appShellTheme['company_name'] ?? 'Estabelecimento'));
$brandTitle = trim((string) ($appShellTheme['title'] ?? 'Painel da Empresa'));
$brandDescription = trim((string) ($appShellTheme['description'] ?? ''));
$footerText = trim((string) ($appShellTheme['footer_text'] ?? 'Comanda360 - Sistema de gestao de atendimento e vendas.'));

$logoPath = trim((string) ($appShellTheme['logo_path'] ?? ''));
$bannerPath = trim((string) ($appShellTheme['banner_path'] ?? ''));
$logoUrl = $logoPath !== '' ? asset_url($logoPath) : '';
$bannerUrl = $bannerPath !== '' ? asset_url($bannerPath) : '';

$userName = trim((string) ($user['name'] ?? 'Usuario'));
$userRole = trim((string) ($user['role_name'] ?? 'Perfil'));
$currentPath = (string) (parse_url((string) ($_SERVER['REQUEST_URI'] ?? ''), PHP_URL_PATH) ?? '');

$navItems = [
    ['/admin/dashboard', 'Dashboard'],
    ['/admin/products', 'Produtos'],
    ['/admin/tables', 'Mesas'],
    ['/admin/commands', 'Comandas'],
    ['/admin/orders', 'Pedidos'],
    ['/admin/kitchen', 'Cozinha'],
    ['/admin/delivery-zones', 'Zonas de Entrega'],
    ['/admin/deliveries', 'Entregas'],
    ['/admin/payments', 'Pagamentos'],
    ['/admin/cash-registers', 'Caixa'],
];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($title ?? 'Sistema') ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        :root{
            --theme-primary:<?= htmlspecialchars($themePrimary) ?>;
            --theme-secondary:<?= htmlspecialchars($themeSecondary) ?>;
            --theme-accent:<?= htmlspecialchars($themeAccent) ?>;
            --surface:#ffffff;
            --surface-soft:#f8fafc;
            --line:#e5e7eb;
            --text:#0f172a;
            --text-muted:#475569;
            --text-light:#e2e8f0;
        }

        *{box-sizing:border-box}
        body{font-family:Arial,sans-serif;margin:0;background:#eef2f7;color:var(--text)}
        .shell{display:grid;grid-template-columns:280px minmax(0,1fr);min-height:100vh}

        aside{
            background:linear-gradient(185deg,var(--theme-secondary) 0%,#111827 100%);
            color:#fff;
            padding:20px 18px;
            display:grid;
            align-content:start;
            gap:16px;
            border-right:1px solid rgba(148,163,184,.22);
        }
        .brand-stack{display:grid;gap:10px}
        .brand-logo-wrap{width:84px;height:84px;border-radius:16px;background:rgba(255,255,255,.08);display:grid;place-items:center;border:1px solid rgba(255,255,255,.18);overflow:hidden}
        .brand-logo-wrap img{width:100%;height:100%;object-fit:cover}
        .brand-title{margin:0;font-size:14px;font-weight:600;color:#f8fafc;letter-spacing:.02em}
        aside h2{margin:0;font-size:24px;line-height:1.1;letter-spacing:.01em}
        .brand-company{margin:0;color:#cbd5e1;font-size:12px}

        .nav-links{display:grid;gap:2px}
        .nav-links a{
            display:block;
            color:#cbd5e1;
            text-decoration:none;
            padding:10px 12px;
            border-radius:10px;
            transition:all .2s ease;
            font-size:14px;
        }
        .nav-links a:hover{color:#fff;background:rgba(255,255,255,.08)}
        .nav-links a.active{color:#fff;background:rgba(255,255,255,.16);border:1px solid rgba(255,255,255,.2)}

        .shell-main{display:grid;grid-template-rows:auto 1fr auto;min-height:100vh}
        .shell-header{
            padding:14px 22px;
            border-bottom:1px solid var(--line);
            background:
                linear-gradient(110deg, rgba(15,23,42,.82) 0%, rgba(15,23,42,.55) 48%, rgba(15,23,42,.66) 100%),
                linear-gradient(120deg, var(--theme-accent) 0%, #0f172a 100%);
            color:#fff;
            display:flex;
            justify-content:space-between;
            align-items:center;
            gap:14px;
            min-height:108px;
        }
        .shell-header.with-banner{
            background:
                linear-gradient(110deg, rgba(15,23,42,.82) 0%, rgba(15,23,42,.58) 48%, rgba(15,23,42,.7) 100%),
                url('<?= htmlspecialchars($bannerUrl) ?>') center/cover no-repeat;
        }
        .shell-header h1{margin:0;font-size:22px;line-height:1.2}
        .shell-header p{margin:6px 0 0;color:#dbeafe;font-size:13px;max-width:720px}

        .shell-user-chip{
            background:rgba(15,23,42,.42);
            border:1px solid rgba(148,163,184,.4);
            border-radius:12px;
            padding:10px 12px;
            text-align:right;
            min-width:220px;
            backdrop-filter:blur(2px);
        }
        .shell-user-chip strong{display:block;font-size:14px}
        .shell-user-chip span{font-size:12px;color:#cbd5e1}

        main{padding:22px;overflow:auto}
        .shell-footer{
            padding:10px 22px;
            background:linear-gradient(120deg, rgba(15,23,42,.9) 0%, rgba(15,23,42,.75) 100%);
            color:#cbd5e1;
            font-size:12px;
            border-top:1px solid rgba(148,163,184,.25);
        }

        .card{background:var(--surface);border-radius:12px;padding:24px;box-shadow:0 10px 28px rgba(15,23,42,.08);border:1px solid rgba(226,232,240,.9)}
        .flash{padding:12px 14px;border-radius:8px;margin-bottom:16px}
        .flash.success{background:#dcfce7;color:#166534}
        .flash.error{background:#fee2e2;color:#991b1b}
        table{width:100%;border-collapse:collapse;margin-top:16px}
        th,td{padding:12px;border-bottom:1px solid #e5e7eb;text-align:left}
        .topbar{display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;gap:16px}

        .btn{display:inline-block;padding:10px 14px;background:var(--theme-primary);color:#fff;text-decoration:none;border-radius:8px;border:0;cursor:pointer}
        .btn.secondary{background:#475569}
        .grid{display:grid;gap:16px}
        .grid.two{grid-template-columns:1fr 1fr}
        input, select, textarea{width:100%;padding:10px;border:1px solid #cbd5e1;border-radius:8px;box-sizing:border-box;background:#fff}
        label{display:block;font-weight:bold;margin-bottom:6px}
        .field{margin-bottom:14px}

        .badge{display:inline-block;padding:4px 8px;border-radius:999px;background:#e2e8f0;color:#334155;font-size:12px}
        .badge.status-default{background:#e2e8f0;color:#334155}
        .badge.status-pending{background:#fef3c7;color:#92400e}
        .badge.status-received{background:#dbeafe;color:#1e40af}
        .badge.status-preparing{background:#e0e7ff;color:#4338ca}
        .badge.status-ready{background:#dcfce7;color:#166534}
        .badge.status-delivered{background:#cffafe;color:#155e75}
        .badge.status-finished{background:#bbf7d0;color:#14532d}
        .badge.status-paid{background:#bbf7d0;color:#14532d}
        .badge.status-paid-waiting-production{background:#fed7aa;color:#9a3412}
        .badge.status-partial{background:#fde68a;color:#78350f}
        .badge.status-canceled{background:#fee2e2;color:#991b1b}
        .badge.status-failed{background:#fecaca;color:#7f1d1d}
        .badge.status-refunded{background:#fce7f3;color:#9d174d}
        .badge.status-open{background:#d1fae5;color:#065f46}
        .badge.status-closed{background:#e5e7eb;color:#374151}
        .badge.status-free{background:#d1fae5;color:#065f46}
        .badge.status-busy{background:#fee2e2;color:#991b1b}
        .badge.status-waiting{background:#fef3c7;color:#92400e}
        .badge.status-blocked{background:#d1d5db;color:#111827}
        .badge.status-overdue{background:#fecaca;color:#7f1d1d}
        .badge.status-trial{background:#e0f2fe;color:#075985}
        .badge.status-active{background:#dcfce7;color:#166534}
        .badge.status-suspended{background:#f3e8ff;color:#6b21a8}
        .badge.status-inactive{background:#f3f4f6;color:#4b5563}
        .badge.status-success{background:#dcfce7;color:#166534}

        .btn.is-loading{opacity:.92;pointer-events:none}
        .btn-spinner{display:inline-block;width:12px;height:12px;border:2px solid rgba(255,255,255,.65);border-top-color:#fff;border-radius:50%;animation:btnspin .7s linear infinite;vertical-align:-2px;margin-right:6px}
        @keyframes btnspin{to{transform:rotate(360deg)}}

        @media (max-width:980px){
            .shell{grid-template-columns:1fr}
            aside{grid-template-columns:1fr;gap:14px;border-right:0;border-bottom:1px solid rgba(148,163,184,.2)}
            .brand-stack{grid-template-columns:auto 1fr;align-items:center}
            .brand-title,.brand-company{grid-column:2}
            .nav-links{grid-template-columns:repeat(auto-fit,minmax(140px,1fr));gap:8px}
            .shell-header{flex-direction:column;align-items:flex-start;min-height:auto}
            .shell-user-chip{width:100%;text-align:left;min-width:0}
            main{padding:16px}
            .shell-footer{padding:10px 16px}
        }
    </style>
</head>
<body>
<div class="shell">
    <aside>
        <div class="brand-stack">
            <div class="brand-logo-wrap">
                <?php if ($logoUrl !== ''): ?>
                    <img src="<?= htmlspecialchars($logoUrl) ?>" alt="Logo do estabelecimento">
                <?php endif; ?>
            </div>
            <p class="brand-title"><?= htmlspecialchars($brandTitle !== '' ? $brandTitle : $companyName) ?></p>
            <h2>Comanda360</h2>
            <p class="brand-company"><?= htmlspecialchars($companyName) ?></p>
        </div>

        <nav class="nav-links">
            <?php foreach ($navItems as $item): ?>
                <?php
                $path = (string) ($item[0] ?? '#');
                $label = (string) ($item[1] ?? 'Menu');
                $isActive = $currentPath === $path || str_ends_with($currentPath, $path);
                ?>
                <a href="<?= htmlspecialchars(base_url($path)) ?>" class="<?= $isActive ? 'active' : '' ?>">
                    <?= htmlspecialchars($label) ?>
                </a>
            <?php endforeach; ?>
            <a href="<?= htmlspecialchars(base_url('/logout')) ?>">Sair</a>
        </nav>
    </aside>

    <div class="shell-main">
        <header class="shell-header<?= $bannerUrl !== '' ? ' with-banner' : '' ?>">
            <div>
                <h1><?= htmlspecialchars($title ?? 'Painel Administrativo') ?></h1>
                <?php if ($brandDescription !== ''): ?>
                    <p><?= htmlspecialchars($brandDescription) ?></p>
                <?php else: ?>
                    <p>Painel de operacao, relatorios e configuracoes do seu estabelecimento.</p>
                <?php endif; ?>
            </div>
            <div class="shell-user-chip">
                <strong><?= htmlspecialchars($userName) ?></strong>
                <span><?= htmlspecialchars($userRole) ?></span>
            </div>
        </header>

        <main>
            <?php if (!empty($flashSuccess)): ?>
                <div class="flash success"><?= htmlspecialchars($flashSuccess) ?></div>
            <?php endif; ?>

            <?php if (!empty($flashError)): ?>
                <div class="flash error"><?= htmlspecialchars($flashError) ?></div>
            <?php endif; ?>

            <?= $content ?>
        </main>

        <footer class="shell-footer">
            <?= htmlspecialchars($footerText) ?>
        </footer>
    </div>
</div>
<script>
(() => {
    const findSubmitControls = (form) => Array.from(form.querySelectorAll('button[type="submit"], input[type="submit"]'));

    const setLoadingState = (control) => {
        if (!control || control.dataset.processing === '1') {
            return;
        }

        control.dataset.processing = '1';
        control.disabled = true;
        control.classList.add('is-loading');

        if (control instanceof HTMLButtonElement) {
            control.dataset.originalHtml = control.innerHTML;
            control.innerHTML = '<span class="btn-spinner" aria-hidden="true"></span>Processando...';
        } else if (control instanceof HTMLInputElement) {
            control.dataset.originalValue = control.value;
            control.value = 'Processando...';
        }
    };

    document.addEventListener('submit', (event) => {
        const form = event.target;
        if (!(form instanceof HTMLFormElement)) {
            return;
        }

        if (form.method.toLowerCase() !== 'post') {
            return;
        }

        if (form.dataset.submitting === '1') {
            event.preventDefault();
            return;
        }

        form.dataset.submitting = '1';

        const controls = findSubmitControls(form);
        controls.forEach((control) => {
            control.disabled = true;
        });

        const submitter = event.submitter instanceof HTMLElement ? event.submitter : null;
        const preferred = submitter && (submitter instanceof HTMLButtonElement || submitter instanceof HTMLInputElement)
            ? submitter
            : (controls[0] ?? null);
        if (preferred) {
            setLoadingState(preferred);
        }
    });
})();
</script>
</body>
</html>
