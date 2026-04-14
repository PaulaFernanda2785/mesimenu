<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Exceptions\ValidationException;
use App\Services\Admin\KitchenService;

final class KitchenController extends Controller
{
    public function __construct(
        private readonly KitchenService $service = new KitchenService()
    ) {}

    public function index(Request $request): Response
    {
        $user = Auth::user();
        $companyId = (int) ($user['company_id'] ?? 0);

        return $this->view('admin/kitchen/index', [
            'title' => 'Producao / Cozinha',
            'user' => $user,
            'queue' => $this->service->queue($companyId),
        ]);
    }

    public function updateStatus(Request $request): Response
    {
        $user = Auth::user();
        $companyId = (int) ($user['company_id'] ?? 0);
        $userId = (int) ($user['id'] ?? 0);

        try {
            $this->service->updateQueueStatus($companyId, $userId, $request->all());
            return $this->backWithSuccess('Status atualizado no painel de cozinha.', '/admin/kitchen');
        } catch (ValidationException $e) {
            return $this->backWithError($e->getMessage(), '/admin/kitchen');
        }
    }

    public function emitTicket(Request $request): Response
    {
        $user = Auth::user();
        $companyId = (int) ($user['company_id'] ?? 0);
        $userId = (int) ($user['id'] ?? 0);
        $orderId = (int) ($request->input('order_id', 0));
        $redirectToPreviewRaw = strtolower(trim((string) ($request->input('redirect_to_preview', ''))));
        $redirectToPreview = in_array($redirectToPreviewRaw, ['1', 'true', 'on', 'yes'], true);

        try {
            $this->service->emitKitchenTicket($companyId, $userId, $request->all());

            if ($redirectToPreview && $orderId > 0) {
                return $this->redirect('/admin/orders/print-ticket?order_id=' . $orderId . '&return_to=kitchen');
            }

            return $this->backWithSuccess('Ticket de cozinha registrado com sucesso.', '/admin/kitchen');
        } catch (ValidationException $e) {
            return $this->backWithError($e->getMessage(), '/admin/kitchen');
        }
    }
}
