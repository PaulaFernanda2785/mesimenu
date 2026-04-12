<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;

final class DashboardController extends Controller
{
    public function index(Request $request): Response
    {
        if (!Auth::check()) {
            return $this->redirect('/login');
        }

        return $this->view('auth/dashboard', [
            'title' => 'Dashboard Administrativo',
            'user' => Auth::user(),
        ]);
    }
}
