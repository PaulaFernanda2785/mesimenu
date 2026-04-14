<?php
declare(strict_types=1);

namespace App\Repositories;

use PDO;

final class DeliveryRepository extends BaseRepository
{
    public function create(array $data): int
    {
        $stmt = $this->db()->prepare("
            INSERT INTO deliveries (
                company_id,
                order_id,
                delivery_address_id,
                delivery_user_id,
                status,
                delivery_fee,
                assigned_at,
                left_at,
                delivered_at,
                notes,
                created_at
            ) VALUES (
                :company_id,
                :order_id,
                :delivery_address_id,
                :delivery_user_id,
                :status,
                :delivery_fee,
                :assigned_at,
                :left_at,
                :delivered_at,
                :notes,
                NOW()
            )
        ");
        $stmt->execute($data);

        return (int) $this->db()->lastInsertId();
    }

    public function allByCompany(int $companyId, ?int $deliveryUserId = null): array
    {
        $sql = "
            SELECT
                d.id,
                d.company_id,
                d.order_id,
                d.delivery_address_id,
                d.delivery_user_id,
                d.status,
                d.delivery_fee,
                d.assigned_at,
                d.left_at,
                d.delivered_at,
                d.notes,
                d.created_at,
                o.order_number,
                o.status AS order_status,
                o.payment_status AS order_payment_status,
                o.customer_name,
                o.channel,
                da.label AS address_label,
                da.street,
                da.number,
                da.complement,
                da.neighborhood,
                da.city,
                da.state,
                da.zip_code,
                da.reference,
                dz.name AS zone_name,
                u.name AS delivery_user_name
            FROM deliveries d
            INNER JOIN orders o ON o.id = d.order_id
            INNER JOIN delivery_addresses da ON da.id = d.delivery_address_id
            LEFT JOIN delivery_zones dz ON dz.id = da.delivery_zone_id
            LEFT JOIN users u ON u.id = d.delivery_user_id
            WHERE d.company_id = :company_id
        ";

        $params = ['company_id' => $companyId];
        if ($deliveryUserId !== null && $deliveryUserId > 0) {
            $sql .= " AND d.delivery_user_id = :delivery_user_id";
            $params['delivery_user_id'] = $deliveryUserId;
        }

        $sql .= " ORDER BY
            CASE d.status
                WHEN 'pending' THEN 1
                WHEN 'assigned' THEN 2
                WHEN 'in_route' THEN 3
                WHEN 'delivered' THEN 4
                WHEN 'failed' THEN 5
                WHEN 'canceled' THEN 6
                ELSE 7
            END,
            d.created_at DESC,
            d.id DESC
        ";

        $stmt = $this->db()->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function findByIdForCompany(int $companyId, int $deliveryId): ?array
    {
        $stmt = $this->db()->prepare("
            SELECT
                d.id,
                d.company_id,
                d.order_id,
                d.delivery_address_id,
                d.delivery_user_id,
                d.status,
                d.delivery_fee,
                d.assigned_at,
                d.left_at,
                d.delivered_at,
                d.notes
            FROM deliveries d
            WHERE d.company_id = :company_id
              AND d.id = :id
            LIMIT 1
        ");
        $stmt->execute([
            'company_id' => $companyId,
            'id' => $deliveryId,
        ]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function updateProgress(array $data): void
    {
        $stmt = $this->db()->prepare("
            UPDATE deliveries
            SET
                delivery_user_id = :delivery_user_id,
                status = :status,
                assigned_at = :assigned_at,
                left_at = :left_at,
                delivered_at = :delivered_at,
                notes = :notes,
                updated_at = NOW()
            WHERE company_id = :company_id
              AND id = :id
        ");
        $stmt->execute($data);
    }

    public function findByOrderIdsForCompany(int $companyId, array $orderIds): array
    {
        $orderIds = array_values(array_unique(array_map(static fn (mixed $id): int => (int) $id, $orderIds)));
        $orderIds = array_values(array_filter($orderIds, static fn (int $id): bool => $id > 0));
        if ($orderIds === []) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($orderIds), '?'));
        $sql = "
            SELECT
                d.order_id,
                d.delivery_user_id,
                d.status,
                d.delivery_fee,
                d.notes,
                d.assigned_at,
                d.left_at,
                d.delivered_at,
                da.label AS address_label,
                da.street,
                da.number,
                da.complement,
                da.neighborhood,
                da.city,
                da.state,
                da.zip_code,
                da.reference,
                dz.name AS zone_name,
                u.name AS delivery_user_name,
                c.phone AS customer_phone
            FROM deliveries d
            INNER JOIN delivery_addresses da ON da.id = d.delivery_address_id
            LEFT JOIN delivery_zones dz ON dz.id = da.delivery_zone_id
            LEFT JOIN users u ON u.id = d.delivery_user_id
            LEFT JOIN customers c ON c.id = da.customer_id
            WHERE d.company_id = ?
              AND d.order_id IN (" . $placeholders . ")
            ORDER BY d.id DESC
        ";

        $params = array_merge([$companyId], $orderIds);
        $stmt = $this->db()->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        $indexed = [];
        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }

            $orderId = (int) ($row['order_id'] ?? 0);
            if ($orderId <= 0 || isset($indexed[$orderId])) {
                continue;
            }
            $indexed[$orderId] = $row;
        }

        return $indexed;
    }
}
