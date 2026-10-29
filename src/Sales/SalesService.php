<?php
declare(strict_types=1);
namespace Sedema\Sales;

use PDO;
use PDOException;
use Throwable;

final class SalesService
{
    public const MODALITIES=['EN_CORRALON','ENTREGA_DOMICILIO','ACOPIO'];
    public const STATUSES=['PENDIENTE','CONFIRMADO','DESPACHO_PROGRAMADO','FACTURADO','CANCELADO'];
    public function __construct(private readonly SalesRepository $repo) {}

    /** @param array<string,mixed> $input */
    public function createOrder(array $input,int $actorUserId,int $existingOrderId = 0): int
    {
        $clientId=(int)($input['idCliente']??0);$warehouseId=(int)($input['idWarehouse']??0);
        $client=$this->repo->client($clientId); if(!$client || (int)($client['activo']??0)!==1) throw new SalesException('Seleccioná un cliente activo.');
        $modality=(string)($input['modalidadOperativa']??''); if(!in_array($modality,self::MODALITIES,true)) throw new SalesException('Seleccioná una modalidad operativa válida.');
        if($warehouseId<=0 || !array_filter($this->repo->warehouses(), fn($w)=>(int)$w['idWarehouse']===$warehouseId)) throw new SalesException('Seleccioná el depósito de origen.');
        $quantities=is_array($input['quantity']??null)?$input['quantity']:[];$items=[];$subtotal=0.0;
        foreach($quantities as $productIdRaw=>$qtyRaw){$productId=(int)$productIdRaw;$qty=(float)str_replace(',','.',(string)$qtyRaw);if($qty<=0)continue;$product=$this->repo->product($productId,$warehouseId);if(!$product)throw new SalesException('Uno de los productos seleccionados ya no está disponible.');if (!is_finite($qty) || $qty > 99999999999.999 || abs($qty-round($qty,3))>0.00001 || (!(int)$product['allowsDecimals'] && abs($qty-round($qty))>0.00001)) throw new SalesException('Cantidad inválida para la unidad del artículo.');if($qty>(float)$product['quantity'])throw new SalesException('Stock insuficiente para '.$product['name'].'. Disponible: '.$product['quantity'].' '.$product['symbol'].'.');$price=(float)$product['salePrice'];$sub=round($qty*$price,2);$subtotal+=$sub;$items[]=['id'=>$productId,'qty'=>$qty,'price'=>$price,'subtotal'=>$sub,'name'=>$product['name']];}
        if(!$items) throw new SalesException('Agregá al menos un artículo al pedido.');
        $discount=max(0,min(100,(float)str_replace(',','.',(string)($input['discountPercent']??0))));
        $volume=max(0,min(100,(float)str_replace(',','.',(string)($input['volumeDiscountPercent']??0))));
        $surcharge=max(0,min(100,(float)str_replace(',','.',(string)($input['cardSurchargePercent']??0))));
        $adjustments=[];$signed=0.0;
        foreach([['DESCUENTO','Descuento comercial',$discount,-1],['DESCUENTO_VOLUMEN','Descuento por volumen',$volume,-1],['RECARGO_TARJETA','Recargo por financiación con tarjeta',$surcharge,1]] as [$type,$desc,$pct,$sign]){if($pct>0){$amount=round($subtotal*$pct/100,2);$signed+=$sign*$amount;$adjustments[]=[$type,$desc,$pct,$amount,(string)($input['tipoTarjeta']??'')];}}
        $total=max(0,round($subtotal+$signed,2));
        $address=trim((string)($input['direccionEntrega']??'')); if($modality==='ENTREGA_DOMICILIO'&&$address==='')$address=(string)$client['direccion'];
        $delivery=trim((string)($input['fechaEntrega']??''));$delivery=$delivery!==''?str_replace('T',' ',$delivery).(strlen($delivery)<=16?':00':''):null;
        $obs=mb_substr(trim((string)($input['observaciones']??'')),0,2000);
        $db=$this->repo->db();$db->beginTransaction();
        try{
            if ($existingOrderId > 0) {
                $this->repo->lockOrder($existingOrderId);
                $reason = $this->repo->correctionBlockedReason($existingOrderId);
                if ($reason) throw new SalesException($reason);
                if (!hash_equals($this->repo->correctionVersion($existingOrderId), (string) ($input['orderVersion'] ?? ''))) {
                    throw new SalesException('El pedido cambió mientras lo editabas. Volvé a abrirlo antes de guardar.');
                }
                foreach ($this->repo->items($existingOrderId) as $oldItem) {
                    $productId = (int) ($oldItem['idInventoryProduct'] ?? 0);
                    if (($productId <= 0 || (float) ($quantities[$productId] ?? 0) <= 0) && empty($input['confirmRemovedItems'])) {
                        throw new SalesException('Confirmá la eliminación de los artículos que quedaron sin cantidad antes de guardar.');
                    }
                }
                $id = $existingOrderId;
                $s = $db->prepare('UPDATE pedido SET idCliente=?,idWarehouse=?,porcentajeDescuento=?,subtotal=?,totalAjustes=?,total=?,modalidadOperativa=?,direccionEntrega=?,fechaEntrega=?,observaciones=? WHERE idPedido=?');
                $s->execute([$clientId,$warehouseId,$discount+$volume,$subtotal,$signed,$total,$modality,$address?:null,$delivery,$obs?:null,$id]);
                $db->prepare('DELETE FROM ajuste WHERE idPedido=?')->execute([$id]);
                $db->prepare('DELETE FROM detallepedido WHERE idPedido=?')->execute([$id]);
            } else {
            $s=$db->prepare("INSERT INTO pedido(idCliente,idUsuario,idWarehouse,fecha,estado,porcentajeDescuento,subtotal,totalAjustes,total,modalidadOperativa,direccionEntrega,fechaEntrega,observaciones) VALUES(?,?,?,NOW(),'CONFIRMADO',?,?,?,?,?,?,?,?)");
            $s->execute([$clientId,$actorUserId,$warehouseId,$discount+$volume,$subtotal,$signed,$total,$modality,$address?:null,$delivery,$obs?:null]);$id=(int)$db->lastInsertId();
            }
            $di=$db->prepare('INSERT INTO detallepedido(idPedido,idProducto,idInventoryProduct,descripcion,observaciones,cantidadSolicitada,cantidadPendiente,precioUnitario,subtotal) VALUES(?,NULL,?,?,?,?,?,?,?)');
            foreach($items as $it){$di->execute([$id,$it['id'],$it['name'],null,$it['qty'],$it['qty'],$it['price'],$it['subtotal']]);}
            $aj=$db->prepare('INSERT INTO ajuste(idPedido,tipoAjuste,tipoTarjeta,descripcion,porcentaje,montoCalculado) VALUES(?,?,?,?,?,?)');foreach($adjustments as $a){$aj->execute([$id,$a[0],$a[0]==='RECARGO_TARJETA'?($a[4]?:null):null,$a[1],$a[2],$a[3]]);}
            $this->repo->audit($id,$actorUserId,$existingOrderId > 0 ? 'ORDER_UPDATED' : 'ORDER_CREATED',($existingOrderId > 0 ? 'Pedido corregido por $' : 'Pedido creado por $').number_format($total,2,'.',''));
            $db->commit();return $id;
        }catch(Throwable $e){if($db->inTransaction())$db->rollBack();throw $e;}
    }

