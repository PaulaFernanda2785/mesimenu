<?php
declare(strict_types=1);

namespace App\Services\Admin;

use App\Exceptions\ValidationException;
use App\Repositories\DashboardRepository;

final class DashboardService
{
    private const REQUIRED_REPORT_VIEWS = [
        'vw_relatorio_vendas_pedidos',
        'vw_fechamento_caixa_resumo',
        'vw_produtos_mais_vendidos',
        'vw_vendas_por_categoria',
    ];

    private const ALLOWED_ORDER_STATUS = [
        'pending',
        'received',
        'preparing',
        'ready',
        'delivered',
        'finished',
        'paid',
        'canceled',
    ];

    private const ALLOWED_CHANNELS = [
        'table',
        'delivery',
        'pickup',
        'counter',
    ];

    private const ALLOWED_PAYMENT_STATUS = [
        'pending',
        'partial',
        'paid',
        'canceled',
    ];

    private const ALLOWED_USER_STATUS = [
        'ativo',
        'inativo',
        'bloqueado',
    ];

    private const ALLOWED_SUPPORT_PRIORITY = [
        'low',
        'medium',
        'high',
        'urgent',
    ];

    private const MAX_IMAGE_SIZE_BYTES = 10485760; // 10MB
    private const DEFAULT_PRIMARY_COLOR = '#1d4ed8';
    private const DEFAULT_SECONDARY_COLOR = '#0f172a';
    private const DEFAULT_ACCENT_COLOR = '#0ea5e9';
    private const DEFAULT_FOOTER_TEXT = 'Comanda360 - Sistema de gestao de atendimento e vendas.';

    public function __construct(
        private readonly DashboardRepository $repository = new DashboardRepository()
    ) {}

    public function panel(int $companyId, array $filters): array
    {
        if ($companyId <= 0) {
            throw new ValidationException('Empresa invalida para o dashboard.');
        }

        $normalizedFilters = $this->normalizeDashboardFilters($filters);
        $period = $normalizedFilters['period'];
        $status = $normalizedFilters['status'];
        $channel = $normalizedFilters['channel'];
        $paymentStatus = $normalizedFilters['payment_status'];
        $minAmount = $normalizedFilters['min_amount'];
        $maxAmount = $normalizedFilters['max_amount'];
        $search = $normalizedFilters['search'];

        $companyProfile = $this->repository->findCompanyProfileWithTheme($companyId);
        if ($companyProfile === null) {
            throw new ValidationException('Empresa nao encontrada para o usuario autenticado.');
        }

        $viewStatus = $this->resolveReportViewsStatus();

        $analytics = [
            'kpis' => [],
            'sales_by_day' => [],
            'orders_by_status' => [],
            'sales_by_channel' => [],
            'cash_kpis' => [],
            'cash_history' => [],
            'top_products' => [],
        ];

        if ($viewStatus['ready']) {
            $analytics = [
                'kpis' => $this->repository->salesKpis(
                    $companyId,
                    $period['start_date'],
                    $period['end_date'],
                    $status,
                    $channel,
                    $paymentStatus,
                    $minAmount,
                    $maxAmount,
                    $search
                ),
                'sales_by_day' => $this->repository->salesByDay(
                    $companyId,
                    $period['start_date'],
                    $period['end_date'],
                    $status,
                    $channel,
                    $paymentStatus,
                    $minAmount,
                    $maxAmount,
                    $search
                ),
                'orders_by_status' => $this->repository->ordersByStatus(
                    $companyId,
                    $period['start_date'],
                    $period['end_date'],
                    $status,
                    $channel,
                    $paymentStatus,
                    $minAmount,
                    $maxAmount,
                    $search
                ),
                'sales_by_channel' => $this->repository->salesByChannel(
                    $companyId,
                    $period['start_date'],
                    $period['end_date'],
                    $status,
                    $channel,
                    $paymentStatus,
                    $minAmount,
                    $maxAmount,
                    $search
                ),
                'cash_kpis' => $this->repository->cashClosingKpis($companyId, $period['start_date'], $period['end_date']),
                'cash_history' => $this->repository->cashClosingHistory($companyId, $period['start_date'], $period['end_date']),
                'top_products' => $this->repository->topProductsByCompany($companyId),
                'payment_summary' => $this->repository->paymentStatusSummary(
                    $companyId,
                    $period['start_date'],
                    $period['end_date'],
                    $status,
                    $channel,
                    $paymentStatus,
                    $minAmount,
                    $maxAmount,
                    $search
                ),
            ];
        }

        return [
            'filters' => [
                'start_date' => $period['start_date'],
                'end_date' => $period['end_date'],
                'period_preset' => $normalizedFilters['period_preset'],
                'status' => $status ?? '',
                'channel' => $channel ?? '',
                'payment_status' => $paymentStatus ?? '',
                'min_amount' => $minAmount !== null ? number_format($minAmount, 2, '.', '') : '',
                'max_amount' => $maxAmount !== null ? number_format($maxAmount, 2, '.', '') : '',
                'search' => $search ?? '',
            ],
            'company' => $companyProfile,
            'report_views' => $viewStatus,
            'analytics' => $analytics,
            'users' => $this->repository->listUsersByCompany($companyId),
            'roles' => $this->repository->companyRoles(),
            'support_tickets' => $this->repository->supportTicketsByCompany($companyId),
        ];
    }

