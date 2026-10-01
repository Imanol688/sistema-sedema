<?php
declare(strict_types=1);

namespace Sedema\Logistics;

use DateTimeImmutable;
use PDOException;

final class LogisticsService
{
    private const STATES = ['EN_ESPERA','EN_PREPARACION','EN_RUTA','ENTREGADO','DEVUELTO'];
    private const TRANSITIONS = [
        'EN_ESPERA' => ['EN_PREPARACION'],
        'EN_PREPARACION' => ['EN_RUTA'],
        'EN_RUTA' => ['ENTREGADO','DEVUELTO'],
        'ENTREGADO' => ['DEVUELTO'],
        'DEVUELTO' => [],
    ];

    public function __construct(private readonly LogisticsRepository $repository) {}

    /** @param array<string,mixed> $input @param array<int|string,mixed> $quantities */
    public function schedule(array $input,array $quantities,int $actor): int
    {
        $orderId=(int)($input['idPedido']??0); $warehouseId=(int)($input['idWarehouse']??0);
        $vehicleId=max(0,(int)($input['idVehiculo']??0)); $mode=(string)($input['modalidad']??'');
        $destination=trim((string)($input['destino']??'')); $notes=trim((string)($input['observacionesEntrega']??''));
        $scheduled=trim((string)($input['fechaHoraProgramada']??''));
        if($orderId<1||$warehouseId<1) throw new LogisticsException('Seleccioná un pedido y un depósito de origen.');
        if(!in_array($mode,['EN_CORRALON','ENTREGA_DOMICILIO'],true)) throw new LogisticsException('Seleccioná una modalidad válida.');
        if($destination===''||mb_strlen($destination)>255) throw new LogisticsException('Ingresá un destino de hasta 255 caracteres.');
        if(mb_strlen($notes)>1000) throw new LogisticsException('Las observaciones son demasiado extensas.');
        $date=DateTimeImmutable::createFromFormat('Y-m-d\TH:i',$scheduled);
        if(!$date) throw new LogisticsException('Ingresá una fecha y hora de entrega válida.');
        if($date<new DateTimeImmutable('-5 minutes')) throw new LogisticsException('La fecha programada no puede estar en el pasado.');
        if($mode==='ENTREGA_DOMICILIO'&&$vehicleId<1) throw new LogisticsException('La entrega a domicilio requiere un vehículo.');
        if($mode==='EN_CORRALON') $vehicleId=0;

        return $this->repository->transaction(function() use($orderId,$warehouseId,$vehicleId,$mode,$destination,$notes,$date,$quantities,$actor): int {
            $order=$this->repository->lockOrder($orderId);if(!$order) throw new LogisticsException('El pedido indicado no existe.');
            if(in_array(mb_strtoupper((string)$order['estado']),['CANCELADO','CANCELADA','ANULADO','ANULADA'],true))throw new LogisticsException('No se puede despachar un pedido cancelado.');
            $warehouse=$this->repository->warehouse($warehouseId);
            if(!$warehouse) throw new LogisticsException('El depósito indicado no está disponible.');
            if($vehicleId>0){$vehicle=$this->repository->lockVehicle($vehicleId);if(!$vehicle||(int)$vehicle['activo']!==1||(string)$vehicle['estado']!=='DISPONIBLE') throw new LogisticsException('El vehículo ya no está disponible.');if($this->repository->vehicleHasScheduleConflict($vehicleId,$date->format('Y-m-d H:i:s')))throw new LogisticsException('El vehículo ya tiene un despacho programado en ese horario.');}
            $items=$this->repository->orderItems($orderId,true); $selected=[];
            foreach($items as $item){
                $detailId=(int)$item['idDetalle']; $qty=$this->decimal($quantities[$detailId]??0);
                if($qty<=0) continue;
                $available=round((float)$item['cantidadPendiente']-(float)$item['reservado'],3);
                if($qty>$available) throw new LogisticsException('La cantidad de '.$item['productName'].' supera el pendiente disponible ('.$this->quantity($available).' '.$item['symbol'].').');
                if((int)$item['allowsDecimals']!==1&&abs($qty-round($qty))>.0001) throw new LogisticsException($item['productName'].' solo admite cantidades enteras.');
                $selected[]=['detail'=>$detailId,'quantity'=>$qty];
            }
            if(!$selected) throw new LogisticsException('Indicá al menos una cantidad para despachar.');
            $dispatchId=$this->repository->createDispatch(['order'=>$orderId,'warehouse'=>$warehouseId,'vehicle'=>$vehicleId?:null,'scheduled'=>$date->format('Y-m-d H:i:s'),'destination'=>$destination,'mode'=>$mode,'notes'=>$notes?:null,'actor'=>$actor?:null]);
            foreach($selected as $item) $this->repository->addDispatchItem($dispatchId,$item['detail'],$item['quantity']);
            $this->repository->createDocuments($dispatchId,(string)$warehouse['name']);
            $this->repository->addHistory($dispatchId,null,'EN_ESPERA','Despacho programado.',$actor);
            $this->repository->markOrderScheduled($orderId);
            return $dispatchId;
        });
    }

