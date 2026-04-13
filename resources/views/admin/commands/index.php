<?php
$commands = is_array($commands ?? null) ? $commands : [];

$totalCommands = count($commands);
$tablesInUse = [];
$identifiedCustomers = 0;
$commandsWithoutTable = 0;
$openedByUsers = [];

foreach ($commands as $command) {
    $tableNumber = $command['table_number'] ?? null;
    if ($tableNumber !== null && (int) $tableNumber > 0) {
        $tablesInUse[(int) $tableNumber] = true;
    } else {
        $commandsWithoutTable++;
    }

    $customerName = trim((string) ($command['customer_name'] ?? ''));
    if ($customerName !== '') {
        $identifiedCustomers++;
    }

    $openedByName = trim((string) ($command['opened_by_user_name'] ?? ''));
    if ($openedByName !== '') {
        $openedByUsers[strtolower($openedByName)] = true;
    }
}
?>

<style>
    .commands-page{display:grid;gap:16px}
    .kpi-grid{display:grid;grid-template-columns:repeat(5,minmax(130px,1fr));gap:12px}
    .kpi-item{background:#fff;border:1px solid #e5e7eb;border-radius:12px;padding:14px}
    .kpi-item strong{display:block;font-size:24px;line-height:1.1}
    .kpi-item span{color:#64748b;font-size:12px}
    .search-row{display:grid;grid-template-columns:1fr auto;gap:8px}
    .search-info{color:#64748b;font-size:12px;margin-top:6px}
    .commands-grid{display:grid;grid-template-columns:repeat(3,minmax(230px,1fr));gap:12px}
    .command-card{background:#fff;border:1px solid #e2e8f0;border-radius:12px;padding:12px;display:grid;gap:10px}
    .command-card.search-hit{outline:2px solid #60a5fa;outline-offset:1px}
    .command-head{display:flex;justify-content:space-between;align-items:flex-start;gap:8px}
    .command-title{font-size:16px;line-height:1.1}
    .command-meta{display:grid;grid-template-columns:1fr 1fr;gap:8px}
    .command-meta-item{border:1px solid #e2e8f0;border-radius:10px;padding:8px;background:#f8fafc}
    .command-meta-item strong{display:block;font-size:11px;color:#64748b;text-transform:uppercase;letter-spacing:.04em;margin-bottom:4px}
    .command-meta-item span{font-size:13px;color:#0f172a}
    .command-notes{border:1px solid #dbeafe;background:#eff6ff;border-radius:10px;padding:9px}
    .command-notes strong{display:block;font-size:11px;color:#1e3a8a;text-transform:uppercase;letter-spacing:.04em;margin-bottom:4px}
    .command-notes p{margin:0;color:#334155;font-size:13px;line-height:1.35}
    @media (max-width:1080px){
        .kpi-grid{grid-template-columns:repeat(3,minmax(120px,1fr))}
        .commands-grid{grid-template-columns:repeat(2,minmax(220px,1fr))}
    }
    @media (max-width:680px){
        .kpi-grid{grid-template-columns:repeat(2,minmax(120px,1fr))}
        .commands-grid{grid-template-columns:1fr}
    }
</style>

<div class="commands-page">
    <div class="topbar">
        <div>
            <h1>Comandas Abertas</h1>
            <p>Painel operacional de comandas ativas no novo padrao visual.</p>
        </div>
        <a class="btn" href="<?= htmlspecialchars(base_url('/admin/commands/create')) ?>">Abrir comanda</a>
    </div>

    <div class="kpi-grid">
        <div class="kpi-item"><strong><?= $totalCommands ?></strong><span>Comandas abertas</span></div>
        <div class="kpi-item"><strong><?= count($tablesInUse) ?></strong><span>Mesas com comanda</span></div>
        <div class="kpi-item"><strong><?= $identifiedCustomers ?></strong><span>Clientes identificados</span></div>
        <div class="kpi-item"><strong><?= $commandsWithoutTable ?></strong><span>Sem mesa vinculada</span></div>
        <div class="kpi-item"><strong><?= count($openedByUsers) ?></strong><span>Atendentes ativos</span></div>
    </div>

    <div class="card">
        <div class="search-row">
            <input id="commandSearch" type="text" placeholder="Buscar por mesa, cliente, atendente, status, numero da comanda ou observacoes">
            <button id="clearCommandSearch" class="btn secondary" type="button">Limpar</button>
        </div>
        <div id="commandSearchInfo" class="search-info">Digite para filtrar as comandas em tempo real.</div>
    </div>

    <div class="commands-grid" id="commandsGrid">
        <?php if (empty($commands)): ?>
            <div class="command-card">Nenhuma comanda aberta no momento.</div>
        <?php else: ?>
            <?php foreach ($commands as $command): ?>
                <?php
                $commandId = (int) ($command['id'] ?? 0);
                $tableNumber = $command['table_number'] !== null ? (int) $command['table_number'] : null;
                $customerName = trim((string) ($command['customer_name'] ?? ''));
                $openedBy = trim((string) ($command['opened_by_user_name'] ?? ''));
                $statusValue = (string) ($command['status'] ?? '');
                $statusLabel = status_label('command_status', $statusValue);
                $openedAt = (string) ($command['opened_at'] ?? '-');
                $notes = trim((string) ($command['notes'] ?? ''));
                $searchText = strtolower(trim(implode(' ', [
                    (string) $commandId,
                    $tableNumber !== null ? (string) $tableNumber : '',
                    $customerName,
                    $openedBy,
                    $statusValue,
                    (string) $statusLabel,
                    $openedAt,
                    $notes,
                ])));
                ?>
                <article class="command-card" data-search="<?= htmlspecialchars($searchText) ?>">
                    <div class="command-head">
                        <div class="command-title">
                            <strong>Comanda #<?= $commandId > 0 ? $commandId : '-' ?></strong><br>
                            <small style="color:#64748b"><?= $tableNumber !== null ? 'Mesa ' . $tableNumber : 'Mesa nao vinculada' ?></small>
                        </div>
                        <span class="badge <?= htmlspecialchars(status_badge_class('command_status', $statusValue)) ?>">
                            <?= htmlspecialchars((string) $statusLabel) ?>
                        </span>
                    </div>

                    <div class="command-meta">
                        <div class="command-meta-item">
                            <strong>Cliente</strong>
                            <span><?= htmlspecialchars($customerName !== '' ? $customerName : 'Nao informado') ?></span>
                        </div>
                        <div class="command-meta-item">
                            <strong>Aberta por</strong>
                            <span><?= htmlspecialchars($openedBy !== '' ? $openedBy : 'Nao identificado') ?></span>
                        </div>
                        <div class="command-meta-item" style="grid-column:1 / -1">
                            <strong>Abertura</strong>
                            <span><?= htmlspecialchars($openedAt) ?></span>
                        </div>
                    </div>

                    <?php if ($notes !== ''): ?>
                        <div class="command-notes">
                            <strong>Observacoes</strong>
                            <p><?= htmlspecialchars($notes) ?></p>
                        </div>
                    <?php endif; ?>
                </article>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<script>
(() => {
    const cards = Array.from(document.querySelectorAll('.command-card[data-search]'));
    const searchInput = document.getElementById('commandSearch');
    const clearButton = document.getElementById('clearCommandSearch');
    const searchInfo = document.getElementById('commandSearchInfo');

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
                searchInfo.textContent = 'Digite para filtrar as comandas em tempo real.';
            } else {
                searchInfo.textContent = visibleCount > 0
                    ? `Filtro ativo: ${visibleCount} comanda(s) encontrada(s).`
                    : 'Nenhuma comanda encontrada para o filtro informado.';
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
