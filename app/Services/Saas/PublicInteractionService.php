<?php
declare(strict_types=1);

namespace App\Services\Saas;

use App\Exceptions\ValidationException;
use App\Repositories\PublicInteractionRepository;

final class PublicInteractionService
{
    private const ALLOWED_STATUS = [
        'pending',
        'published',
        'rejected',
    ];

    private const LIST_PER_PAGE = 10;
    private const MAX_NAME_LENGTH = 120;
    private const MAX_EMAIL_LENGTH = 160;
    private const MAX_MESSAGE_LENGTH = 2000;

    public function __construct(
        private readonly PublicInteractionRepository $repository = new PublicInteractionRepository()
    ) {}

    public function panel(array $filters): array
    {
        $normalizedFilters = $this->normalizeFilters($filters);
        $page = $this->repository->listPaginated(
            [
                'search' => $normalizedFilters['search'],
                'status' => $normalizedFilters['status'],
            ],
            $normalizedFilters['page'],
            $normalizedFilters['per_page']
        );

        $items = is_array($page['items'] ?? null) ? $page['items'] : [];
        $total = (int) ($page['total'] ?? 0);
        $currentPage = (int) ($page['page'] ?? 1);
        $perPage = (int) ($page['per_page'] ?? $normalizedFilters['per_page']);
        $lastPage = (int) ($page['last_page'] ?? 1);
        $from = $total > 0 ? (($currentPage - 1) * $perPage) + 1 : 0;
        $to = $total > 0 ? min($total, $currentPage * $perPage) : 0;

        return [
            'items' => $items,
            'filters' => $normalizedFilters,
            'pagination' => [
                'total' => $total,
                'page' => $currentPage,
                'per_page' => $perPage,
                'last_page' => $lastPage,
                'from' => $from,
                'to' => $to,
                'pages' => $this->buildPaginationPages($currentPage, $lastPage),
            ],
            'summary' => $this->repository->metrics([
                'search' => $normalizedFilters['search'],
                'status' => $normalizedFilters['status'],
            ]),
        ];
    }

    public function update(int $userId, array $input): void
    {
        if ($userId <= 0) {
            throw new ValidationException('Usuario SaaS invalido para moderar a publicacao.');
        }

        $interactionId = (int) ($input['interaction_id'] ?? 0);
        if ($interactionId <= 0) {
            throw new ValidationException('Publicacao invalida para atualizacao.');
        }

        $interaction = $this->repository->findById($interactionId);
        if ($interaction === null) {
            throw new ValidationException('Publicacao nao encontrada para atualizacao.');
        }

        $name = trim((string) ($input['visitor_name'] ?? ''));
        $email = strtolower(trim((string) ($input['visitor_email'] ?? '')));
        $message = trim((string) ($input['message'] ?? ''));
        $status = strtolower(trim((string) ($input['status'] ?? 'pending')));

        if ($name === '') {
            throw new ValidationException('Informe o nome do visitante para salvar a publicacao.');
        }
        if (strlen($name) > self::MAX_NAME_LENGTH) {
            throw new ValidationException('O nome do visitante deve ter no maximo 120 caracteres.');
        }

        if ($email === '' || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            throw new ValidationException('Informe um e-mail valido para a publicacao.');
        }
        if (strlen($email) > self::MAX_EMAIL_LENGTH) {
            throw new ValidationException('O e-mail do visitante deve ter no maximo 160 caracteres.');
        }

        if ($message === '') {
            throw new ValidationException('A mensagem nao pode ficar vazia.');
        }
        if (strlen($message) > self::MAX_MESSAGE_LENGTH) {
            throw new ValidationException('A mensagem deve ter no maximo 2000 caracteres.');
        }

        if (!in_array($status, self::ALLOWED_STATUS, true)) {
            throw new ValidationException('Status invalido para moderacao.');
        }

        $publishedAt = '';
        if ($status === 'published') {
            $publishedAt = trim((string) ($interaction['published_at'] ?? ''));
            if ($publishedAt === '') {
                $publishedAt = date('Y-m-d H:i:s');
            }
        }

        $this->repository->updateModeration($interactionId, [
            'visitor_name' => $name,
            'visitor_email' => $email,
            'message' => $message,
            'status' => $status,
            'reviewed_by_user_id' => $userId,
            'reviewed_at' => date('Y-m-d H:i:s'),
            'published_at' => $publishedAt,
        ]);
    }

    public function delete(int $interactionId): void
    {
        if ($interactionId <= 0) {
            throw new ValidationException('Publicacao invalida para exclusao.');
        }

        $interaction = $this->repository->findById($interactionId);
        if ($interaction === null) {
            throw new ValidationException('Publicacao nao encontrada para exclusao.');
        }

        $this->repository->delete($interactionId);
    }

    private function normalizeFilters(array $filters): array
    {
        $search = trim((string) ($filters['interaction_search'] ?? ''));
        if (strlen($search) > 80) {
            $search = substr($search, 0, 80);
        }

        $status = strtolower(trim((string) ($filters['interaction_status'] ?? '')));
        if ($status !== '' && !in_array($status, self::ALLOWED_STATUS, true)) {
            $status = '';
        }

        $page = (int) ($filters['interaction_page'] ?? 1);
        if ($page <= 0) {
            $page = 1;
        }

        return [
            'search' => $search,
            'status' => $status,
            'page' => $page,
            'per_page' => self::LIST_PER_PAGE,
        ];
    }

    private function buildPaginationPages(int $currentPage, int $lastPage): array
    {
        $lastPage = max(1, $lastPage);
        $currentPage = max(1, min($currentPage, $lastPage));

        $pages = [1, $lastPage, $currentPage];
        for ($offset = -2; $offset <= 2; $offset++) {
            $pages[] = $currentPage + $offset;
        }

        $normalized = [];
        foreach ($pages as $page) {
            $value = (int) $page;
            if ($value >= 1 && $value <= $lastPage) {
                $normalized[$value] = true;
            }
        }

        $result = array_keys($normalized);
        sort($result);
        return $result;
    }
}
