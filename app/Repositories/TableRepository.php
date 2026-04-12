<?php
declare(strict_types=1);

namespace App\Repositories;

use PDO;

final class TableRepository extends BaseRepository
{
    public function allByCompany(int $companyId): array
    {
        $sql = "
            SELECT
                t.id,
                t.company_id,
                t.name,
                t.number,
                t.capacity,
                t.qr_code_token,
                t.status,
                t.created_at
            FROM tables t
            WHERE t.company_id = :company_id
            ORDER BY t.number ASC
        ";

        $stmt = $this->db()->prepare($sql);
        $stmt->execute(['company_id' => $companyId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function create(array $data): int
    {
        $sql = "
            INSERT INTO tables (
                company_id, name, number, capacity, qr_code_token, status, created_at
            ) VALUES (
                :company_id, :name, :number, :capacity, :qr_code_token, :status, NOW()
            )
        ";

        $stmt = $this->db()->prepare($sql);
        $stmt->execute($data);

        return (int) $this->db()->lastInsertId();
    }

    public function findByNumber(int $companyId, int $number): ?array
    {
        $stmt = $this->db()->prepare("
            SELECT id, company_id, name, number, capacity, qr_code_token, status
            FROM tables
            WHERE company_id = :company_id
              AND number = :number
            LIMIT 1
        ");
        $stmt->execute([
            'company_id' => $companyId,
            'number' => $number,
        ]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function findById(int $companyId, int $id): ?array
    {
        $stmt = $this->db()->prepare("
            SELECT id, company_id, name, number, capacity, qr_code_token, status
            FROM tables
            WHERE company_id = :company_id
              AND id = :id
            LIMIT 1
        ");
        $stmt->execute([
            'company_id' => $companyId,
            'id' => $id,
        ]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function updateStatus(int $tableId, string $status): void
    {
        $stmt = $this->db()->prepare("
            UPDATE tables
            SET status = :status,
                updated_at = NOW()
            WHERE id = :id
        ");
        $stmt->execute([
            'status' => $status,
            'id' => $tableId,
        ]);
    }
}
