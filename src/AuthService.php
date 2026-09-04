<?php
declare(strict_types=1);

namespace Sedema;

use DateInterval;
use DateTimeImmutable;
use PDO;

final class AuthService
{
    private const MAX_ATTEMPTS = 5;
    private const DUMMY_HASH = '$2y$10$xupz8QmE8iGHZrJp9eQ.yOcMWnAEN0pN5YkFPkGo59dFE4SfjmRmW';

    public function __construct(private readonly PDO $db)
    {
    }

    /** @return array{ok:bool,message:string} */
    public function login(string $username, string $password, string $ip, string $userAgent): array
    {
        $username = mb_strtolower(trim($username));
        if ($username === '' || mb_strlen($username) > 100 || $password === '') {
            return $this->failedResult();
        }

        $identityHash = hash('sha256', $username, true);
        $ipHash = hash_hmac('sha256', $ip, (string) Config::get('APP_KEY', 'development-only'), true);
        if ($this->recentAttempts($identityHash, $ipHash) >= self::MAX_ATTEMPTS) {
            return ['ok' => false, 'message' => 'Demasiados intentos. Esperá 15 minutos antes de volver a intentar.'];
        }

        $statement = $this->db->prepare(
            'SELECT u.idUsuario, u.username, u.email, u.passwordHash, u.roles, u.permisos, u.habilitado,
                    u.failedAttempts, u.lockedUntil, u.authVersion,
                    CONCAT(e.nombre, " ", e.apellido) AS nombreCompleto, e.activo AS empleadoActivo
             FROM usuario u
             LEFT JOIN empleado e ON e.idEmpleado = u.idEmpleado
             WHERE LOWER(u.username) = :username OR LOWER(u.email) = :email
             LIMIT 1'
        );
        $statement->execute([
            'username' => $username,
            'email' => $username,
        ]);
        $user = $statement->fetch();

        $hash = is_array($user) ? (string) $user['passwordHash'] : self::DUMMY_HASH;
        $passwordIsValid = password_verify($password, $hash);
        $isLocked = is_array($user) && !empty($user['lockedUntil']) && strtotime((string) $user['lockedUntil']) > time();
        $isEnabled = is_array($user) && (int) $user['habilitado'] === 1 && ($user['empleadoActivo'] === null || (int) $user['empleadoActivo'] === 1);

        if (!$user || !$passwordIsValid || !$isEnabled || $isLocked) {
            $this->registerFailure($identityHash, $ipHash, $user ? (int) $user['idUsuario'] : null, $userAgent);
            return $this->failedResult();
        }

        $this->db->beginTransaction();
        try {
            $this->db->prepare('UPDATE usuario SET failedAttempts = 0, lockedUntil = NULL, ultimoAcceso = NOW() WHERE idUsuario = ?')
                ->execute([(int) $user['idUsuario']]);
            $this->db->prepare('DELETE FROM login_attempt WHERE identityHash = ?')
                ->execute([$identityHash]);

            if (password_needs_rehash($hash, PASSWORD_DEFAULT)) {
                $this->db->prepare('UPDATE usuario SET passwordHash = ? WHERE idUsuario = ?')
                    ->execute([password_hash($password, PASSWORD_DEFAULT), (int) $user['idUsuario']]);
            }

            $this->audit((int) $user['idUsuario'], 'LOGIN_OK', $ipHash, $userAgent);
            $this->db->commit();
        } catch (\Throwable $error) {
            $this->db->rollBack();
            throw $error;
        }

        session_regenerate_id(true);
        Csrf::rotate();
        $_SESSION['auth'] = [
            'id' => (int) $user['idUsuario'],
            'username' => (string) $user['username'],
            'name' => trim((string) ($user['nombreCompleto'] ?: $user['username'])),
            'role' => (string) $user['roles'],
            'permissions' => json_decode((string) ($user['permisos'] ?: '[]'), true) ?: [],
            'auth_version' => (int) $user['authVersion'],
            'logged_at' => time(),
        ];

        return ['ok' => true, 'message' => 'Acceso concedido.'];
    }

    /** @return array{id:int,username:string,name:string,role:string,permissions:array,auth_version:int,logged_at:int}|null */
    public function authenticatedUser(): ?array
    {
        $sessionUser = $_SESSION['auth'] ?? null;
        if (!is_array($sessionUser) || empty($sessionUser['id'])) {
            return null;
        }

        if ((int) ($sessionUser['logged_at'] ?? 0) < time() - 28800) {
            $this->logout();
            return null;
        }

        $statement = $this->db->prepare('SELECT habilitado, authVersion FROM usuario WHERE idUsuario = ? LIMIT 1');
        $statement->execute([(int) $sessionUser['id']]);
        $status = $statement->fetch();
        if (!$status || (int) $status['habilitado'] !== 1 || (int) $status['authVersion'] !== (int) $sessionUser['auth_version']) {
            $this->logout();
            return null;
        }

        return $sessionUser;
    }

    public function logout(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
        }
        session_destroy();
    }

    private function recentAttempts(string $identityHash, string $ipHash): int
    {
        $statement = $this->db->prepare(
            'SELECT COUNT(*) FROM login_attempt
             WHERE attemptedAt >= DATE_SUB(NOW(), INTERVAL 15 MINUTE)
               AND (identityHash = ? OR ipHash = ?)'
        );
        $statement->execute([$identityHash, $ipHash]);
        return (int) $statement->fetchColumn();
    }

    private function registerFailure(string $identityHash, string $ipHash, ?int $userId, string $userAgent): void
    {
        $statement = $this->db->prepare('INSERT INTO login_attempt (identityHash, ipHash, attemptedAt) VALUES (?, ?, NOW())');
        $statement->execute([$identityHash, $ipHash]);

        if ($userId !== null) {
            $this->db->prepare(
                'UPDATE usuario
                 SET failedAttempts = failedAttempts + 1,
                     lockedUntil = IF(failedAttempts + 1 >= ?, DATE_ADD(NOW(), INTERVAL 15 MINUTE), lockedUntil)
                 WHERE idUsuario = ?'
            )->execute([self::MAX_ATTEMPTS, $userId]);
            $this->audit($userId, 'LOGIN_FAIL', $ipHash, $userAgent);
        }
    }

    private function audit(?int $userId, string $event, string $ipHash, string $userAgent): void
    {
        $statement = $this->db->prepare(
            'INSERT INTO auth_audit (idUsuario, eventType, ipHash, userAgent, createdAt)
             VALUES (?, ?, ?, ?, NOW())'
        );
        $statement->execute([$userId, $event, $ipHash, mb_substr($userAgent, 0, 255)]);
    }

    /** @return array{ok:false,message:string} */
    private function failedResult(): array
    {
        return ['ok' => false, 'message' => 'Usuario o contraseña incorrectos, o la cuenta no está disponible.'];
    }
}
