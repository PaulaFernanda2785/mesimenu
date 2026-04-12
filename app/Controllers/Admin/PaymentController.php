<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Exceptions\ValidationException;
use App\Services\Admin\PaymentService;

final class PaymentController extends Controller
{
    public function __construct(
        private readonly PaymentService $service = new PaymentService()
    ) {}

    public function index(Request $request): Response
    {
        $user = Auth::user();
        $companyId = (int) ($user['company_id'] ?? 0);

        return $this->view('admin/payments/index', [
            'title' => 'Pagamentos',
            'user' => $user,
            'payments' => $this->service->list($companyId),
        ]);
    }

    public function create(Request $request): Response
    {
        $user = Auth::user();
        $companyId = (int) ($user['company_id'] ?? 0);

        return $this->view('admin/payments/create', [
            'title' => 'Registrar Pagamento',
            'user' => $user,
            'hasOpenCashRegister' => $this->service->hasOpenCashRegister($companyId),
            'orders' => $this->service->payableOrders($companyId),
            'paymentMethods' => $this->service->paymentMethods($companyId),
        ]);
    }

    public function store(Request $request): Response
    {
        $user = Auth::user();
        $companyId = (int) ($user['company_id'] ?? 0);
        $userId = (int) ($user['id'] ?? 0);

        try {
            $this->service->create($companyId, $userId, $request->all());
            return $this->backWithSuccess('Pagamento registrado com sucesso.', '/admin/payments');
        } catch (ValidationException $e) {
            return $this->backWithError($e->getMessage(), '/admin/payments/create');
        }
    }
}

