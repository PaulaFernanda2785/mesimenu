<?php
declare(strict_types=1);

namespace App\Services\Shared;

use App\Repositories\AppShellRepository;

final class AppShellService
{
    public function __construct(
        private readonly AppShellRepository $repository = new AppShellRepository()
    ) {}

    public function resolveForUser(?array $user): array
    {
        $defaults = [
            'company_name' => 'Estabelecimento',
            'title' => 'Seu painel',
            'description' => '',
            'primary_color' => '#1d4ed8',
            'secondary_color' => '#0f172a',
            'accent_color' => '#0ea5e9',
            'logo_path' => '',
            'banner_path' => '',
            'footer_text' => 'Comanda360 - Sistema de gestao de atendimento e vendas.',
        ];

        if (!is_array($user)) {
            return $defaults;
        }

        $companyId = (int) ($user['company_id'] ?? 0);
        if ($companyId <= 0) {
            return $defaults;
        }

        $profile = $this->repository->findCompanyShellConfig($companyId);
        if ($profile === null) {
            return $defaults;
        }

        $companyName = trim((string) ($profile['name'] ?? ''));
        $title = trim((string) ($profile['title'] ?? ''));

        $normalized = [
            'company_name' => $companyName !== '' ? $companyName : $defaults['company_name'],
            'title' => $title !== '' ? $title : ($companyName !== '' ? $companyName : $defaults['title']),
            'description' => trim((string) ($profile['description'] ?? '')),
            'primary_color' => $this->normalizeColor($profile['primary_color'] ?? null, $defaults['primary_color']),
            'secondary_color' => $this->normalizeColor($profile['secondary_color'] ?? null, $defaults['secondary_color']),
            'accent_color' => $this->normalizeColor($profile['accent_color'] ?? null, $defaults['accent_color']),
            'logo_path' => trim((string) ($profile['logo_path'] ?? '')),
            'banner_path' => trim((string) ($profile['banner_path'] ?? '')),
            'footer_text' => trim((string) ($profile['footer_text'] ?? '')),
        ];

        if ($normalized['footer_text'] === '') {
            $normalized['footer_text'] = $defaults['footer_text'];
        }

        return $normalized;
    }

    private function normalizeColor(mixed $value, string $fallback): string
    {
        $color = strtolower(trim((string) ($value ?? '')));
        if (preg_match('/^#[0-9a-f]{6}$/', $color) !== 1) {
            return $fallback;
        }

        return $color;
    }
}
