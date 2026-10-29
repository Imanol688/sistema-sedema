<?php
declare(strict_types=1);
namespace Sedema\Payments;

use PDO;
use Throwable;

final class MercadoPagoQrService
{
    public function __construct(private readonly PaymentRepository $repo,private readonly MercadoPagoQrClient $client=new MercadoPagoQrClient()){}

    public static function installed(PDO $db): bool
    {
        $q=$db->query("SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='mp_qr_order'");
        return (int)$q->fetchColumn()===1;
    }

    public static function pending(PDO $db,int $orderId): ?int
    {
        if(!self::installed($db))return null;
        $q=$db->prepare("SELECT p.idPago FROM pago p INNER JOIN mp_qr_order m ON m.idPago=p.idPago WHERE p.idPedido=? AND p.estadoPago='PENDIENTE' LIMIT 1");
        $q->execute([$orderId]);return ($id=$q->fetchColumn())?(int)$id:null;
    }

    public function order(int $paymentId): ?array
    {
        if(!self::installed($this->repo->db()))return null;
        $q=$this->repo->db()->prepare('SELECT * FROM mp_qr_order WHERE idPago=?');$q->execute([$paymentId]);return $q->fetch()?:null;
    }

    public function register(int $orderId,int $actor,float $base,float $amount,string $adjType,float $pct,float $adj,string $notes,?PaymentRequest $request=null): int
    {
        MercadoPagoQrClient::validateAmount($amount);
        $config=$this->client->configuration();$db=$this->repo->db();
        if(!self::installed($db))throw new PaymentException('Ejecutá primero database/014_mercadopago_qr.sql en MySQL Workbench.');
        $key=MercadoPagoQrClient::uuid();$reference='SEDEMA'.str_replace('-','',$key);
        $value=number_format($amount,2,'.','');
        $payload=['type'=>'qr','external_reference'=>$reference,'total_amount'=>$value,
            'description'=>'Pedido SEDEMA #'.$orderId,'expiration_time'=>'PT15M',
            'config'=>['qr'=>['external_pos_id'=>$config['pos'],'mode'=>'static']],
            'transactions'=>['payments'=>[['amount'=>$value]]]];
        $db->beginTransaction();
        try{
            if ($request && ($existing=$request->start($db))!==null) { $db->commit(); return $existing; }
            // La caja y el pedido se reservan antes de enviar una orden remota.
            $slot=$config['seller'].':'.$config['pos'];
            $db->prepare('INSERT IGNORE INTO mp_qr_pos_lock(posExternalId) VALUES(?)')->execute([$slot]);
            $q=$db->prepare('SELECT activePaymentId FROM mp_qr_pos_lock WHERE posExternalId=? FOR UPDATE');$q->execute([$slot]);
            $active=(int)$q->fetchColumn();
            if($active>0)throw new PaymentException('La caja tiene un QR pendiente: cobro #'.$active.'. Verificalo o cancelalo antes de crear otro.');
            $q=$db->prepare('SELECT idPedido FROM pedido WHERE idPedido=? FOR UPDATE');$q->execute([$orderId]);
            $o=$this->repo->order($orderId);
            if(!$o||in_array((string)$o['estado'],['CANCELADO','FACTURADO'],true))throw new PaymentException('El pedido no admite nuevos cobros QR.');
            $q=$db->prepare("SELECT COUNT(*) FROM pago WHERE idPedido=? AND estadoPago='PENDIENTE'");$q->execute([$orderId]);
            if((int)$q->fetchColumn()>0)throw new PaymentException('El pedido ya tiene un cobro pendiente. Resolvelo antes de enviar el QR.');
            if($base<=0||$base>round((float)$o['total']-(float)$o['pagado'],2)+0.004)throw new PaymentException('El saldo cambió. Volvé a abrir el pedido.');
            $id=$this->repo->insertPayment($orderId,$actor,$base,$amount,'MERCADO_PAGO_QR','PENDIENTE','PAGO',null,$notes);
            if($pct>0)$this->repo->insertAdjustment($id,$adjType,$pct,$adj);
            $db->prepare('INSERT INTO mp_qr_order(idPago,externalReference,idempotencyKey,posExternalId,sellerId,environment,amount,requestJson) VALUES(?,?,?,?,?,?,?,?)')
                ->execute([$id,$reference,$key,$config['pos'],$config['seller'],$config['environment'],$amount,json_encode($payload,JSON_THROW_ON_ERROR)]);
            $db->prepare('UPDATE mp_qr_pos_lock SET activePaymentId=? WHERE posExternalId=?')->execute([$id,$slot]);
            $this->repo->audit($id,$orderId,$actor,'MP_QR_RESERVED',$reference);
            if ($request) $request->finish($db,$id);
            $db->commit();
        }catch(Throwable $e){if($db->inTransaction())$db->rollBack();throw $e;}
        // Un timeout no borra la reserva ni cambia la clave de idempotencia.
        try{$this->verify($id,$actor);}catch(PaymentException $e){$this->recordError($id,$e->getMessage());}
        return $id;
    }