    public function report(int $companyId, array $filters, array $user): array
    {
        if ($companyId <= 0) {
            throw new ValidationException('Empresa invalida para gerar relatorio.');
        }

        $normalizedFilters = $this->normalizeDashboardFilters($filters);
        $period = $normalizedFilters['period'];
        $status = $normalizedFilters['status'];
        $channel = $normalizedFilters['channel'];
        $paymentStatus = $normalizedFilters['payment_status'];
        $minAmount = $normalizedFilters['min_amount'];
        $maxAmount = $normalizedFilters['max_amount'];
        $search = $normalizedFilters['search'];

        $companyProfile = $this->repository->findCompanyProfileWithTheme($companyId);
        if ($companyProfile === null) {
            throw new ValidationException('Empresa nao encontrada para gerar relatorio.');
        }

        $viewStatus = $this->resolveReportViewsStatus();
        if (!$viewStatus['ready']) {
            throw new ValidationException('As views de relatorio ainda nao estao prontas para gerar a previa.');
        }

        $kpis = $this->repository->salesKpis(
            $companyId,
            $period['start_date'],
            $period['end_date'],
            $status,
            $channel,
            $paymentStatus,
            $minAmount,
            $maxAmount,
            $search
        );

        $ordersByStatus = $this->repository->ordersByStatus(
            $companyId,
            $period['start_date'],
            $period['end_date'],
            $status,
            $channel,
            $paymentStatus,
            $minAmount,
            $maxAmount,
            $search
        );

        $salesByChannel = $this->repository->salesByChannel(
            $companyId,
            $period['start_date'],
            $period['end_date'],
            $status,
            $channel,
            $paymentStatus,
            $minAmount,
            $maxAmount,
            $search
        );

        $paymentSummary = $this->repository->paymentStatusSummary(
            $companyId,
            $period['start_date'],
            $period['end_date'],
            $status,
            $channel,
            $paymentStatus,
            $minAmount,
            $maxAmount,
            $search
        );

        return [
            'company' => $companyProfile,
            'filters' => [
                'start_date' => $period['start_date'],
                'end_date' => $period['end_date'],
                'period_preset' => $normalizedFilters['period_preset'],
                'status' => $status ?? '',
                'channel' => $channel ?? '',
                'payment_status' => $paymentStatus ?? '',
                'min_amount' => $minAmount !== null ? number_format($minAmount, 2, '.', '') : '',
                'max_amount' => $maxAmount !== null ? number_format($maxAmount, 2, '.', '') : '',
                'search' => $search ?? '',
            ],
            'kpis' => $kpis,
            'orders_by_status' => $ordersByStatus,
            'sales_by_channel' => $salesByChannel,
            'payment_summary' => $paymentSummary,
            'daily_series' => $this->repository->salesByDay(
                $companyId,
                $period['start_date'],
                $period['end_date'],
                $status,
                $channel,
                $paymentStatus,
                $minAmount,
                $maxAmount,
                $search,
                31
            ),
            'cash_kpis' => $this->repository->cashClosingKpis($companyId, $period['start_date'], $period['end_date']),
            'top_products' => $this->repository->topProductsByCompany($companyId, 10),
            'orders' => $this->repository->detailedOrdersForReport(
                $companyId,
                $period['start_date'],
                $period['end_date'],
                $status,
                $channel,
                $paymentStatus,
                $minAmount,
                $maxAmount,
                $search,
                300
            ),
            'generated_at' => date('Y-m-d H:i:s'),
            'generated_by' => trim((string) ($user['name'] ?? 'Administrador')),
        ];
    }

