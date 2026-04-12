<?php
declare(strict_types=1);

namespace App\Repositories;

use PDO;

final class CashMovementRepository extends BaseRepository
{
    public function create(array $data): int
    {
        $stmt = $this->db()->prepare("
            INSERT INTO cash_movements (
                company_id,
                cash_register_id,
                payment_id,
                type,
                description,
                amount,
                movement_at,
                created_by_user_id
            ) VALUES (
                :company_id,
                :cash_register_id,
                :payment_id,
                :type,
                :description,
                :amount,
                NOW(),
                :created_by_user_id
            )
        ");
        $stmt->execute($data);

        return (int) $this->db()->lastInsertId();
    }

    public function totalsByCashRegister(int $companyId, int $cashRegisterId): array
    {
        $stmt = $this->db()->prepare("
            SELECT
                COALESCE(SUM(CASE WHEN type = 'income' THEN amount ELSE 0 END), 0) AS total_income,
                COALESCE(SUM(CASE WHEN type = 'expense' THEN amount ELSE 0 END), 0) AS total_expense,
                COALESCE(SUM(CASE WHEN type = 'adjustment' THEN amount ELSE 0 END), 0) AS total_adjustment
            FROM cash_movements
            WHERE company_id = :company_id
              AND cash_register_id = :cash_register_id
        ");
        $stmt->execute([
            'company_id' => $companyId,
            'cash_register_id' => $cashRegisterId,
        ]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

        return [
            'total_income' => round((float) ($row['total_income'] ?? 0), 2),
            'total_expense' => round((float) ($row['total_expense'] ?? 0), 2),
            'total_adjustment' => round((float) ($row['total_adjustment'] ?? 0), 2),
        ];
    }
}

