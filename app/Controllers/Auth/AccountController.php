<?php
declare(strict_types=1);

namespace App\Controllers\Auth;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Exceptions\ValidationException;
use App\Services\Auth\AccountService;

final class AccountController extends Controller
{
    public function editPassword(Request $request): Response
    {
        $user = Auth::user() ?? [];
        $service = new AccountService();

        try {
            $account = $service->passwordViewData($user);
        } catch (ValidationException $e) {
            return $this->backWithError($e->getMessage(), '/login');
        }

        return $this->view(
            'auth/account_password',
            [
                'title' => 'Alterar senha',
                'user' => $user,
                'account' => $account,
            ],
            $this->resolveLayoutForUser($user)
        );
    }

    public function updatePassword(Request $request): Response
    {
        $user = Auth::user() ?? [];
        $service = new AccountService();
        $redirectTo = '/account/password';

        $guard = $this->guardSingleSubmit($request, 'account.password.update', $redirectTo);
        if ($guard instanceof Response) {
            return $guard;
        }

        try {
            $updatedUser = $service->changePassword($user, $request->all());
            Auth::login($updatedUser);
            return $this->backWithSuccess('Senha atualizada com sucesso.', $redirectTo);
        } catch (ValidationException $e) {
            return $this->backWithError($e->getMessage(), $redirectTo);
        }
    }

    private function resolveLayoutForUser(array $user): string
    {
        $context = strtolower(trim((string) ($user['role_context'] ?? '')));
        $isSaas = (int) ($user['is_saas_user'] ?? 0) === 1 || $context === 'saas';
        return $isSaas ? 'layouts/saas' : 'layouts/app';
    }
}
