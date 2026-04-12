<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Exceptions\ValidationException;
use App\Services\Admin\CommandService;
use App\Services\Admin\TableService;

final class CommandController extends Controller
{
    public function __construct(
        private readonly CommandService $service = new CommandService(),
        private readonly TableService $tableService = new TableService()
    ) {}

    public function index(Request $request): Response
    {
        $user = Auth::user();
        $companyId = (int) ($user['company_id'] ?? 0);

        return $this->view('admin/commands/index', [
            'title' => 'Comandas Abertas',
            'user' => $user,
            'commands' => $this->service->listOpen($companyId),
        ]);
    }

    public function create(Request $request): Response
    {
        $user = Auth::user();
        $companyId = (int) ($user['company_id'] ?? 0);

        return $this->view('admin/commands/create', [
            'title' => 'Abrir Comanda',
            'user' => $user,
            'tables' => $this->tableService->list($companyId),
        ]);
    }

    public function store(Request $request): Response
    {
        $user = Auth::user();
        $companyId = (int) ($user['company_id'] ?? 0);
        $userId = (int) ($user['id'] ?? 0);

        try {
            $this->service->open($companyId, $userId, $request->all());
            return $this->backWithSuccess('Comanda aberta com sucesso.', '/admin/commands');
        } catch (ValidationException $e) {
            return $this->backWithError($e->getMessage(), '/admin/commands/create');
        }
    }
}
