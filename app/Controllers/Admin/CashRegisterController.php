<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Exceptions\ValidationException;
use App\Services\Admin\CashRegisterService;

final class CashRegisterController extends Controller
{
    public function __construct(
        private readonly CashRegisterService $service = new CashRegisterService()
    ) {}

    public function index(Request $request): Response
    {
        $user = Auth::user();
        $companyId = (int) ($user['company_id'] ?? 0);

        return $this->view('admin/cash_registers/index', [
            'title' => 'Caixa',
            'user' => $user,
            'openCashRegister' => $this->service->currentOpen($companyId),
            'cashRegisters' => $this->service->list($companyId),
        ]);
    }

    public function open(Request $request): Response
    {
        $user = Auth::user();
        $companyId = (int) ($user['company_id'] ?? 0);
        $userId = (int) ($user['id'] ?? 0);

        try {
            $this->service->open($companyId, $userId, $request->all());
            return $this->backWithSuccess('Caixa aberto com sucesso.', '/admin/cash-registers');
        } catch (ValidationException $e) {
            return $this->backWithError($e->getMessage(), '/admin/cash-registers');
        }
    }

    public function close(Request $request): Response
    {
        $user = Auth::user();
        $companyId = (int) ($user['company_id'] ?? 0);
        $userId = (int) ($user['id'] ?? 0);

        try {
            $this->service->close($companyId, $userId, $request->all());
            return $this->backWithSuccess('Caixa fechado com sucesso.', '/admin/cash-registers');
        } catch (ValidationException $e) {
            return $this->backWithError($e->getMessage(), '/admin/cash-registers');
        }
    }
}

