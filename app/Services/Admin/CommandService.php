<?php
declare(strict_types=1);

namespace App\Services\Admin;

use App\Exceptions\ValidationException;
use App\Repositories\CommandRepository;
use App\Repositories\TableRepository;

final class CommandService
{
    public function __construct(
        private readonly CommandRepository $commands = new CommandRepository(),
        private readonly TableRepository $tables = new TableRepository()
    ) {}

    public function listOpen(int $companyId): array
    {
        return $this->commands->openCommandsByCompany($companyId);
    }

    public function open(int $companyId, int $userId, array $input): int
    {
        $tableId = (int) ($input['table_id'] ?? 0);
        $customerName = trim((string) ($input['customer_name'] ?? ''));
        $notes = trim((string) ($input['notes'] ?? ''));

        if ($tableId <= 0) {
            throw new ValidationException('Selecione uma mesa válida.');
        }

        $table = $this->tables->findById($companyId, $tableId);
        if ($table === null) {
            throw new ValidationException('Mesa não encontrada para esta empresa.');
        }

        if ($this->commands->findOpenByTable($companyId, $tableId) !== null) {
            throw new ValidationException('Já existe uma comanda aberta para esta mesa.');
        }

        if ($customerName === '') {
            throw new ValidationException('Informe o nome do cliente.');
        }

        $commandId = $this->commands->create([
            'company_id' => $companyId,
            'table_id' => $tableId,
            'customer_id' => null,
            'opened_by_user_id' => $userId,
            'customer_name' => $customerName,
            'notes' => $notes !== '' ? $notes : null,
        ]);

        $this->tables->updateStatus($tableId, 'ocupada');

        return $commandId;
    }
}
