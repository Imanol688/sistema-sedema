<?php
declare(strict_types=1);
namespace Sedema\Payments;

use PDO;

final class PaymentRepository
{
    public function __construct(private readonly PDO $db) {}
    public function db(): PDO { return $this->db; }

    /** @return list<array<string,mixed>> */
    public function orders(string $search='', string $status='pending'): array
    {
        $where=["pe.estado <> 'CANCELADO'"];$params=[];
        if($search!==''){
            $like='%'.$search.'%';
            $where[]='(CAST(pe.idPedido AS CHAR) LIKE ? OR c.nombre LIKE ? OR c.apellido LIKE ? OR c.cuitDNI LIKE ?)';
            array_push($params,$like,$like,$like,$like);
        }
        $having='';
        if($status==='pending')$having=' HAVING saldo > 0.004';
        elseif($status==='paid')$having=' HAVING saldo <= 0.004';
        $sql="SELECT pe.idPedido,pe.fecha,pe.estado,pe.total,pe.modalidadOperativa,c.idCliente,c.nombre,c.apellido,c.cuitDNI,c.tipoCliente,
            COALESCE((SELECT SUM(pg.importe) FROM pago pg WHERE pg.idPedido=pe.idPedido AND pg.estadoPago='APROBADO'),0) pagado,
            GREATEST(pe.total-COALESCE((SELECT SUM(pg2.importe) FROM pago pg2 WHERE pg2.idPedido=pe.idPedido AND pg2.estadoPago='APROBADO'),0),0) saldo
            FROM pedido pe INNER JOIN cliente c ON c.idCliente=pe.idCliente
            WHERE ".implode(' AND ',$where).$having." ORDER BY pe.fecha DESC,pe.idPedido DESC LIMIT 250";
        $st=$this->db->prepare($sql);$st->execute($params);return $st->fetchAll();
    }

