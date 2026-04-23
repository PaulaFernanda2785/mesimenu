<?php
declare(strict_types=1);

namespace App\Controllers\Saas;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Exceptions\ValidationException;
use App\Services\Saas\PublicInteractionService;

final class PublicInteractionController extends Controller
{
    public function __construct(
        private readonly PublicInteractionService $service = new PublicInteractionService()
    ) {}

    public function index(Request $request): Response
    {
        $user = Auth::user();

        return $this->view('saas/public_interactions/index', [
            'title' => 'Interações Públicas',
            'user' => $user,
            'interactionPanel' => $this->service->panel($request->query),
        ], 'layouts/saas');
    }

    public function update(Request $request): Response
    {
        $interactionId = (int) ($request->input('interaction_id', 0));
        $redirectTo = $this->resolveRedirect($request);
        $guard = $this->guardSingleSubmit($request, 'saas.public_interactions.update.' . $interactionId, $redirectTo);
        if ($guard !== null) {
            return $guard;
        }

        $user = Auth::user() ?? [];
        $userId = (int) ($user['id'] ?? 0);

        try {
            $this->service->update($userId, $request->all());
            return $this->backWithSuccess('Interação pública atualizada com sucesso.', $redirectTo);
        } catch (ValidationException $e) {
            return $this->backWithError($e->getMessage(), $redirectTo);
        }
    }

    public function delete(Request $request): Response
    {
        $interactionId = (int) ($request->input('interaction_id', 0));
        $redirectTo = $this->resolveRedirect($request);
        $guard = $this->guardSingleSubmit($request, 'saas.public_interactions.delete.' . $interactionId, $redirectTo);
        if ($guard !== null) {
            return $guard;
        }

        try {
            $this->service->delete($interactionId);
            return $this->backWithSuccess('Interação pública excluída com sucesso.', $redirectTo);
        } catch (ValidationException $e) {
            return $this->backWithError($e->getMessage(), $redirectTo);
        }
    }

    private function resolveRedirect(Request $request): string
    {
        $default = '/saas/public-interactions';
        $queryRaw = trim((string) ($request->input('return_query', '')));
        if ($queryRaw === '') {
            return $default;
        }

        parse_str($queryRaw, $params);
        if (!is_array($params)) {
            return $default;
        }

        $allowedKeys = [
            'interaction_search',
            'interaction_status',
            'interaction_page',
        ];

        $safe = [];
        foreach ($allowedKeys as $key) {
            if (array_key_exists($key, $params)) {
                $safe[$key] = (string) $params[$key];
            }
        }

        $query = http_build_query($safe);
        return '/saas/public-interactions' . ($query !== '' ? '?' . $query : '');
    }
}
