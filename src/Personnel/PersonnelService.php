<?php
declare(strict_types=1);

namespace Sedema\Personnel;

use PDOException;

final class PersonnelService
{
    public function __construct(private readonly PersonnelRepository $repository)
    {
    }

    /** @param array<string,mixed> $input */
    public function saveEmployee(array $input): int
    {
        $id = max(0, (int) ($input['idEmpleado'] ?? 0));
        $name = $this->requiredText($input['nombre'] ?? '', 'Ingresá el nombre.', 100);
        $surname = $this->requiredText($input['apellido'] ?? '', 'Ingresá el apellido.', 100);
        $dni = $this->requiredText($input['dni'] ?? '', 'Ingresá el DNI.', 20);
        $phone = mb_substr(trim((string) ($input['telefono'] ?? '')), 0, 50);
        $salary = $this->money($input['sueldoBase'] ?? 0, 'El sueldo base no es válido.');

        if ($this->repository->dniExists($dni, $id)) {
            throw new PersonnelException('Ya existe un empleado registrado con ese DNI.');
        }

        $data = [
            'nombre' => $name,
            'apellido' => $surname,
            'dni' => $dni,
            'telefono' => $phone !== '' ? $phone : null,
            'sueldoBase' => number_format($salary, 2, '.', ''),
        ];
        try {
            if ($id > 0) {
                if (!$this->repository->employee($id)) {
                    throw new PersonnelException('El empleado indicado no existe.');
                }
                $this->repository->updateEmployee($id, $data);
                return $id;
            }
            return $this->repository->insertEmployee($data);
        } catch (PDOException $error) {
            if ((string) $error->getCode() === '23000') {
                throw new PersonnelException('No se pudo guardar el legajo porque hay datos duplicados.');
            }
            throw $error;
        }
    }

    public function setEmployeeActive(int $employeeId, bool $active, int $actorUserId): void
    {
        $employee = $this->repository->employee($employeeId);
        if (!$employee) {
            throw new PersonnelException('El empleado indicado no existe.');
        }
        $actorEmployeeId = $this->repository->employeeIdForUser($actorUserId);
        if (!$active && $actorEmployeeId === $employeeId) {
            throw new PersonnelException('No podés dar de baja tu propio legajo mientras estás usando el sistema.');
        }
        $this->repository->setEmployeeActive($employeeId, $active);
    }

    /** @param array<string,mixed> $input */
    public function createPayroll(array $input, int $adminUserId): int
    {
        $employeeId = max(0, (int) ($input['idEmpleado'] ?? 0));
        $employee = $this->repository->employee($employeeId);
        if (!$employee || (int) $employee['activo'] !== 1) {
            throw new PersonnelException('Seleccioná un empleado activo.');
        }
        $period = trim((string) ($input['periodo'] ?? ''));
        if (!preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $period)) {
            throw new PersonnelException('El período debe tener formato AAAA-MM.');
        }
        if ($this->repository->payrollExists($employeeId, $period)) {
            throw new PersonnelException('Ese empleado ya tiene una liquidación registrada para el período seleccionado.');
        }

        $baseSalary = max(0.0, (float) $employee['sueldoBase']);
        $items = [[
            'type' => 'HABER',
            'concept' => 'Sueldo base',
            'amount' => $baseSalary,
        ]];
        foreach ($this->parseConcepts((string) ($input['haberes'] ?? ''), 'HABER') as $item) {
            $items[] = $item;
        }
        foreach ($this->parseConcepts((string) ($input['descuentos'] ?? ''), 'DESCUENTO') as $item) {
            $items[] = $item;
        }

        $totalEarnings = 0.0;
        $totalDiscounts = 0.0;
        foreach ($items as $item) {
            if ($item['type'] === 'HABER') {
                $totalEarnings += $item['amount'];
            } else {
                $totalDiscounts += $item['amount'];
            }
        }
        $net = $totalEarnings - $totalDiscounts;
        if ($net < 0) {
            throw new PersonnelException('Los descuentos no pueden superar el total de haberes.');
        }

        return $this->repository->createPayroll(
            $employeeId,
            $adminUserId,
            $period,
            $baseSalary,
            $totalEarnings,
            $totalDiscounts,
            $net,
            $items
        );
    }

    public function formatMoney(float $amount): string
    {
        return '$ ' . number_format($amount, 2, ',', '.');
    }

    /** @return list<array{type:string,concept:string,amount:float}> */
    private function parseConcepts(string $text, string $type): array
    {
        $items = [];
        foreach (preg_split('/\R/u', trim($text)) ?: [] as $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }
            $parts = array_map('trim', explode('=', $line, 2));
            if (count($parts) !== 2 || $parts[0] === '') {
                throw new PersonnelException('Cada concepto debe escribirse como Concepto=Importe.');
            }
            $concept = mb_substr($parts[0], 0, 150);
            $amount = $this->money(str_replace(',', '.', $parts[1]), 'Hay un importe de concepto no válido.');
            if ($amount <= 0) {
                throw new PersonnelException('Los importes de los conceptos deben ser mayores que cero.');
            }
            $items[] = ['type' => $type, 'concept' => $concept, 'amount' => $amount];
        }
        return $items;
    }

    private function requiredText(mixed $value, string $message, int $max): string
    {
        $text = trim((string) $value);
        if ($text === '') {
            throw new PersonnelException($message);
        }
        return mb_substr($text, 0, $max);
    }

    private function money(mixed $value, string $message): float
    {
        $normalized = str_replace(',', '.', trim((string) $value));
        if ($normalized === '' || !is_numeric($normalized)) {
            throw new PersonnelException($message);
        }
        $amount = (float) $normalized;
        if (!is_finite($amount) || $amount < 0) {
            throw new PersonnelException($message);
        }
        return round($amount, 2);
    }
}
