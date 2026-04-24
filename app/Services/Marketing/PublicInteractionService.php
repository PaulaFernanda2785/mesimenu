<?php
declare(strict_types=1);

namespace App\Services\Marketing;

use App\Exceptions\ValidationException;
use App\Repositories\PublicInteractionRepository;

final class PublicInteractionService
{
    private const MAX_NAME_LENGTH = 120;
    private const MAX_EMAIL_LENGTH = 160;
    private const MAX_MESSAGE_LENGTH = 2000;
    private const MAX_SOURCE_URL_LENGTH = 255;
    private const MAX_USER_AGENT_LENGTH = 255;

    public function __construct(
        private readonly PublicInteractionRepository $repository = new PublicInteractionRepository()
    ) {}

    public function store(array $input, array $server = []): void
    {
        $name = trim((string) ($input['name'] ?? ''));
        $email = strtolower(trim((string) ($input['email'] ?? '')));
        $message = trim((string) ($input['message'] ?? ''));
        $sourceUrl = trim((string) ($input['source_url'] ?? ''));
        $submittedIp = trim((string) ($server['REMOTE_ADDR'] ?? ''));
        $userAgent = trim((string) ($server['HTTP_USER_AGENT'] ?? ''));

        if ($name === '') {
            throw new ValidationException('Informe seu nome para registrar a mensagem.');
        }
        if (strlen($name) > self::MAX_NAME_LENGTH) {
            throw new ValidationException('O nome deve ter no máximo 120 caracteres.');
        }

        if ($email === '') {
            throw new ValidationException('Informe um e-mail válido para contato.');
        }
        if (strlen($email) > self::MAX_EMAIL_LENGTH) {
            throw new ValidationException('O e-mail deve ter no máximo 160 caracteres.');
        }
        if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            throw new ValidationException('Informe um e-mail válido para contato.');
        }

        if ($message === '') {
            throw new ValidationException('Escreva sua mensagem antes de enviar.');
        }
        if (strlen($message) > self::MAX_MESSAGE_LENGTH) {
            throw new ValidationException('A mensagem deve ter no máximo 2000 caracteres.');
        }

        if ($sourceUrl !== '' && strlen($sourceUrl) > self::MAX_SOURCE_URL_LENGTH) {
            $sourceUrl = substr($sourceUrl, 0, self::MAX_SOURCE_URL_LENGTH);
        }
        if ($submittedIp !== '' && strlen($submittedIp) > 45) {
            $submittedIp = substr($submittedIp, 0, 45);
        }
        if ($userAgent !== '' && strlen($userAgent) > self::MAX_USER_AGENT_LENGTH) {
            $userAgent = substr($userAgent, 0, self::MAX_USER_AGENT_LENGTH);
        }

        $this->repository->create([
            'visitor_name' => $name,
            'visitor_email' => $email,
            'message' => $message,
            'source_page' => $sourceUrl !== '' ? $sourceUrl : null,
            'submitted_ip' => $submittedIp !== '' ? $submittedIp : null,
            'user_agent' => $userAgent !== '' ? $userAgent : null,
        ]);
    }
}
