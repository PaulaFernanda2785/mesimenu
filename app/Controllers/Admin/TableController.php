<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Exceptions\ValidationException;
use App\Repositories\PermissionRepository;
use App\Services\Admin\TableService;

final class TableController extends Controller
{
    public function __construct(
        private readonly TableService $service = new TableService(),
        private readonly PermissionRepository $permissions = new PermissionRepository()
    ) {}

    public function index(Request $request): Response
    {
        $user = Auth::user();
        $companyId = (int) ($user['company_id'] ?? 0);
        $roleId = (int) ($user['role_id'] ?? 0);
        $panel = $this->service->panel($companyId);

        return $this->view('admin/tables/index', [
            'title' => 'Mesas',
            'user' => $user,
            'summary' => $panel['summary'] ?? [],
            'tables' => $panel['tables'] ?? [],
            'canManageTables' => $this->permissions->roleHasPermission($roleId, 'tables.manage'),
        ]);
    }

    public function create(Request $request): Response
    {
        return $this->view('admin/tables/create', [
            'title' => 'Nova Mesa',
            'user' => Auth::user(),
            'table' => null,
            'formAction' => base_url('/admin/tables/store'),
            'submitLabel' => 'Salvar mesa',
            'mode' => 'create',
        ]);
    }

    public function store(Request $request): Response
    {
        $guard = $this->guardSingleSubmit($request, 'tables.store', '/admin/tables/create');
        if ($guard !== null) {
            return $guard;
        }

        $user = Auth::user();
        $companyId = (int) ($user['company_id'] ?? 0);

        try {
            $this->service->create($companyId, $request->all());
            return $this->backWithSuccess('Mesa cadastrada com sucesso.', '/admin/tables');
        } catch (ValidationException $e) {
            return $this->backWithError($e->getMessage(), '/admin/tables/create');
        }
    }

    public function edit(Request $request): Response
    {
        $user = Auth::user();
        $companyId = (int) ($user['company_id'] ?? 0);
        $tableId = (int) ($request->input('table_id', 0));

        try {
            return $this->view('admin/tables/create', [
                'title' => 'Editar Mesa',
                'user' => $user,
                'table' => $this->service->findForEdit($companyId, $tableId),
                'formAction' => base_url('/admin/tables/update'),
                'submitLabel' => 'Salvar alteracoes',
                'mode' => 'edit',
            ]);
        } catch (ValidationException $e) {
            return $this->backWithError($e->getMessage(), '/admin/tables');
        }
    }

    public function update(Request $request): Response
    {
        $tableId = (int) ($request->input('table_id', 0));
        $guard = $this->guardSingleSubmit($request, 'tables.update', '/admin/tables/edit?table_id=' . $tableId);
        if ($guard !== null) {
            return $guard;
        }

        $user = Auth::user();
        $companyId = (int) ($user['company_id'] ?? 0);

        try {
            $this->service->update($companyId, $tableId, $request->all());
            return $this->backWithSuccess('Mesa atualizada com sucesso.', '/admin/tables');
        } catch (ValidationException $e) {
            return $this->backWithError($e->getMessage(), '/admin/tables/edit?table_id=' . $tableId);
        }
    }

    public function delete(Request $request): Response
    {
        $guard = $this->guardSingleSubmit($request, 'tables.delete', '/admin/tables');
        if ($guard !== null) {
            return $guard;
        }

        $user = Auth::user();
        $companyId = (int) ($user['company_id'] ?? 0);
        $tableId = (int) ($request->input('table_id', 0));

        try {
            $this->service->delete($companyId, $tableId);
            return $this->backWithSuccess('Mesa excluida com sucesso.', '/admin/tables');
        } catch (ValidationException $e) {
            return $this->backWithError($e->getMessage(), '/admin/tables');
        }
    }

    public function printQr(Request $request): Response
    {
        $user = Auth::user();
        $companyId = (int) ($user['company_id'] ?? 0);
        $tableId = (int) ($request->input('table_id', 0));

        try {
            return $this->view('admin/tables/print_qr', [
                'title' => 'Imprimir QR da Mesa',
                'user' => $user,
                'context' => $this->service->qrPrintContext($companyId, $tableId),
            ]);
        } catch (ValidationException $e) {
            return $this->backWithError($e->getMessage(), '/admin/tables');
        }
    }
}
