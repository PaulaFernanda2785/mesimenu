<?php
declare(strict_types=1);

namespace App\Repositories;

use PDO;

final class SubscriptionPaymentRepository extends BaseRepository
{
    public function allForSaas(): array
    {
        $sql = "
            SELECT
                sp.id,
                sp.subscription_id,
                sp.company_id,
                sp.reference_month,
                sp.reference_year,
                sp.amount,
                sp.status,
                sp.payment_method,
                sp.paid_at,
                sp.due_date,
                sp.transaction_reference,
                sp.created_at,
                c.name AS company_name,
                c.slug AS company_slug,
                s.status AS subscription_status,
                s.billing_cycle,
                p.name AS plan_name
            FROM subscription_payments sp
            INNER JOIN companies c ON c.id = sp.company_id
            INNER JOIN subscriptions s ON s.id = sp.subscription_id
            INNER JOIN plans p ON p.id = s.plan_id
            ORDER BY sp.due_date DESC, sp.id DESC
        ";

        $stmt = $this->db()->prepare($sql);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function create(array $data): int
    {
        $stmt = $this->db()->prepare("
            INSERT INTO subscription_payments (
                subscription_id,
                company_id,
                reference_month,
                reference_year,
                amount,
                status,
                payment_method,
                paid_at,
                due_date,
                transaction_reference,
                created_at
            ) VALUES (
                :subscription_id,
                :company_id,
                :reference_month,
                :reference_year,
                :amount,
                :status,
                :payment_method,
                :paid_at,
                :due_date,
                :transaction_reference,
                NOW()
            )
        ");
        $stmt->execute($data);

        return (int) $this->db()->lastInsertId();
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db()->prepare("
            SELECT
                id,
                subscription_id,
                company_id,
                reference_month,
                reference_year,
                amount,
                status,
                payment_method,
                paid_at,
                due_date,
                transaction_reference
            FROM subscription_payments
            WHERE id = :id
            LIMIT 1
        ");
        $stmt->execute(['id' => $id]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function findByReference(int $subscriptionId, int $referenceMonth, int $referenceYear): ?array
    {
        $stmt = $this->db()->prepare("
            SELECT id, subscription_id, reference_month, reference_year, status
            FROM subscription_payments
            WHERE subscription_id = :subscription_id
              AND reference_month = :reference_month
              AND reference_year = :reference_year
            LIMIT 1
        ");
        $stmt->execute([
            'subscription_id' => $subscriptionId,
            'reference_month' => $referenceMonth,
            'reference_year' => $referenceYear,
        ]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function updateStatus(
        int $paymentId,
        string $status,
        ?string $paymentMethod,
        ?string $transactionReference,
        ?string $paidAt
    ): void {
        $stmt = $this->db()->prepare("
            UPDATE subscription_payments
            SET
                status = :status,
                payment_method = :payment_method,
                transaction_reference = :transaction_reference,
                paid_at = :paid_at,
                updated_at = NOW()
            WHERE id = :id
        ");
        $stmt->execute([
            'status' => $status,
            'payment_method' => $paymentMethod,
            'transaction_reference' => $transactionReference,
            'paid_at' => $paidAt,
            'id' => $paymentId,
        ]);
    }

    public function summary(): array
    {
        $stmt = $this->db()->prepare("
            SELECT
                COUNT(*) AS total_charges,
                SUM(CASE WHEN status = 'pendente' THEN 1 ELSE 0 END) AS pending_charges,
                SUM(CASE WHEN status = 'pago' THEN 1 ELSE 0 END) AS paid_charges,
                SUM(CASE WHEN status = 'vencido' THEN 1 ELSE 0 END) AS overdue_charges,
                SUM(CASE WHEN status = 'pago' THEN amount ELSE 0 END) AS total_paid_amount
            FROM subscription_payments
        ");
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: [];
    }
}

