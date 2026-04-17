<?php
declare(strict_types=1);

namespace App\Services\Saas;

use App\Exceptions\ValidationException;
use App\Repositories\DashboardRepository;

final class SupportService
{
    private const ALLOWED_SUPPORT_STATUS = [
        'open',
        'in_progress',
        'resolved',
        'closed',
    ];

    private const ALLOWED_SUPPORT_PRIORITY = [
        'low',
        'medium',
        'high',
        'urgent',
    ];

    private const ALLOWED_SUPPORT_ASSIGNMENT = [
        'assigned',
        'unassigned',
    ];

    private const SUPPORT_LIST_PER_PAGE = 10;

    public function __construct(
        private readonly DashboardRepository $repository = new DashboardRepository()
    ) {}

    public function panel(array $filters): array
    {
        $normalizedFilters = $this->normalizeFilters($filters);
        $ticketPage = $this->repository->listSupportTicketsForSaasPaginated(
            [
                'search' => $normalizedFilters['search'],
                'status' => $normalizedFilters['status'],
                'priority' => $normalizedFilters['priority'],
                'assignment' => $normalizedFilters['assignment'],
                'company_search' => $normalizedFilters['company_search'],
            ],
            $normalizedFilters['page'],
            $normalizedFilters['per_page']
        );

        $items = is_array($ticketPage['items'] ?? null) ? $ticketPage['items'] : [];
        $total = (int) ($ticketPage['total'] ?? 0);
        $page = (int) ($ticketPage['page'] ?? 1);
        $perPage = (int) ($ticketPage['per_page'] ?? $normalizedFilters['per_page']);
        $lastPage = (int) ($ticketPage['last_page'] ?? 1);
        $from = $total > 0 ? (($page - 1) * $perPage) + 1 : 0;
        $to = $total > 0 ? min($total, $page * $perPage) : 0;

        $ticketIds = array_values(array_filter(array_map(
            static fn (array $ticket): int => (int) ($ticket['id'] ?? 0),
            $items
        )));

        return [
            'tickets' => $items,
            'threads' => $this->hydrateThreads(
                $items,
                $this->repository->listSupportTicketMessagesByTicketIds($ticketIds)
            ),
            'filters' => $normalizedFilters,
            'pagination' => [
                'total' => $total,
                'page' => $page,
                'per_page' => $perPage,
                'last_page' => $lastPage,
                'from' => $from,
                'to' => $to,
                'pages' => $this->buildPaginationPages($page, $lastPage),
            ],
            'summary' => $this->repository->supportTicketMetricsForSaas(
                [
                    'search' => $normalizedFilters['search'],
                    'status' => $normalizedFilters['status'],
                    'priority' => $normalizedFilters['priority'],
                    'assignment' => $normalizedFilters['assignment'],
                    'company_search' => $normalizedFilters['company_search'],
                ]
            ),
        ];
    }

    public function replyToTicket(int $userId, array $input): void
    {
        if ($userId <= 0) {
            throw new ValidationException('Usuario SaaS invalido para responder chamado.');
        }

        $ticketId = (int) ($input['ticket_id'] ?? 0);
        if ($ticketId <= 0) {
            throw new ValidationException('Chamado invalido para resposta.');
        }

        $ticket = $this->repository->findSupportTicketById($ticketId);
        if ($ticket === null) {
            throw new ValidationException('Chamado nao encontrado para resposta.');
        }

        $message = trim((string) ($input['message'] ?? ''));
        if ($message === '') {
            throw new ValidationException('Escreva a resposta para registrar no historico do chamado.');
        }

        $status = strtolower(trim((string) ($input['status'] ?? 'in_progress')));
        if (!in_array($status, self::ALLOWED_SUPPORT_STATUS, true)) {
            throw new ValidationException('Status invalido para resposta do chamado.');
        }

        $closedAt = in_array($status, ['resolved', 'closed'], true)
            ? date('Y-m-d H:i:s')
            : null;

        $this->repository->transaction(function () use ($ticketId, $userId, $message, $status, $closedAt): void {
            $this->repository->createSupportTicketMessage([
                'ticket_id' => $ticketId,
                'sender_user_id' => $userId,
                'sender_context' => 'saas',
                'message' => $message,
            ]);

            $this->repository->updateSupportTicketConversationState($ticketId, [
                'assigned_to_user_id' => $userId,
                'status' => $status,
                'closed_at' => $closedAt,
            ]);
        });
    }

    private function normalizeFilters(array $filters): array
    {
        $search = trim((string) ($filters['support_search'] ?? ''));
        if (strlen($search) > 80) {
            $search = substr($search, 0, 80);
        }

        $companySearch = trim((string) ($filters['support_company_search'] ?? ''));
        if (strlen($companySearch) > 80) {
            $companySearch = substr($companySearch, 0, 80);
        }

        $status = strtolower(trim((string) ($filters['support_status'] ?? '')));
        if ($status !== '' && !in_array($status, self::ALLOWED_SUPPORT_STATUS, true)) {
            $status = '';
        }

        $priority = strtolower(trim((string) ($filters['support_priority'] ?? '')));
        if ($priority !== '' && !in_array($priority, self::ALLOWED_SUPPORT_PRIORITY, true)) {
            $priority = '';
        }

        $assignment = strtolower(trim((string) ($filters['support_assignment'] ?? '')));
        if ($assignment !== '' && !in_array($assignment, self::ALLOWED_SUPPORT_ASSIGNMENT, true)) {
            $assignment = '';
        }

        $page = (int) ($filters['support_page'] ?? 1);
        if ($page <= 0) {
            $page = 1;
        }

        return [
            'search' => $search,
            'company_search' => $companySearch,
            'status' => $status,
            'priority' => $priority,
            'assignment' => $assignment,
            'page' => $page,
            'per_page' => self::SUPPORT_LIST_PER_PAGE,
        ];
    }

    private function hydrateThreads(array $tickets, array $messagesByTicketId): array
    {
        $threads = [];
        foreach ($tickets as $ticket) {
            $ticketId = (int) ($ticket['id'] ?? 0);
            if ($ticketId <= 0) {
                continue;
            }

            $messages = is_array($messagesByTicketId[$ticketId] ?? null) ? $messagesByTicketId[$ticketId] : [];
            if ($messages === []) {
                $messages[] = [
                    'id' => 0,
                    'ticket_id' => $ticketId,
                    'sender_user_id' => (int) ($ticket['opened_by_user_id'] ?? 0),
                    'sender_context' => 'company',
                    'message' => (string) ($ticket['description'] ?? ''),
                    'created_at' => (string) ($ticket['created_at'] ?? ''),
                    'updated_at' => (string) ($ticket['updated_at'] ?? ''),
                    'sender_user_name' => (string) ($ticket['opened_by_user_name'] ?? '-'),
                    'sender_role_name' => 'Empresa',
                ];
            }

            $threads[$ticketId] = $messages;
        }

        return $threads;
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
