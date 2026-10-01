<?php
declare(strict_types=1);

namespace Sedema\Access;

use PDO;

final class UserAccessRepository
{
    public function __construct(private readonly PDO $db) {}

    /** @return list<array<string,mixed>> */
    public function accounts(string $search = ''): array
    {
        $params = [];
        $where = 'WHERE u.deletedAt IS NULL';
        if ($search !== '') {
            $where .= " AND (e.nombre LIKE :nombre OR e.apellido LIKE :apellido OR e.dni LIKE :dni
                       OR u.email LIKE :email OR u.username LIKE :username OR u.roles LIKE :role)";
            $term = '%' . $search . '%';
            $params = ['nombre'=>$term,'apellido'=>$term,'dni'=>$term,'email'=>$term,'username'=>$term,'role'=>$term];
        }
        $statement = $this->db->prepare(
            "SELECT u.idUsuario, u.idEmpleado, u.username, u.email, u.roles, u.sector, u.permisos,
                    u.habilitado, u.mustChangePassword, u.initialSetupCompleted, u.profilePhoto,
                    u.credentialSentAt, u.ultimoAcceso, e.nombre, e.apellido, e.dni, e.activo
             FROM usuario u
             INNER JOIN empleado e ON e.idEmpleado = u.idEmpleado
             {$where}
             ORDER BY u.habilitado DESC, e.apellido, e.nombre"
        );
        $statement->execute($params);
        return $statement->fetchAll() ?: [];
    }

    /** @return array<string,mixed>|null */
    public function account(int $userId): ?array
    {
        $statement = $this->db->prepare(
            'SELECT u.*, e.nombre, e.apellido, e.dni, e.telefono, e.activo
             FROM usuario u INNER JOIN empleado e ON e.idEmpleado = u.idEmpleado
             WHERE u.idUsuario = ? AND u.deletedAt IS NULL LIMIT 1'
        );
        $statement->execute([$userId]);
        $row = $statement->fetch();
        return is_array($row) ? $row : null;
    }

    /** @return array<string,mixed>|null */
    public function employeeByDni(string $dni): ?array
    {
        $statement = $this->db->prepare(
            'SELECT e.*, u.idUsuario, u.deletedAt FROM empleado e LEFT JOIN usuario u ON u.idEmpleado = e.idEmpleado WHERE e.dni = ? LIMIT 1'
        );
        $statement->execute([$dni]);
        $row = $statement->fetch();
        return is_array($row) ? $row : null;
    }

    public function emailExists(string $email, int $exceptUserId = 0): bool
    {
        $statement = $this->db->prepare('SELECT 1 FROM usuario WHERE deletedAt IS NULL AND LOWER(email)=LOWER(?) AND idUsuario<>? LIMIT 1');
        $statement->execute([$email, $exceptUserId]);
        return (bool) $statement->fetchColumn();
    }

    public function usernameExists(string $username, int $exceptUserId = 0): bool
    {
        $statement = $this->db->prepare('SELECT 1 FROM usuario WHERE deletedAt IS NULL AND LOWER(username)=LOWER(?) AND idUsuario<>? LIMIT 1');
        $statement->execute([$username, $exceptUserId]);
        return (bool) $statement->fetchColumn();
    }

    /** @param list<string> $permissions */
    public function createAccount(array $employeeData, string $email, string $passwordHash, string $role, array $permissions, int $actorUserId): int
    {
        $employee = $this->employeeByDni((string) $employeeData['dni']);
        if ($employee && !empty($employee['idUsuario']) && empty($employee['deletedAt'])) {
            throw new AccessException('El empleado indicado ya tiene una cuenta de acceso vinculada.');
        }

        if ($employee) {
            $employeeId = (int) $employee['idEmpleado'];
            $this->db->prepare('UPDATE empleado SET nombre=?, apellido=?, activo=1 WHERE idEmpleado=?')
                ->execute([$employeeData['nombre'], $employeeData['apellido'], $employeeId]);
        } else {
            $this->db->prepare('INSERT INTO empleado (nombre, apellido, dni, telefono, sueldoBase, activo) VALUES (?, ?, ?, NULL, 0.00, 1)')
                ->execute([$employeeData['nombre'], $employeeData['apellido'], $employeeData['dni']]);
            $employeeId = (int) $this->db->lastInsertId();
        }

        $tempUsername = $this->temporaryUsername((string) $employeeData['dni']);
        $encodedPermissions = json_encode($permissions, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        if ($employee && !empty($employee['idUsuario']) && !empty($employee['deletedAt'])) {
            $userId = (int) $employee['idUsuario'];
            $this->db->prepare(
                'UPDATE usuario SET username=?, email=?, passwordHash=?, mustChangePassword=1, initialSetupCompleted=0,
                 roles=?, sector=NULL, permisos=?, habilitado=1, failedAttempts=0, lockedUntil=NULL,
                 authVersion=authVersion+1, credentialSentAt=NULL, emailValidated=1, deletedAt=NULL, deletedBy=NULL,
                 updatedAt=NOW() WHERE idUsuario=?'
            )->execute([$tempUsername,$email,$passwordHash,$role,$encodedPermissions,$userId]);
        } else {
            $statement = $this->db->prepare(
                'INSERT INTO usuario
                    (idEmpleado, username, email, passwordHash, mustChangePassword, initialSetupCompleted, roles, sector, permisos,
                     habilitado, failedAttempts, authVersion, credentialSentAt, emailValidated, createdAt, updatedAt)
                 VALUES (?, ?, ?, ?, 1, 0, ?, NULL, ?, 1, 0, 1, NULL, 1, NOW(), NOW())'
            );
            $statement->execute([$employeeId,$tempUsername,$email,$passwordHash,$role,$encodedPermissions]);
            $userId = (int) $this->db->lastInsertId();
        }

        $this->audit($userId, $actorUserId, 'ACCOUNT_CREATED', 'Cuenta creada para ' . $email . ' con rol ' . $role . '.');
        return $userId;
    }

    public function markCredentialSent(int $userId, int $actorUserId): void
    {
        $this->db->prepare('UPDATE usuario SET credentialSentAt=NOW() WHERE idUsuario=?')->execute([$userId]);
        $this->audit($userId, $actorUserId, 'TEMP_CREDENTIAL_SENT', 'Credencial temporal enviada por correo.');
    }

    public function replaceTemporaryPassword(int $userId, string $passwordHash, int $actorUserId): void
    {
        $this->db->prepare(
            'UPDATE usuario SET passwordHash=?, mustChangePassword=1, initialSetupCompleted=0,
             failedAttempts=0, lockedUntil=NULL, habilitado=1, authVersion=authVersion+1,
             credentialSentAt=NULL, updatedAt=NOW() WHERE idUsuario=? AND deletedAt IS NULL'
        )->execute([$passwordHash, $userId]);
        $this->audit($userId, $actorUserId, 'PERMISSIONS_CHANGED', 'Se regeneró la credencial temporal y se invalidaron sesiones anteriores.');
    }

    /** @param list<string> $permissions */
    public function updateAccess(int $userId, string $email, string $role, array $permissions, bool $enabled, int $actorUserId): void
    {
        $current = $this->account($userId);
        if (!$current) {
            throw new AccessException('La cuenta indicada no existe.');
        }
        $statement = $this->db->prepare(
            'UPDATE usuario SET email=?, roles=?, sector=NULL, permisos=?, habilitado=?, emailValidated=1,
             authVersion=authVersion+1, updatedAt=NOW() WHERE idUsuario=? AND deletedAt IS NULL'
        );
        $statement->execute([$email,$role,json_encode($permissions, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),$enabled?1:0,$userId]);
        if (mb_strtolower((string) $current['email']) !== mb_strtolower($email)) {
            $this->audit($userId, $actorUserId, 'EMAIL_CHANGED', 'Correo actualizado de ' . (string) $current['email'] . ' a ' . $email . '.');
        }
        $this->audit($userId, $actorUserId, 'PERMISSIONS_CHANGED', 'Rol o permisos actualizados.');
        $this->audit($userId, $actorUserId, $enabled ? 'ACCOUNT_ENABLED' : 'ACCOUNT_DISABLED', $enabled ? 'Cuenta habilitada.' : 'Cuenta deshabilitada.');
    }

    public function deleteAccount(int $userId, int $actorUserId): void
    {
        $account = $this->account($userId);
        if (!$account) {
            throw new AccessException('La cuenta indicada no existe.');
        }
        $this->audit($userId, $actorUserId, 'ACCOUNT_DELETED', 'Cuenta eliminada por el administrador. El legajo se conserva.');
        $stamp = date('YmdHis');
        $deletedUsername = 'deleted.' . $userId . '.' . $stamp;
        $deletedEmail = 'deleted+' . $userId . '.' . $stamp . '@invalid.local';
        $randomHash = password_hash(bin2hex(random_bytes(32)), PASSWORD_DEFAULT);
        $this->db->prepare(
            'UPDATE usuario SET username=?, email=?, passwordHash=?, permisos=JSON_ARRAY(), habilitado=0,
             mustChangePassword=0, initialSetupCompleted=1, credentialSentAt=NULL, profilePhoto=NULL,
             failedAttempts=0, lockedUntil=NULL, authVersion=authVersion+1, deletedAt=NOW(), deletedBy=?, updatedAt=NOW()
             WHERE idUsuario=?'
        )->execute([$deletedUsername,$deletedEmail,$randomHash,$actorUserId,$userId]);
    }

    public function completeInitialSetup(int $userId, string $username, string $passwordHash, ?string $profilePhoto): void
    {
        $statement = $this->db->prepare(
            'UPDATE usuario SET username=?, passwordHash=?, mustChangePassword=0, initialSetupCompleted=1,
             profilePhoto=COALESCE(?, profilePhoto), failedAttempts=0, lockedUntil=NULL,
             authVersion=authVersion+1, updatedAt=NOW() WHERE idUsuario=? AND deletedAt IS NULL'
        );
        $statement->execute([$username,$passwordHash,$profilePhoto,$userId]);
        $this->audit($userId, $userId, 'FIRST_ACCESS_COMPLETED', 'Configuración inicial completada.');
    }

    private function temporaryUsername(string $dni): string
    {
        $base = mb_substr('tmp.' . preg_replace('/[^0-9A-Za-z]/', '', $dni), 0, 80);
        do { $candidate = $base . '.' . bin2hex(random_bytes(3)); } while ($this->usernameExists($candidate));
        return $candidate;
    }

    private function audit(?int $userId, ?int $actorUserId, string $event, string $detail): void
    {
        $this->db->prepare('INSERT INTO user_access_audit (idUsuario, actorUserId, eventType, detail, createdAt) VALUES (?, ?, ?, ?, NOW())')
            ->execute([$userId,$actorUserId,$event,mb_substr($detail,0,500)]);
    }

    public function transaction(callable $callback): mixed
    {
        $this->db->beginTransaction();
        try {
            $result = $callback();
            $this->db->commit();
            return $result;
        } catch (\Throwable $error) {
            if ($this->db->inTransaction()) { $this->db->rollBack(); }
            throw $error;
        }
    }
}