    public function updateBranding(int $companyId, array $input, array $files): void
    {
        if ($companyId <= 0) {
            throw new ValidationException('Empresa invalida para personalizacao.');
        }

        $companyProfile = $this->repository->findCompanyProfileWithTheme($companyId);
        if ($companyProfile === null) {
            throw new ValidationException('Empresa nao encontrada para personalizacao.');
        }

        $companyName = trim((string) ($input['company_name'] ?? ''));
        if ($companyName === '') {
            throw new ValidationException('Informe o nome da empresa.');
        }

        $title = trim((string) ($input['title'] ?? ''));
        $description = trim((string) ($input['description'] ?? ''));
        $footerText = trim((string) ($input['footer_text'] ?? ''));

        $primaryColor = $this->normalizeHexColor($input['primary_color'] ?? null, self::DEFAULT_PRIMARY_COLOR);
        $secondaryColor = $this->normalizeHexColor($input['secondary_color'] ?? null, self::DEFAULT_SECONDARY_COLOR);
        $accentColor = $this->normalizeHexColor($input['accent_color'] ?? null, self::DEFAULT_ACCENT_COLOR);

        $currentLogoPath = $this->normalizeStoredAssetPath((string) ($companyProfile['logo_path'] ?? ''));
        $currentBannerPath = $this->normalizeStoredAssetPath((string) ($companyProfile['banner_path'] ?? ''));

        $removeLogo = isset($input['remove_logo']);
        $removeBanner = isset($input['remove_banner']);

        $uploadedLogo = $this->storeCompanyImage(
            $companyId,
            'logo',
            $files['logo_file'] ?? null,
            (string) ($input['logo_data_base64'] ?? '')
        );
        $uploadedBanner = $this->storeCompanyImage(
            $companyId,
            'banner',
            $files['banner_file'] ?? null,
            (string) ($input['banner_data_base64'] ?? '')
        );

        $logoPath = $currentLogoPath;
        if ($uploadedLogo !== null) {
            $logoPath = $uploadedLogo;
        } elseif ($removeLogo) {
            $logoPath = null;
        }

        $bannerPath = $currentBannerPath;
        if ($uploadedBanner !== null) {
            $bannerPath = $uploadedBanner;
        } elseif ($removeBanner) {
            $bannerPath = null;
        }

        $this->repository->transaction(function () use (
            $companyId,
            $companyName,
            $primaryColor,
            $secondaryColor,
            $accentColor,
            $logoPath,
            $bannerPath,
            $title,
            $description,
            $footerText
        ): void {
            $this->repository->updateCompanyName($companyId, $companyName);
            $this->repository->upsertCompanyTheme($companyId, [
                'primary_color' => $primaryColor,
                'secondary_color' => $secondaryColor,
                'accent_color' => $accentColor,
                'logo_path' => $logoPath,
                'banner_path' => $bannerPath,
                'title' => $title !== '' ? $title : $companyName,
                'description' => $description !== '' ? $description : null,
                'footer_text' => $footerText !== '' ? $footerText : self::DEFAULT_FOOTER_TEXT,
            ]);
        });

        if ($currentLogoPath !== null && $logoPath !== $currentLogoPath) {
            $this->deleteCompanyImage($currentLogoPath);
        }

        if ($currentBannerPath !== null && $bannerPath !== $currentBannerPath) {
            $this->deleteCompanyImage($currentBannerPath);
        }
    }

