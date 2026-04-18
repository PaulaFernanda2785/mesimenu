<?php
declare(strict_types=1);

namespace App\Services\Admin;

use App\Exceptions\ValidationException;
use App\Repositories\SubscriptionRepository;

final class CompanyPlanLimitService
{
    private const RESOURCE_META = [
        'users' => [
            'field' => 'plan_max_users',
            'label' => 'usuarios internos',
        ],
        'products' => [
            'field' => 'plan_max_products',
            'label' => 'produtos',
        ],
        'tables' => [
            'field' => 'plan_max_tables',
            'label' => 'mesas',
        ],
    ];

    public function __construct(
        private readonly SubscriptionRepository $subscriptions = new SubscriptionRepository()
    ) {}

    public function usageSummary(int $companyId, string $resource, int $used): array
    {
        $meta = self::RESOURCE_META[$resource] ?? null;
        if ($meta === null) {
            throw new ValidationException('Recurso invalido para validacao de limite do plano.');
        }

        $subscription = $this->subscriptions->findCurrentByCompanyId($companyId);
        $limit = null;
        $planName = 'Plano atual';
        if (is_array($subscription)) {
            $planNameRaw = trim((string) ($subscription['plan_name'] ?? ''));
            if ($planNameRaw !== '') {
                $planName = $planNameRaw;
            }

            $rawLimit = $subscription[$meta['field']] ?? null;
            if ($rawLimit !== null && $rawLimit !== '') {
                $limit = max(0, (int) $rawLimit);
            }
        }

        $remaining = $limit === null ? null : max(0, $limit - $used);
        $reached = $limit !== null && $used >= $limit;

        return [
            'resource' => $resource,
            'resource_label' => (string) $meta['label'],
            'plan_name' => $planName,
            'used' => $used,
            'limit' => $limit,
            'remaining' => $remaining,
            'is_unlimited' => $limit === null,
            'reached' => $reached,
            'limit_label' => $limit === null ? 'Ilimitado' : (string) $limit,
            'usage_label' => $limit === null
                ? ($used . ' cadastrados')
                : ($used . ' de ' . $limit . ' cadastrados'),
        ];
    }

    public function assertCanCreate(int $companyId, string $resource, int $used): void
    {
        $summary = $this->usageSummary($companyId, $resource, $used);
        if (!$summary['reached']) {
            return;
        }

        $label = (string) ($summary['resource_label'] ?? 'registros');
        $planName = (string) ($summary['plan_name'] ?? 'Plano atual');
        $limit = (int) ($summary['limit'] ?? 0);

        throw new ValidationException(
            'O plano ' . $planName . ' atingiu o limite de ' . $limit . ' ' . $label . '. Ajuste o plano no SaaS ou remova cadastros antes de criar novos registros.'
        );
    }
}
