<?php
declare(strict_types=1);
namespace Sedema\Payments;

use Throwable;

final class PaymentService
{
    public const METHODS=['EFECTIVO','TARJETA','CHEQUE','TRANSFERENCIA'];
    public const OPERATIONS=['PAGO','CUENTA_CORRIENTE'];

    public function __construct(private readonly PaymentRepository $repo,private readonly PaymentGateway $gateway){}

    /** @param array<string,mixed> $input */
    public function register(array $input,int $actor): int
    {
        $orderId=(int)($input['idPedido']??0);
        $order=$this->repo->order($orderId);
        if(!$order)throw new PaymentException('El pedido no existe.');
        if((string)$order['estado']==='CANCELADO')throw new PaymentException('No se puede cobrar un pedido cancelado.');

        $paid=(float)$order['pagado'];
        $balance=max(0,round((float)$order['total']-$paid,2));
        if($balance<=0.004)throw new PaymentException('El pedido ya está completamente cancelado.');

        $operation=strtoupper(trim((string)($input['tipoOperacion']??'PAGO')));
        if(!in_array($operation,self::OPERATIONS,true))throw new PaymentException('Seleccioná una operación válida.');

        $requested=$this->money($input['importeCobro']??$balance);
        if($requested<=0)throw new PaymentException('Ingresá un importe mayor a cero.');
        if($requested>$balance+0.004)throw new PaymentException('El importe no puede superar el saldo pendiente del pedido.');

        $adjType=strtoupper(trim((string)($input['adjustmentType']??'NINGUNO')));
        if(!in_array($adjType,['NINGUNO','DESCUENTO','RECARGO'],true))$adjType='NINGUNO';
        $pct=max(0,min(100,$this->money($input['adjustmentPercent']??0)));
        if($adjType==='NINGUNO')$pct=0;
        $adj=round($requested*$pct/100,2);
        $finalAmount=$adjType==='DESCUENTO'?max(0,round($requested-$adj,2)):round($requested+$adj,2);
        if($finalAmount<=0)throw new PaymentException('El importe final del cobro debe ser mayor a cero.');

        $method=null;
        $status='APROBADO';
        $cashReceived=null;
        $cashChange=null;
        $check=null;

        if($operation==='PAGO'){
            $method=strtoupper(trim((string)($input['medioPago']??'')));
            if(!in_array($method,self::METHODS,true))throw new PaymentException('Seleccioná un medio de pago válido.');

            if($method==='EFECTIVO'){
                $cashReceived=$this->money($input['importeRecibido']??0);
                if($cashReceived+0.004<$finalAmount)throw new PaymentException('El importe recibido no alcanza para cubrir el cobro.');
                $cashChange=round($cashReceived-$finalAmount,2);
            }elseif($method==='CHEQUE'){
                $bank=trim((string)($input['chequeBanco']??''));
                $number=trim((string)($input['chequeNumero']??''));
                $holder=trim((string)($input['chequeTitular']??''));
                $issue=trim((string)($input['chequeFechaEmision']??''));
                $due=trim((string)($input['chequeFechaVencimiento']??''));
                if($bank===''||$number===''||$holder===''||!$this->validDate($issue)||!$this->validDate($due))throw new PaymentException('Completá todos los datos obligatorios del cheque.');
                if($due<$issue)throw new PaymentException('La fecha de vencimiento del cheque no puede ser anterior a su emisión.');
                $check=['bank'=>$bank,'number'=>$number,'holder'=>$holder,'issue'=>$issue,'due'=>$due,'notes'=>trim((string)($input['chequeObservaciones']??''))];
                $status='PENDIENTE';
            }elseif(in_array($method,['TARJETA','TRANSFERENCIA'],true)){
                $status='PENDIENTE';
            }
        }

        $db=$this->repo->db();
        $db->beginTransaction();
        try{
            $notes=trim((string)($input['observaciones']??''));
            $paymentId=$this->repo->insertPayment($orderId,$actor,$requested,$finalAmount,$method,$status,$operation,null,$notes);
            if($pct>0)$this->repo->insertAdjustment($paymentId,$adjType,$pct,$adj);

            if($operation==='CUENTA_CORRIENTE'){
                if($pct>0)$this->repo->applyAdjustmentToOrder($paymentId,$orderId,$adjType,$adj);
                $this->repo->addCurrentAccountDebit((int)$order['idCliente'],$orderId,$paymentId,$finalAmount);
                $this->repo->audit($paymentId,$orderId,$actor,'CURRENT_ACCOUNT_CHARGE','Cuenta corriente $'.number_format($finalAmount,2,'.',''));
            }elseif($method==='EFECTIVO'){
                $this->repo->insertCashDetail($paymentId,(float)$cashReceived,(float)$cashChange);
                if($pct>0)$this->repo->applyAdjustmentToOrder($paymentId,$orderId,$adjType,$adj);
                $this->repo->audit($paymentId,$orderId,$actor,'PAYMENT_APPROVED','EFECTIVO $'.number_format($finalAmount,2,'.',''));
            }elseif($method==='CHEQUE'){
                $this->repo->insertCheckDetail($paymentId,$check['bank'],$check['number'],$check['holder'],$check['issue'],$check['due'],$check['notes']);
                $this->repo->audit($paymentId,$orderId,$actor,'CHECK_REGISTERED','CHEQUE '.$check['number'].' $'.number_format($finalAmount,2,'.',''));
            }elseif(in_array((string)$method,['TARJETA','TRANSFERENCIA'],true)){
                $payer=(string)($order['email']??'');
                $gateway=$this->gateway->create($finalAmount,'Pedido SEDEMA #'.$orderId,$payer,(string)$method);
                if($gateway['externalId']==='')throw new PaymentException('La pasarela no devolvió un identificador de operación.');
                $this->repo->db()->prepare('UPDATE pago SET transaccionExternaID=? WHERE idPago=?')->execute([$gateway['externalId'],$paymentId]);
                $this->repo->insertGateway($paymentId,(string)$method,$gateway['provider'],$gateway['externalId'],$gateway['status'],$gateway['qrPayload'],$gateway['qrImageUrl'],json_encode($gateway['response'],JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR));
                $this->repo->audit($paymentId,$orderId,$actor,'GATEWAY_REQUEST_CREATED',(string)$method.' '.$gateway['externalId']);
                if($gateway['status']==='APPROVED')$this->approve($paymentId,$actor,false);
            }

            $db->commit();
            return $paymentId;
        }catch(Throwable $e){
            if($db->inTransaction())$db->rollBack();
            if($e instanceof PaymentException)throw $e;
            throw new PaymentException('No se pudo registrar el cobro.');
        }
    }