    public function restoreFactoryStyle(int $companyId): void
    {
        if ($companyId <= 0) {
            throw new ValidationException('Empresa invalida para restaurar estilo.');
        }

        $companyProfile = $this->repository->findCompanyProfileWithTheme($companyId);
        if ($companyProfile === null) {
            throw new ValidationException('Empresa nao encontrada para restaurar estilo.');
        }

        $companyName = trim((string) ($companyProfile['name'] ?? ''));
        if ($companyName === '') {
            $companyName = 'Estabelecimento';
        }

        $currentLogoPath = $this->normalizeStoredAssetPath((string) ($companyProfile['logo_path'] ?? ''));
        $currentBannerPath = $this->normalizeStoredAssetPath((string) ($companyProfile['banner_path'] ?? ''));

        $this->repository->upsertCompanyTheme($companyId, [
            'primary_color' => self::DEFAULT_PRIMARY_COLOR,
            'secondary_color' => self::DEFAULT_SECONDARY_COLOR,
            'accent_color' => self::DEFAULT_ACCENT_COLOR,
            'logo_path' => null,
            'banner_path' => null,
            'title' => $companyName,
            'description' => null,
            'footer_text' => self::DEFAULT_FOOTER_TEXT,
        ]);

        if ($currentLogoPath !== null) {
            $this->deleteCompanyImage($currentLogoPath);
        }
        if ($currentBannerPath !== null) {
            $this->deleteCompanyImage($currentBannerPath);
        }
    }

    public function createInternalUser(int $companyId, array $input): int
    {
        if ($companyId <= 0) {
            throw new ValidationException('Empresa invalida para cadastro de usuario.');
        }

        $name = trim((string) ($input['name'] ?? ''));
        $email = strtolower(trim((string) ($input['email'] ?? '')));
        $phone = trim((string) ($input['phone'] ?? ''));
        $roleId = (int) ($input['role_id'] ?? 0);
        $status = $this->normalizeUserStatus($input['status'] ?? 'ativo');
        $password = (string) ($input['password'] ?? '');

        if ($name === '') {
            throw new ValidationException('Informe o nome do usuario.');
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new ValidationException('Informe um e-mail valido para o usuario.');
        }

        if (strlen($password) < 6) {
            throw new ValidationException('Informe uma senha com no minimo 6 caracteres.');
        }

        $role = $this->repository->findCompanyRoleById($roleId);
        if ($role === null) {
            throw new ValidationException('Selecione um perfil valido para usuario interno.');
        }

        $existingByEmail = $this->repository->findUserByEmail($email);
        if ($existingByEmail !== null) {
            throw new ValidationException('Ja existe usuario cadastrado com este e-mail.');
        }

        return $this->repository->createCompanyUser([
            'company_id' => $companyId,
            'role_id' => $roleId,
            'name' => $name,
            'email' => $email,
            'phone' => $phone !== '' ? $phone : null,
            'password_hash' => password_hash($password, PASSWORD_DEFAULT),
            'status' => $status,
        ]);
    }

