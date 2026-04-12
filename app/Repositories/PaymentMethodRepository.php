<?php
declare(strict_types=1);

namespace App\Repositories;

use PDO;

final class PaymentMethodRepository extends BaseRepository
{
    public function activeByCompany(int $companyId): array
    {
        $stmt = $this->db()->prepare("
            SELECT id, company_id, name, code, status
            FROM payment_methods
            WHERE company_id = :company_id
              AND status = 'ativo'
            ORDER BY name ASC
        ");
        $stmt->execute(['company_id' => $companyId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function findActiveById(int $companyId, int $paymentMethodId): ?array
    {
        $stmt = $this->db()->prepare("
            SELECT id, company_id, name, code, status
            FROM payment_methods
            WHERE company_id = :company_id
              AND id = :id
              AND status = 'ativo'
            LIMIT 1
        ");
        $stmt->execute([
            'company_id' => $companyId,
            'id' => $paymentMethodId,
        ]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }
}

