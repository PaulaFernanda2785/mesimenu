<?php
declare(strict_types=1);

namespace App\Services\Shared;

use App\Exceptions\ValidationException;
use App\Repositories\CompanyRepository;
use App\Repositories\SubscriptionPaymentRepository;
use App\Repositories\SubscriptionRepository;
use DateInterval;
use DateTimeImmutable;

final class SubscriptionPlanMigrationService
{
    public function __construct(
        private readonly CompanyRepository $companies = new CompanyRepository(),
        private readonly SubscriptionRepository $subscriptions = new SubscriptionRepository(),
        private readonly SubscriptionPaymentRepository $subscriptionPayments = new SubscriptionPaymentRepository()
    ) {}

    public function shouldApply(array $currentSubscription, array $targetSubscription, string $targetCompanySubscriptionStatus): bool
    {
        $targetCompanySubscriptionStatus = strtolower(trim($targetCompanySubscriptionStatus));
        if ($targetCompanySubscriptionStatus !== 'ativa') {
            return false;
        }

        $currentStatus = strtolower(trim((string) ($currentSubscription['status'] ?? '')));
        if (!in_array($currentStatus, ['ativa', 'trial', 'vencida'], true)) {
            return false;
        }

        return (int) ($currentSubscription['plan_id'] ?? 0) !== (int) ($targetSubscription['plan_id'] ?? 0)
            || strtolower(trim((string) ($currentSubscription['billing_cycle'] ?? ''))) !== strtolower(trim((string) ($targetSubscription['billing_cycle'] ?? '')))
            || round((float) ($currentSubscription['amount'] ?? 0), 2) !== round((float) ($targetSubscription['amount'] ?? 0), 2);
    }

    public function previewMigration(array $currentSubscription, array $targetSubscription, ?string $requestedDueDate): array
    {
        $subscriptionId = (int) ($currentSubscription['id'] ?? 0);
        if ($subscriptionId <= 0) {
            throw new ValidationException('Assinatura invalida para migracao de plano.');
        }

        $today = new DateTimeImmutable('today');
        $blockingOpenPayment = $this->findBlockingOpenPayment($subscriptionId, $today);
        if ($blockingOpenPayment !== null) {
            throw new ValidationException('Nao e possivel migrar o plano com a fatura atual em aberto ou vencida. Regularize a cobranca do ciclo vigente antes da migracao.');
        }

        [$cycleStart, $cycleEnd] = $this->resolveCycleWindow($currentSubscription);
        $currentAmount = round((float) ($currentSubscription['amount'] ?? 0), 2);
        $targetAmount = round((float) ($targetSubscription['amount'] ?? 0), 2);

        $totalSeconds = max(1, $cycleEnd->getTimestamp() - $cycleStart->getTimestamp());
        $remainingSeconds = max(0, $cycleEnd->getTimestamp() - (new DateTimeImmutable('now'))->getTimestamp());
        $unusedRatio = min(1, max(0, $remainingSeconds / $totalSeconds));
        $unusedCredit = round($currentAmount * $unusedRatio, 2);

        $balanceDue = round(max(0, $targetAmount - $unusedCredit), 2);
        $carryCredit = round(max(0, $unusedCredit - $targetAmount), 2);
        $existingCreditBalance = round((float) ($currentSubscription['billing_credit_balance'] ?? 0), 2);

        $migrationStartsAt = (new DateTimeImmutable('now'))->format('Y-m-d H:i:s');
        $migrationEndsAt = $this->buildNextCycleEnd($migrationStartsAt, (string) ($targetSubscription['billing_cycle'] ?? 'mensal'));
        $chargeDueDate = $this->normalizeDueDate($requestedDueDate, $today);

        $migrationMeta = [
            'type' => 'plan_migration',
            'migrated_at' => date('c'),
            'from_plan_id' => (int) ($currentSubscription['plan_id'] ?? 0),
            'to_plan_id' => (int) ($targetSubscription['plan_id'] ?? 0),
            'from_billing_cycle' => (string) ($currentSubscription['billing_cycle'] ?? ''),
            'to_billing_cycle' => (string) ($targetSubscription['billing_cycle'] ?? ''),
            'from_amount' => $currentAmount,
            'to_amount' => $targetAmount,
            'current_cycle_start' => $cycleStart->format(DATE_ATOM),
            'current_cycle_end' => $cycleEnd->format(DATE_ATOM),
            'unused_ratio' => round($unusedRatio, 6),
            'unused_credit' => $unusedCredit,
            'balance_due' => $balanceDue,
            'carry_credit' => $carryCredit,
            'gateway_reauthorization_required' => true,
        ];

        $subscriptionPayload = $targetSubscription;
        $subscriptionPayload['starts_at'] = $migrationStartsAt;
        $subscriptionPayload['ends_at'] = $migrationEndsAt;
        $subscriptionPayload['canceled_at'] = null;
        $subscriptionPayload['billing_credit_balance'] = round($existingCreditBalance + $carryCredit, 2);

        return [
            'subscription' => $subscriptionPayload,
            'charge_due_date' => $chargeDueDate,
            'charge_amount' => $balanceDue,
            'existing_credit_balance' => $existingCreditBalance,
            'migration_meta' => $migrationMeta,
        ];
    }