    public function updateInternalUser(int $companyId, int $userId, int $currentUserId, array $input): void
    {
        if ($companyId <= 0 || $userId <= 0) {
            throw new ValidationException('Usuario invalido para atualizacao.');
        }

        $existing = $this->repository->findUserByIdForCompany($companyId, $userId);
        if ($existing === null) {
            throw new ValidationException('Usuario nao encontrado para a empresa autenticada.');
        }

        $name = trim((string) ($input['name'] ?? ''));
        $email = strtolower(trim((string) ($input['email'] ?? '')));
        $phone = trim((string) ($input['phone'] ?? ''));
        $roleId = (int) ($input['role_id'] ?? 0);
        $status = $this->normalizeUserStatus($input['status'] ?? $existing['status'] ?? 'ativo');
        $newPassword = trim((string) ($input['password'] ?? ''));

        if ($name === '') {
            throw new ValidationException('Informe o nome do usuario.');
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new ValidationException('Informe um e-mail valido para o usuario.');
        }

        $role = $this->repository->findCompanyRoleById($roleId);
        if ($role === null) {
            throw new ValidationException('Selecione um perfil valido para usuario interno.');
        }

        $existingByEmail = $this->repository->findUserByEmail($email);
        if ($existingByEmail !== null && (int) ($existingByEmail['id'] ?? 0) !== $userId) {
            throw new ValidationException('Ja existe outro usuario cadastrado com este e-mail.');
        }

        if ($userId === $currentUserId && $status !== 'ativo') {
            throw new ValidationException('Nao e permitido desativar ou bloquear o proprio usuario logado.');
        }

        $passwordHash = null;
        if ($newPassword !== '') {
            if (strlen($newPassword) < 6) {
                throw new ValidationException('A nova senha deve ter no minimo 6 caracteres.');
            }
            $passwordHash = password_hash($newPassword, PASSWORD_DEFAULT);
        }

        $this->repository->updateCompanyUser($companyId, $userId, [
            'role_id' => $roleId,
            'name' => $name,
            'email' => $email,
            'phone' => $phone !== '' ? $phone : null,
            'status' => $status,
            'password_hash' => $passwordHash,
        ]);
    }

    public function openSupportTicket(int $companyId, int $openedByUserId, array $input): int
    {
        if ($companyId <= 0 || $openedByUserId <= 0) {
            throw new ValidationException('Contexto invalido para abertura de chamado.');
        }

        $subject = trim((string) ($input['subject'] ?? ''));
        $description = trim((string) ($input['description'] ?? ''));
        $priority = strtolower(trim((string) ($input['priority'] ?? 'medium')));

        if ($subject === '') {
            throw new ValidationException('Informe o assunto do chamado tecnico.');
        }

        if (strlen($subject) > 180) {
            throw new ValidationException('O assunto deve ter no maximo 180 caracteres.');
        }

        if ($description === '') {
            throw new ValidationException('Descreva o chamado para a equipe tecnica.');
        }

        if (!in_array($priority, self::ALLOWED_SUPPORT_PRIORITY, true)) {
            throw new ValidationException('Prioridade invalida para o chamado.');
        }

        return $this->repository->createSupportTicket([
            'company_id' => $companyId,
            'opened_by_user_id' => $openedByUserId,
            'assigned_to_user_id' => $this->repository->findDefaultSupportAssignee(),
            'subject' => $subject,
            'description' => $description,
            'priority' => $priority,
        ]);
    }

    private function resolveReportViewsStatus(): array
    {
        $existing = $this->repository->findExistingReportViews(self::REQUIRED_REPORT_VIEWS);
        $existingMap = array_fill_keys($existing, true);

        $missing = [];
        foreach (self::REQUIRED_REPORT_VIEWS as $viewName) {
            if (!isset($existingMap[strtolower($viewName)])) {
                $missing[] = $viewName;
            }
        }

        return [
            'ready' => $missing === [],
            'missing' => $missing,
        ];
    }