    public function verify(int $paymentId,int $actor): string
    {
        $payment=$this->repo->payment($paymentId);
        if(!$payment)throw new PaymentException('El cobro no existe.');
        if((string)$payment['estadoPago']!=='PENDIENTE')return (string)$payment['estadoPago'];
        if(!in_array((string)$payment['medioPago'],['TARJETA','TRANSFERENCIA'],true))throw new PaymentException('Este medio de pago no se verifica mediante pasarela.');
        $external=trim((string)($payment['externalId']??$payment['transaccionExternaID']??''));
        if($external==='')throw new PaymentException('El cobro no posee referencia externa.');
        $result=$this->gateway->status($external);
        $status=strtoupper($result['status']);
        $this->repo->updateGateway($paymentId,$status,json_encode($result['response'],JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR));
        if($status==='APPROVED'){
            $this->approve($paymentId,$actor);
            return 'APROBADO';
        }
        if(in_array($status,['REJECTED','CANCELLED','FAILED'],true)){
            $this->repo->markPaymentRejected($paymentId);
            $this->repo->audit($paymentId,(int)$payment['idPedido'],$actor,'PAYMENT_REJECTED',$status);
            return 'RECHAZADO';
        }
        return 'PENDIENTE';
    }

    public function approveCheck(int $paymentId,int $actor): void
    {
        $payment=$this->repo->payment($paymentId);
        if(!$payment)throw new PaymentException('El cobro no existe.');
        if((string)$payment['medioPago']!=='CHEQUE')throw new PaymentException('La operación no corresponde a un cheque.');
        if((string)$payment['estadoPago']!=='PENDIENTE')throw new PaymentException('El cheque ya fue procesado.');
        $this->approve($paymentId,$actor);
        $this->repo->audit($paymentId,(int)$payment['idPedido'],$actor,'CHECK_APPROVED',(string)($payment['checkNumber']??''));
    }