    public function applyMigration(array $currentSubscription, array $preparedMigration): void
    {
        $subscriptionId = (int) ($currentSubscription['id'] ?? 0);
        $companyId = (int) ($currentSubscription['company_id'] ?? 0);
        if ($subscriptionId <= 0 || $companyId <= 0) {
            throw new ValidationException('Assinatura invalida para aplicar a migracao de plano.');
        }

        $subscriptionPayload = is_array($preparedMigration['subscription'] ?? null) ? $preparedMigration['subscription'] : [];
        $migrationMeta = is_array($preparedMigration['migration_meta'] ?? null) ? $preparedMigration['migration_meta'] : [];
        $chargeAmount = round((float) ($preparedMigration['charge_amount'] ?? 0), 2);
        $chargeDueDate = trim((string) ($preparedMigration['charge_due_date'] ?? date('Y-m-d')));

        foreach ($this->subscriptionPayments->listOpenBySubscriptionId($subscriptionId) as $payment) {
            $paymentId = (int) ($payment['id'] ?? 0);
            if ($paymentId <= 0) {
                continue;
            }

            $details = $this->mergePaymentDetails($payment, [
                'migration_cancelled_at' => date('c'),
                'migration' => $migrationMeta,
            ]);

            $this->subscriptionPayments->updateRecord($paymentId, [
                'status' => 'cancelado',
                'payment_method' => $payment['payment_method'] ?? null,
                'paid_at' => null,
                'due_date' => (string) ($payment['due_date'] ?? date('Y-m-d')),
                'transaction_reference' => $payment['transaction_reference'] ?? null,
                'charge_origin' => 'migracao',
                'pix_code' => null,
                'pix_qr_payload' => null,
                'pix_qr_image_base64' => null,
                'pix_ticket_url' => null,
                'payment_details_json' => json_encode($details, JSON_UNESCAPED_SLASHES),
                'gateway_payment_id' => null,
                'gateway_payment_url' => null,
                'gateway_status' => null,
                'gateway_webhook_payload_json' => null,
                'gateway_last_synced_at' => null,
            ]);
        }

        $this->companies->updateSubscription($subscriptionId, $subscriptionPayload);
        $this->subscriptions->updateBillingProfile($subscriptionId, [
            'preferred_payment_method' => 'pix',
            'auto_charge_enabled' => 0,
            'card_brand' => null,
            'card_last_digits' => null,
        ]);
        $this->subscriptions->updateGatewayProfile($subscriptionId, [
            'gateway_provider' => null,
            'gateway_subscription_id' => null,
            'gateway_checkout_url' => null,
            'gateway_status' => null,
            'gateway_webhook_payload_json' => null,
            'gateway_last_synced_at' => null,
        ]);

        if ($chargeAmount <= 0) {
            return;
        }

        $this->subscriptionPayments->create([
            'subscription_id' => $subscriptionId,
            'company_id' => $companyId,
            'reference_month' => (int) date('n'),
            'reference_year' => (int) date('Y'),
            'amount' => $chargeAmount,
            'status' => 'pendente',
            'payment_method' => 'pix',
            'paid_at' => null,
            'due_date' => $chargeDueDate,
            'transaction_reference' => null,
            'charge_origin' => 'migracao',
            'pix_code' => null,
            'pix_qr_payload' => null,
            'payment_details_json' => json_encode([
                'source' => 'plan_migration',
                'migration' => $migrationMeta,
            ], JSON_UNESCAPED_SLASHES),
        ]);
    }

    private function findBlockingOpenPayment(int $subscriptionId, DateTimeImmutable $today): ?array
    {
        foreach ($this->subscriptionPayments->listOpenBySubscriptionId($subscriptionId) as $payment) {
            $dueDate = $this->dateOnly((string) ($payment['due_date'] ?? ''));
            if ($dueDate <= $today) {
                return $payment;
            }
        }

        return null;
    }

    private function resolveCycleWindow(array $subscription): array
    {
        $cycle = strtolower(trim((string) ($subscription['billing_cycle'] ?? 'mensal')));
        $anchor = $this->dateTimeOrNow((string) ($subscription['starts_at'] ?? ''));
        $now = new DateTimeImmutable('now');
        $start = $anchor;
        $end = $this->advanceCycle($start, $cycle);
        $guard = 0;

        while ($end <= $now && $guard < 240) {
            $start = $end;
            $end = $this->advanceCycle($end, $cycle);
            $guard++;
        }

        return [$start, $end];
    }

    private function buildNextCycleEnd(string $startsAt, string $billingCycle): string
    {
        $start = $this->dateTimeOrNow($startsAt);
        return $this->advanceCycle($start, strtolower(trim($billingCycle)))->format('Y-m-d H:i:s');
    }

    private function advanceCycle(DateTimeImmutable $date, string $billingCycle): DateTimeImmutable
    {
        return $billingCycle === 'anual'
            ? $date->add(new DateInterval('P1Y'))
            : $date->add(new DateInterval('P1M'));
    }

    private function dateOnly(string $value): DateTimeImmutable
    {
        $timestamp = strtotime(trim($value));
        if ($timestamp === false) {
            return new DateTimeImmutable('today');
        }

        return new DateTimeImmutable(date('Y-m-d', $timestamp));
    }

    private function dateTimeOrNow(string $value): DateTimeImmutable
    {
        $timestamp = strtotime(trim($value));
        if ($timestamp === false) {
            return new DateTimeImmutable('now');
        }

        return new DateTimeImmutable(date('Y-m-d H:i:s', $timestamp));
    }

    private function normalizeDueDate(?string $value, DateTimeImmutable $today): string
    {
        $raw = trim((string) ($value ?? ''));
        if ($raw === '') {
            return $today->format('Y-m-d');
        }

        $date = $this->dateOnly($raw);
        if ($date < $today) {
            return $today->format('Y-m-d');
        }

        return $date->format('Y-m-d');
    }

    private function mergePaymentDetails(array $payment, array $merge): array
    {
        $details = [];
        $raw = trim((string) ($payment['payment_details_json'] ?? ''));
        if ($raw !== '') {
            $decoded = json_decode($raw, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $details = $decoded;
            }
        }

        return array_replace_recursive($details, $merge);
    }
}
