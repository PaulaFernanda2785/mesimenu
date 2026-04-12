<?php
declare(strict_types=1);

namespace App\Repositories;

use PDO;

final class OrderStatusHistoryRepository extends BaseRepository
{
    public function create(array $data): int
    {
        $stmt = $this->db()->prepare("
            INSERT INTO order_status_history (
                company_id,
                order_id,
                old_status,
                new_status,
                changed_by_user_id,
                changed_at,
                notes
            ) VALUES (
                :company_id,
                :order_id,
                :old_status,
                :new_status,
                :changed_by_user_id,
                NOW(),
                :notes
            )
        ");
        $stmt->execute($data);

        return (int) $this->db()->lastInsertId();
    }

    public function latestByOrderIds(int $companyId, array $orderIds): array
    {
        if ($orderIds === []) {
            return [];
        }

        $placeholders = [];
        $params = [
            'company_id_sub' => $companyId,
            'company_id_main' => $companyId,
        ];

        foreach ($orderIds as $index => $orderId) {
            $key = 'order_id_' . $index;
            $placeholders[] = ':' . $key;
            $params[$key] = (int) $orderId;
        }

        $sql = "
            SELECT
                osh.order_id,
                osh.old_status,
                osh.new_status,
                osh.changed_at,
                osh.notes,
                u.name AS changed_by_user_name
            FROM order_status_history osh
            LEFT JOIN users u ON u.id = osh.changed_by_user_id
            INNER JOIN (
                SELECT order_id, MAX(id) AS max_id
                FROM order_status_history
                WHERE company_id = :company_id_sub
                  AND order_id IN (" . implode(', ', $placeholders) . ")
                GROUP BY order_id
            ) latest
                ON latest.max_id = osh.id
            WHERE osh.company_id = :company_id_main
        ";

        $stmt = $this->db()->prepare($sql);
        $stmt->execute($params);

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        $map = [];

        foreach ($rows as $row) {
            $map[(int) $row['order_id']] = $row;
        }

        return $map;
    }
}
