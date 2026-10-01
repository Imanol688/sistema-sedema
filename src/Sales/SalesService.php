<?php
declare(strict_types=1);
namespace Sedema\Sales;

use PDO;
use Throwable;

final class SalesService
{
    public const MODALITIES=['EN_CORRALON','ENTREGA_DOMICILIO','ACOPIO'];
    public const STATUSES=['PENDIENTE','CONFIRMADO','DESPACHO_PROGRAMADO','FACTURADO','CANCELADO'];
    public function __construct(private readonly SalesRepository $repo) {}

    /** @param array<string,mixed> $input */
    public function createOrder(array $input,int $actorUserId): int
    {
        $clientId=(int)($input['idCliente']??0);$warehouseId=(int)($input['idWarehouse']??0);
        $client=$this->repo->client($clientId); if(!$client || (int)($client['activo']??0)!==1) throw new SalesException('Seleccioná un cliente activo.');
        $modality=(string)($input['modalidadOperativa']??''); if(!in_array($modality,self::MODALITIES,true)) throw new SalesException('Seleccioná una modalidad operativa válida.');
        if($warehouseId<=0) throw new SalesException('Seleccioná el depósito de origen.');
        $quantities=is_array($input['quantity']??null)?$input['quantity']:[];$items=[];$subtotal=0.0;
        foreach($quantities as $productIdRaw=>$qtyRaw){$productId=(int)$productIdRaw;$qty=(float)str_replace(',','.',(string)$qtyRaw);if($qty<=0)continue;$product=$this->repo->product($productId,$warehouseId);if(!$product)throw new SalesException('Uno de los productos seleccionados ya no está disponible.');if($qty>(float)$product['quantity'])throw new SalesException('Stock insuficiente para '.$product['name'].'. Disponible: '.$product['quantity'].' '.$product['symbol'].'.');$price=(float)$product['salePrice'];$sub=round($qty*$price,2);$subtotal+=$sub;$items[]=['id'=>$productId,'qty'=>$qty,'price'=>$price,'subtotal'=>$sub,'name'=>$product['name']];}
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
            $s=$db->prepare("INSERT INTO pedido(idCliente,idUsuario,idWarehouse,fecha,estado,porcentajeDescuento,subtotal,totalAjustes,total,modalidadOperativa,direccionEntrega,fechaEntrega,observaciones) VALUES(?,?,?,NOW(),'CONFIRMADO',?,?,?,?,?,?,?,?)");
            $s->execute([$clientId,$actorUserId,$warehouseId,$discount+$volume,$subtotal,$signed,$total,$modality,$address?:null,$delivery,$obs?:null]);$id=(int)$db->lastInsertId();
            $di=$db->prepare('INSERT INTO detallepedido(idPedido,idProducto,idInventoryProduct,descripcion,observaciones,cantidadSolicitada,cantidadPendiente,precioUnitario,subtotal) VALUES(?,NULL,?,?,?,?,?,?,?)');
            foreach($items as $it){$di->execute([$id,$it['id'],$it['name'],null,$it['qty'],$it['qty'],$it['price'],$it['subtotal']]);}
            $aj=$db->prepare('INSERT INTO ajuste(idPedido,tipoAjuste,tipoTarjeta,descripcion,porcentaje,montoCalculado) VALUES(?,?,?,?,?,?)');foreach($adjustments as $a){$aj->execute([$id,$a[0],$a[0]==='RECARGO_TARJETA'?($a[4]?:null):null,$a[1],$a[2],$a[3]]);}
            $this->repo->audit($id,$actorUserId,'ORDER_CREATED','Pedido creado por $'.number_format($total,2,'.',''));
            $db->commit();return $id;
        }catch(Throwable $e){if($db->inTransaction())$db->rollBack();throw $e;}
    }

    public function cancel(int $id,int $actor): void { $o=$this->repo->order($id);if(!$o)throw new SalesException('El pedido no existe.');if(in_array((string)$o['estado'],['FACTURADO','CANCELADO'],true))throw new SalesException('El pedido no puede cancelarse en su estado actual.');$s=$this->repo->db()->prepare("UPDATE pedido SET estado='CANCELADO' WHERE idPedido=?");$s->execute([$id]);$this->repo->audit($id,$actor,'ORDER_CANCELLED','Pedido cancelado'); }

    /** @param array<string,mixed> $input */
    public function issueRemittance(int $id,array $input,int $actor): int
    {
        $o=$this->repo->order($id);if(!$o)throw new SalesException('El pedido no existe.');if((string)$o['estado']==='CANCELADO')throw new SalesException('No se puede emitir un remito para un pedido cancelado.');if($this->repo->remittanceByOrder($id))throw new SalesException('Este pedido ya tiene un remito emitido.');
        $date=trim((string)($input['fechaHoraProgramada']??''));if($date==='')throw new SalesException('Indicá fecha y hora de entrega/retiro.');$date=str_replace('T',' ',$date).(strlen($date)<=16?':00':'');
        $destination=mb_substr(trim((string)($input['destino']??($o['direccionEntrega']??''))),0,255);if($destination==='')$destination='Retiro en corralón';
        $vehicle=(int)($input['idVehiculo']??0);$transport=mb_substr(trim((string)($input['transportista']??'')),0,150);$origin=mb_substr(trim((string)($input['sucursalOrigen']??'SEDEMA S.R.L.')),0,100);
        $warehouse=(int)($o['idWarehouse']??0);if($warehouse<1)throw new SalesException('El pedido no tiene un depósito de origen válido.');
        if((string)$o['modalidadOperativa']==='ENTREGA_DOMICILIO'&&$vehicle<1)throw new SalesException('La entrega a domicilio requiere un vehículo.');
        $items=array_values(array_filter($this->repo->items($id),static fn(array $item):bool=>(float)$item['cantidadPendiente']>0));
        if(!$items)throw new SalesException('El pedido no tiene cantidades pendientes para despachar.');
        $db=$this->repo->db();$db->beginTransaction();
        try{
            $s=$db->prepare("INSERT INTO despacho(idPedido,idWarehouse,idVehiculo,transportista,fechaHoraProgramada,destino,modalidad,estado,observacionesEntrega,creadoPor) VALUES(?,?,?,?,?,?,?,'EN_ESPERA',NULL,?)");
            $modal=(string)$o['modalidadOperativa']==='ENTREGA_DOMICILIO'?'ENTREGA_DOMICILIO':'EN_CORRALON';
            $s->execute([$id,$warehouse,$vehicle>0?$vehicle:null,$transport?:null,$date,$destination,$modal,$actor?:null]);
            $dispatch=(int)$db->lastInsertId();
            $dispatchItem=$db->prepare('INSERT INTO itemdespacho(idDespacho,idDetallePedido,cantidadADespachar) VALUES(?,?,?)');
            foreach($items as $item){$dispatchItem->execute([$dispatch,$item['idDetalle'],$item['cantidadPendiente']]);}
            $number='R-'.date('Ymd').'-'.str_pad((string)$dispatch,6,'0',STR_PAD_LEFT);
            $r=$db->prepare('INSERT INTO remitodespacho(idDespacho,numeroRemito,fechaEmision,sucursalOrigen,hojaRuta,esDevolucion) VALUES(?,?,NOW(),?,?,0)');
            $r->execute([$dispatch,$number,$origin,'HR-'.$number]);
            $remit=(int)$db->lastInsertId();
            $db->prepare('INSERT INTO despacho_estado_historial(idDespacho,estadoAnterior,estadoNuevo,observaciones,idUsuario) VALUES(?,NULL,\'EN_ESPERA\',\'Despacho programado desde Ventas.\',?)')->execute([$dispatch,$actor?:null]);
            $db->prepare("UPDATE pedido SET estado='DESPACHO_PROGRAMADO' WHERE idPedido=?")->execute([$id]);
            $this->repo->audit($id,$actor,'REMIT_ISSUED',$number);
            $db->commit();return $remit;
        }catch(Throwable $e){if($db->inTransaction())$db->rollBack();throw $e;}
    }

    /** @param array<string,mixed> $input */
    public function issueInvoice(int $id,array $input,int $actor): int
    {
        $o=$this->repo->order($id);if(!$o)throw new SalesException('El pedido no existe.');if((string)$o['estado']==='CANCELADO')throw new SalesException('No se puede facturar un pedido cancelado.');if($this->repo->invoiceByOrder($id))throw new SalesException('El pedido ya posee una factura.');$paid=$this->repo->approvedPayments($id);if($paid+0.005<(float)$o['total'])throw new SalesException('El pedido todavía no está completamente pagado. Pagos aprobados: $'.number_format($paid,2,',','.').'.');
        $type=(string)($input['tipoFactura']??'B');if(!in_array($type,['A','B','C'],true))throw new SalesException('Tipo de factura inválido.');$pv=max(1,(int)($input['puntoVenta']??1));$number=max(1,(int)($input['nroComprobante']??0));if($number<=0)throw new SalesException('Ingresá el número de comprobante.');$sign=mb_substr(trim((string)($input['firmayAclaracion']??'')),0,150);
        $discount=0.0;$surcharge=0.0;foreach($this->repo->adjustments($id) as $a){if(str_starts_with((string)$a['tipoAjuste'],'DESCUENTO'))$discount+=(float)$a['montoCalculado'];elseif((string)$a['tipoAjuste']==='RECARGO_TARJETA')$surcharge+=(float)$a['montoCalculado'];}
        $s=$this->repo->db()->prepare('INSERT INTO factura(idPedido,nroComprobante,puntoVenta,fechaEmision,tipoFactura,totalFacturado,descuentoAplicado,recargoAplicado,firmayAclaracion,nroEnvio,observaciones) VALUES(?,?,?,NOW(),?,?,?,?,?,?,?)');$rem=$this->repo->remittanceByOrder($id);$s->execute([$id,$number,$pv,$type,$o['total'],$discount,$surcharge,$sign?:null,$rem['numeroRemito']??null,null]);$invoice=(int)$this->repo->db()->lastInsertId();$this->repo->db()->prepare("UPDATE pedido SET estado='FACTURADO' WHERE idPedido=?")->execute([$id]);$this->repo->audit($id,$actor,'INVOICE_ISSUED','Factura '.$type.' '.$pv.'-'.$number);return $invoice;
    }

    public static function modalityLabel(string $v): string { return match($v){'EN_CORRALON'=>'Retiro en corralón','ENTREGA_DOMICILIO'=>'Entrega a domicilio','ACOPIO'=>'Acopio',default=>$v}; }
}