    private function normalizePeriod(array $filters, string $periodPreset): array
    {
        $today = date('Y-m-d');
        $defaultStart = date('Y-m-d', strtotime('-29 days'));

        if ($periodPreset !== 'custom') {
            return $this->presetToPeriod($periodPreset, $today);
        }

        $startDate = trim((string) ($filters['start_date'] ?? $defaultStart));
        $endDate = trim((string) ($filters['end_date'] ?? $today));

        if (!$this->isValidDate($startDate)) {
            $startDate = $defaultStart;
        }
        if (!$this->isValidDate($endDate)) {
            $endDate = $today;
        }

        if ($startDate > $endDate) {
            [$startDate, $endDate] = [$endDate, $startDate];
        }

        $days = (int) ((strtotime($endDate . ' 00:00:00') - strtotime($startDate . ' 00:00:00')) / 86400);
        if ($days > 365) {
            $startDate = date('Y-m-d', strtotime($endDate . ' -365 days'));
        }

        return [
            'start_date' => $startDate,
            'end_date' => $endDate,
        ];
    }

    private function normalizeStatusFilter(mixed $value): ?string
    {
        $status = strtolower(trim((string) ($value ?? '')));
        if ($status === '') {
            return null;
        }

        return in_array($status, self::ALLOWED_ORDER_STATUS, true) ? $status : null;
    }

    private function normalizeChannelFilter(mixed $value): ?string
    {
        $channel = strtolower(trim((string) ($value ?? '')));
        if ($channel === '') {
            return null;
        }

        return in_array($channel, self::ALLOWED_CHANNELS, true) ? $channel : null;
    }

    private function normalizePaymentStatusFilter(mixed $value): ?string
    {
        $status = strtolower(trim((string) ($value ?? '')));
        if ($status === '') {
            return null;
        }

        return in_array($status, self::ALLOWED_PAYMENT_STATUS, true) ? $status : null;
    }

    private function normalizePeriodPreset(mixed $value): string
    {
        $preset = strtolower(trim((string) ($value ?? 'custom')));
        return match ($preset) {
            'today', 'yesterday', 'last7', 'last30', 'month_current', 'month_previous' => $preset,
            default => 'custom',
        };
    }

    private function normalizeMoneyFilter(mixed $value): ?float
    {
        $raw = trim((string) ($value ?? ''));
        if ($raw === '') {
            return null;
        }

        $normalized = str_replace(',', '.', $raw);
        if (!is_numeric($normalized)) {
            return null;
        }

        $amount = round((float) $normalized, 2);
        if ($amount < 0) {
            return null;
        }

        return $amount;
    }

    private function normalizeSearchFilter(mixed $value): ?string
    {
        $search = trim((string) ($value ?? ''));
        if ($search === '') {
            return null;
        }

        if (strlen($search) > 80) {
            $search = substr($search, 0, 80);
        }

        return strtolower($search);
    }

    private function normalizeDashboardFilters(array $filters): array
    {
        $periodPreset = $this->normalizePeriodPreset($filters['period_preset'] ?? null);
        $period = $this->normalizePeriod($filters, $periodPreset);
        $status = $this->normalizeStatusFilter($filters['status'] ?? null);
        $channel = $this->normalizeChannelFilter($filters['channel'] ?? null);
        $paymentStatus = $this->normalizePaymentStatusFilter($filters['payment_status'] ?? null);
        $minAmount = $this->normalizeMoneyFilter($filters['min_amount'] ?? null);
        $maxAmount = $this->normalizeMoneyFilter($filters['max_amount'] ?? null);
        $search = $this->normalizeSearchFilter($filters['search'] ?? null);

        if ($minAmount !== null && $maxAmount !== null && $minAmount > $maxAmount) {
            [$minAmount, $maxAmount] = [$maxAmount, $minAmount];
        }

        return [
            'period_preset' => $periodPreset,
            'period' => $period,
            'status' => $status,
            'channel' => $channel,
            'payment_status' => $paymentStatus,
            'min_amount' => $minAmount,
            'max_amount' => $maxAmount,
            'search' => $search,
        ];
    }

