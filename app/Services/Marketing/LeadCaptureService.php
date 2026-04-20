<?php
declare(strict_types=1);

namespace App\Services\Marketing;

use App\Exceptions\ValidationException;

final class LeadCaptureService
{
    public function store(array $input, array $server = []): void
    {
        $name = $this->requireText($input['name'] ?? '', 'Informe seu nome.');
        $email = $this->normalizeEmail($input['email'] ?? '');
        $company = $this->nullableText($input['company'] ?? null);
        $phone = $this->nullableText($input['phone'] ?? null);
        $message = $this->nullableText($input['message'] ?? null);
        $planInterest = $this->nullableText($input['plan_interest'] ?? null);
        $billingCycle = $this->normalizeBillingCycle($input['billing_cycle_interest'] ?? null);

        $payload = [
            'created_at' => date('c'),
            'name' => $name,
            'email' => $email,
            'company' => $company,
            'phone' => $phone,
            'message' => $message,
            'plan_interest' => $planInterest,
            'billing_cycle_interest' => $billingCycle,
            'source_url' => $this->nullableText($input['source_url'] ?? null),
            'utm_source' => $this->nullableText($input['utm_source'] ?? null),
            'utm_medium' => $this->nullableText($input['utm_medium'] ?? null),
            'utm_campaign' => $this->nullableText($input['utm_campaign'] ?? null),
            'utm_term' => $this->nullableText($input['utm_term'] ?? null),
            'utm_content' => $this->nullableText($input['utm_content'] ?? null),
            'ip_address' => $this->nullableText($server['REMOTE_ADDR'] ?? null),
            'user_agent' => $this->nullableText($server['HTTP_USER_AGENT'] ?? null),
        ];

        $encoded = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($encoded === false) {
            throw new ValidationException('Nao foi possivel registrar o contato agora.');
        }

        $dir = BASE_PATH . '/storage/marketing_leads';
        if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
            throw new ValidationException('Nao foi possivel preparar o armazenamento de leads.');
        }

        $file = $dir . '/' . date('Y-m') . '.jsonl';
        $result = @file_put_contents($file, $encoded . PHP_EOL, FILE_APPEND | LOCK_EX);
        if ($result === false) {
            throw new ValidationException('Nao foi possivel salvar o contato agora.');
        }
    }

    private function requireText(mixed $value, string $message): string
    {
        $normalized = trim((string) ($value ?? ''));
        if ($normalized === '') {
            throw new ValidationException($message);
        }

        return substr($normalized, 0, 120);
    }

    private function normalizeEmail(mixed $value): string
    {
        $email = strtolower(trim((string) ($value ?? '')));
        if ($email === '' || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            throw new ValidationException('Informe um e-mail valido.');
        }

        return substr($email, 0, 160);
    }

    private function normalizeBillingCycle(mixed $value): ?string
    {
        $normalized = strtolower(trim((string) ($value ?? '')));
        if ($normalized === '') {
            return null;
        }

        if (!in_array($normalized, ['mensal', 'anual'], true)) {
            throw new ValidationException('Ciclo de cobranca invalido.');
        }

        return $normalized;
    }

    private function nullableText(mixed $value): ?string
    {
        $normalized = trim((string) ($value ?? ''));
        if ($normalized === '') {
            return null;
        }

        return substr($normalized, 0, 2000);
    }
}
