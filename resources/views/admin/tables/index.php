<?php
$summary = is_array($summary ?? null) ? $summary : [];
$tables = is_array($tables ?? null) ? $tables : [];
$canManageTables = !empty($canManageTables);
?>

<style>
    .tables-page{display:grid;gap:16px}
    .kpi-grid{display:grid;grid-template-columns:repeat(5,minmax(130px,1fr));gap:12px}
    .kpi-item{background:#fff;border:1px solid #e5e7eb;border-radius:12px;padding:14px}
    .kpi-item strong{display:block;font-size:24px;line-height:1.1}
    .kpi-item span{color:#64748b;font-size:12px}
    .search-row{display:grid;grid-template-columns:1fr auto;gap:8px}
    .search-info{color:#64748b;font-size:12px;margin-top:6px}
    .tables-grid{display:grid;grid-template-columns:repeat(3,minmax(230px,1fr));gap:12px}
    .table-card{background:#fff;border:1px solid #e5e7eb;border-radius:12px;padding:12px;display:flex;flex-direction:column;gap:10px}
    .table-card.search-hit{outline:2px solid #60a5fa;outline-offset:1px}
    .table-head{display:flex;justify-content:space-between;align-items:flex-start;gap:10px}
    .table-title strong{font-size:18px;line-height:1}
    .table-title small{color:#64748b}
    .table-meta{display:grid;grid-template-columns:1fr 1fr;gap:8px}
    .table-meta-item{border:1px solid #e2e8f0;border-radius:10px;padding:9px;background:#f8fafc}
    .table-meta-item strong{display:block;font-size:11px;color:#64748b;text-transform:uppercase;letter-spacing:.04em;margin-bottom:4px}
    .table-meta-item span{font-size:14px;color:#0f172a}
    .table-actions{display:flex;gap:6px;flex-wrap:wrap;align-items:center}
    .btn-qr{background:linear-gradient(135deg,#0f766e,#0d9488);color:#fff;border:1px solid #0f766e}
    .btn-qr:hover{filter:brightness(1.04)}
    @media (max-width:1000px){
        .kpi-grid{grid-template-columns:repeat(3,minmax(120px,1fr))}
        .tables-grid{grid-template-columns:repeat(2,minmax(220px,1fr))}
    }
    @media (max-width:680px){
        .kpi-grid{grid-template-columns:repeat(2,minmax(120px,1fr))}
        .tables-grid{grid-template-columns:1fr}
    }
</style>

<div class="tables-page">
    <div class="topbar">
        <div>
            <h1>Mesas</h1>
            <p>Painel operacional com status da mesa, capacidade e acoes rapidas.</p>
        </div>
        <?php if ($canManageTables): ?>
            <a class="btn" href="<?= htmlspecialchars(base_url('/admin/tables/create')) ?>">Nova mesa</a>
        <?php endif; ?>
    </div>

    <div class="kpi-grid">
        <div class="kpi-item"><strong><?= (int) ($summary['total'] ?? 0) ?></strong><span>Total de mesas</span></div>
        <div class="kpi-item"><strong><?= (int) ($summary['livre'] ?? 0) ?></strong><span>Livres</span></div>
        <div class="kpi-item"><strong><?= (int) ($summary['ocupada'] ?? 0) ?></strong><span>Ocupadas</span></div>
        <div class="kpi-item"><strong><?= (int) ($summary['aguardando_fechamento'] ?? 0) ?></strong><span>Aguardando fechamento</span></div>
        <div class="kpi-item"><strong><?= (int) ($summary['bloqueada'] ?? 0) ?></strong><span>Bloqueadas</span></div>
    </div>

    <div class="card">
        <div class="search-row">
            <input id="tableSearch" type="text" placeholder="Buscar por numero, nome, status, capacidade ou token QR">
            <button id="clearTableSearch" class="btn secondary" type="button">Limpar</button>
        </div>
        <div id="tableSearchInfo" class="search-info">Digite para filtrar as mesas de forma inteligente.</div>
    </div>

    <div class="tables-grid" id="tablesGrid">
        <?php if (empty($tables)): ?>
            <div class="table-card">Nenhuma mesa cadastrada.</div>
        <?php else: ?>
            <?php foreach ($tables as $table): ?>
                <?php
                $tableId = (int) ($table['id'] ?? 0);
                $tableNumber = (int) ($table['number'] ?? 0);
                $tableName = trim((string) ($table['name'] ?? ''));
                $statusValue = (string) ($table['status'] ?? 'livre');
                $capacity = array_key_exists('capacity', $table) && $table['capacity'] !== null ? (int) $table['capacity'] : null;
                $token = (string) ($table['qr_code_token'] ?? '');
                $statusLabel = status_label('table_status', $statusValue);
                $searchText = strtolower(trim(implode(' ', [
                    (string) $tableNumber,
                    $tableName,
                    (string) $capacity,
                    $token,
                    $statusValue,
                    (string) $statusLabel,
                ])));
                ?>
                <div class="table-card" data-search="<?= htmlspecialchars($searchText) ?>">
                    <div class="table-head">
                        <div class="table-title">
                            <strong>Mesa <?= $tableNumber > 0 ? $tableNumber : '-' ?></strong><br>
                            <small><?= htmlspecialchars($tableName !== '' ? $tableName : 'Sem nome definido') ?></small>
                        </div>
                        <span class="badge <?= htmlspecialchars(status_badge_class('table_status', $statusValue)) ?>">
                            <?= htmlspecialchars($statusLabel) ?>
                        </span>
                    </div>

                    <div class="table-meta">
                        <div class="table-meta-item">
                            <strong>Capacidade</strong>
                            <span><?= $capacity !== null ? $capacity . ' pessoa(s)' : 'Nao informada' ?></span>
                        </div>
                        <div class="table-meta-item">
                            <strong>Token QR</strong>
                            <span title="<?= htmlspecialchars($token) ?>"><?= htmlspecialchars(substr($token, 0, 18) . (strlen($token) > 18 ? '...' : '')) ?></span>
                        </div>
                    </div>

                    <div class="table-actions">
                        <a class="btn btn-qr" href="<?= htmlspecialchars(base_url('/admin/tables/print-qr?table_id=' . $tableId)) ?>" target="_blank" rel="noopener">QR para imprimir</a>

                        <?php if ($canManageTables): ?>
                            <a class="btn secondary" href="<?= htmlspecialchars(base_url('/admin/tables/edit?table_id=' . $tableId)) ?>">Editar</a>
                            <form method="POST" action="<?= htmlspecialchars(base_url('/admin/tables/delete')) ?>" onsubmit="return confirm('Excluir esta mesa? Esta acao nao pode ser desfeita.');">
                                <?= form_security_fields('tables.delete') ?>
                                <input type="hidden" name="table_id" value="<?= $tableId ?>">
                                <button class="btn secondary" type="submit">Excluir</button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<script>
(() => {
    const cards = Array.from(document.querySelectorAll('.table-card[data-search]'));
    const searchInput = document.getElementById('tableSearch');
    const clearButton = document.getElementById('clearTableSearch');
    const searchInfo = document.getElementById('tableSearchInfo');

    const normalize = (value) => String(value || '')
        .toLowerCase()
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '');

    const applyFilter = () => {
        const rawQuery = searchInput ? searchInput.value : '';
        const tokens = normalize(rawQuery).split(/\s+/).filter(Boolean);

        let visibleCount = 0;
        let firstVisibleCard = null;

        cards.forEach((card) => {
            card.classList.remove('search-hit');
            const haystack = normalize(card.getAttribute('data-search') || '');
            const match = tokens.every((token) => haystack.includes(token));
            card.style.display = match ? '' : 'none';

            if (match) {
                visibleCount++;
                if (firstVisibleCard === null) {
                    firstVisibleCard = card;
                }
            }
        });

        if (firstVisibleCard) {
            firstVisibleCard.classList.add('search-hit');
        }

        if (searchInfo) {
            if (tokens.length === 0) {
                searchInfo.textContent = 'Digite para filtrar as mesas de forma inteligente.';
            } else {
                searchInfo.textContent = visibleCount > 0
                    ? `Filtro ativo: ${visibleCount} mesa(s) encontrada(s).`
                    : 'Nenhuma mesa encontrada para o filtro informado.';
            }
        }
    };

    if (searchInput) {
        searchInput.addEventListener('input', applyFilter);
    }

    if (clearButton && searchInput) {
        clearButton.addEventListener('click', () => {
            searchInput.value = '';
            applyFilter();
            searchInput.focus();
        });
    }

    applyFilter();
})();
</script>