    public function verify(int $id,int $actor,?array $notificationOrder=null): string
    {
        $row=$this->order($id);if(!$row)throw new PaymentException('No existe una solicitud QR para este cobro.');
        if(($row['status']??'')==='validation_rejected'&&empty($row['externalOrderId'])){
            $closed=$this->repo->payment($id);
            if($closed&&$closed['estadoPago']==='RECHAZADO')return 'RECHAZADO';
        }
        $config=$this->client->configuration();
        if($config['seller']!==(string)$row['sellerId']||$config['environment']!==(string)$row['environment'])throw new PaymentException('Este cobro pertenece a otra cuenta o entorno de Mercado Pago.');
        // Un bloqueo de aplicación serializa las consultas y notificaciones de la misma operación.
        $db=$this->repo->db();$lock='sedema_mp_'.substr(hash('sha256',$id.':'.$row['externalReference']),0,40);
        $q=$db->prepare('SELECT GET_LOCK(?,1)');$q->execute([$lock]);
        if((int)$q->fetchColumn()!==1)throw new PaymentException('Este cobro ya está siendo verificado. Intentá nuevamente en unos segundos.');
        try{
            $row=$this->order($id);
            if($notificationOrder!==null){
                $this->validateRemote($row,$notificationOrder);
                if(empty($row['externalOrderId'])){
                    $db->prepare('UPDATE mp_qr_order SET externalOrderId=? WHERE idPago=? AND externalOrderId IS NULL')->execute([(string)$notificationOrder['id'],$id]);
                    $db->prepare('UPDATE pago SET transaccionExternaID=? WHERE idPago=?')->execute([(string)$notificationOrder['id'],$id]);
                    $row=$this->order($id);
                }
            }
            // Evitar reenvíos tardíos cuando se desconoce el ID remoto: reconciliar antes.
            if(empty($row['externalOrderId'])&&time()-strtotime((string)$row['createdAt'])>900){
                throw new PaymentException('La solicitud quedó sin ID confirmado hace más de 15 minutos. Revisá la orden en Mercado Pago y las notificaciones con el responsable antes de reintentar. La reserva se conserva para evitar duplicados.');
            }
            $remote=empty($row['externalOrderId'])
                ?$this->client->create(json_decode((string)$row['requestJson'],true,512,JSON_THROW_ON_ERROR),(string)$row['idempotencyKey'])
                :$this->client->get((string)$row['externalOrderId']);
            $this->validateRemote($row,$remote);
            // Siempre obtener el estado vigente; nunca acreditar usando la respuesta de un POST o un webhook.
            if(empty($row['externalOrderId'])){
                $db->prepare('UPDATE mp_qr_order SET externalOrderId=? WHERE idPago=? AND externalOrderId IS NULL')->execute([(string)$remote['id'],$id]);
                $db->prepare('UPDATE pago SET transaccionExternaID=? WHERE idPago=?')->execute([(string)$remote['id'],$id]);
                $remote=$this->client->get((string)$remote['id']);
                $this->validateRemote($this->order($id),$remote);
            }
            return $this->apply($id,$remote,$actor);
        }catch(PaymentException $e){
            // Una respuesta fallida nunca acredita ni libera una reserva incierta.
            // La recuperación reutiliza la misma clave; nunca crea otro cobro local.
            $this->recordError($id,$e->getMessage());throw $e;
        }
        finally{$db->prepare('SELECT RELEASE_LOCK(?)')->execute([$lock]);}
    }

    public function cancel(int $id,int $actor): string
    {
        $state=$this->verify($id,$actor);
        if($state!=='PENDIENTE')return $state;
        $row=$this->order($id);
        $this->client->cancel((string)$row['externalOrderId'],substr(hash('sha256','cancel:'.$row['idempotencyKey']),0,36));
        return $this->verify($id,$actor);
    }

    /** Se comparan cuenta, caja, moneda, importe y referencia local antes de acreditar. */
    public function validateRemote(array $row,array $remote): void
    {
        if((string)($remote['type']??'')!=='qr'
            ||(string)($remote['external_reference']??'')!==(string)$row['externalReference']
            ||(string)($remote['user_id']??'')!==(string)$row['sellerId']
            ||(string)($remote['currency']??'')!=='ARS'
            ||(string)($remote['config']['qr']['external_pos_id']??'')!==(string)$row['posExternalId']
            ||empty($remote['id'])
            ||(!empty($row['externalOrderId'])&&(string)$remote['id']!==(string)$row['externalOrderId'])
            ||!isset($remote['total_amount'])||abs((float)$remote['total_amount']-(float)$row['amount'])>0.004){
            throw new PaymentException('La orden de Mercado Pago no coincide con el cobro local. No se acreditó dinero.');
        }
        if(isset($remote['live_mode'])&&(bool)$remote['live_mode']!==($row['environment']==='production'))throw new PaymentException('El entorno de Mercado Pago no coincide con la operación.');
    }

