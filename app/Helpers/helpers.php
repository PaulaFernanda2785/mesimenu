<?php
declare(strict_types=1);

if (!function_exists('config')) {
    function config(string $file): array
    {
        $path = BASE_PATH . '/config/' . $file . '.php';
        if (!file_exists($path)) {
            throw new RuntimeException('Arquivo de configuração não encontrado: ' . $file);
        }
        return require $path;
    }
}

if (!function_exists('base_url')) {
    function base_url(string $path = ''): string
    {
        $app = config('app');
        return rtrim($app['base_url'], '/') . '/' . ltrim($path, '/');
    }
}

if (!function_exists('view')) {
    function view(string $template, array $data = [], string $layout = 'layouts/app'): string
    {
        return \App\Core\View::render($template, $data, $layout);
    }
}

if (!function_exists('redirect')) {
    function redirect(string $to): \App\Core\Response
    {
        return \App\Core\Response::redirect($to);
    }
}

if (!function_exists('status_label')) {
    function status_label(string $context, mixed $value): string
    {
        $raw = trim((string) ($value ?? ''));
        if ($raw === '') {
            return '-';
        }

        $maps = [
            'order_status' => [
                'pending' => 'Pendente',
                'received' => 'Recebido',
                'preparing' => 'Em preparo',
                'ready' => 'Pronto',
                'delivered' => 'Entregue',
                'paid' => 'Pago',
                'finished' => 'Finalizado',
                'canceled' => 'Cancelado',
            ],
            'order_payment_status' => [
                'pending' => 'Pendente',
                'partial' => 'Parcial',
                'paid' => 'Pago',
                'canceled' => 'Cancelado',
            ],
            'payment_status' => [
                'pending' => 'Pendente',
                'paid' => 'Pago',
                'failed' => 'Falhou',
                'refunded' => 'Estornado',
                'canceled' => 'Cancelado',
            ],
            'cash_register_status' => [
                'open' => 'Aberto',
                'closed' => 'Fechado',
            ],
            'command_status' => [
                'aberta' => 'Aberta',
                'fechada' => 'Fechada',
                'cancelada' => 'Cancelada',
            ],
            'table_status' => [
                'livre' => 'Livre',
                'ocupada' => 'Ocupada',
                'aguardando_fechamento' => 'Aguardando fechamento',
                'bloqueada' => 'Bloqueada',
            ],
            'company_status' => [
                'ativa' => 'Ativa',
                'active' => 'Ativa',
                'teste' => 'Em teste',
                'trial' => 'Em teste',
                'suspensa' => 'Suspensa',
                'suspended' => 'Suspensa',
                'cancelada' => 'Cancelada',
                'canceled' => 'Cancelada',
            ],
            'company_subscription_status' => [
                'ativa' => 'Ativa',
                'trial' => 'Em teste',
                'inadimplente' => 'Inadimplente',
                'suspensa' => 'Suspensa',
                'cancelada' => 'Cancelada',
                'vencida' => 'Vencida',
                'expired' => 'Vencida',
            ],
            'plan_status' => [
                'ativo' => 'Ativo',
                'inativo' => 'Inativo',
                'active' => 'Ativo',
                'inactive' => 'Inativo',
            ],
            'subscription_status' => [
                'ativa' => 'Ativa',
                'trial' => 'Em teste',
                'vencida' => 'Vencida',
                'cancelada' => 'Cancelada',
            ],
            'billing_cycle' => [
                'mensal' => 'Mensal',
                'anual' => 'Anual',
                'monthly' => 'Mensal',
                'yearly' => 'Anual',
            ],
            'subscription_payment_status' => [
                'pendente' => 'Pendente',
                'pago' => 'Pago',
                'vencido' => 'Vencido',
                'cancelado' => 'Cancelado',
            ],
        ];

        $map = $maps[$context] ?? null;
        if (is_array($map) && array_key_exists($raw, $map)) {
            return $map[$raw];
        }

        $normalized = str_replace(['_', '-'], ' ', $raw);
        return ucfirst($normalized);
    }
}
