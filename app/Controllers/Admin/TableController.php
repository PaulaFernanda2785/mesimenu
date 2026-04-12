<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Exceptions\ValidationException;
use App\Services\Admin\TableService;

final class TableController extends Controller
{
    public function __construct(
        private readonly TableService $service = new TableService()
    ) {}

    public function index(Request $request): Response
    {
        $user = Auth::user();
        $companyId = (int) ($user['company_id'] ?? 0);

        return $this->view('admin/tables/index', [
            'title' => 'Mesas',
            'user' => $user,
            'tables' => $this->service->list($companyId),
        ]);
    }

    public function create(Request $request): Response
    {
        return $this->view('admin/tables/create', [
            'title' => 'Nova Mesa',
            'user' => Auth::user(),
        ]);
    }

    public function store(Request $request): Response
    {
        $user = Auth::user();
        $companyId = (int) ($user['company_id'] ?? 0);

        try {
            $this->service->create($companyId, $request->all());
            return $this->backWithSuccess('Mesa cadastrada com sucesso.', '/admin/tables');
        } catch (ValidationException $e) {
            return $this->backWithError($e->getMessage(), '/admin/tables/create');
        }
    }
}