    public function apply(int $id,array $remote,int $actor): string
    {
        $row=$this->order($id);if(!$row)throw new PaymentException('Cobro QR inexistente.');
        $this->validateRemote($row,$remote);
        $status=(string)($remote['status']??'');$detail=(string)($remote['status_detail']??'');
        $payments=$remote['transactions']['payments']??[];
        $approved=$status==='processed'&&count($payments)===1&&(string)($payments[0]['status']??'')==='processed'
            &&(string)($payments[0]['status_detail']??'')==='accredited';
        $refund=$status==='refunded'||$detail==='partially_refunded';
        $terminal=in_array($status,['canceled','expired','failed'],true);
        $net=(float)$row['amount'];
        if($approved||$refund){
            if(count($payments)!==1)throw new PaymentException('La orden no tiene una única transacción verificable.');
            $tx=$payments[0];
            if(!isset($tx['paid_amount'])||abs((float)$tx['paid_amount']-(float)$row['amount'])>0.004)throw new PaymentException('El importe acreditado no coincide. Se requiere revisión del cobro.');
            $returned=(float)($tx['refunded_amount']??0);
            if($refund&&$returned<=0&&$status==='refunded')$returned=(float)$row['amount'];
            if($returned<0||$returned>(float)$row['amount']+0.004)throw new PaymentException('Importe de devolución inconsistente.');
            $net=round((float)$row['amount']-$returned,2);
            if($refund&&$detail==='partially_refunded'&&$returned<=0)throw new PaymentException('Devolución parcial sin importe confirmado. Revisá el cobro.');
        }
        $payment=$this->repo->payment($id);$db=$this->repo->db();$db->beginTransaction();
        try{
            $q=$db->prepare('SELECT idPedido FROM pedido WHERE idPedido=? FOR UPDATE');$q->execute([(int)$payment['idPedido']]);
            $q=$db->prepare('SELECT idPago FROM pago WHERE idPago=? FOR UPDATE');$q->execute([$id]);
            $payment=$this->repo->payment($id);$before=(string)$payment['estadoPago'];$beforeAmount=(float)$payment['importe'];
            if($approved||$refund){
                $new=$net>0.004?'APROBADO':'RECHAZADO';
                $db->prepare('UPDATE pago SET estadoPago=?,importe=? WHERE idPago=?')->execute([$new,$net,$id]);
                if($net>0.004&&!empty($payment['adjustmentType'])&&empty($payment['adjustmentAppliedAt'])){
                    $this->repo->applyAdjustmentToOrder($id,(int)$payment['idPedido'],(string)$payment['adjustmentType'],(float)$payment['adjustmentAmount']);
                }
                $this->repo->creditAccountPayment($id);
                if($before!==$new||abs($beforeAmount-$net)>0.004)$this->audit($id,(int)$payment['idPedido'],$actor,$refund?'MP_QR_REFUNDED':'MP_QR_APPROVED','Orden '.$remote['id'].'; neto '.$net);
            }elseif($terminal&&$before==='PENDIENTE'){
                $this->repo->markPaymentRejected($id);
                $this->audit($id,(int)$payment['idPedido'],$actor,'MP_QR_CLOSED',$status);
            }
            $db->prepare('UPDATE mp_qr_order SET status=?,statusDetail=?,responseJson=?,lastError=NULL,checkedAt=NOW() WHERE idPago=?')
                ->execute([$status,$detail,json_encode($remote,JSON_THROW_ON_ERROR),$id]);
            if($approved||$refund||$terminal)$db->prepare('UPDATE mp_qr_pos_lock SET activePaymentId=NULL WHERE activePaymentId=?')->execute([$id]);
            $result=$this->repo->payment($id);$db->commit();return (string)$result['estadoPago'];
        }catch(Throwable $e){if($db->inTransaction())$db->rollBack();throw $e;}
    }

    private function audit(int $id,int $orderId,int $actor,string $event,string $detail): void
    {
        $this->repo->db()->prepare('INSERT INTO payment_audit(idPago,idPedido,idUsuario,eventType,detail) VALUES(?,?,?,?,?)')
            ->execute([$id,$orderId,$actor>0?$actor:null,$event,($actor>0?'':'Verificación automática Mercado Pago; ').$detail]);
    }

    private function recordError(int $id,string $message): void
    {
        preg_match('/^.{0,250}/us',$message,$parts);
        $this->repo->db()->prepare('UPDATE mp_qr_order SET lastError=? WHERE idPago=?')->execute([$parts[0]??'Error al procesar el cobro.',$id]);
    }
}