    public function deleteOrder(int $id, string $version, int $actor): void
    {
        $db = $this->repo->db();
        $db->beginTransaction();
        try {
            $this->repo->lockOrder($id);
            $reason = $this->repo->correctionBlockedReason($id);
            if ($reason) throw new SalesException($reason);
            if (!hash_equals($this->repo->correctionVersion($id), $version)) throw new SalesException('El pedido cambió. Revisalo antes de eliminar.');
            $order = $this->repo->order($id);
            $this->repo->audit($id, $actor, 'ORDER_DELETED', 'Pedido eliminado sin operaciones asociadas. Total $' . number_format((float) $order['total'], 2, '.', ''));
            $db->prepare('DELETE FROM ajuste WHERE idPedido=?')->execute([$id]);
            $db->prepare('DELETE FROM detallepedido WHERE idPedido=?')->execute([$id]);
            $db->prepare('DELETE FROM pedido WHERE idPedido=?')->execute([$id]);
            $db->commit();
        } catch (Throwable $e) {
            if ($db->inTransaction()) $db->rollBack();
            throw $e;
        }
    }

    public function cancel(int $id, int $actor): void
    {
        $db = $this->repo->db();
        $db->beginTransaction();
        try {
            $this->repo->lockOrder($id);
            if ($reason = $this->repo->correctionBlockedReason($id)) throw new SalesException($reason);
            $db->prepare("UPDATE pedido SET estado='CANCELADO' WHERE idPedido=?")->execute([$id]);
            $this->repo->audit($id, $actor, 'ORDER_CANCELLED', 'Pedido cancelado sin operaciones asociadas');
            $db->commit();
        } catch (Throwable $error) {
            if ($db->inTransaction()) $db->rollBack();
            throw $error;
        }
    }

