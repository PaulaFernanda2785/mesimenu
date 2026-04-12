<?php
declare(strict_types=1);

namespace App\Controllers\Auth;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Services\Auth\LoginService;
use RuntimeException;

final class LoginController extends Controller
{
    public function show(Request $request): Response
    {
        if (Auth::check()) {
            return $this->redirect('/admin/dashboard');
        }

        return $this->view('auth/login', [
            'title' => 'Login',
            'error' => null,
        ], 'layouts/auth');
    }

    public function store(Request $request): Response
    {
        $email = trim((string) $request->input('email', ''));
        $password = (string) $request->input('password', '');

        try {
            if ($email === '' || $password === '') {
                throw new RuntimeException('Informe e-mail e senha.');
            }

            $service = new LoginService();
            $user = $service->attempt($email, $password);

            Auth::login($user);

            return $this->redirect('/admin/dashboard');
        } catch (RuntimeException $e) {
            return $this->view('auth/login', [
                'title' => 'Login',
                'error' => $e->getMessage(),
            ], 'layouts/auth');
        }
    }

    public function logout(Request $request): Response
    {
        Auth::logout();
        return $this->redirect('/login');
    }
}
