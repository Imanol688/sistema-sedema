<?php
declare(strict_types=1);

namespace Sedema\Logistics;

use PDO;

final class LogisticsRepository
{
    public function __construct(private readonly PDO $db) {}

    public function transaction(callable $callback): mixed
    {
        $this->db->beginTransaction();
        try { $result = $callback(); $this->db->commit(); return $result; }
        catch (\Throwable $error) { if ($this->db->inTransaction()) $this->db->rollBack(); throw $error; }
    }

    /** @return array{waiting:int,preparing:int,onRoute:int,delivered:int} */
    public function summary(): array
    {
        $row = $this->db->query("SELECT SUM(estado='EN_ESPERA') waiting, SUM(estado='EN_PREPARACION') preparing, SUM(estado='EN_RUTA') onRoute, SUM(estado='ENTREGADO') delivered FROM despacho")->fetch() ?: [];
        return ['waiting'=>(int)($row['waiting']??0), 'preparing'=>(int)($row['preparing']??0), 'onRoute'=>(int)($row['onRoute']??0), 'delivered'=>(int)($row['delivered']??0)];
    }

    /** @return list<array<string,mixed>> */
    public function dispatches(string $state = ''): array
    {
        $where = $state !== '' ? 'WHERE d.estado = :state' : '';
        $sql = "SELECT d.*, CONCAT(c.nombre, ' ', COALESCE(c.apellido,'')) clienteNombre, c.telefono,
                       w.name warehouseName, w.address warehouseAddress, v.matricula, v.modelo,
                       COUNT(i.idItemDespacho) itemCount, COALESCE(SUM(i.cantidadADespachar),0) totalUnits
                FROM despacho d JOIN pedido p ON p.idPedido=d.idPedido JOIN cliente c ON c.idCliente=p.idCliente
                JOIN inventory_warehouse w ON w.idWarehouse=d.idWarehouse LEFT JOIN vehiculo v ON v.idVehiculo=d.idVehiculo
                LEFT JOIN itemdespacho i ON i.idDespacho=d.idDespacho $where
                GROUP BY d.idDespacho ORDER BY d.fechaHoraProgramada DESC, d.idDespacho DESC LIMIT 250";
        $statement = $this->db->prepare($sql); $statement->execute($state !== '' ? ['state'=>$state] : []); return $statement->fetchAll();
    }

