<?php
declare(strict_types=1);
namespace Sedema\Payments;
use PDO;

/** A form key is committed with the payment. External QR requests keep their own remote idempotency key. */
final class PaymentRequest
{
    private function __construct(private string $key, private string $hash, private int $actor) {}
    public static function fromInput(array $input,int $actor): self
    {
        $key=$input['operation_key']??'';
        if(!is_string($key)||!preg_match('/^[a-f0-9]{64}$/D',$key)||$actor<1)throw new PaymentException('Recargá el formulario antes de registrar el cobro.');
        unset($input['operation_key'],$input['csrf_token']);
        return new self($key,hash('sha256',json_encode(self::canonical($input),JSON_THROW_ON_ERROR)),$actor);
    }
    private static function canonical(mixed $value): mixed
    {
        if(!is_array($value))return $value;
        ksort($value);foreach($value as &$item)$item=self::canonical($item);unset($item);return $value;
    }
    public function existing(PDO $db,bool $lock=false): ?int
    {
        $s=$db->prepare('SELECT * FROM module_operation WHERE operationKey=?'.($lock?' FOR UPDATE':''));$s->execute([$this->key]);$row=$s->fetch(PDO::FETCH_ASSOC);
        if(!$row)return null;
        if((int)$row['actorUserId']!==$this->actor||$row['operationType']!=='payments.register'||!hash_equals($row['payloadHash'],$this->hash))throw new PaymentException('Este formulario ya se utilizó con otros datos. Recargá la página.');
        return $row['resultJson']!==null?(int)json_decode($row['resultJson'],true,512,JSON_THROW_ON_ERROR):null;
    }
    public function start(PDO $db): ?int
    {
        if(!$db->inTransaction())throw new \LogicException('PaymentRequest necesita la transacción del cobro.');
        $db->prepare("INSERT INTO module_operation(operationKey,actorUserId,operationType,payloadHash) VALUES(?,?,'payments.register',?) ON DUPLICATE KEY UPDATE operationKey=VALUES(operationKey)")
            ->execute([$this->key,$this->actor,$this->hash]);
        return $this->existing($db,true);
    }
    public function finish(PDO $db,int $payment): void
    {
        $db->prepare('UPDATE module_operation SET resultJson=?,completedAt=NOW() WHERE operationKey=?')->execute([json_encode($payment,JSON_THROW_ON_ERROR),$this->key]);
    }
}
