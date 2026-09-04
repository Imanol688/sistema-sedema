<?php
declare(strict_types=1);

namespace Sedema\Personnel;

use PDO;

final class PersonnelRepository
{
    public function __construct(private readonly PDO $db)
    {
    }

    /** @return array{total:int,active:int,inactive:int,withUser:int} */
    public function summary(): array
    {
        $row = $this->db->query(
            'SELECT COUNT(*) AS total,
                    SUM(CASE WHEN e.activo = 1 THEN 1 ELSE 0 END) AS activeCount,
                    SUM(CASE WHEN e.activo = 0 THEN 1 ELSE 0 END) AS inactiveCount,
                    SUM(CASE WHEN u.idUsuario IS NOT NULL THEN 1 ELSE 0 END) AS withUser
             FROM empleado e
             LEFT JOIN usuario u ON u.idEmpleado = e.idEmpleado'
        )->fetch();

        return [
            'total' => (int) ($row['total'] ?? 0),
            'active' => (int) ($row['activeCount'] ?? 0),
            'inactive' => (int) ($row['inactiveCount'] ?? 0),
            'withUser' => (int) ($row['withUser'] ?? 0),
        ];
    }

    /** @return list<array<string,mixed>> */
    public function employees(string $search = '', string $status = 'active'): array
    {
        $conditions = [];
        $params = [];
        if ($status === 'active') {
            $conditions[] = 'e.activo = 1';
        } elseif ($status === 'inactive') {
            $conditions[] = 'e.activo = 0';
        }
        if ($search !== '') {
            $conditions[] = '(e.nombre LIKE :search OR e.apellido LIKE :search OR e.dni LIKE :search OR u.username LIKE :search)';
            $params['search'] = '%' . $search . '%';
        }
        $where = $conditions ? 'WHERE ' . implode(' AND ', $conditions) : '';
        $statement = $this->db->prepare(
            "SELECT e.idEmpleado, e.nombre, e.apellido, e.dni, e.telefono, e.sueldoBase, e.activo,
                    u.idUsuario, u.username, u.roles, u.habilitado
             FROM empleado e
             LEFT JOIN usuario u ON u.idEmpleado = e.idEmpleado
             {$where}
             ORDER BY e.activo DESC, e.apellido, e.nombre, e.idEmpleado"
        );
        $statement->execute($params);
        return $statement->fetchAll() ?: [];
    }

    /** @return array<string,mixed>|null */
    public function employee(int $id): ?array
    {
        $statement = $this->db->prepare(
            'SELECT e.idEmpleado, e.nombre, e.apellido, e.dni, e.telefono, e.sueldoBase, e.activo,
                    u.idUsuario, u.username, u.roles, u.habilitado
             FROM empleado e
             LEFT JOIN usuario u ON u.idEmpleado = e.idEmpleado
             WHERE e.idEmpleado = ? LIMIT 1'
        );
        $statement->execute([$id]);
        $row = $statement->fetch();
        return is_array($row) ? $row : null;
    }

    public function dniExists(string $dni, int $exceptId = 0): bool
    {
        $statement = $this->db->prepare('SELECT 1 FROM empleado WHERE dni = ? AND idEmpleado <> ? LIMIT 1');
        $statement->execute([$dni, $exceptId]);
        return (bool) $statement->fetchColumn();
    }

    /** @param array<string,mixed> $data */
    public function insertEmployee(array $data): int
    {
        $statement = $this->db->prepare(
            'INSERT INTO empleado (nombre, apellido, dni, telefono, sueldoBase, activo)
             VALUES (:nombre, :apellido, :dni, :telefono, :sueldoBase, 1)'
        );
        $statement->execute($data);
        return (int) $this->db->lastInsertId();
    }

    /** @param array<string,mixed> $data */
    public function updateEmployee(int $id, array $data): void
    {
        $statement = $this->db->prepare(
            'UPDATE empleado
             SET nombre = :nombre, apellido = :apellido, dni = :dni, telefono = :telefono, sueldoBase = :sueldoBase
             WHERE idEmpleado = :id'
        );
        $statement->execute($data + ['id' => $id]);
    }

    public function setEmployeeActive(int $id, bool $active): void
    {
        $this->db->prepare('UPDATE empleado SET activo = ? WHERE idEmpleado = ?')->execute([$active ? 1 : 0, $id]);
        if (!$active) {
            $this->db->prepare('UPDATE usuario SET authVersion = authVersion + 1 WHERE idEmpleado = ?')->execute([$id]);
        }
    }

    public function employeeIdForUser(int $userId): ?int
    {
        $statement = $this->db->prepare('SELECT idEmpleado FROM usuario WHERE idUsuario = ? LIMIT 1');
        $statement->execute([$userId]);
        $value = $statement->fetchColumn();
        return $value === false ? null : (int) $value;
    }

