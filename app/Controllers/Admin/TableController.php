<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Core\View;
use App\Exceptions\ValidationException;
use App\Repositories\PermissionRepository;
use App\Services\Admin\CommandService;
use App\Services\Admin\OrderService;
use App\Services\Admin\TableService;

final class TableController extends Controller
{
    public function __construct(
        private readonly TableService $service = new TableService(),
        private readonly PermissionRepository $permissions = new PermissionRepository(),
        private readonly OrderService $orders = new OrderService(),
        private readonly CommandService $commands = new CommandService()
    ) {}

    public function index(Request $request): Response
    {
        $user = Auth::user();
        $companyId = (int) ($user['company_id'] ?? 0);
        $roleId = (int) ($user['role_id'] ?? 0);
        $panel = $this->service->panel($companyId);
        $orderPanel = $this->orders->operationalPanelByTable($companyId);
        $ordersByTableNumber = $this->indexOrdersByTableNumber($orderPanel['tables'] ?? []);
        $commandsByTableNumber = $this->indexOpenCommandsByTableNumber($this->commands->listOpen($companyId));

        return $this->view('admin/tables/index', [
            'title' => 'Mesas',
            'user' => $user,
            'summary' => $panel['summary'] ?? [],
            'tables' => $panel['tables'] ?? [],
            'ordersByTableNumber' => $ordersByTableNumber,
            'commandsByTableNumber' => $commandsByTableNumber,
            'canManageTables' => $this->permissions->roleHasPermission($roleId, 'tables.manage'),
            'canUpdateStatus' => $this->permissions->roleHasPermission($roleId, 'orders.status'),
            'canCancelOrder' => $this->permissions->roleHasPermission($roleId, 'orders.cancel'),
            'canSendKitchen' => $this->permissions->roleHasPermission($roleId, 'orders.status'),
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

    public function modalContent(Request $request): Response
    {
        $user = Auth::user();
        $companyId = (int) ($user['company_id'] ?? 0);
        $roleId = (int) ($user['role_id'] ?? 0);
        $tableId = (int) ($request->input('table_id', 0));

        try {
            $html = View::render(
                'admin/tables/_modal_content',
                $this->buildTableModalContext($companyId, $roleId, $tableId),
                'layouts/_fragment'
            );

            return Response::make($html, 200, [
                'Content-Type' => 'text/html; charset=UTF-8',
                'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
                'Pragma' => 'no-cache',
            ]);
        } catch (ValidationException $e) {
            $message = htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
            $html = '<div class="modal-grid"><section class="modal-block"><p class="muted">' . $message . '</p></section></div>';

            return Response::make($html, 422, [
                'Content-Type' => 'text/html; charset=UTF-8',
                'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
                'Pragma' => 'no-cache',
            ]);
        }
    }

    private function indexOrdersByTableNumber(array $rows): array
    {
        $indexed = [];
        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }

            $tableNumberRaw = $row['table_number'] ?? null;
            if ($tableNumberRaw === null) {
                continue;
            }

            $tableNumber = (int) $tableNumberRaw;
            if ($tableNumber <= 0) {
                continue;
            }

            $indexed[$tableNumber] = $row;
        }

        return $indexed;
    }

    private function indexOpenCommandsByTableNumber(array $rows): array
    {
        $indexed = [];
        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }

            $tableNumberRaw = $row['table_number'] ?? null;
            if ($tableNumberRaw === null) {
                continue;
            }

            $tableNumber = (int) $tableNumberRaw;
            if ($tableNumber <= 0) {
                continue;
            }

            if (!isset($indexed[$tableNumber])) {
                $indexed[$tableNumber] = [];
            }
            $indexed[$tableNumber][] = $row;
        }

        return $indexed;
    }

    private function buildTableModalContext(int $companyId, int $roleId, int $tableId): array
    {
        $table = $this->service->findForEdit($companyId, $tableId);
        $tableNumber = (int) ($table['number'] ?? 0);

        $orderPanel = $this->orders->operationalPanelByTable($companyId);
        $ordersByTableNumber = $this->indexOrdersByTableNumber($orderPanel['tables'] ?? []);
        $tableOrderPanel = is_array($ordersByTableNumber[$tableNumber] ?? null) ? $ordersByTableNumber[$tableNumber] : [];
        $tableOrders = is_array($tableOrderPanel['orders'] ?? null) ? $tableOrderPanel['orders'] : [];

        $commandsByTableNumber = $this->indexOpenCommandsByTableNumber($this->commands->listOpen($companyId));
        $tableCommands = is_array($commandsByTableNumber[$tableNumber] ?? null) ? $commandsByTableNumber[$tableNumber] : [];

        return [
            'tableCommands' => $tableCommands,
            'tableOrders' => $tableOrders,
            'tableOrdersCount' => (int) ($tableOrderPanel['orders_count'] ?? 0),
            'tableItemsTotal' => (int) ($tableOrderPanel['items_total'] ?? 0),
            'tableAmountTotal' => (float) ($tableOrderPanel['amount_total'] ?? 0),
            'canUpdateStatus' => $this->permissions->roleHasPermission($roleId, 'orders.status'),
            'canCancelOrder' => $this->permissions->roleHasPermission($roleId, 'orders.cancel'),
            'canSendKitchen' => $this->permissions->roleHasPermission($roleId, 'orders.status'),
        ];
    }
}
