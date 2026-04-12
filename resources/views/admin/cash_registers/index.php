<div class="topbar">
    <div>
        <h1>Caixa</h1>
        <p>Abertura e fechamento de caixa da empresa autenticada.</p>
    </div>
    <a class="btn secondary" href="<?= htmlspecialchars(base_url('/admin/payments')) ?>">Ver pagamentos</a>
</div>

<?php if (!empty($openCashRegister)): ?>
    <div class="card" style="margin-bottom:16px">
        <h3>Caixa aberto</h3>
        <p>
            Abertura: <strong><?= htmlspecialchars((string) $openCashRegister['opened_at']) ?></strong>
            | Valor inicial: <strong>R$ <?= number_format((float) $openCashRegister['opening_amount'], 2, ',', '.') ?></strong>
        </p>
        <p>
            Entradas: <strong>R$ <?= number_format((float) ($openCashRegister['total_income'] ?? 0), 2, ',', '.') ?></strong>
            | Saidas: <strong>R$ <?= number_format((float) ($openCashRegister['total_expense'] ?? 0), 2, ',', '.') ?></strong>
            | Ajustes: <strong>R$ <?= number_format((float) ($openCashRegister['total_adjustment'] ?? 0), 2, ',', '.') ?></strong>
        </p>
        <p>
            Saldo calculado atual: <strong>R$ <?= number_format((float) ($openCashRegister['current_calculated_amount'] ?? 0), 2, ',', '.') ?></strong>
        </p>

        <form method="POST" action="<?= htmlspecialchars(base_url('/admin/cash-registers/close')) ?>">
            <div class="grid two">
                <div class="field">
                    <label for="closing_amount_reported">Valor informado no fechamento (R$)</label>
                    <input id="closing_amount_reported" name="closing_amount_reported" type="number" min="0" step="0.01" required>
                </div>
                <div class="field">
                    <label for="close_notes">Observacoes</label>
                    <input id="close_notes" name="notes" type="text" placeholder="Opcional">
                </div>
            </div>
            <button class="btn" type="submit">Fechar caixa</button>
        </form>
    </div>
<?php else: ?>
    <div class="card" style="margin-bottom:16px">
        <h3>Abrir novo caixa</h3>
        <form method="POST" action="<?= htmlspecialchars(base_url('/admin/cash-registers/open')) ?>">
            <div class="grid two">
                <div class="field">
                    <label for="opening_amount">Valor de abertura (R$)</label>
                    <input id="opening_amount" name="opening_amount" type="number" min="0" step="0.01" value="0.00" required>
                </div>
                <div class="field">
                    <label for="open_notes">Observacoes</label>
                    <input id="open_notes" name="notes" type="text" placeholder="Opcional">
                </div>
            </div>
            <button class="btn" type="submit">Abrir caixa</button>
        </form>
    </div>
<?php endif; ?>

<div class="card">
    <h3>Historico de caixas</h3>
    <table>
        <thead>
            <tr>
                <th>Status</th>
                <th>Abertura</th>
                <th>Fechamento</th>
                <th>Valor inicial</th>
                <th>Valor informado</th>
                <th>Valor calculado</th>
            </tr>
        </thead>
        <tbody>
        <?php if (empty($cashRegisters)): ?>
            <tr><td colspan="6">Nenhum caixa registrado.</td></tr>
        <?php else: ?>
            <?php foreach ($cashRegisters as $cashRegister): ?>
                <tr>
                    <td><span class="badge"><?= htmlspecialchars(status_label('cash_register_status', $cashRegister['status'] ?? null)) ?></span></td>
                    <td><?= htmlspecialchars((string) $cashRegister['opened_at']) ?></td>
                    <td><?= htmlspecialchars((string) ($cashRegister['closed_at'] ?? '-')) ?></td>
                    <td>R$ <?= number_format((float) $cashRegister['opening_amount'], 2, ',', '.') ?></td>
                    <td>
                        <?= $cashRegister['closing_amount_reported'] !== null
                            ? 'R$ ' . number_format((float) $cashRegister['closing_amount_reported'], 2, ',', '.')
                            : '-' ?>
                    </td>
                    <td>
                        <?= $cashRegister['closing_amount_calculated'] !== null
                            ? 'R$ ' . number_format((float) $cashRegister['closing_amount_calculated'], 2, ',', '.')
                            : '-' ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
        </tbody>
    </table>
</div>
