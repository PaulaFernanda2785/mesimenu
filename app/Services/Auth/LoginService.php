<?php
declare(strict_types=1);

namespace App\Services\Auth;

use App\Repositories\UserRepository;
use RuntimeException;

final class LoginService
{
    private const MAX_ATTEMPTS = 5;
    private const WINDOW_SECONDS = 900;
    private const LOCKOUT_SECONDS = 900;

    public function __construct(
        private readonly UserRepository $users = new UserRepository()
    ) {}

    public function attempt(string $email, string $password, string $clientIp = ''): array
    {
        $normalizedEmail = strtolower(trim($email));
        $throttleState = $this->readThrottleState($normalizedEmail, $clientIp);
        if (($throttleState['locked_until'] ?? 0) > time()) {
            $remaining = max(60, (int) (($throttleState['locked_until'] ?? 0) - time()));
            $minutes = (int) ceil($remaining / 60);
            throw new RuntimeException('Muitas tentativas de login. Tente novamente em ' . $minutes . ' minuto(s).');
        }

        $user = $this->users->findByEmail($normalizedEmail);

        if ($user === null) {
            $this->registerFailedAttempt($normalizedEmail, $clientIp, $throttleState);
            throw new RuntimeException('E-mail ou senha invalidos.');
        }

        if (($user['status'] ?? '') !== 'ativo') {
            $this->registerFailedAttempt($normalizedEmail, $clientIp, $throttleState);
            throw new RuntimeException('E-mail ou senha invalidos.');
        }

        if (!password_verify($password, (string) ($user['password_hash'] ?? ''))) {
            $this->registerFailedAttempt($normalizedEmail, $clientIp, $throttleState);
            throw new RuntimeException('E-mail ou senha invalidos.');
        }

        $userId = (int) ($user['id'] ?? 0);
        if ($userId > 0) {
            $this->users->touchLastLogin($userId);
            $user['last_login_at'] = date('Y-m-d H:i:s');
        }

        $this->clearThrottleState($normalizedEmail, $clientIp);
        unset($user['password_hash']);

        return $user;
    }

    private function registerFailedAttempt(string $email, string $clientIp, array $state): void
    {
        $now = time();
        $attempts = array_values(array_filter(
            is_array($state['attempts'] ?? null) ? $state['attempts'] : [],
            static fn (mixed $value): bool => is_int($value) && ($now - $value) <= self::WINDOW_SECONDS
        ));
        $attempts[] = $now;

        $lockedUntil = 0;
        if (count($attempts) >= self::MAX_ATTEMPTS) {
            $lockedUntil = $now + self::LOCKOUT_SECONDS;
        }

        $this->writeThrottleState($email, $clientIp, [
            'attempts' => $attempts,
            'locked_until' => $lockedUntil,
        ]);
    }

    private function readThrottleState(string $email, string $clientIp): array
    {
        $path = $this->throttlePath($email, $clientIp);
        if (!is_file($path)) {
            return [
                'attempts' => [],
                'locked_until' => 0,
            ];
        }

        $json = @file_get_contents($path);
        if (!is_string($json) || trim($json) === '') {
            return [
                'attempts' => [],
                'locked_until' => 0,
            ];
        }

        $decoded = json_decode($json, true);
        if (!is_array($decoded)) {
            return [
                'attempts' => [],
                'locked_until' => 0,
            ];
        }

        $now = time();
        $attempts = array_values(array_filter(
            is_array($decoded['attempts'] ?? null) ? $decoded['attempts'] : [],
            static fn (mixed $value): bool => is_int($value) && ($now - $value) <= self::WINDOW_SECONDS
        ));
        $lockedUntil = (int) ($decoded['locked_until'] ?? 0);
        if ($lockedUntil <= $now) {
            $lockedUntil = 0;
        }

        return [
            'attempts' => $attempts,
            'locked_until' => $lockedUntil,
        ];
    }

    private function clearThrottleState(string $email, string $clientIp): void
    {
        $path = $this->throttlePath($email, $clientIp);
        if (is_file($path)) {
            @unlink($path);
        }
    }

    private function writeThrottleState(string $email, string $clientIp, array $state): void
    {
        $dir = $this->throttleDirectory();
        if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
            return;
        }

        $payload = json_encode($state, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($payload === false) {
            return;
        }

        @file_put_contents($this->throttlePath($email, $clientIp), $payload, LOCK_EX);
    }

    private function throttleDirectory(): string
    {
        return BASE_PATH . '/storage/cache/auth_login';
    }

    private function throttlePath(string $email, string $clientIp): string
    {
        $normalizedIp = trim($clientIp) !== '' ? trim($clientIp) : 'unknown';
        $key = hash('sha256', strtolower(trim($email)) . '|' . $normalizedIp);
        return $this->throttleDirectory() . '/' . $key . '.json';
    }
}
