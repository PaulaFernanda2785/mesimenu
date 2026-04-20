<?php
declare(strict_types=1);

namespace App\Controllers\Marketing;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Exceptions\ValidationException;
use App\Services\Marketing\LeadCaptureService;

final class LeadController extends Controller
{
    public function __construct(
        private readonly LeadCaptureService $service = new LeadCaptureService()
    ) {}

    public function store(Request $request): Response
    {
        $redirectTo = '/#contato';
        $guard = $this->guardSingleSubmit($request, 'marketing.contact.store', $redirectTo);
        if ($guard !== null) {
            return $guard;
        }

        try {
            $this->service->store($request->all(), $request->server);
            return $this->backWithSuccess('Contato registrado. Agora o proximo passo e responder esse lead com velocidade comercial.', $redirectTo);
        } catch (ValidationException $e) {
            return $this->backWithError($e->getMessage(), $redirectTo);
        }
    }
}
