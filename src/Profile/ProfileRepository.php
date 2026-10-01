<?php
declare(strict_types=1);

namespace Sedema\Profile;

use PDO;

final class ProfileRepository
{
    public function __construct(private readonly PDO $db) {}

    /** @return array<string,mixed>|null */
    public function profile(int $userId): ?array
    {
        $statement = $this->db->prepare(
            'SELECT u.idUsuario, u.idEmpleado, u.username, u.email, u.passwordHash, u.roles, u.profilePhoto, u.authVersion,
                    e.nombre, e.apellido, e.dni, e.telefono, e.activo
             FROM usuario u
             INNER JOIN empleado e ON e.idEmpleado = u.idEmpleado
             WHERE u.idUsuario = ? AND u.deletedAt IS NULL LIMIT 1'
        );
        $statement->execute([$userId]);
        $row = $statement->fetch();
        return is_array($row) ? $row : null;
    }

    public function emailExists(string $email, int $exceptUserId): bool
    {
        $statement = $this->db->prepare('SELECT 1 FROM usuario WHERE deletedAt IS NULL AND LOWER(email)=LOWER(?) AND idUsuario<>? LIMIT 1');
        $statement->execute([$email, $exceptUserId]);
        return (bool) $statement->fetchColumn();
    }

    public function usernameExists(string $username, int $exceptUserId): bool
    {
        $statement = $this->db->prepare('SELECT 1 FROM usuario WHERE deletedAt IS NULL AND LOWER(username)=LOWER(?) AND idUsuario<>? LIMIT 1');
        $statement->execute([$username, $exceptUserId]);
        return (bool) $statement->fetchColumn();
    }

    public function updateProfile(int $userId, int $employeeId, string $name, string $lastName, string $username, string $email, ?string $phone, ?string $photo): void
    {
        $this->db->beginTransaction();
        try {
            $this->db->prepare('UPDATE empleado SET nombre=?, apellido=?, telefono=? WHERE idEmpleado=?')
                ->execute([$name, $lastName, $phone, $employeeId]);
            $this->db->prepare(
                'UPDATE usuario SET username=?, email=?, profilePhoto=COALESCE(?, profilePhoto), emailValidated=1, updatedAt=NOW() WHERE idUsuario=? AND deletedAt IS NULL'
            )->execute([$username, $email, $photo, $userId]);
            $this->db->commit();
        } catch (\Throwable $error) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $error;
        }
    }

    public function updatePassword(int $userId, string $passwordHash): int
    {
        $this->db->prepare(
            'UPDATE usuario SET passwordHash=?, failedAttempts=0, lockedUntil=NULL, authVersion=authVersion+1, updatedAt=NOW() WHERE idUsuario=? AND deletedAt IS NULL'
        )->execute([$passwordHash, $userId]);
        $statement = $this->db->prepare('SELECT authVersion FROM usuario WHERE idUsuario=? LIMIT 1');
        $statement->execute([$userId]);
        return (int) $statement->fetchColumn();
    }
}
