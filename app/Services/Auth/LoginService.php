<?php
declare(strict_types=1);

namespace App\Services\Auth;

use App\Repositories\UserRepository;
use RuntimeException;

final class LoginService
{
    public function __construct(
        private readonly UserRepository $users = new UserRepository()
    ) {}

    public function attempt(string $email, string $password): array
    {
        $user = $this->users->findByEmail($email);

        if ($user === null) {
            throw new RuntimeException('Usuário não encontrado.');
        }

        if ($user['status'] !== 'ativo') {
            throw new RuntimeException('Usuário inativo ou bloqueado.');
        }

        if (!password_verify($password, $user['password_hash'])) {
            throw new RuntimeException('Senha inválida.');
        }

        unset($user['password_hash']);

        return $user;
    }
}
