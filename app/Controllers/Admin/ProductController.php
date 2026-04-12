<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Exceptions\ValidationException;
use App\Services\Admin\ProductService;

final class ProductController extends Controller
{
    public function __construct(
        private readonly ProductService $service = new ProductService()
    ) {}

    public function index(Request $request): Response
    {
        $user = Auth::user();
        $companyId = (int) ($user['company_id'] ?? 0);

        $products = $this->service->list($companyId);

        return $this->view('admin/products/index', [
            'title' => 'Produtos',
            'user' => $user,
            'products' => $products,
        ]);
    }

    public function create(Request $request): Response
    {
        $user = Auth::user();
        $companyId = (int) ($user['company_id'] ?? 0);

        return $this->view('admin/products/create', [
            'title' => 'Novo Produto',
            'user' => $user,
            'categories' => $this->service->categories($companyId),
        ]);
    }

    public function store(Request $request): Response
    {
        $user = Auth::user();
        $companyId = (int) ($user['company_id'] ?? 0);

        try {
            $this->service->create($companyId, $request->all());
            return $this->backWithSuccess('Produto cadastrado com sucesso.', '/admin/products');
        } catch (ValidationException $e) {
            return $this->backWithError($e->getMessage(), '/admin/products/create');
        }
    }
}