    private function presetToPeriod(string $periodPreset, string $today): array
    {
        $base = strtotime($today . ' 00:00:00');
        $startDate = $today;
        $endDate = $today;

        switch ($periodPreset) {
            case 'today':
                $startDate = $today;
                $endDate = $today;
                break;
            case 'yesterday':
                $startDate = date('Y-m-d', strtotime('-1 day', $base));
                $endDate = $startDate;
                break;
            case 'last7':
                $startDate = date('Y-m-d', strtotime('-6 days', $base));
                $endDate = $today;
                break;
            case 'last30':
                $startDate = date('Y-m-d', strtotime('-29 days', $base));
                $endDate = $today;
                break;
            case 'month_current':
                $startDate = date('Y-m-01', $base);
                $endDate = $today;
                break;
            case 'month_previous':
                $startDate = date('Y-m-01', strtotime('first day of previous month', $base));
                $endDate = date('Y-m-t', strtotime('last day of previous month', $base));
                break;
            default:
                $startDate = date('Y-m-d', strtotime('-29 days', $base));
                $endDate = $today;
                break;
        }

        return [
            'start_date' => $startDate,
            'end_date' => $endDate,
        ];
    }

    private function normalizeUserStatus(mixed $value): string
    {
        $status = strtolower(trim((string) ($value ?? 'ativo')));
        if (!in_array($status, self::ALLOWED_USER_STATUS, true)) {
            return 'ativo';
        }

        return $status;
    }

    private function normalizeHexColor(mixed $value, string $fallback): string
    {
        $color = strtolower(trim((string) ($value ?? '')));
        if (preg_match('/^#[0-9a-f]{6}$/', $color) !== 1) {
            return $fallback;
        }

        return $color;
    }