    public function transition(int $dispatchId,string $newState,string $notes,int $actor): void
    {
        $newState=mb_strtoupper(trim($newState)); $notes=trim($notes);
        if($dispatchId<1||!in_array($newState,self::STATES,true)) throw new LogisticsException('La transición solicitada no es válida.');
        if(mb_strlen($notes)>500) throw new LogisticsException('La observación no puede superar 500 caracteres.');
        $this->repository->transaction(function() use($dispatchId,$newState,$notes,$actor): void {
            $dispatch=$this->repository->dispatch($dispatchId,true);
            if(!$dispatch) throw new LogisticsException('El despacho indicado no existe.');
            $current=(string)$dispatch['estado'];
            if(!in_array($newState,self::TRANSITIONS[$current]??[],true)) throw new LogisticsException('No se puede pasar de '.$this->stateLabel($current).' a '.$this->stateLabel($newState).'.');
            $items=$this->repository->dispatchItems($dispatchId,true);
            if(!$items) throw new LogisticsException('El despacho no contiene productos.');

            if(in_array($newState,['EN_PREPARACION','EN_RUTA'],true)) $this->validateStock($items,(int)$dispatch['idWarehouse']);
            if($newState==='EN_RUTA'){
                if((string)$dispatch['modalidad']==='ENTREGA_DOMICILIO'){
                    $vehicleId=(int)($dispatch['idVehiculo']??0); if($vehicleId<1) throw new LogisticsException('La entrega no tiene vehículo asignado.');
                    $vehicle=$this->repository->lockVehicle($vehicleId); if(!$vehicle||(string)$vehicle['estado']!=='DISPONIBLE') throw new LogisticsException('El vehículo no está disponible para iniciar la ruta.');
                    $this->repository->setVehicleState($vehicleId,'OCUPADO');
                }
                foreach($items as $item){
                    $qty=(float)$item['cantidadADespachar']; $stock=$this->repository->stockForUpdate((int)$item['idProducto'],(int)$dispatch['idWarehouse']);
                    $before=(float)$stock['quantity']; $after=round($before-$qty,3);
                    if($after<0) throw new LogisticsException('Stock insuficiente para '.$item['productName'].'.');
                    if(!$this->repository->reducePending((int)$item['idDetallePedido'],$qty)) throw new LogisticsException('El pedido ya no tiene saldo suficiente para '.$item['productName'].'.');
                    $this->repository->setStock((int)$item['idProducto'],(int)$dispatch['idWarehouse'],$after);
                    $this->repository->addMovement((int)$item['idProducto'],(int)$dispatch['idWarehouse'],'EGRESO',$qty,$before,$after,'Salida por despacho #'.$dispatchId,$actor,'DESP-'.$dispatchId.'-ITEM-'.$item['idItemDespacho']);
                }
            }
            if($newState==='ENTREGADO'&&(int)($dispatch['idVehiculo']??0)>0) $this->repository->setVehicleState((int)$dispatch['idVehiculo'],'DISPONIBLE');
            if($newState==='DEVUELTO'){
                foreach($items as $item){
                    $qty=(float)$item['cantidadADespachar']; $stock=$this->repository->stockForUpdate((int)$item['idProducto'],(int)$dispatch['idWarehouse']);
                    if(!$stock) throw new LogisticsException('No existe la posición de stock para devolver '.$item['productName'].'.');
                    $before=(float)$stock['quantity'];$after=round($before+$qty,3);
                    $this->repository->setStock((int)$item['idProducto'],(int)$dispatch['idWarehouse'],$after);
                    $this->repository->restorePending((int)$item['idDetallePedido'],$qty);
                    $this->repository->addMovement((int)$item['idProducto'],(int)$dispatch['idWarehouse'],'INGRESO',$qty,$before,$after,'Devolución del despacho #'.$dispatchId,$actor,'DEV-'.$dispatchId.'-ITEM-'.$item['idItemDespacho']);
                }
                if((int)($dispatch['idVehiculo']??0)>0) $this->repository->setVehicleState((int)$dispatch['idVehiculo'],'DISPONIBLE');
            }
            $this->repository->updateDispatchState($dispatchId,$newState);
            $this->repository->addHistory($dispatchId,$current,$newState,$notes?:null,$actor);
        });
    }

