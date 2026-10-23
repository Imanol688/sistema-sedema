<?php
declare(strict_types=1);
namespace Sedema\Inventory;

use PDO;
use PDOException;
use Throwable;

/** Durable deduplication, committed atomically with the stock/business changes. */
final class OperationGuard
{
    public static function newKey(): string
    {
        return bin2hex(random_bytes(32));
    }

    public static function version(array $input, string $exception): int
    {
        $version = filter_var($input['operationVersion'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]]);
        if ($version === false || $version === null) {
            throw new $exception('Recargá el despacho antes de confirmar la operación.');
        }
        return $version;
    }

    public static function assertVersion(array $dispatch, int $expected, string $exception): void
    {
        if ((int) $dispatch['operationVersion'] !== $expected) {
            throw new $exception('Otro usuario modificó este despacho. Recargá la página y revisá sus datos.');
        }
    }

    public static function run(PDO $db, array $input, int $actor, string $operation, callable $callback, string $exception = InventoryException::class): mixed
    {
        $key = $input['operation_key'] ?? '';
        if (!is_string($key) || !preg_match('/^[a-f0-9]{64}$/D', $key) || $actor < 1) {
            throw new $exception('El formulario venció. Recargá la página antes de confirmar.');
        }
        unset($input['operation_key'], $input['csrf_token']);
        $hash = hash('sha256', json_encode(self::canonical($input), JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
        if ($db->inTransaction()) {
            throw new \LogicException('OperationGuard requiere una transacción propia.');
        }
        for ($attempt = 0; $attempt < 3; $attempt++) {
            $db->beginTransaction();
            try {
                // The unique key serializes simultaneous retries, even across browser sessions.
                $s = $db->prepare('INSERT INTO module_operation (operationKey,actorUserId,operationType,payloadHash) VALUES (?,?,?,?) ON DUPLICATE KEY UPDATE operationKey=VALUES(operationKey)');
                $s->execute([$key, $actor, $operation, $hash]);
                $s = $db->prepare('SELECT * FROM module_operation WHERE operationKey=? FOR UPDATE');
                $s->execute([$key]);
                $row = $s->fetch(PDO::FETCH_ASSOC);
                if ((int) $row['actorUserId'] !== $actor || $row['operationType'] !== $operation || !hash_equals($row['payloadHash'], $hash)) {
                    throw new $exception('Este formulario ya se utilizó con otros datos. Recargá la página para iniciar una nueva operación.');
                }
                if ($row['resultJson'] !== null) {
                    $result = json_decode($row['resultJson'], true, 512, JSON_THROW_ON_ERROR);
                    $db->commit();
                    return $result;
                }
                $result = $callback();
                $db->prepare('UPDATE module_operation SET resultJson=?,completedAt=NOW() WHERE operationKey=?')
                    ->execute([json_encode($result, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR), $key]);
                $db->commit();
                return $result;
            } catch (Throwable $error) {
                if ($db->inTransaction()) $db->rollBack();
                if ($error instanceof PDOException && in_array((int) ($error->errorInfo[1] ?? 0), [1205, 1213], true)) {
                    if ($attempt < 2) {
                        usleep(random_int(10000, 40000));
                        continue;
                    }
                    throw new $exception('Otra operación está utilizando estos datos. Reintentá con el mismo formulario.');
                }
                throw $error;
            }
        }
        throw new \LogicException('No se pudo completar la operación.');
    }

    private static function canonical(mixed $value): mixed
    {
        if (!is_array($value)) return $value;
        ksort($value);
        foreach ($value as $key => $item) $value[$key] = self::canonical($item);
        return $value;
    }
}
