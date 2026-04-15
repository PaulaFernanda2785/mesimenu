<?php
declare(strict_types=1);

namespace App\Core;

use App\Services\Shared\AppShellService;
use Throwable;

abstract class Controller
{
    protected function view(string $template, array $data = [], string $layout = 'layouts/app'): Response
    {
        if ($layout === 'layouts/app' && !array_key_exists('appShellTheme', $data)) {
            try {
                $user = is_array($data['user'] ?? null) ? $data['user'] : Auth::user();
                $data['appShellTheme'] = (new AppShellService())->resolveForUser($user);
            } catch (Throwable) {
                $data['appShellTheme'] = [];
            }
        }

        $data['flashSuccess'] = Session::getFlash('success');
        $data['flashError'] = Session::getFlash('error');

        return Response::make(View::render($template, $data, $layout));
    }

    protected function redirect(string $to): Response
    {
        return Response::redirect($to);
    }

    protected function backWithError(string $message, string $to): Response
    {
        Session::flash('error', $message);
        return $this->redirect($to);
    }

    protected function backWithSuccess(string $message, string $to): Response
    {
        Session::flash('success', $message);
        return $this->redirect($to);
    }

    protected function guardSingleSubmit(Request $request, string $scope, string $redirectTo): ?Response
    {
        $result = validate_form_submission($request->all(), $scope, 5);
        if (($result['ok'] ?? false) === true) {
            return null;
        }

        return $this->backWithError((string) ($result['message'] ?? 'Nao foi possivel validar a requisicao.'), $redirectTo);
    }
}
