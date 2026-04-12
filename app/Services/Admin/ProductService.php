<?php
declare(strict_types=1);

namespace App\Services\Admin;

use App\Exceptions\ValidationException;
use App\Repositories\ProductRepository;

final class ProductService
{
    public function __construct(
        private readonly ProductRepository $products = new ProductRepository()
    ) {}

    public function list(int $companyId): array
    {
        return $this->products->allByCompany($companyId);
    }

    public function categories(int $companyId): array
    {
        return $this->products->categoriesByCompany($companyId);
    }

    public function create(int $companyId, array $input): int
    {
        $name = trim((string) ($input['name'] ?? ''));
        $slug = trim((string) ($input['slug'] ?? ''));
        $categoryId = (int) ($input['category_id'] ?? 0);
        $price = (float) ($input['price'] ?? 0);
        $promotionalPriceRaw = trim((string) ($input['promotional_price'] ?? ''));

        if ($name === '') {
            throw new ValidationException('Informe o nome do produto.');
        }

        if ($slug === '') {
            throw new ValidationException('Informe o slug do produto.');
        }

        if ($categoryId <= 0) {
            throw new ValidationException('Selecione uma categoria válida.');
        }

        if ($price < 0) {
            throw new ValidationException('O preço não pode ser negativo.');
        }

        $promotionalPrice = null;
        if ($promotionalPriceRaw !== '') {
            $promotionalPrice = (float) $promotionalPriceRaw;
            if ($promotionalPrice < 0) {
                throw new ValidationException('O preço promocional não pode ser negativo.');
            }
        }

        $data = [
            'company_id' => $companyId,
            'category_id' => $categoryId,
            'name' => $name,
            'slug' => $slug,
            'description' => ($input['description'] ?? '') !== '' ? trim((string) $input['description']) : null,
            'sku' => ($input['sku'] ?? '') !== '' ? trim((string) $input['sku']) : null,
            'price' => $price,
            'promotional_price' => $promotionalPrice,
            'is_featured' => isset($input['is_featured']) ? 1 : 0,
            'is_active' => isset($input['is_active']) ? 1 : 0,
            'is_paused' => isset($input['is_paused']) ? 1 : 0,
            'allows_notes' => isset($input['allows_notes']) ? 1 : 0,
            'has_additionals' => isset($input['has_additionals']) ? 1 : 0,
            'display_order' => (int) ($input['display_order'] ?? 0),
        ];

        return $this->products->create($data);
    }
}
