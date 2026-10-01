<?php
declare(strict_types=1);

namespace Sedema\Clients;

use PDO;

final class ClientRepository
{
    public function __construct(private readonly PDO $db)
    {
    }

    /** @return array{total:int,active:int,companies:int,professionals:int} */
    public function summary(): array
    {
        $row = $this->db->query(
            "SELECT COUNT(*) AS total,
                    COALESCE(SUM(CASE WHEN activo = 1 THEN 1 ELSE 0 END), 0) AS active,
                    COALESCE(SUM(CASE WHEN activo = 1 AND tipoCliente = 'EMPRESA' THEN 1 ELSE 0 END), 0) AS companies,
                    COALESCE(SUM(CASE WHEN activo = 1 AND tipoCliente IN ('ALBAÑIL','CONTRATISTA') THEN 1 ELSE 0 END), 0) AS professionals
             FROM cliente"
        )->fetch();

        return [
            'total' => (int) ($row['total'] ?? 0),
            'active' => (int) ($row['active'] ?? 0),
            'companies' => (int) ($row['companies'] ?? 0),
            'professionals' => (int) ($row['professionals'] ?? 0),
        ];
    }

    /** @return list<array<string,mixed>> */
    public function clients(string $search = '', string $type = 'all', string $status = 'active'): array
    {
        $where = [];
        $params = [];

        if ($search !== '') {
            $like = '%' . $search . '%';
            $where[] = '(c.nombre LIKE ? OR c.apellido LIKE ? OR CONCAT(c.nombre, " ", c.apellido) LIKE ? OR CONCAT(c.apellido, " ", c.nombre) LIKE ? OR c.cuitDNI LIKE ? OR c.razonSocial LIKE ? OR c.telefono LIKE ? OR c.localidad LIKE ?)';
            array_push($params, $like, $like, $like, $like, $like, $like, $like, $like);
        }
        if ($type !== 'all') {
            $where[] = 'c.tipoCliente = ?';
            $params[] = $type;
        }
        if ($status === 'active') {
            $where[] = 'c.activo = 1';
        } elseif ($status === 'inactive') {
            $where[] = 'c.activo = 0';
        }

        $sql = 'SELECT c.* FROM cliente c';
        if ($where) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }
        $sql .= ' ORDER BY c.activo DESC, c.apellido ASC, c.nombre ASC, c.idCliente DESC';

        $statement = $this->db->prepare($sql);
        $statement->execute($params);
        return $statement->fetchAll();
    }

    /** @return array<string,mixed>|null */
    public function client(int $id): ?array
    {
        $statement = $this->db->prepare('SELECT * FROM cliente WHERE idCliente = ? LIMIT 1');
        $statement->execute([$id]);
        $row = $statement->fetch();
        return $row ?: null;
    }

    public function documentExists(string $document, int $exceptId = 0): bool
    {
        $sql = 'SELECT 1 FROM cliente WHERE cuitDNI = ?';
        $params = [$document];
        if ($exceptId > 0) {
            $sql .= ' AND idCliente <> ?';
            $params[] = $exceptId;
        }
        $sql .= ' LIMIT 1';
        $statement = $this->db->prepare($sql);
        $statement->execute($params);
        return (bool) $statement->fetchColumn();
    }

    /** @param array<string,mixed> $data */
    public function insert(array $data): int
    {
        $statement = $this->db->prepare(
            'INSERT INTO cliente (nombre, apellido, cuitDNI, tipoCliente, razonSocial, telefono, direccion, localidad, activo)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1)'
        );
        $statement->execute([
            $data['nombre'], $data['apellido'], $data['cuitDNI'], $data['tipoCliente'],
            $data['razonSocial'], $data['telefono'], $data['direccion'], $data['localidad'],
        ]);
        return (int) $this->db->lastInsertId();
    }

    /** @param array<string,mixed> $data */
    public function update(int $id, array $data): void
    {
        $statement = $this->db->prepare(
            'UPDATE cliente
             SET nombre = ?, apellido = ?, cuitDNI = ?, tipoCliente = ?, razonSocial = ?, telefono = ?, direccion = ?, localidad = ?
             WHERE idCliente = ?'
        );
        $statement->execute([
            $data['nombre'], $data['apellido'], $data['cuitDNI'], $data['tipoCliente'],
            $data['razonSocial'], $data['telefono'], $data['direccion'], $data['localidad'], $id,
        ]);
    }

    public function setActive(int $id, bool $active): void
    {
        $statement = $this->db->prepare('UPDATE cliente SET activo = ? WHERE idCliente = ?');
        $statement->execute([$active ? 1 : 0, $id]);
    }
}
