<section class="dash-section<?= $activeSection === 'support' ? ' active' : '' ?>" data-section="support">
    <div class="support-grid">
        <div class="card">
            <h3 style="margin-top:0">Fale com a equipe tecnica</h3>
            <p class="ticket-note">Abra chamados diretamente para a area do administrador do sistema. O historico fica registrado para acompanhamento.</p>
            <form method="POST" action="<?= htmlspecialchars(base_url('/admin/dashboard/support/store')) ?>">
                <?= form_security_fields('dashboard.support.store') ?>
                <div class="field"><label for="ticket_subject">Assunto</label><input id="ticket_subject" name="subject" type="text" maxlength="180" required></div>
                <div class="field"><label for="ticket_priority">Prioridade</label><select id="ticket_priority" name="priority"><option value="medium">Media</option><option value="low">Baixa</option><option value="high">Alta</option><option value="urgent">Urgente</option></select></div>
                <div class="field"><label for="ticket_description">Mensagem para equipe tecnica</label><textarea id="ticket_description" name="description" rows="6" required placeholder="Descreva problema, impacto, horario e contexto do erro."></textarea></div>
                <button class="btn" type="submit">Abrir chamado</button>
            </form>
        </div>

        <div class="card">
            <h3 style="margin-top:0">Historico de chamados</h3>
            <?php if ($supportTickets === []): ?>
                <div class="empty-state">Nenhum chamado aberto ate o momento.</div>
            <?php else: ?>
                <table class="dash-table">
                    <thead><tr><th>#</th><th>Assunto</th><th>Prioridade</th><th>Status</th><th>Abertura</th><th>Responsavel</th></tr></thead>
                    <tbody>
                        <?php foreach ($supportTickets as $ticket): ?>
                            <?php
                            $priorityRaw = strtolower(trim((string) ($ticket['priority'] ?? 'medium')));
                            $statusRaw = strtolower(trim((string) ($ticket['status'] ?? 'open')));
                            $priorityBadge = match ($priorityRaw) {
                                'low' => 'status-default',
                                'medium' => 'status-pending',
                                'high' => 'status-waiting',
                                'urgent' => 'status-canceled',
                                default => 'status-default',
                            };
                            $statusBadge = match ($statusRaw) {
                                'open' => 'status-open',
                                'in_progress' => 'status-received',
                                'resolved' => 'status-success',
                                'closed' => 'status-closed',
                                default => 'status-default',
                            };
                            ?>
                            <tr>
                                <td>#<?= (int) ($ticket['id'] ?? 0) ?></td>
                                <td>
                                    <strong><?= htmlspecialchars((string) ($ticket['subject'] ?? '-')) ?></strong>
                                    <p class="ticket-note" style="margin-top:6px"><?= nl2br(htmlspecialchars((string) ($ticket['description'] ?? '')), false) ?></p>
                                    <small class="muted">Aberto por: <?= htmlspecialchars((string) ($ticket['opened_by_user_name'] ?? '-')) ?></small>
                                </td>
                                <td><span class="badge <?= htmlspecialchars($priorityBadge) ?>"><?= htmlspecialchars($supportPriorityLabels[$priorityRaw] ?? ucfirst($priorityRaw)) ?></span></td>
                                <td><span class="badge <?= htmlspecialchars($statusBadge) ?>"><?= htmlspecialchars($supportStatusLabels[$statusRaw] ?? ucfirst($statusRaw)) ?></span></td>
                                <td><?= htmlspecialchars((string) ($ticket['created_at'] ?? '-')) ?></td>
                                <td><?= htmlspecialchars((string) ($ticket['assigned_to_user_name'] ?? 'Nao atribuido')) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</section>
