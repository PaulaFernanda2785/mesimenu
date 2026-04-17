<?php
declare(strict_types=1);

namespace App\Controllers\Saas;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Exceptions\ValidationException;
use App\Repositories\PermissionRepository;
use App\Services\Saas\PlanService;

final class PlanController extends Controller
{
    public function __construct(
        private readonly PlanService $service = new PlanService(),
        private readonly PermissionRepository $permissions = new PermissionRepository()
    ) {}

    public function index(Request $request): Response
    {
        $user = Auth::user() ?? [];
        $roleId = (int) ($user['role_id'] ?? 0);

        return $this->view('saas/plans/index', [
            'title' => 'Planos',
            'user' => $user,
            'planPanel' => $this->service->panel($request->query),
            'canManagePlans' => $this->permissions->roleHasPermission($roleId, 'plans.manage'),
        ], 'layouts/saas');
    }

    public function store(Request $request): Response
    {
        $redirectTo = '/saas/plans';
        $guard = $this->guardSingleSubmit($request, 'saas.plans.store', $redirectTo);
        if ($guard !== null) {
            return $guard;
        }

        try {
            $this->service->createPlan($request->all());
            return $this->backWithSuccess('Plano cadastrado com sucesso.', $redirectTo);
        } catch (ValidationException $e) {
            return $this->backWithError($e->getMessage(), $redirectTo);
        }
    }

    public function update(Request $request): Response
    {
        $planId = (int) ($request->input('plan_id', 0));
        $redirectTo = $this->resolvePlansRedirect($request);
        $guard = $this->guardSingleSubmit($request, 'saas.plans.update.' . $planId, $redirectTo);
        if ($guard !== null) {
            return $guard;
        }

        try {
            $this->service->updatePlan($planId, $request->all());
            return $this->backWithSuccess('Plano atualizado com sucesso.', $redirectTo);
        } catch (ValidationException $e) {
            return $this->backWithError($e->getMessage(), $redirectTo);
        }
    }

    public function delete(Request $request): Response
    {
        $planId = (int) ($request->input('plan_id', 0));
        $redirectTo = $this->resolvePlansRedirect($request);
        $guard = $this->guardSingleSubmit($request, 'saas.plans.delete.' . $planId, $redirectTo);
        if ($guard !== null) {
            return $guard;
        }

        try {
            $this->service->deletePlan($planId);
            return $this->backWithSuccess('Plano excluido com sucesso.', $redirectTo);
        } catch (ValidationException $e) {
            return $this->backWithError($e->getMessage(), $redirectTo);
        }
    }

    private function resolvePlansRedirect(Request $request): string
    {
        $default = '/saas/plans';
        $queryRaw = trim((string) ($request->input('return_query', '')));
        if ($queryRaw === '') {
            return $default;
        }

        parse_str($queryRaw, $params);
        if (!is_array($params)) {
            return $default;
        }

        $allowedKeys = [
            'plan_search',
            'plan_status',
            'plan_page',
        ];

        $safe = [];
        foreach ($allowedKeys as $key) {
            if (array_key_exists($key, $params)) {
                $safe[$key] = (string) $params[$key];
            }
        }

        $query = http_build_query($safe);
        return '/saas/plans' . ($query !== '' ? '?' . $query : '');
    }
}