    /** @return list<array<string,mixed>> */
    public function activeEmployees(): array
    {
        return $this->db->query(
            'SELECT idEmpleado, nombre, apellido, dni, sueldoBase
             FROM empleado WHERE activo = 1 ORDER BY apellido, nombre'
        )->fetchAll() ?: [];
    }

    /** @return list<array<string,mixed>> */
    public function payrolls(string $period = ''): array
    {
        $params = [];
        $where = '';
        if ($period !== '') {
            $where = 'WHERE l.periodo = :periodo';
            $params['periodo'] = $period;
        }
        $statement = $this->db->prepare(
            "SELECT l.idLiquidacion, l.idEmpleado, l.periodo, l.sueldoBase, l.totalHaberes,
                    l.totalDescuentos, l.montoNeto, l.numeroRecibo, l.fechaLiquidacion,
                    e.nombre, e.apellido, e.dni
             FROM liquidacionsueldo l
             INNER JOIN empleado e ON e.idEmpleado = l.idEmpleado
             {$where}
             ORDER BY l.periodo DESC, e.apellido, e.nombre, l.idLiquidacion DESC"
        );
        $statement->execute($params);
        return $statement->fetchAll() ?: [];
    }

    /** @return array<string,mixed>|null */
    public function payroll(int $id): ?array
    {
        $statement = $this->db->prepare(
            'SELECT l.*, e.nombre, e.apellido, e.dni, e.telefono,
                    CONCAT(a.nombre, " ", a.apellido) AS liquidadorNombre
             FROM liquidacionsueldo l
             INNER JOIN empleado e ON e.idEmpleado = l.idEmpleado
             INNER JOIN usuario u ON u.idUsuario = l.idAdminLiquidador
             LEFT JOIN empleado a ON a.idEmpleado = u.idEmpleado
             WHERE l.idLiquidacion = ? LIMIT 1'
        );
        $statement->execute([$id]);
        $row = $statement->fetch();
        return is_array($row) ? $row : null;
    }

    /** @return list<array<string,mixed>> */
    public function payrollItems(int $payrollId): array
    {
        $statement = $this->db->prepare(
            'SELECT idConcepto, tipo, concepto, importe
             FROM personnel_payroll_item WHERE idLiquidacion = ? ORDER BY idConcepto'
        );
        $statement->execute([$payrollId]);
        return $statement->fetchAll() ?: [];
    }

    public function payrollExists(int $employeeId, string $period): bool
    {
        $statement = $this->db->prepare('SELECT 1 FROM liquidacionsueldo WHERE idEmpleado = ? AND periodo = ? LIMIT 1');
        $statement->execute([$employeeId, $period]);
        return (bool) $statement->fetchColumn();
    }

    /** @param list<array{type:string,concept:string,amount:float}> $items */
    public function createPayroll(
        int $employeeId,
        int $adminUserId,
        string $period,
        float $baseSalary,
        float $totalEarnings,
        float $totalDiscounts,
        float $net,
        array $items
    ): int {
        return $this->transaction(function () use ($employeeId, $adminUserId, $period, $baseSalary, $totalEarnings, $totalDiscounts, $net, $items): int {
            $statement = $this->db->prepare(
                'INSERT INTO liquidacionsueldo
                    (idEmpleado, idAdminLiquidador, periodo, sueldoBase, totalHaberes, totalDescuentos, montoNeto, numeroRecibo, fechaLiquidacion)
                 VALUES (?, ?, ?, ?, ?, ?, ?, NULL, NOW())'
            );
            $statement->execute([$employeeId, $adminUserId, $period, $baseSalary, $totalEarnings, $totalDiscounts, $net]);
            $id = (int) $this->db->lastInsertId();
            $receipt = sprintf('REC-%s-%06d', str_replace('-', '', $period), $id);
            $this->db->prepare('UPDATE liquidacionsueldo SET numeroRecibo = ? WHERE idLiquidacion = ?')->execute([$receipt, $id]);

            $itemStatement = $this->db->prepare(
                'INSERT INTO personnel_payroll_item (idLiquidacion, tipo, concepto, importe) VALUES (?, ?, ?, ?)'
            );
            foreach ($items as $item) {
                $itemStatement->execute([$id, $item['type'], $item['concept'], $item['amount']]);
            }
            return $id;
        });
    }

    public function transaction(callable $callback): mixed
    {
        $this->db->beginTransaction();
        try {
            $result = $callback();
            $this->db->commit();
            return $result;
        } catch (\Throwable $error) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $error;
        }
    }
}
