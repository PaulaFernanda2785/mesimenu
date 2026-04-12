<?php
declare(strict_types=1);

namespace App\Services\Admin;

use App\Exceptions\ValidationException;
use App\Repositories\TableRepository;

final class TableService
{
    public function __construct(
        private readonly TableRepository $tables = new TableRepository()
    ) {}

    public function list(int $companyId): array
    {
        return $this->tables->allByCompany($companyId);
    }

    public function create(int $companyId, array $input): int
    {
        $number = (int) ($input['number'] ?? 0);
        $name = trim((string) ($input['name'] ?? ''));
        $capacityRaw = trim((string) ($input['capacity'] ?? ''));

        if ($number <= 0) {
            throw new ValidationException('Informe um número de mesa válido.');
        }

        if ($this->tables->findByNumber($companyId, $number) !== null) {
            throw new ValidationException('Já existe uma mesa com esse número.');
        }

        $capacity = null;
        if ($capacityRaw !== '') {
            $capacity = (int) $capacityRaw;
            if ($capacity <= 0) {
                throw new ValidationException('A capacidade deve ser maior que zero.');
            }
        }

        $token = 'mesa-' . $companyId . '-' . $number . '-' . bin2hex(random_bytes(4));

        return $this->tables->create([
            'company_id' => $companyId,
            'name' => $name !== '' ? $name : 'Mesa ' . str_pad((string) $number, 2, '0', STR_PAD_LEFT),
            'number' => $number,
            'capacity' => $capacity,
            'qr_code_token' => $token,
            'status' => 'livre',
        ]);
    }
}
