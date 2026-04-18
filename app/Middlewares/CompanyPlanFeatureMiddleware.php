<?php
declare(strict_types=1);

namespace App\Middlewares;

use App\Core\Auth;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Services\Admin\CompanyPlanFeatureService;

final class CompanyPlanFeatureMiddleware
{
    public function __construct(
        private readonly string $featureKey,
        private readonly CompanyPlanFeatureService $features = new CompanyPlanFeatureService()
    ) {}

    public function handle(Request $request): ?Response
    {
        if (!Auth::check()) {
            Session::flash('error', 'Faca login para acessar esta area.');
            return Response::redirect('/login');
        }

        $user = Auth::user() ?? [];
        $companyId = (int) ($user['company_id'] ?? 0);

        if ($companyId <= 0) {
            Session::flash('error', 'Empresa invalida para validar os recursos do plano.');
            return Response::redirect('/admin/dashboard?section=subscription');
        }

        if ($this->features->isEnabledForCompany($companyId, $this->featureKey)) {
            return null;
        }

        Session::flash('error', 'O plano atual da empresa nao inclui este modulo. Ative o recurso correspondente no plano para liberar o acesso.');
        return Response::redirect('/admin/dashboard?section=subscription');
    }
}