    /** @param array<string,mixed> $input */
    public function issueRemittance(int $id, array $input, int $actor): int
    {
        $order = $this->repo->order($id);
        if (!$order) throw new SalesException('El pedido no existe.');
        $this->requireSettledOrder($id, $order);
        $logisticsRepo = new \Sedema\Logistics\LogisticsRepository($this->repo->db());
        // The durable operation key also protects a retry after all pending items were reserved.
        $request = $input;
        $request['idPedido'] = $id;
        $request['idWarehouse'] = (int) $order['idWarehouse'];
        $request['modalidad'] = (string) $order['modalidadOperativa'] === 'ACOPIO'
            ? (string) ($input['modalidad'] ?? 'EN_CORRALON') : (string) $order['modalidadOperativa'];
        $request['_reserve_available'] = true;
        try {
            $dispatch = (new \Sedema\Logistics\LogisticsService($logisticsRepo))->schedule($request, [], $actor);
        } catch (\Sedema\Logistics\LogisticsException $error) {
            throw new SalesException($error->getMessage(), 0, $error);
        }
        $q = $this->repo->db()->prepare('SELECT idRemito FROM remitodespacho WHERE idDespacho=?');
        $q->execute([$dispatch]);
        return (int) $q->fetchColumn();
    }

    /** @param array<string,mixed> $input */
    public function issueInvoice(int $id, array $input, int $actor): int
    {
        $db = $this->repo->db();
        $db->beginTransaction();
        try {
            $this->repo->lockOrder($id);
            $o = $this->repo->order($id);
            if ((string) $o['estado'] === 'CANCELADO') throw new SalesException('No se puede facturar un pedido cancelado.');
            if ($this->repo->invoiceByOrder($id)) throw new SalesException('El pedido ya posee una factura.');
            $this->requireSettledOrder($id, $o);
            $rem = $this->repo->remittanceByOrder($id);
            if (!$rem || (int)$rem['itemCount']<1 || in_array((string) $rem['despachoEstado'], ['CANCELADO','DEVUELTO'], true)) throw new SalesException('Emití primero el remito de los productos para continuar con la factura.');
            $type = (string) ($input['tipoFactura'] ?? 'B');
            if (!in_array($type, ['A','B','C'], true)) throw new SalesException('Tipo de factura inválido.');
            $pv = (int) ($input['puntoVenta'] ?? 1);
            if ($pv <= 0) throw new SalesException('Ingresá un punto de venta válido.');
            $sign = mb_substr(trim((string) ($input['firmayAclaracion'] ?? '')), 0, 150);
            $discount = 0.0; $surcharge = 0.0;
            foreach ($this->repo->adjustments($id) as $a) {
                if (str_contains((string) $a['tipoAjuste'], 'DESCUENTO')) $discount += (float) $a['montoCalculado'];
                elseif (str_contains((string) $a['tipoAjuste'], 'RECARGO')) $surcharge += (float) $a['montoCalculado'];
            }
            $insert = $db->prepare('INSERT INTO factura(idPedido,nroComprobante,puntoVenta,fechaEmision,tipoFactura,totalFacturado,descuentoAplicado,recargoAplicado,firmayAclaracion,nroEnvio,observaciones) VALUES(?,?,?,NOW(),?,?,?,?,?,?,?)');
            $invoice = 0;
            for ($attempt = 0; $attempt < 20; $attempt++) {
                $number = random_int(10000000, 99999999);
                $exists = $db->prepare('SELECT COUNT(*) FROM factura WHERE nroComprobante=?');
                $exists->execute([$number]);
                if ((int) $exists->fetchColumn() > 0) continue;
                try {
                    $insert->execute([$id,$number,$pv,$type,$o['total'],$discount,$surcharge,$sign?:null,$rem['numeroRemito'],null]);
                    $invoice = (int) $db->lastInsertId();
                    break;
                } catch (PDOException $e) {
                    // A concurrent sale may have chosen the same random number.
                    if ((int) ($e->errorInfo[1] ?? 0) !== 1062) throw $e;
                }
            }
            if ($invoice === 0) throw new SalesException('No se pudo reservar un número único. Intentá nuevamente.');
            $db->prepare("UPDATE pedido SET estado='FACTURADO' WHERE idPedido=?")->execute([$id]);
            $this->repo->audit($id,$actor,'INVOICE_ISSUED','Factura '.$type.' '.$pv.'-'.$number);
            $db->commit();
            return $invoice;
        } catch (Throwable $e) {
            if ($db->inTransaction()) $db->rollBack();
            throw $e;
        }
    }

    private function remittanceNumberExists(string $number): bool
    {
        $s = $this->repo->db()->prepare('SELECT COUNT(*) FROM remitodespacho WHERE numeroRemito=?');
        $s->execute([$number]);
        return (int) $s->fetchColumn() > 0;
    }

    private function requireSettledOrder(int $id, array $order): void
    {
        $paid = $this->repo->settledPayments($id);
        if ($paid <= 0 || $paid + 0.005 < (float) $order['total']) {
            throw new SalesException('Primero completá el pago del pedido con cobros aprobados para emitir el remito y la factura.');
        }
    }

    public static function modalityLabel(string $v): string { return match($v){'EN_CORRALON'=>'Retiro en corralón','ENTREGA_DOMICILIO'=>'Entrega a domicilio','ACOPIO'=>'Acopio',default=>$v}; }
}
