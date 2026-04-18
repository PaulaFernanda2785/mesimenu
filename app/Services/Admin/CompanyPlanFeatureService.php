<?php
declare(strict_types=1);

namespace App\Services\Admin;

use App\Repositories\SubscriptionRepository;
use App\Services\Shared\PlanFeatureCatalogService;

final class CompanyPlanFeatureService
{
    public function __construct(
        private readonly SubscriptionRepository $subscriptions = new SubscriptionRepository(),
        private readonly PlanFeatureCatalogService $catalog = new PlanFeatureCatalogService()
    ) {}

    public function featureStateForCompany(int $companyId): array
    {
        if ($companyId <= 0) {
            return $this->catalog->defaultState();
        }

        $subscription = $this->subscriptions->findCurrentByCompanyId($companyId);
        if (!is_array($subscription)) {
            return $this->catalog->defaultState();
        }

        return $this->catalog->stateFromJson($subscription['plan_features_json'] ?? null);
    }

    public function isEnabledForCompany(int $companyId, string $featureKey): bool
    {
        $state = $this->featureStateForCompany($companyId);
        return !empty($state[$featureKey]);
    }
}