    public function rejectCheck(int $paymentId,int $actor): void
    {
        $payment=$this->repo->payment($paymentId);
        if(!$payment)throw new PaymentException('El cobro no existe.');
        if((string)$payment['medioPago']!=='CHEQUE')throw new PaymentException('La operación no corresponde a un cheque.');
        if((string)$payment['estadoPago']!=='PENDIENTE')throw new PaymentException('El cheque ya fue procesado.');
        $this->repo->markPaymentRejected($paymentId);
        $this->repo->audit($paymentId,(int)$payment['idPedido'],$actor,'CHECK_REJECTED',(string)($payment['checkNumber']??''));
    }

    public function mockApprove(int $paymentId,int $actor): void
    {
        if($this->gateway->mode()!=='mock')throw new PaymentException('La aprobación manual solo está disponible en modo simulación.');
        $payment=$this->repo->payment($paymentId);
        if(!$payment)throw new PaymentException('El cobro no existe.');
        if(!in_array((string)$payment['medioPago'],['TARJETA','TRANSFERENCIA'],true))throw new PaymentException('Solo tarjeta y transferencia utilizan la simulación de pasarela.');
        if((string)$payment['estadoPago']!=='PENDIENTE')throw new PaymentException('El cobro ya fue procesado.');
        $this->repo->updateGateway($paymentId,'APPROVED',json_encode(['mode'=>'mock','status'=>'APPROVED'],JSON_THROW_ON_ERROR));
        $this->approve($paymentId,$actor);
    }

    private function approve(int $paymentId,int $actor,bool $audit=true): void
    {
        $payment=$this->repo->payment($paymentId);
        if(!$payment)throw new PaymentException('El cobro no existe.');
        if((string)$payment['estadoPago']==='APROBADO')return;
        $this->repo->markPaymentApproved($paymentId);
        if(!empty($payment['adjustmentType']) && empty($payment['adjustmentAppliedAt'])){
            $this->repo->applyAdjustmentToOrder($paymentId,(int)$payment['idPedido'],(string)$payment['adjustmentType'],(float)$payment['adjustmentAmount']);
        }
        if($audit)$this->repo->audit($paymentId,(int)$payment['idPedido'],$actor,'PAYMENT_APPROVED',$this->operationLabel($payment).' $'.number_format((float)$payment['importe'],2,'.',''));
    }

    private function money(mixed $value): float
    {
        return round((float)str_replace(',','.',trim((string)$value)),2);
    }

    private function validDate(string $date): bool
    {
        $d=\DateTimeImmutable::createFromFormat('!Y-m-d',$date);
        return $d!==false && $d->format('Y-m-d')===$date;
    }

    /** @param array<string,mixed> $payment */
    private function operationLabel(array $payment): string
    {
        if((string)($payment['tipoOperacion']??'PAGO')==='CUENTA_CORRIENTE')return 'CUENTA CORRIENTE';
        return (string)($payment['medioPago']??'PAGO');
    }

    public static function methodLabel(?string $m): string
    {
        return match((string)$m){
            'EFECTIVO'=>'Efectivo',
            'TARJETA'=>'Tarjeta',
            'CHEQUE'=>'Cheque',
            'TRANSFERENCIA'=>'Transferencia',
            'CONTROLADOR'=>'Controlador (histórico)',
            default=>(string)$m,
        };
    }

    public static function paymentLabel(array $p): string
    {
        return (string)($p['tipoOperacion']??'PAGO')==='CUENTA_CORRIENTE'?'Cuenta corriente':self::methodLabel(isset($p['medioPago'])?(string)$p['medioPago']:null);
    }
}