    /** @return array<string,mixed>|null */
    public function order(int $id): ?array
    {
        $st=$this->db->prepare("SELECT pe.*,c.nombre,c.apellido,c.cuitDNI,c.tipoCliente,c.razonSocial,c.direccion,c.localidad,c.telefono,
            COALESCE((SELECT SUM(pg.importe) FROM pago pg WHERE pg.idPedido=pe.idPedido AND pg.estadoPago='APROBADO'),0) pagado
            FROM pedido pe INNER JOIN cliente c ON c.idCliente=pe.idCliente WHERE pe.idPedido=? LIMIT 1");
        $st->execute([$id]);$row=$st->fetch();return $row?:null;
    }

    /** @return list<array<string,mixed>> */
    public function payments(int $orderId): array
    {
        $st=$this->db->prepare("SELECT p.*,u.username actorUsername,a.tipoAjuste adjustmentType,a.porcentaje adjustmentPercent,a.montoCalculado adjustmentAmount,a.appliedAt adjustmentAppliedAt,
            g.provider gatewayProvider,g.externalId,g.status gatewayStatus,g.qrPayload,g.qrImageUrl,g.lastCheckedAt,
            ca.importeRecibido cashReceived,ca.vuelto cashChange,
            ch.banco checkBank,ch.numeroCheque checkNumber,ch.titular checkHolder,ch.fechaEmision checkIssueDate,ch.fechaVencimiento checkDueDate
            FROM pago p LEFT JOIN usuario u ON u.idUsuario=p.idUsuario
            LEFT JOIN payment_adjustment a ON a.idPago=p.idPago
            LEFT JOIN payment_gateway_request g ON g.idPago=p.idPago
            LEFT JOIN payment_cash_detail ca ON ca.idPago=p.idPago
            LEFT JOIN payment_check_detail ch ON ch.idPago=p.idPago
            WHERE p.idPedido=? ORDER BY p.fechaPago DESC,p.idPago DESC");
        $st->execute([$orderId]);return $st->fetchAll();
    }

    /** @return array<string,mixed>|null */
    public function payment(int $id): ?array
    {
        $st=$this->db->prepare("SELECT p.*,pe.total orderTotal,c.idCliente,c.nombre,c.apellido,c.cuitDNI,c.tipoCliente,a.tipoAjuste adjustmentType,a.porcentaje adjustmentPercent,a.montoCalculado adjustmentAmount,a.appliedAt adjustmentAppliedAt,
            g.provider gatewayProvider,g.externalId,g.status gatewayStatus,g.qrPayload,g.qrImageUrl,g.responseJson,g.lastCheckedAt,
            ca.importeRecibido cashReceived,ca.vuelto cashChange,
            ch.banco checkBank,ch.numeroCheque checkNumber,ch.titular checkHolder,ch.fechaEmision checkIssueDate,ch.fechaVencimiento checkDueDate,ch.observaciones checkNotes
            FROM pago p INNER JOIN pedido pe ON pe.idPedido=p.idPedido INNER JOIN cliente c ON c.idCliente=pe.idCliente
            LEFT JOIN payment_adjustment a ON a.idPago=p.idPago
            LEFT JOIN payment_gateway_request g ON g.idPago=p.idPago
            LEFT JOIN payment_cash_detail ca ON ca.idPago=p.idPago
            LEFT JOIN payment_check_detail ch ON ch.idPago=p.idPago
            WHERE p.idPago=? LIMIT 1");
        $st->execute([$id]);$row=$st->fetch();return $row?:null;
    }

    public function currentAccountBalance(int $clientId): float
    {
        $st=$this->db->prepare("SELECT COALESCE(SUM(CASE WHEN movementType='DEBITO' THEN amount ELSE -amount END),0) FROM current_account_movement WHERE idCliente=?");
        $st->execute([$clientId]);return (float)$st->fetchColumn();
    }

    /** @return list<array<string,mixed>> */
    public function currentAccountMovements(int $clientId): array
    {
        $st=$this->db->prepare("SELECT m.*,p.estadoPago,p.medioPago FROM current_account_movement m LEFT JOIN pago p ON p.idPago=m.idPago WHERE m.idCliente=? ORDER BY m.createdAt DESC,m.idMovement DESC LIMIT 200");
        $st->execute([$clientId]);return $st->fetchAll();
    }

    public function insertPayment(int $orderId,int $userId,float $baseAmount,float $finalAmount,?string $method,string $status,string $operationType='PAGO',?string $externalId=null,string $notes=''): int
    {
        $st=$this->db->prepare("INSERT INTO pago(idPedido,idUsuario,tipoOperacion,fechaPago,importe,importeBase,medioPago,estadoPago,transaccionExternaID,observaciones) VALUES(?,?,?,NOW(),?,?,?,?,?,?)");
        $st->execute([$orderId,$userId,$operationType,$finalAmount,$baseAmount,$method,$status,$externalId,$notes?:null]);
        return (int)$this->db->lastInsertId();
    }

    public function insertAdjustment(int $paymentId,string $type,float $percent,float $amount): void
    {
        $st=$this->db->prepare("INSERT INTO payment_adjustment(idPago,tipoAjuste,porcentaje,montoCalculado) VALUES(?,?,?,?)");$st->execute([$paymentId,$type,$percent,$amount]);
    }

    public function insertCashDetail(int $paymentId,float $received,float $change): void
    {
        $st=$this->db->prepare("INSERT INTO payment_cash_detail(idPago,importeRecibido,vuelto) VALUES(?,?,?)");$st->execute([$paymentId,$received,$change]);
    }

    public function insertCheckDetail(int $paymentId,string $bank,string $number,string $holder,string $issueDate,string $dueDate,string $notes=''): void
    {
        $st=$this->db->prepare("INSERT INTO payment_check_detail(idPago,banco,numeroCheque,titular,fechaEmision,fechaVencimiento,observaciones) VALUES(?,?,?,?,?,?,?)");
        $st->execute([$paymentId,$bank,$number,$holder,$issueDate,$dueDate,$notes?:null]);
    }

    public function insertGateway(int $paymentId,string $method,string $provider,string $externalId,string $status,?string $qrPayload,?string $qrImageUrl,string $responseJson): void
    {
        $st=$this->db->prepare("INSERT INTO payment_gateway_request(idPago,provider,paymentMethod,externalId,status,qrPayload,qrImageUrl,responseJson,lastCheckedAt) VALUES(?,?,?,?,?,?,?,?,NOW())");
        $st->execute([$paymentId,$provider,$method,$externalId,$status,$qrPayload,$qrImageUrl,$responseJson]);
    }

    public function updateGateway(int $paymentId,string $status,string $responseJson): void
    {
        $st=$this->db->prepare("UPDATE payment_gateway_request SET status=?,responseJson=?,lastCheckedAt=NOW(),updatedAt=NOW() WHERE idPago=?");$st->execute([$status,$responseJson,$paymentId]);
    }

    public function markPaymentApproved(int $paymentId): void { $this->db->prepare("UPDATE pago SET estadoPago='APROBADO' WHERE idPago=?")->execute([$paymentId]); }
    public function markPaymentRejected(int $paymentId): void { $this->db->prepare("UPDATE pago SET estadoPago='RECHAZADO' WHERE idPago=?")->execute([$paymentId]); }

    public function applyAdjustmentToOrder(int $paymentId,int $orderId,string $type,float $amount): void
    {
        $signed=$type==='DESCUENTO' ? -$amount : $amount;
        $this->db->prepare("UPDATE pedido SET total=GREATEST(total+?,0),totalAjustes=totalAjustes+? WHERE idPedido=?")->execute([$signed,$signed,$orderId]);
        $this->db->prepare("UPDATE payment_adjustment SET appliedAt=NOW() WHERE idPago=? AND appliedAt IS NULL")->execute([$paymentId]);
        $label=$type==='DESCUENTO'?'COBRANZA_DESCUENTO':'COBRANZA_RECARGO';
        $desc=$type==='DESCUENTO'?'Descuento aplicado en cobranza':'Recargo aplicado en cobranza';
        $st=$this->db->prepare("INSERT INTO ajuste(idPedido,tipoAjuste,tipoTarjeta,descripcion,porcentaje,montoCalculado) SELECT ?,?,NULL,?,porcentaje,montoCalculado FROM payment_adjustment WHERE idPago=?");
        $st->execute([$orderId,$label,$desc,$paymentId]);
    }

    public function addCurrentAccountDebit(int $clientId,int $orderId,int $paymentId,float $amount): void
    {
        $st=$this->db->prepare("INSERT INTO current_account_movement(idCliente,idPedido,idPago,movementType,amount,description) VALUES(?,?,?,'DEBITO',?,'Imputación de pedido a cuenta corriente')");
        $st->execute([$clientId,$orderId,$paymentId,$amount]);
    }

    public function audit(int $paymentId,int $orderId,int $userId,string $event,string $detail=''): void
    {
        $st=$this->db->prepare("INSERT INTO payment_audit(idPago,idPedido,idUsuario,eventType,detail) VALUES(?,?,?,?,?)");
        $st->execute([$paymentId?:null,$orderId,$userId,$event,$detail?:null]);
    }
}
