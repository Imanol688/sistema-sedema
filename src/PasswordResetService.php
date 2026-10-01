<?php
declare(strict_types=1);

namespace Sedema;

use PDO;

final class PasswordResetService
{
    public function __construct(private readonly PDO $db)
    {
    }

    public function request(string $identifier): void
    {
        $identifier = mb_strtolower(trim($identifier));
        if ($identifier === '' || mb_strlen($identifier) > 150) {
            return;
        }

        $statement = $this->db->prepare(
            'SELECT idUsuario, email FROM usuario
             WHERE (LOWER(username) = ? OR LOWER(email) = ?) AND habilitado = 1 LIMIT 1'
        );
        $statement->execute([$identifier, $identifier]);
        $user = $statement->fetch();
        if (!$user || !filter_var($user['email'], FILTER_VALIDATE_EMAIL)) {
            password_verify('constant-time-padding', '$2y$10$xupz8QmE8iGHZrJp9eQ.yOcMWnAEN0pN5YkFPkGo59dFE4SfjmRmW');
            return;
        }

        $selector = bin2hex(random_bytes(9));
        $validator = bin2hex(random_bytes(32));
        $tokenHash = hash('sha256', $validator, true);

        $this->db->beginTransaction();
        try {
            $this->db->prepare('UPDATE password_reset_token SET usedAt = NOW() WHERE idUsuario = ? AND usedAt IS NULL')
                ->execute([(int) $user['idUsuario']]);
            $this->db->prepare(
                'INSERT INTO password_reset_token (idUsuario, selector, tokenHash, expiresAt, createdAt)
                 VALUES (?, ?, ?, DATE_ADD(NOW(), INTERVAL 30 MINUTE), NOW())'
            )->execute([(int) $user['idUsuario'], $selector, $tokenHash]);
            $this->db->commit();
        } catch (\Throwable $error) {
            $this->db->rollBack();
            throw $error;
        }

        $url = Config::appUrl() . '/reset-password.php?selector=' . rawurlencode($selector) . '&validator=' . rawurlencode($validator);
        (new ResetMailer())->send((string) $user['email'], $url);
    }

    public function reset(string $selector, string $validator, string $password): bool
    {
        if (!preg_match('/^[a-f0-9]{18}$/', $selector) || !preg_match('/^[a-f0-9]{64}$/', $validator)) {
            return false;
        }

        $this->db->beginTransaction();
        try {
            $statement = $this->db->prepare(
                'SELECT idToken, idUsuario, tokenHash FROM password_reset_token
                 WHERE selector = ? AND usedAt IS NULL AND expiresAt > NOW() LIMIT 1 FOR UPDATE'
            );
            $statement->execute([$selector]);
            $token = $statement->fetch();
            if (!$token || !hash_equals((string) $token['tokenHash'], hash('sha256', $validator, true))) {
                $this->db->rollBack();
                return false;
            }

            $newHash = password_hash($password, PASSWORD_DEFAULT);
            $this->db->prepare(
                'UPDATE usuario SET passwordHash = ?, failedAttempts = 0, lockedUntil = NULL, authVersion = authVersion + 1
                 WHERE idUsuario = ?'
            )->execute([$newHash, (int) $token['idUsuario']]);
            $this->db->prepare('UPDATE password_reset_token SET usedAt = NOW() WHERE idUsuario = ? AND usedAt IS NULL')
                ->execute([(int) $token['idUsuario']]);
            $this->db->prepare(
                'INSERT INTO auth_audit (idUsuario, eventType, createdAt) VALUES (?, "PASSWORD_RESET", NOW())'
            )->execute([(int) $token['idUsuario']]);
            $this->db->commit();
            return true;
        } catch (\Throwable $error) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $error;
        }
    }
}

