<?php
declare(strict_types=1);

namespace App\Middlewares;

use App\Core\Auth;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;

final class AuthMiddleware
{
    public function handle(Request $request): ?Response
    {
        if (!Auth::check()) {
            Session::flash('error', 'Faça login para acessar esta área.');
            return Response::redirect('/login');
        }

        return null;
    }
}
