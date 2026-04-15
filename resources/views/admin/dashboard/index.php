<?php
$panel = is_array($panel ?? null) ? $panel : [];
$filters = is_array($panel['filters'] ?? null) ? $panel['filters'] : [];
$company = is_array($panel['company'] ?? null) ? $panel['company'] : [];
$reportViews = is_array($panel['report_views'] ?? null) ? $panel['report_views'] : ['ready' => false, 'missing' => []];
$analytics = is_array($panel['analytics'] ?? null) ? $panel['analytics'] : [];
$users = is_array($panel['users'] ?? null) ? $panel['users'] : [];
$roles = is_array($panel['roles'] ?? null) ? $panel['roles'] : [];
$supportTickets = is_array($panel['support_tickets'] ?? null) ? $panel['support_tickets'] : [];

$kpis = is_array($analytics['kpis'] ?? null) ? $analytics['kpis'] : [];
$salesByDay = is_array($analytics['sales_by_day'] ?? null) ? $analytics['sales_by_day'] : [];
$ordersByStatus = is_array($analytics['orders_by_status'] ?? null) ? $analytics['orders_by_status'] : [];
$salesByChannel = is_array($analytics['sales_by_channel'] ?? null) ? $analytics['sales_by_channel'] : [];
$paymentSummary = is_array($analytics['payment_summary'] ?? null) ? $analytics['payment_summary'] : [];
$cashKpis = is_array($analytics['cash_kpis'] ?? null) ? $analytics['cash_kpis'] : [];
$cashHistory = is_array($analytics['cash_history'] ?? null) ? $analytics['cash_history'] : [];
$topProducts = is_array($analytics['top_products'] ?? null) ? $analytics['top_products'] : [];

$activeSectionRaw = trim((string) ($activeSection ?? 'overview'));
$allowedSections = ['overview', 'branding', 'users', 'support'];
$activeSection = in_array($activeSectionRaw, $allowedSections, true) ? $activeSectionRaw : 'overview';

$startDate = trim((string) ($filters['start_date'] ?? date('Y-m-d', strtotime('-29 days'))));
$endDate = trim((string) ($filters['end_date'] ?? date('Y-m-d')));
$periodPresetFilter = trim((string) ($filters['period_preset'] ?? 'custom'));
$statusFilter = trim((string) ($filters['status'] ?? ''));
$channelFilter = trim((string) ($filters['channel'] ?? ''));
$paymentStatusFilter = trim((string) ($filters['payment_status'] ?? ''));
$minAmountFilter = trim((string) ($filters['min_amount'] ?? ''));
$maxAmountFilter = trim((string) ($filters['max_amount'] ?? ''));
$searchFilter = trim((string) ($filters['search'] ?? ''));

$formatMoney = static fn (mixed $value): string => 'R$ ' . number_format((float) $value, 2, ',', '.');
$formatInt = static fn (mixed $value): string => number_format((int) $value, 0, ',', '.');

$statusOptions = [
    '' => 'Todos os status',
    'pending' => 'Pendente',
    'received' => 'Recebido',
    'preparing' => 'Em preparo',
    'ready' => 'Pronto',
    'delivered' => 'Entregue',
    'finished' => 'Finalizado',
    'paid' => 'Pago',
    'canceled' => 'Cancelado',
];

$channelOptions = [
    '' => 'Todos os canais',
    'table' => 'Mesa',
    'delivery' => 'Entrega',
    'pickup' => 'Retirada',
    'counter' => 'Balcao',
];

$paymentStatusOptions = [
    '' => 'Todos os pagamentos',
    'pending' => 'Pendente',
    'partial' => 'Parcial',
    'paid' => 'Pago',
    'canceled' => 'Cancelado',
];

$periodPresetOptions = [
    'custom' => 'Personalizado',
    'today' => 'Hoje',
    'yesterday' => 'Ontem',
    'last7' => 'Ultimos 7 dias',
    'last30' => 'Ultimos 30 dias',
    'month_current' => 'Mes atual',
    'month_previous' => 'Mes anterior',
];

$supportPriorityLabels = [
    'low' => 'Baixa',
    'medium' => 'Media',
    'high' => 'Alta',
    'urgent' => 'Urgente',
];

$supportStatusLabels = [
    'open' => 'Aberto',
    'in_progress' => 'Em andamento',
    'resolved' => 'Resolvido',
    'closed' => 'Fechado',
];
?>