    /** @return list<array<string,mixed>> */
    public function orders(): array
    {
        return $this->db->query("SELECT p.idPedido, p.fecha, p.estado, p.modalidadOperativa, CONCAT(c.nombre,' ',COALESCE(c.apellido,'')) clienteNombre,
            SUM(dp.cantidadPendiente > 0) pendingLines, SUM(dp.cantidadPendiente) pendingTotal FROM pedido p JOIN cliente c ON c.idCliente=p.idCliente JOIN detallepedido dp ON dp.idPedido=p.idPedido
            GROUP BY p.idPedido HAVING pendingTotal > 0 ORDER BY p.fecha DESC LIMIT 200")->fetchAll();
    }

    /** @return list<array<string,mixed>> */
    public function orderItems(int $orderId, bool $lock = false): array
    {
        $sql = "SELECT dp.idDetalle, dp.idPedido, COALESCE(dp.idInventoryProduct, dp.idProducto) idProducto, dp.cantidadSolicitada, dp.cantidadPendiente,
                    ip.code, ip.name productName, iu.symbol, iu.allowsDecimals,
                    COALESCE((SELECT SUM(ix.cantidadADespachar) FROM itemdespacho ix JOIN despacho dx ON dx.idDespacho=ix.idDespacho
                      WHERE ix.idDetallePedido=dp.idDetalle AND dx.estado IN ('EN_ESPERA','EN_PREPARACION')),0) reservado
                FROM detallepedido dp JOIN inventory_product ip ON ip.idProduct=COALESCE(dp.idInventoryProduct, dp.idProducto) JOIN inventory_unit iu ON iu.idUnit=ip.idUnit
                WHERE dp.idPedido=? ORDER BY dp.idDetalle" . ($lock ? ' FOR UPDATE' : '');
        $statement=$this->db->prepare($sql); $statement->execute([$orderId]); return $statement->fetchAll();
    }

    /** @return list<array<string,mixed>> */
    public function warehouses(): array { return $this->db->query('SELECT * FROM inventory_warehouse WHERE active=1 ORDER BY name')->fetchAll(); }
    /** @return list<array<string,mixed>> */
    public function vehicles(bool $onlyActive = true): array { return $this->db->query('SELECT * FROM vehiculo' . ($onlyActive?' WHERE activo=1':'') . ' ORDER BY matricula')->fetchAll(); }
    public function vehicle(int $id): ?array { $s=$this->db->prepare('SELECT * FROM vehiculo WHERE idVehiculo=?');$s->execute([$id]);$r=$s->fetch();return is_array($r)?$r:null; }

    public function lockOrder(int $id): ?array { $s=$this->db->prepare('SELECT * FROM pedido WHERE idPedido=? FOR UPDATE'); $s->execute([$id]); $r=$s->fetch(); return is_array($r)?$r:null; }
    public function lockVehicle(int $id): ?array { $s=$this->db->prepare('SELECT * FROM vehiculo WHERE idVehiculo=? FOR UPDATE'); $s->execute([$id]); $r=$s->fetch(); return is_array($r)?$r:null; }
    public function vehicleHasScheduleConflict(int $id,string $scheduled): bool { $s=$this->db->prepare("SELECT 1 FROM despacho WHERE idVehiculo=? AND fechaHoraProgramada=? AND estado IN ('EN_ESPERA','EN_PREPARACION','EN_RUTA') LIMIT 1");$s->execute([$id,$scheduled]);return(bool)$s->fetchColumn(); }
    public function warehouse(int $id): ?array { $s=$this->db->prepare('SELECT * FROM inventory_warehouse WHERE idWarehouse=? AND active=1'); $s->execute([$id]); $r=$s->fetch(); return is_array($r)?$r:null; }

    /** @param array<string,mixed> $data */
    public function createDispatch(array $data): int
    {
        $s=$this->db->prepare('INSERT INTO despacho (idPedido,idWarehouse,idVehiculo,fechaHoraProgramada,destino,modalidad,estado,observacionesEntrega,creadoPor) VALUES (:order,:warehouse,:vehicle,:scheduled,:destination,:mode,\'EN_ESPERA\',:notes,:actor)');
        $s->execute($data); return (int)$this->db->lastInsertId();
    }
    public function addDispatchItem(int $dispatchId,int $detailId,float $quantity): void { $this->db->prepare('INSERT INTO itemdespacho (idDespacho,idDetallePedido,cantidadADespachar) VALUES (?,?,?)')->execute([$dispatchId,$detailId,$quantity]); }
    public function createDocuments(int $dispatchId,string $branch): void { $code=str_pad((string)$dispatchId,8,'0',STR_PAD_LEFT); $this->db->prepare('INSERT INTO remitodespacho (idDespacho,numeroRemito,sucursalOrigen,hojaRuta) VALUES (?,?,?,?)')->execute([$dispatchId,'R-'.$code,$branch,'HR-'.$code]); }
    public function addHistory(int $dispatchId,?string $from,string $to,?string $notes,int $actor): void { $this->db->prepare('INSERT INTO despacho_estado_historial (idDespacho,estadoAnterior,estadoNuevo,observaciones,idUsuario) VALUES (?,?,?,?,?)')->execute([$dispatchId,$from,$to,$notes?:null,$actor?:null]); }
    public function markOrderScheduled(int $orderId): void { $this->db->prepare("UPDATE pedido SET estado='DESPACHO_PROGRAMADO' WHERE idPedido=? AND estado NOT IN ('CANCELADO','FACTURADO')")->execute([$orderId]); }

    /** @return array<string,mixed>|null */
    public function dispatch(int $id,bool $lock=false): ?array
    {
        $sql="SELECT d.*,p.fecha pedidoFecha,p.total pedidoTotal,p.estado pedidoEstado,CONCAT(c.nombre,' ',COALESCE(c.apellido,'')) clienteNombre,c.cuitDNI,c.telefono,c.direccion clienteDireccion,c.localidad,
                    w.name warehouseName,w.address warehouseAddress,v.matricula,v.modelo,v.capacidadCarga,v.estado vehicleState,r.numeroRemito,r.hojaRuta,r.fechaEmision
              FROM despacho d JOIN pedido p ON p.idPedido=d.idPedido JOIN cliente c ON c.idCliente=p.idCliente JOIN inventory_warehouse w ON w.idWarehouse=d.idWarehouse
              LEFT JOIN vehiculo v ON v.idVehiculo=d.idVehiculo LEFT JOIN remitodespacho r ON r.idDespacho=d.idDespacho WHERE d.idDespacho=?".($lock?' FOR UPDATE':'');
        $s=$this->db->prepare($sql);$s->execute([$id]);$r=$s->fetch();return is_array($r)?$r:null;
    }
    /** @return list<array<string,mixed>> */
    public function dispatchItems(int $id,bool $lock=false): array
    {
        $sql='SELECT i.*,COALESCE(dp.idInventoryProduct,dp.idProducto) idProducto,dp.cantidadSolicitada,dp.cantidadPendiente,ip.code,ip.name productName,iu.symbol,iu.allowsDecimals FROM itemdespacho i JOIN detallepedido dp ON dp.idDetalle=i.idDetallePedido JOIN inventory_product ip ON ip.idProduct=COALESCE(dp.idInventoryProduct,dp.idProducto) JOIN inventory_unit iu ON iu.idUnit=ip.idUnit WHERE i.idDespacho=? ORDER BY i.idItemDespacho'.($lock?' FOR UPDATE':'');
        $s=$this->db->prepare($sql);$s->execute([$id]);return $s->fetchAll();
    }
    /** @return list<array<string,mixed>> */
    public function history(int $id): array { $s=$this->db->prepare("SELECT h.*,COALESCE(CONCAT(e.nombre,' ',e.apellido),u.username,'Sistema') actorName FROM despacho_estado_historial h LEFT JOIN usuario u ON u.idUsuario=h.idUsuario LEFT JOIN empleado e ON e.idEmpleado=u.idEmpleado WHERE h.idDespacho=? ORDER BY h.creadoEn DESC,h.idHistorial DESC");$s->execute([$id]);return $s->fetchAll(); }
    public function stockForUpdate(int $productId,int $warehouseId): ?array { $s=$this->db->prepare('SELECT s.quantity,p.name productName,u.symbol FROM inventory_stock s JOIN inventory_product p ON p.idProduct=s.idProduct JOIN inventory_unit u ON u.idUnit=p.idUnit WHERE s.idProduct=? AND s.idWarehouse=? FOR UPDATE');$s->execute([$productId,$warehouseId]);$r=$s->fetch();return is_array($r)?$r:null; }
    public function setStock(int $productId,int $warehouseId,float $quantity): void { $this->db->prepare('UPDATE inventory_stock SET quantity=? WHERE idProduct=? AND idWarehouse=?')->execute([$quantity,$productId,$warehouseId]); }
    public function addMovement(int $productId,int $warehouseId,string $type,float $qty,float $before,float $after,string $note,int $actor,string $ref): void { $this->db->prepare('INSERT INTO inventory_movement (idProduct,idWarehouse,movementType,quantity,previousQuantity,resultingQuantity,observations,actorUserId,sourceModule,sourceReference) VALUES (?,?,?,?,?,?,?,?,\'LOGISTICA\',?)')->execute([$productId,$warehouseId,$type,$qty,$before,$after,$note,$actor?:null,$ref]); }
    public function reducePending(int $detailId,float $qty): bool { $s=$this->db->prepare('UPDATE detallepedido SET cantidadPendiente=cantidadPendiente-? WHERE idDetalle=? AND cantidadPendiente>=?');$s->execute([$qty,$detailId,$qty]);return $s->rowCount()===1; }
    public function restorePending(int $detailId,float $qty): void { $this->db->prepare('UPDATE detallepedido SET cantidadPendiente=LEAST(cantidadSolicitada,cantidadPendiente+?) WHERE idDetalle=?')->execute([$qty,$detailId]); }
    public function updateDispatchState(int $id,string $state): void { $column=['EN_RUTA'=>'despachadoEn','ENTREGADO'=>'entregadoEn','DEVUELTO'=>'devueltoEn'][$state]??null; $sql='UPDATE despacho SET estado=?'.($column?', '.$column.'=NOW()':'').' WHERE idDespacho=?';$this->db->prepare($sql)->execute([$state,$id]); }
    public function setVehicleState(int $id,string $state): void { $this->db->prepare('UPDATE vehiculo SET estado=? WHERE idVehiculo=?')->execute([$state,$id]); }
    /** @param array<string,mixed> $data */
    public function saveVehicle(array $data): int { if((int)$data['id']>0){$this->db->prepare('UPDATE vehiculo SET matricula=:plate,modelo=:model,capacidadCarga=:capacity,estado=:state WHERE idVehiculo=:id')->execute($data);return (int)$data['id'];} unset($data['id']);$this->db->prepare('INSERT INTO vehiculo (matricula,modelo,capacidadCarga,estado,activo) VALUES (:plate,:model,:capacity,:state,1)')->execute($data);return(int)$this->db->lastInsertId(); }
    public function setVehicleActive(int $id,bool $active): bool { $s=$this->db->prepare('UPDATE vehiculo SET activo=? WHERE idVehiculo=? AND estado<>\'OCUPADO\'');$s->execute([$active?1:0,$id]);return $s->rowCount()===1; }
}
