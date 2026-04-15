<section class="dash-section<?= $activeSection === 'users' ? ' active' : '' ?>" data-section="users">
    <div class="users-grid">
        <div class="card">
            <h3 style="margin-top:0">Cadastro de usuario interno</h3>
            <p class="ticket-note">Cadastro/edicao por empresa com validacao de escopo por `company_id`.</p>
            <form method="POST" action="<?= htmlspecialchars(base_url('/admin/dashboard/users/store')) ?>">
                <?= form_security_fields('dashboard.users.store') ?>
                <div class="field"><label for="new_user_name">Nome</label><input id="new_user_name" name="name" type="text" required></div>
                <div class="field"><label for="new_user_email">E-mail</label><input id="new_user_email" name="email" type="email" required></div>
                <div class="field"><label for="new_user_phone">Telefone</label><input id="new_user_phone" name="phone" type="text" placeholder="Opcional"></div>
                <div class="field">
                    <label for="new_user_role">Perfil</label>
                    <select id="new_user_role" name="role_id" required>
                        <option value="">Selecione o perfil</option>
                        <?php foreach ($roles as $role): ?>
                            <option value="<?= (int) ($role['id'] ?? 0) ?>"><?= htmlspecialchars((string) ($role['name'] ?? '-')) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="field"><label for="new_user_status">Status</label><select id="new_user_status" name="status"><option value="ativo">Ativo</option><option value="inativo">Inativo</option><option value="bloqueado">Bloqueado</option></select></div>
                <div class="field"><label for="new_user_password">Senha inicial</label><input id="new_user_password" name="password" type="password" minlength="6" required></div>
                <button class="btn" type="submit">Cadastrar usuario</button>
            </form>
        </div>

        <div class="card">
            <h3 style="margin-top:0">Usuarios da empresa</h3>
            <?php if ($users === []): ?>
                <div class="empty-state">Nenhum usuario interno encontrado para esta empresa.</div>
            <?php else: ?>
                <div class="grid" style="gap:10px">
                    <?php foreach ($users as $userRow): ?>
                        <?php
                        $uId = (int) ($userRow['id'] ?? 0);
                        $uStatus = strtolower(trim((string) ($userRow['status'] ?? 'ativo')));
                        $uStatusBadge = match ($uStatus) {
                            'ativo' => 'status-active',
                            'inativo' => 'status-inactive',
                            'bloqueado' => 'status-blocked',
                            default => 'status-default',
                        };
                        ?>
                        <details class="user-card">
                            <summary>
                                <div>
                                    <strong><?= htmlspecialchars((string) ($userRow['name'] ?? 'Usuario')) ?></strong><br>
                                    <small class="muted"><?= htmlspecialchars((string) ($userRow['email'] ?? '-')) ?> | <?= htmlspecialchars((string) ($userRow['role_name'] ?? '-')) ?></small>
                                </div>
                                <span class="badge <?= htmlspecialchars($uStatusBadge) ?>"><?= htmlspecialchars(ucfirst($uStatus)) ?></span>
                            </summary>
                            <form method="POST" action="<?= htmlspecialchars(base_url('/admin/dashboard/users/update')) ?>" style="margin-top:10px">
                                <?= form_security_fields('dashboard.users.update.' . $uId) ?>
                                <input type="hidden" name="user_id" value="<?= $uId ?>">
                                <div class="grid two">
                                    <div class="field"><label>Nome</label><input name="name" type="text" required value="<?= htmlspecialchars((string) ($userRow['name'] ?? '')) ?>"></div>
                                    <div class="field"><label>E-mail</label><input name="email" type="email" required value="<?= htmlspecialchars((string) ($userRow['email'] ?? '')) ?>"></div>
                                </div>
                                <div class="grid two">
                                    <div class="field"><label>Telefone</label><input name="phone" type="text" value="<?= htmlspecialchars((string) ($userRow['phone'] ?? '')) ?>"></div>
                                    <div class="field">
                                        <label>Perfil</label>
                                        <select name="role_id" required>
                                            <?php foreach ($roles as $role): ?>
                                                <?php $roleId = (int) ($role['id'] ?? 0); ?>
                                                <option value="<?= $roleId ?>" <?= $roleId === (int) ($userRow['role_id'] ?? 0) ? 'selected' : '' ?>><?= htmlspecialchars((string) ($role['name'] ?? '-')) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="grid two">
                                    <div class="field"><label>Status</label><select name="status"><option value="ativo" <?= $uStatus === 'ativo' ? 'selected' : '' ?>>Ativo</option><option value="inativo" <?= $uStatus === 'inativo' ? 'selected' : '' ?>>Inativo</option><option value="bloqueado" <?= $uStatus === 'bloqueado' ? 'selected' : '' ?>>Bloqueado</option></select></div>
                                    <div class="field"><label>Nova senha (opcional)</label><input name="password" type="password" minlength="6" placeholder="Preencha apenas para alterar"></div>
                                </div>
                                <button class="btn secondary" type="submit">Salvar alteracoes</button>
                            </form>
                        </details>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>
