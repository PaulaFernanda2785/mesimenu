<?php
declare(strict_types=1);

namespace App\Services\Shared;

final class PlanFeatureCatalogService
{
    private const BUSINESS_FEATURE_CATALOG = [
        [
            'key' => 'cardapio_digital',
            'label' => 'Cardapio digital',
            'description' => 'Habilita catalogo, categorias, adicionais e operacao comercial do cardapio.',
            'default' => true,
        ],
        [
            'key' => 'qrcode_mesa',
            'label' => 'QR Code de mesa',
            'description' => 'Libera experiencia de salao com mesa, QR e consumo no local.',
            'default' => true,
        ],
        [
            'key' => 'comandas',
            'label' => 'Comandas',
            'description' => 'Permite abertura, acompanhamento e fechamento operacional de comandas.',
            'default' => true,
        ],
        [
            'key' => 'cozinha',
            'label' => 'Cozinha',
            'description' => 'Habilita fila de producao, preparo e emissao de tickets operacionais.',
            'default' => true,
        ],
        [
            'key' => 'pagamentos',
            'label' => 'Pagamentos',
            'description' => 'Libera recebimentos, baixa financeira e historico de cobrancas.',
            'default' => true,
        ],
        [
            'key' => 'caixa',
            'label' => 'Caixa',
            'description' => 'Habilita abertura, fechamento e controle operacional de caixa.',
            'default' => true,
        ],
        [
            'key' => 'delivery',
            'label' => 'Delivery',
            'description' => 'Libera zonas de entrega, roteirizacao e acompanhamento de entregas.',
            'default' => false,
        ],
        [
            'key' => 'estoque',
            'label' => 'Estoque',
            'description' => 'Habilita controle de itens, movimentacoes, alertas e gestao operacional de estoque.',
            'default' => false,
        ],
        [
            'key' => 'relatorios',
            'label' => 'Relatorios',
            'description' => 'Libera visoes gerenciais e leitura consolidada da operacao.',
            'default' => true,
        ],
    ];

    private const AUTOMATIC_FLAG_LABELS = [
        'usuarios_ilimitados' => 'Usuarios ilimitados',
        'produtos_ilimitados' => 'Produtos ilimitados',
        'mesas_ilimitadas' => 'Mesas ilimitadas',
    ];

    public function catalog(): array
    {
        return self::BUSINESS_FEATURE_CATALOG;
    }

    public function keys(): array
    {
        return array_map(
            static fn (array $feature): string => (string) $feature['key'],
            self::BUSINESS_FEATURE_CATALOG
        );
    }

    public function defaultState(): array
    {
        $state = [];
        foreach (self::BUSINESS_FEATURE_CATALOG as $feature) {
            $state[(string) $feature['key']] = false;
        }

        return $state;
    }

    public function stateFromJson(mixed $value): array
    {
        $state = $this->defaultState();
        $decoded = $this->decodeJson($value);
        if ($decoded === []) {
            return $state;
        }

        $business = is_array($decoded['recursos_negocio'] ?? null)
            ? $decoded['recursos_negocio']
            : $decoded;

        foreach ($this->keys() as $key) {
            if (array_key_exists($key, $business)) {
                $state[$key] = (bool) $business[$key];
            }
        }

        return $state;
    }

    public function isEnabledInJson(mixed $value, string $featureKey): bool
    {
        $state = $this->stateFromJson($value);
        return !empty($state[$featureKey]);
    }

    public function summaryFromJson(mixed $value): array
    {
        $decoded = $this->decodeJson($value);
        if ($decoded === []) {
            return [];
        }

        $state = $this->stateFromJson($value);
        $labelsByKey = [];
        foreach (self::BUSINESS_FEATURE_CATALOG as $feature) {
            $labelsByKey[(string) $feature['key']] = (string) $feature['label'];
        }

        $summary = [];
        foreach ($state as $key => $enabled) {
            if ($enabled && isset($labelsByKey[$key])) {
                $summary[] = $labelsByKey[$key];
            }
        }

        $automaticFlags = is_array($decoded['flags_automaticas'] ?? null)
            ? $decoded['flags_automaticas']
            : $decoded;

        foreach (self::AUTOMATIC_FLAG_LABELS as $key => $label) {
            if (!empty($automaticFlags[$key])) {
                $summary[] = $label;
            }
        }

        return $summary;
    }

    public function publicLandingConfigFromJson(mixed $value): array
    {
        $decoded = $this->decodeJson($value);
        $publicConfig = is_array($decoded['vitrine_publica'] ?? null)
            ? $decoded['vitrine_publica']
            : [];

        return [
            'destaque' => (bool) ($publicConfig['destaque'] ?? false),
        ];
    }

    public function isFeaturedOnPublicLanding(mixed $value): bool
    {
        $config = $this->publicLandingConfigFromJson($value);
        return !empty($config['destaque']);
    }

    private function decodeJson(mixed $value): array
    {
        $raw = trim((string) ($value ?? ''));
        if ($raw === '') {
            return [];
        }

        $decoded = json_decode($raw, true);
        return is_array($decoded) ? $decoded : [];
    }
}
