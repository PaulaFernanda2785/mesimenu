<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Exceptions\HttpException;
use App\Exceptions\ValidationException;
use App\Services\Admin\DashboardService;

final class DashboardController extends Controller
{
    public function __construct(
        private readonly DashboardService $service = new DashboardService()
    ) {}

    public function index(Request $request): Response
    {
        if (!Auth::check()) {
            return $this->redirect('/login');
        }

        $user = Auth::user() ?? [];
        $this->ensureAccess($user);

        $companyId = (int) ($user['company_id'] ?? 0);

        return $this->view('admin/dashboard/index', [
            'title' => 'Dashboard Administrativo',
            'user' => $user,
            'panel' => $this->service->panel($companyId, $request->query),
            'activeSection' => trim((string) ($request->input('section', 'overview'))),
        ]);
    }

    public function report(Request $request): Response
    {
        if (!Auth::check()) {
            return $this->redirect('/login');
        }

        $user = Auth::user() ?? [];
        $this->ensureAccess($user);
        $companyId = (int) ($user['company_id'] ?? 0);

        try {
            return $this->view('admin/dashboard/report', [
                'title' => 'Previa de Relatorio',
                'user' => $user,
                'report' => $this->service->report($companyId, $request->query, $user),
            ]);
        } catch (ValidationException $e) {
            return $this->backWithError($e->getMessage(), '/admin/dashboard?section=overview');
        }
    }

    public function updateTheme(Request $request): Response
    {
        if (!Auth::check()) {
            return $this->redirect('/login');
        }

        $guard = $this->guardSingleSubmit($request, 'dashboard.theme.update', '/admin/dashboard?section=branding');
        if ($guard !== null) {
            return $guard;
        }

        $user = Auth::user() ?? [];
        $this->ensureAccess($user);

        $companyId = (int) ($user['company_id'] ?? 0);

        try {
            $this->service->updateBranding($companyId, $request->all(), $request->files);
            return $this->backWithSuccess('Identidade visual e dados da empresa atualizados com sucesso.', '/admin/dashboard?section=branding');
        } catch (ValidationException $e) {
            return $this->backWithError($e->getMessage(), '/admin/dashboard?section=branding');
        }
    }

    public function storeUser(Request $request): Response
    {
        if (!Auth::check()) {
            return $this->redirect('/login');
        }

        $guard = $this->guardSingleSubmit($request, 'dashboard.users.store', '/admin/dashboard?section=users');
        if ($guard !== null) {
            return $guard;
        }

        $user = Auth::user() ?? [];
        $this->ensureAccess($user);

        $companyId = (int) ($user['company_id'] ?? 0);

        try {
            $this->service->createInternalUser($companyId, $request->all());
            return $this->backWithSuccess('Usuario interno cadastrado com sucesso.', '/admin/dashboard?section=users');
        } catch (ValidationException $e) {
            return $this->backWithError($e->getMessage(), '/admin/dashboard?section=users');
        }
    }

    public function updateUser(Request $request): Response
    {
        if (!Auth::check()) {
            return $this->redirect('/login');
        }

        $userId = (int) ($request->input('user_id', 0));
        $guard = $this->guardSingleSubmit($request, 'dashboard.users.update.' . $userId, '/admin/dashboard?section=users');
        if ($guard !== null) {
            return $guard;
        }

        $user = Auth::user() ?? [];
        $this->ensureAccess($user);

        $companyId = (int) ($user['company_id'] ?? 0);
        $currentUserId = (int) ($user['id'] ?? 0);

        try {
            $this->service->updateInternalUser($companyId, $userId, $currentUserId, $request->all());
            return $this->backWithSuccess('Usuario interno atualizado com sucesso.', '/admin/dashboard?section=users');
        } catch (ValidationException $e) {
            return $this->backWithError($e->getMessage(), '/admin/dashboard?section=users');
        }
    }

    public function storeSupportTicket(Request $request): Response
    {
        if (!Auth::check()) {
            return $this->redirect('/login');
        }

        $guard = $this->guardSingleSubmit($request, 'dashboard.support.store', '/admin/dashboard?section=support');
        if ($guard !== null) {
            return $guard;
        }

        $user = Auth::user() ?? [];
        $this->ensureAccess($user);

        $companyId = (int) ($user['company_id'] ?? 0);
        $openedByUserId = (int) ($user['id'] ?? 0);

        try {
            $this->service->openSupportTicket($companyId, $openedByUserId, $request->all());
            return $this->backWithSuccess('Chamado tecnico aberto e encaminhado para o administrador do sistema.', '/admin/dashboard?section=support');
        } catch (ValidationException $e) {
            return $this->backWithError($e->getMessage(), '/admin/dashboard?section=support');
        }
    }

    private function ensureAccess(array $user): void
    {
        $roleSlug = strtolower(trim((string) ($user['role_slug'] ?? '')));
        $companyId = (int) ($user['company_id'] ?? 0);

        if ($companyId <= 0) {
            throw new HttpException('403 - Usuario sem vinculo de empresa para acessar dashboard.', 403);
        }

        if (!in_array($roleSlug, ['admin_establishment', 'manager'], true)) {
            throw new HttpException('403 - Apenas administrador do estabelecimento e gerente podem acessar este dashboard.', 403);
        }
    }
}