<style>
    .dash-page{display:grid;gap:16px}
    .dash-nav{display:flex;gap:8px;flex-wrap:wrap}
    .dash-tab-btn{border:1px solid #cbd5e1;background:#fff;border-radius:999px;padding:8px 12px;cursor:pointer;color:#0f172a}
    .dash-tab-btn.active{background:#1d4ed8;color:#fff;border-color:#1d4ed8}
    .dash-section{display:none;gap:16px}
    .dash-section.active{display:grid}
    .dash-filter-grid{display:grid;grid-template-columns:repeat(5,minmax(140px,1fr));gap:10px;align-items:end}
    .dash-kpi-grid{display:grid;grid-template-columns:repeat(6,minmax(130px,1fr));gap:12px}
    .dash-kpi{background:#fff;border:1px solid #e2e8f0;border-radius:12px;padding:14px;box-shadow:0 8px 20px rgba(15,23,42,.05)}
    .dash-kpi strong{display:block;font-size:22px;line-height:1.1;color:#0f172a}
    .dash-kpi span{display:block;margin-top:6px;font-size:12px;color:#64748b;text-transform:uppercase;letter-spacing:.04em}
    .dash-grid-2{display:grid;grid-template-columns:1.5fr 1fr;gap:14px}
    .dash-grid-3{display:grid;grid-template-columns:1fr 1fr 1fr;gap:14px}
    .dash-bars{display:grid;gap:8px}
    .dash-bar-row{display:grid;grid-template-columns:78px 1fr 110px;gap:8px;align-items:center}
    .dash-bar-track{height:12px;background:#e2e8f0;border-radius:999px;overflow:hidden}
    .dash-bar-fill{height:100%;display:block;background:linear-gradient(90deg,#1d4ed8,#2563eb);border-radius:999px}
    .dash-label{font-size:12px;color:#475569}
    .dash-value{font-size:12px;color:#0f172a;text-align:right;font-weight:600}
    .dash-donut-wrap{display:grid;place-items:center;gap:10px}
    .dash-donut{width:180px;height:180px;border-radius:999px;display:grid;place-items:center}
    .dash-donut-hole{width:108px;height:108px;border-radius:999px;background:#fff;display:grid;place-items:center;text-align:center;padding:6px}
    .dash-donut-hole strong{font-size:22px;line-height:1}
    .dash-donut-hole span{font-size:11px;color:#64748b;text-transform:uppercase;letter-spacing:.05em}
    .dash-legend{display:grid;gap:7px}
    .dash-legend-item{display:flex;justify-content:space-between;gap:8px;align-items:center;font-size:13px}
    .dash-dot{width:10px;height:10px;border-radius:999px;display:inline-block;margin-right:6px}
    .dash-table{width:100%;border-collapse:collapse}
    .dash-table th,.dash-table td{padding:10px;border-bottom:1px solid #e2e8f0;font-size:13px;text-align:left;vertical-align:top}
    .dash-table th{font-size:12px;text-transform:uppercase;letter-spacing:.04em;color:#64748b}
    .dash-pagination{display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;margin-top:10px}
    .dash-pagination-controls{display:flex;gap:8px;flex-wrap:wrap;align-items:center}
    .dash-page-btn{border:1px solid #cbd5e1;background:#fff;color:#0f172a;border-radius:8px;padding:7px 10px;cursor:pointer;min-width:36px}
    .dash-page-btn.is-active{background:#1d4ed8;border-color:#1d4ed8;color:#fff}
    .dash-pagination-info{font-size:12px;color:#475569}
    .brand-grid{display:grid;grid-template-columns:1.2fr 1fr;gap:14px}
    .brand-preview{display:grid;gap:10px}
    .brand-media{border:1px solid #dbeafe;border-radius:12px;background:#f8fafc;padding:10px}
    .brand-media img{display:block;width:100%;max-height:210px;object-fit:cover;border-radius:10px;border:1px solid #cbd5e1}
    .brand-media small{display:block;margin-top:6px;color:#64748b}
    .users-grid{display:grid;grid-template-columns:1fr 1.4fr;gap:14px}
    .user-card{border:1px solid #dbeafe;border-radius:10px;padding:10px;background:#f8fafc}
    .user-card summary{cursor:pointer;display:flex;justify-content:space-between;align-items:center;gap:10px}
    .user-card summary strong{font-size:14px}
    .user-card[open]{background:#fff}
    .support-grid{display:grid;grid-template-columns:1fr 1.4fr;gap:14px}
    .ticket-note{margin:0;color:#475569;font-size:13px;line-height:1.4}
    .muted{color:#64748b}
    .empty-state{padding:12px;border:1px dashed #cbd5e1;border-radius:10px;background:#f8fafc;color:#475569}
    @media (max-width:1220px){
        .dash-kpi-grid{grid-template-columns:repeat(3,minmax(120px,1fr))}
        .dash-filter-grid{grid-template-columns:repeat(3,minmax(140px,1fr))}
        .dash-grid-3{grid-template-columns:1fr 1fr}
        .brand-grid,.users-grid,.support-grid{grid-template-columns:1fr}
    }
    @media (max-width:820px){
        .dash-grid-2,.dash-grid-3{grid-template-columns:1fr}
        .dash-filter-grid{grid-template-columns:1fr 1fr}
        .dash-kpi-grid{grid-template-columns:repeat(2,minmax(120px,1fr))}
        .dash-bar-row{grid-template-columns:60px 1fr 90px}
    }
    @media (max-width:620px){
        .dash-filter-grid,.dash-kpi-grid{grid-template-columns:1fr}
        .dash-bar-row{grid-template-columns:52px 1fr 80px}
    }
</style>

<div class="dash-page">
    <div class="topbar">
        <div>
            <h1 style="margin:0">Dashboard estrategico do estabelecimento</h1>
            <p class="muted" style="margin:6px 0 0">Painel estatistico, personalizacao da marca, usuarios internos e chamados tecnicos no mesmo modulo administrativo.</p>
        </div>
        <span class="badge status-default">Acesso exclusivo: Administrador e Gerente</span>
    </div>

    <div class="dash-nav" id="dashboardSectionNav">
        <button type="button" class="dash-tab-btn<?= $activeSection === 'overview' ? ' active' : '' ?>" data-section-target="overview">Painel estatistico</button>
        <button type="button" class="dash-tab-btn<?= $activeSection === 'branding' ? ' active' : '' ?>" data-section-target="branding">Personalizacao</button>
        <button type="button" class="dash-tab-btn<?= $activeSection === 'users' ? ' active' : '' ?>" data-section-target="users">Usuarios internos</button>
        <button type="button" class="dash-tab-btn<?= $activeSection === 'support' ? ' active' : '' ?>" data-section-target="support">Fale com equipe tecnica</button>
    </div>

    <?php require __DIR__ . '/_overview.php'; ?>
    <?php require __DIR__ . '/_branding.php'; ?>
    <?php require __DIR__ . '/_users.php'; ?>
    <?php require __DIR__ . '/_support.php'; ?>
</div>

<script>
(() => {
    const sectionButtons = Array.from(document.querySelectorAll('[data-section-target]'));
    const sections = Array.from(document.querySelectorAll('[data-section]'));
    if (sectionButtons.length === 0 || sections.length === 0) {
        return;
    }

    const periodPreset = document.getElementById('dash_period_preset');
    const startDateInput = document.getElementById('dash_start_date');
    const endDateInput = document.getElementById('dash_end_date');

    const setSection = (section) => {
        sectionButtons.forEach((button) => {
            button.classList.toggle('active', button.getAttribute('data-section-target') === section);
        });
        sections.forEach((panel) => {
            panel.classList.toggle('active', panel.getAttribute('data-section') === section);
        });
        const url = new URL(window.location.href);
        url.searchParams.set('section', section);
        window.history.replaceState({}, '', `${url.pathname}?${url.searchParams.toString()}`);
    };

    sectionButtons.forEach((button) => {
        button.addEventListener('click', () => {
            const target = button.getAttribute('data-section-target') || 'overview';
            setSection(target);
        });
    });

    const refreshDateFieldState = () => {
        if (!(periodPreset instanceof HTMLSelectElement) || !(startDateInput instanceof HTMLInputElement) || !(endDateInput instanceof HTMLInputElement)) {
            return;
        }
        const isCustom = periodPreset.value === 'custom';
        startDateInput.readOnly = !isCustom;
        endDateInput.readOnly = !isCustom;
        startDateInput.style.opacity = isCustom ? '1' : '.75';
        endDateInput.style.opacity = isCustom ? '1' : '.75';
    };

    if (periodPreset instanceof HTMLSelectElement) {
        periodPreset.addEventListener('change', refreshDateFieldState);
        refreshDateFieldState();
    }

    const autoRefreshMs = 30000;
    window.setInterval(() => {
        if (document.hidden) {
            return;
        }

        const activePanel = document.querySelector('[data-section].active');
        if (!(activePanel instanceof HTMLElement) || activePanel.getAttribute('data-section') !== 'overview') {
            return;
        }

        const active = document.activeElement;
        if (active instanceof HTMLElement) {
            const tagName = active.tagName;
            if (tagName === 'INPUT' || tagName === 'TEXTAREA' || tagName === 'SELECT') {
                return;
            }
        }

        window.location.reload();
    }, autoRefreshMs);
})();
</script>