    /** @param list<array<string,mixed>> $items */
    private function validateStock(array $items,int $warehouseId): void
    {
        $required=[];$names=[];
        foreach($items as $item){$product=(int)$item['idProducto'];$required[$product]=($required[$product]??0)+(float)$item['cantidadADespachar'];$names[$product]=(string)$item['productName'];}
        foreach($required as $product=>$qty){$stock=$this->repository->stockForUpdate($product,$warehouseId);if(!$stock||(float)$stock['quantity']+0.0001<$qty) throw new LogisticsException('Stock insuficiente para '.$names[$product].'. Requerido: '.$this->quantity($qty).'.');}
    }

    /** @param array<string,mixed> $input */
    public function saveVehicle(array $input): int
    {
        $id=max(0,(int)($input['idVehiculo']??0));$plate=mb_strtoupper(trim((string)($input['matricula']??'')));$model=trim((string)($input['modelo']??''));$capacity=$this->decimal($input['capacidadCarga']??0);$state=(string)($input['estado']??'DISPONIBLE');
        if($plate===''||mb_strlen($plate)>20) throw new LogisticsException('Ingresá una matrícula de hasta 20 caracteres.');
        if($model===''||mb_strlen($model)>100) throw new LogisticsException('Ingresá un modelo de hasta 100 caracteres.');
        if($capacity<=0) throw new LogisticsException('La capacidad de carga debe ser mayor que cero.');
        if(!in_array($state,['DISPONIBLE','EN_MANTENIMIENTO'],true)) throw new LogisticsException('El estado ocupado solo se asigna automáticamente al iniciar una ruta.');
        if($id>0){$current=$this->repository->vehicle($id);if(!$current)throw new LogisticsException('El vehículo indicado no existe.');if((string)$current['estado']==='OCUPADO')throw new LogisticsException('El vehículo no puede editarse mientras está ocupado.');}
        try{return $this->repository->saveVehicle(['id'=>$id,'plate'=>$plate,'model'=>$model,'capacity'=>$capacity,'state'=>$state]);}
        catch(PDOException $e){if((string)$e->getCode()==='23000')throw new LogisticsException('La matrícula ya está registrada.');throw $e;}
    }
    public function setVehicleActive(int $id,bool $active): void { if($id<1||!$this->repository->vehicle($id))throw new LogisticsException('El vehículo indicado no existe.');if(!$this->repository->setVehicleActive($id,$active))throw new LogisticsException('No se puede dar de baja un vehículo ocupado.'); }
    public function stateLabel(string $state): string { return ['EN_ESPERA'=>'En espera','EN_PREPARACION'=>'En preparación','EN_RUTA'=>'En ruta','ENTREGADO'=>'Entregado','DEVUELTO'=>'Devuelto'][$state]??$state; }
    public function quantity(float $value): string { return rtrim(rtrim(number_format($value,3,',','.'),'0'),','); }
    private function decimal(mixed $value): float { $v=str_replace(',','.',trim((string)$value));if($v===''||!preg_match('/^\d+(?:\.\d{1,3})?$/',$v))return 0.0;return round((float)$v,3); }
}