    private function isValidDate(string $date): bool
    {
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) !== 1) {
            return false;
        }

        [$year, $month, $day] = array_map('intval', explode('-', $date));
        return checkdate($month, $day, $year);
    }

    private function storeCompanyImage(int $companyId, string $type, mixed $file, string $base64Image): ?string
    {
        $uploadedPath = $this->storeCompanyImageFromUpload($companyId, $type, $file);
        if ($uploadedPath !== null) {
            return $uploadedPath;
        }

        return $this->storeCompanyImageFromBase64($companyId, $type, $base64Image);
    }

    private function normalizeStoredAssetPath(string $path): ?string
    {
        $value = trim($path);
        if ($value === '') {
            return null;
        }

        $normalized = '/' . ltrim(str_replace('\\', '/', $value), '/');
        if (!str_starts_with($normalized, '/uploads/company/')) {
            return $normalized;
        }

        return $normalized;
    }

    private function storeCompanyImageFromUpload(int $companyId, string $type, mixed $file): ?string
    {
        if (!is_array($file)) {
            return null;
        }

        $error = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);
        if ($error === UPLOAD_ERR_NO_FILE) {
            return null;
        }
        if ($error !== UPLOAD_ERR_OK) {
            throw new ValidationException($this->uploadErrorMessage($error));
        }

        $tmpName = (string) ($file['tmp_name'] ?? '');
        $isUploadedFile = $tmpName !== '' && is_uploaded_file($tmpName);
        $isLocalEnv = strtolower((string) getenv('APP_ENV')) === 'local';
        if (!$isUploadedFile && !($isLocalEnv && $tmpName !== '' && is_file($tmpName))) {
            throw new ValidationException('Arquivo enviado para identidade visual invalido.');
        }

        $size = (int) ($file['size'] ?? 0);
        if ($size <= 0 || $size > self::MAX_IMAGE_SIZE_BYTES) {
            throw new ValidationException('A imagem deve ter ate 10MB.');
        }

        $imageInfo = @getimagesize($tmpName);
        if (!is_array($imageInfo) || empty($imageInfo['mime'])) {
            throw new ValidationException('Arquivo enviado nao e uma imagem valida.');
        }

        $extension = $this->imageExtensionFromMime((string) $imageInfo['mime']);
        $targetPath = $this->prepareCompanyImageTargetPath($companyId, $type, $extension);

        $moved = move_uploaded_file($tmpName, $targetPath['absolute']);
        if (!$moved && $isLocalEnv && is_file($tmpName)) {
            $moved = @rename($tmpName, $targetPath['absolute']);
            if (!$moved) {
                $moved = @copy($tmpName, $targetPath['absolute']);
                if ($moved) {
                    @unlink($tmpName);
                }
            }
        }

        if (!$moved) {
            throw new ValidationException('Nao foi possivel salvar a imagem da empresa.');
        }

        return $targetPath['relative'];
    }

    private function storeCompanyImageFromBase64(int $companyId, string $type, string $base64Image): ?string
    {
        $base64 = trim($base64Image);
        if ($base64 === '') {
            return null;
        }

        if (!preg_match('#^data:image/([a-zA-Z0-9.+-]+);base64,#', $base64)) {
            throw new ValidationException('Formato de imagem invalido enviado pelo formulario.');
        }

        $payload = substr($base64, (int) strpos($base64, ',') + 1);
        $binary = base64_decode($payload, true);
        if ($binary === false || $binary === '') {
            throw new ValidationException('Falha ao decodificar a imagem enviada.');
        }

        $size = strlen($binary);
        if ($size <= 0 || $size > self::MAX_IMAGE_SIZE_BYTES) {
            throw new ValidationException('A imagem deve ter ate 10MB.');
        }

        $imageInfo = @getimagesizefromstring($binary);
        if (!is_array($imageInfo) || empty($imageInfo['mime'])) {
            throw new ValidationException('Conteudo enviado nao e uma imagem valida.');
        }

        $extension = $this->imageExtensionFromMime((string) $imageInfo['mime']);
        $targetPath = $this->prepareCompanyImageTargetPath($companyId, $type, $extension);

        if (@file_put_contents($targetPath['absolute'], $binary) === false) {
            throw new ValidationException('Nao foi possivel salvar a imagem enviada.');
        }

        return $targetPath['relative'];
    }

    private function imageExtensionFromMime(string $mime): string
    {
        $extensions = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
            'image/gif' => 'gif',
        ];

        $normalized = strtolower(trim($mime));
        if (!isset($extensions[$normalized])) {
            throw new ValidationException('Formato de imagem nao suportado. Use JPG, PNG, WEBP ou GIF.');
        }

        return $extensions[$normalized];
    }

    private function prepareCompanyImageTargetPath(int $companyId, string $type, string $extension): array
    {
        $baseDir = BASE_PATH . '/public/uploads/company/' . $companyId . '/branding';
        if (!is_dir($baseDir) && !mkdir($baseDir, 0775, true) && !is_dir($baseDir)) {
            throw new ValidationException('Nao foi possivel preparar pasta de upload da empresa.');
        }

        $prefix = $type === 'banner' ? 'banner_' : 'logo_';
        $filename = $prefix . date('YmdHis') . '_' . bin2hex(random_bytes(5)) . '.' . $extension;

        return [
            'absolute' => $baseDir . '/' . $filename,
            'relative' => '/uploads/company/' . $companyId . '/branding/' . $filename,
        ];
    }

    private function deleteCompanyImage(string $path): void
    {
        $normalized = '/' . ltrim(str_replace('\\', '/', trim($path)), '/');
        if (!str_starts_with($normalized, '/uploads/company/')) {
            return;
        }

        $absolutePath = BASE_PATH . '/public' . $normalized;
        if (is_file($absolutePath)) {
            @unlink($absolutePath);
        }
    }

    private function uploadErrorMessage(int $error): string
    {
        return match ($error) {
            UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'A imagem excede o limite permitido pelo servidor.',
            UPLOAD_ERR_PARTIAL => 'O upload da imagem foi interrompido. Tente novamente.',
            UPLOAD_ERR_NO_TMP_DIR => 'Servidor sem pasta temporaria para upload.',
            UPLOAD_ERR_CANT_WRITE => 'Servidor sem permissao para gravar o upload.',
            UPLOAD_ERR_EXTENSION => 'Uma extensao do PHP bloqueou o upload.',
            default => 'Falha no envio da imagem.',
        };
    }
}
