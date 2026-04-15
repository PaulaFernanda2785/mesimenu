<?php
declare(strict_types=1);

namespace App\Repositories;

use PDO;

final class DashboardRepository extends BaseRepository
{
    public function transaction(callable $callback): mixed
    {
        $db = $this->db();
        $db->beginTransaction();

        try {
            $result = $callback();
            $db->commit();
            return $result;
        } catch (\Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            throw $e;
        }
    }

    public function findCompanyProfileWithTheme(int $companyId): ?array
    {
        $stmt = $this->db()->prepare("
            SELECT
                c.id,
                c.name,
                c.email,
                c.phone,
                ct.primary_color,
                ct.secondary_color,
                ct.accent_color,
                ct.logo_path,
                ct.banner_path,
                ct.title,
                ct.description,
                ct.footer_text
            FROM companies c
            LEFT JOIN company_themes ct
                ON ct.company_id = c.id
            WHERE c.id = :company_id
            LIMIT 1
        ");
        $stmt->execute(['company_id' => $companyId]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function updateCompanyName(int $companyId, string $name): void
    {
        $stmt = $this->db()->prepare("
            UPDATE companies
            SET name = :name,
                updated_at = NOW()
            WHERE id = :company_id
        ");
        $stmt->execute([
            'name' => $name,
            'company_id' => $companyId,
        ]);
    }

    public function upsertCompanyTheme(int $companyId, array $theme): void
    {
        $stmt = $this->db()->prepare("
            INSERT INTO company_themes (
                company_id,
                primary_color,
                secondary_color,
                accent_color,
                logo_path,
                banner_path,
                title,
                description,
                footer_text,
                created_at,
                updated_at
            ) VALUES (
                :company_id,
                :primary_color,
                :secondary_color,
                :accent_color,
                :logo_path,
                :banner_path,
                :title,
                :description,
                :footer_text,
                NOW(),
                NOW()
            )
            ON DUPLICATE KEY UPDATE
                primary_color = VALUES(primary_color),
                secondary_color = VALUES(secondary_color),
                accent_color = VALUES(accent_color),
                logo_path = VALUES(logo_path),
                banner_path = VALUES(banner_path),
                title = VALUES(title),
                description = VALUES(description),
                footer_text = VALUES(footer_text),
                updated_at = NOW()
        ");

        $stmt->execute([
            'company_id' => $companyId,
            'primary_color' => $theme['primary_color'] ?? null,
            'secondary_color' => $theme['secondary_color'] ?? null,
            'accent_color' => $theme['accent_color'] ?? null,
            'logo_path' => $theme['logo_path'] ?? null,
            'banner_path' => $theme['banner_path'] ?? null,
            'title' => $theme['title'] ?? null,
            'description' => $theme['description'] ?? null,
            'footer_text' => $theme['footer_text'] ?? null,
        ]);
    }

    public function findExistingReportViews(array $viewNames): array
    {
        if ($viewNames === []) {
            return [];
        }

        $placeholders = [];
        $params = [];
        foreach ($viewNames as $index => $viewName) {
            $key = 'view_' . $index;
            $placeholders[] = ':' . $key;
            $params[$key] = strtolower(trim((string) $viewName));
        }

        $sql = "
            SELECT LOWER(table_name) AS view_name
            FROM information_schema.views
            WHERE table_schema = DATABASE()
              AND LOWER(table_name) IN (" . implode(', ', $placeholders) . ")
        ";

        $stmt = $this->db()->prepare($sql);
        $stmt->execute($params);

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        $existing = [];
        foreach ($rows as $row) {
            $name = strtolower((string) ($row['view_name'] ?? ''));
            if ($name !== '') {
                $existing[$name] = true;
            }
        }

        return array_keys($existing);
    }

    public function salesKpis(
        int $companyId,
        string $startDate,
        string $endDate,
        ?string $status,
        ?string $channel,
        ?string $paymentStatus,
        ?float $minAmount,
        ?float $maxAmount,
        ?string $search
    ): array
    {
        [$whereSql, $params] = $this->buildSalesWhere(
            $companyId,
            $startDate,
            $endDate,
            $status,
            $channel,
            $paymentStatus,
            $minAmount,
            $maxAmount,
            $search
        );

        $stmt = $this->db()->prepare("
            SELECT
                COUNT(*) AS total_orders,
                COALESCE(SUM(CASE WHEN status <> 'canceled' THEN total_amount ELSE 0 END), 0) AS total_sales,
                COALESCE(AVG(CASE WHEN status <> 'canceled' THEN total_amount END), 0) AS avg_ticket,
                COALESCE(SUM(CASE WHEN status = 'canceled' THEN 1 ELSE 0 END), 0) AS canceled_orders
            FROM vw_relatorio_vendas_pedidos
            WHERE {$whereSql}
        ");
        $stmt->execute($params);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: [];
    }

    public function salesByDay(
        int $companyId,
        string $startDate,
        string $endDate,
        ?string $status,
        ?string $channel,
        ?string $paymentStatus,
        ?float $minAmount,
        ?float $maxAmount,
        ?string $search,
        int $limit = 14
    ): array
    {
        $limit = max(1, min(60, $limit));
        [$whereSql, $params] = $this->buildSalesWhere(
            $companyId,
            $startDate,
            $endDate,
            $status,
            $channel,
            $paymentStatus,
            $minAmount,
            $maxAmount,
            $search
        );

        $stmt = $this->db()->prepare("
            SELECT
                DATE(created_at) AS report_date,
                COUNT(*) AS total_orders,
                COALESCE(SUM(CASE WHEN status <> 'canceled' THEN total_amount ELSE 0 END), 0) AS total_sales
            FROM vw_relatorio_vendas_pedidos
            WHERE {$whereSql}
            GROUP BY DATE(created_at)
            ORDER BY report_date DESC
            LIMIT {$limit}
        ");
        $stmt->execute($params);

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        return array_reverse($rows);
    }

    public function ordersByStatus(
        int $companyId,
        string $startDate,
        string $endDate,
        ?string $status,
        ?string $channel,
        ?string $paymentStatus,
        ?float $minAmount,
        ?float $maxAmount,
        ?string $search
    ): array
    {
        [$whereSql, $params] = $this->buildSalesWhere(
            $companyId,
            $startDate,
            $endDate,
            $status,
            $channel,
            $paymentStatus,
            $minAmount,
            $maxAmount,
            $search
        );

        $stmt = $this->db()->prepare("
            SELECT
                status,
                COUNT(*) AS total_orders,
                COALESCE(SUM(total_amount), 0) AS total_amount
            FROM vw_relatorio_vendas_pedidos
            WHERE {$whereSql}
            GROUP BY status
            ORDER BY total_orders DESC, status ASC
        ");
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function salesByChannel(
        int $companyId,
        string $startDate,
        string $endDate,
        ?string $status,
        ?string $channel,
        ?string $paymentStatus,
        ?float $minAmount,
        ?float $maxAmount,
        ?string $search
    ): array
    {
        [$whereSql, $params] = $this->buildSalesWhere(
            $companyId,
            $startDate,
            $endDate,
            $status,
            $channel,
            $paymentStatus,
            $minAmount,
            $maxAmount,
            $search
        );

        $stmt = $this->db()->prepare("
            SELECT
                channel,
                COUNT(*) AS total_orders,
                COALESCE(SUM(CASE WHEN status <> 'canceled' THEN total_amount ELSE 0 END), 0) AS total_sales
            FROM vw_relatorio_vendas_pedidos
            WHERE {$whereSql}
            GROUP BY channel
            ORDER BY total_sales DESC, channel ASC
        ");
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function cashClosingKpis(int $companyId, string $startDate, string $endDate): array
    {
        $stmt = $this->db()->prepare("
            SELECT
                COUNT(*) AS total_closings,
                COALESCE(SUM(closing_amount_calculated), 0) AS total_closed_amount,
                COALESCE(SUM(ABS(COALESCE(closing_amount_reported, 0) - COALESCE(closing_amount_calculated, 0))), 0) AS total_difference
            FROM vw_fechamento_caixa_resumo
            WHERE company_id = :company_id
              AND DATE(opened_at) BETWEEN :start_date AND :end_date
              AND cash_register_status = 'closed'
        ");
        $stmt->execute([
            'company_id' => $companyId,
            'start_date' => $startDate,
            'end_date' => $endDate,
        ]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: [];
    }

    public function cashClosingHistory(int $companyId, string $startDate, string $endDate, int $limit = 10): array
    {
        $limit = max(1, min(50, $limit));

        $stmt = $this->db()->prepare("
            SELECT
                cash_register_id,
                opened_at,
                closed_at,
                opened_by_user_name,
                closed_by_user_name,
                opening_amount,
                closing_amount_calculated,
                closing_amount_reported,
                cash_register_status
            FROM vw_fechamento_caixa_resumo
            WHERE company_id = :company_id
              AND DATE(opened_at) BETWEEN :start_date AND :end_date
            ORDER BY opened_at DESC, cash_register_id DESC
            LIMIT {$limit}
        ");
        $stmt->execute([
            'company_id' => $companyId,
            'start_date' => $startDate,
            'end_date' => $endDate,
        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function topProductsByCompany(int $companyId, int $limit = 6): array
    {
        $limit = max(1, min(20, $limit));

        $stmt = $this->db()->prepare("
            SELECT
                product_name,
                category_name,
                total_quantidade_vendida,
                valor_total_vendido
            FROM vw_produtos_mais_vendidos
            WHERE company_id = :company_id
            ORDER BY valor_total_vendido DESC, total_quantidade_vendida DESC
            LIMIT {$limit}
        ");
        $stmt->execute(['company_id' => $companyId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function paymentStatusSummary(
        int $companyId,
        string $startDate,
        string $endDate,
        ?string $status,
        ?string $channel,
        ?string $paymentStatus,
        ?float $minAmount,
        ?float $maxAmount,
        ?string $search
    ): array
    {
        [$whereSql, $params] = $this->buildSalesWhere(
            $companyId,
            $startDate,
            $endDate,
            $status,
            $channel,
            $paymentStatus,
            $minAmount,
            $maxAmount,
            $search
        );

        $stmt = $this->db()->prepare("
            SELECT
                payment_status,
                COUNT(*) AS total_orders,
                COALESCE(SUM(total_amount), 0) AS total_amount
            FROM vw_relatorio_vendas_pedidos
            WHERE {$whereSql}
            GROUP BY payment_status
            ORDER BY total_orders DESC, payment_status ASC
        ");
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function detailedOrdersForReport(
        int $companyId,
        string $startDate,
        string $endDate,
        ?string $status,
        ?string $channel,
        ?string $paymentStatus,
        ?float $minAmount,
        ?float $maxAmount,
        ?string $search,
        int $limit = 250
    ): array
    {
        $limit = max(10, min(500, $limit));
        [$whereSql, $params] = $this->buildSalesWhere(
            $companyId,
            $startDate,
            $endDate,
            $status,
            $channel,
            $paymentStatus,
            $minAmount,
            $maxAmount,
            $search
        );

        $stmt = $this->db()->prepare("
            SELECT
                order_id,
                order_number,
                channel,
                status,
                payment_status,
                customer_name,
                subtotal_amount,
                discount_amount,
                delivery_fee,
                total_amount,
                placed_by_user_name,
                created_at,
                canceled_at
            FROM vw_relatorio_vendas_pedidos
            WHERE {$whereSql}
            ORDER BY created_at DESC, order_id DESC
            LIMIT {$limit}
        ");
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function categorySalesByCompany(int $companyId, int $limit = 6): array
    {
        $limit = max(1, min(20, $limit));

        $stmt = $this->db()->prepare("
            SELECT
                category_name,
                total_quantidade_vendida,
                valor_total_vendido,
                total_pedidos_com_categoria
            FROM vw_vendas_por_categoria
            WHERE company_id = :company_id
            ORDER BY valor_total_vendido DESC, total_quantidade_vendida DESC
            LIMIT {$limit}
        ");
        $stmt->execute(['company_id' => $companyId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function companyRoles(): array
    {
        $stmt = $this->db()->prepare("
            SELECT
                id,
                name,
                slug
            FROM roles
            WHERE context = 'company'
            ORDER BY
                CASE slug
                    WHEN 'admin_establishment' THEN 1
                    WHEN 'manager' THEN 2
                    WHEN 'cashier' THEN 3
                    WHEN 'waiter' THEN 4
                    WHEN 'kitchen' THEN 5
                    WHEN 'delivery' THEN 6
                    ELSE 99
                END,
                name ASC
        ");
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function findCompanyRoleById(int $roleId): ?array
    {
        $stmt = $this->db()->prepare("
            SELECT id, name, slug
            FROM roles
            WHERE id = :id
              AND context = 'company'
            LIMIT 1
        ");
        $stmt->execute(['id' => $roleId]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function listUsersByCompany(int $companyId): array
    {
        $stmt = $this->db()->prepare("
            SELECT
                u.id,
                u.company_id,
                u.role_id,
                u.name,
                u.email,
                u.phone,
                u.status,
                u.created_at,
                r.name AS role_name,
                r.slug AS role_slug
            FROM users u
            INNER JOIN roles r
                ON r.id = u.role_id
            WHERE u.company_id = :company_id
              AND u.deleted_at IS NULL
              AND u.is_saas_user = 0
            ORDER BY u.created_at DESC, u.id DESC
        ");
        $stmt->execute(['company_id' => $companyId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function findUserByIdForCompany(int $companyId, int $userId): ?array
    {
        $stmt = $this->db()->prepare("
            SELECT
                id,
                company_id,
                role_id,
                name,
                email,
                phone,
                status
            FROM users
            WHERE company_id = :company_id
              AND id = :id
              AND deleted_at IS NULL
              AND is_saas_user = 0
            LIMIT 1
        ");
        $stmt->execute([
            'company_id' => $companyId,
            'id' => $userId,
        ]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function findUserByEmail(string $email): ?array
    {
        $stmt = $this->db()->prepare("
            SELECT id, company_id, deleted_at
            FROM users
            WHERE email = :email
            LIMIT 1
        ");
        $stmt->execute(['email' => $email]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function createCompanyUser(array $data): int
    {
        $stmt = $this->db()->prepare("
            INSERT INTO users (
                company_id,
                role_id,
                name,
                email,
                phone,
                password_hash,
                status,
                is_saas_user,
                created_at,
                updated_at
            ) VALUES (
                :company_id,
                :role_id,
                :name,
                :email,
                :phone,
                :password_hash,
                :status,
                0,
                NOW(),
                NOW()
            )
        ");

        $stmt->execute([
            'company_id' => $data['company_id'],
            'role_id' => $data['role_id'],
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'],
            'password_hash' => $data['password_hash'],
            'status' => $data['status'],
        ]);

        return (int) $this->db()->lastInsertId();
    }

    public function updateCompanyUser(int $companyId, int $userId, array $data): void
    {
        $sets = [
            'role_id = :role_id',
            'name = :name',
            'email = :email',
            'phone = :phone',
            'status = :status',
            'updated_at = NOW()',
        ];

        $params = [
            'company_id' => $companyId,
            'id' => $userId,
            'role_id' => $data['role_id'],
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'],
            'status' => $data['status'],
        ];

        if (!empty($data['password_hash'])) {
            $sets[] = 'password_hash = :password_hash';
            $params['password_hash'] = $data['password_hash'];
        }

        $sql = "
            UPDATE users
            SET " . implode(', ', $sets) . "
            WHERE company_id = :company_id
              AND id = :id
              AND deleted_at IS NULL
              AND is_saas_user = 0
        ";

        $stmt = $this->db()->prepare($sql);
        $stmt->execute($params);
    }

    public function supportTicketsByCompany(int $companyId, int $limit = 30): array
    {
        $limit = max(1, min(100, $limit));

        $stmt = $this->db()->prepare("
            SELECT
                st.id,
                st.subject,
                st.description,
                st.status,
                st.priority,
                st.created_at,
                st.updated_at,
                st.closed_at,
                uo.name AS opened_by_user_name,
                ua.name AS assigned_to_user_name
            FROM support_tickets st
            INNER JOIN users uo
                ON uo.id = st.opened_by_user_id
            LEFT JOIN users ua
                ON ua.id = st.assigned_to_user_id
            WHERE st.company_id = :company_id
            ORDER BY st.created_at DESC, st.id DESC
            LIMIT {$limit}
        ");
        $stmt->execute(['company_id' => $companyId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function findDefaultSupportAssignee(): ?int
    {
        $stmt = $this->db()->prepare("
            SELECT u.id
            FROM users u
            INNER JOIN roles r
                ON r.id = u.role_id
            WHERE u.deleted_at IS NULL
              AND u.status = 'ativo'
              AND u.is_saas_user = 1
              AND r.slug IN ('saas_support', 'saas_admin')
            ORDER BY
                CASE r.slug
                    WHEN 'saas_support' THEN 1
                    WHEN 'saas_admin' THEN 2
                    ELSE 3
                END,
                u.id ASC
            LIMIT 1
        ");
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            return null;
        }

        $id = (int) ($row['id'] ?? 0);
        return $id > 0 ? $id : null;
    }

    public function createSupportTicket(array $data): int
    {
        $stmt = $this->db()->prepare("
            INSERT INTO support_tickets (
                company_id,
                opened_by_user_id,
                assigned_to_user_id,
                subject,
                description,
                status,
                priority,
                created_at,
                updated_at
            ) VALUES (
                :company_id,
                :opened_by_user_id,
                :assigned_to_user_id,
                :subject,
                :description,
                'open',
                :priority,
                NOW(),
                NOW()
            )
        ");

        $stmt->execute([
            'company_id' => $data['company_id'],
            'opened_by_user_id' => $data['opened_by_user_id'],
            'assigned_to_user_id' => $data['assigned_to_user_id'],
            'subject' => $data['subject'],
            'description' => $data['description'],
            'priority' => $data['priority'],
        ]);

        return (int) $this->db()->lastInsertId();
    }

    private function buildSalesWhere(
        int $companyId,
        string $startDate,
        string $endDate,
        ?string $status,
        ?string $channel,
        ?string $paymentStatus,
        ?float $minAmount,
        ?float $maxAmount,
        ?string $search
    ): array
    {
        $where = [
            'company_id = :company_id',
            'DATE(created_at) BETWEEN :start_date AND :end_date',
        ];

        $params = [
            'company_id' => $companyId,
            'start_date' => $startDate,
            'end_date' => $endDate,
        ];

        if ($status !== null) {
            $where[] = 'status = :status';
            $params['status'] = $status;
        }

        if ($channel !== null) {
            $where[] = 'channel = :channel';
            $params['channel'] = $channel;
        }

        if ($paymentStatus !== null) {
            $where[] = 'payment_status = :payment_status';
            $params['payment_status'] = $paymentStatus;
        }

        if ($minAmount !== null) {
            $where[] = 'total_amount >= :min_amount';
            $params['min_amount'] = $minAmount;
        }

        if ($maxAmount !== null) {
            $where[] = 'total_amount <= :max_amount';
            $params['max_amount'] = $maxAmount;
        }

        if ($search !== null && $search !== '') {
            $where[] = "(
                LOWER(COALESCE(order_number, '')) LIKE :search
                OR LOWER(COALESCE(customer_name, '')) LIKE :search
                OR LOWER(COALESCE(channel, '')) LIKE :search
                OR LOWER(COALESCE(status, '')) LIKE :search
                OR LOWER(COALESCE(payment_status, '')) LIKE :search
            )";
            $params['search'] = '%' . strtolower($search) . '%';
        }

        return [implode(' AND ', $where), $params];
    }
}
