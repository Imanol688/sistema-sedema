<?php
declare(strict_types=1);
namespace Sedema\Sales;

use PDO;

final class SalesRepository
{
    public function __construct(private readonly PDO $db) {}
    public function db(): PDO { return $this->db; }

    /** @return list<array<string,mixed>> */
    public function clients(): array
    {
        return $this->db->query("SELECT idCliente,nombre,apellido,cuitDNI,tipoCliente,razonSocial,direccion,localidad FROM cliente WHERE activo=1 ORDER BY apellido,nombre")->fetchAll();
    }

    /** @return list<array<string,mixed>> */
    public function warehouses(): array
    {
        return $this->db->query("SELECT idWarehouse,name FROM inventory_warehouse WHERE active=1 ORDER BY name")->fetchAll();
    }

    /** @return list<array<string,mixed>> */
    public function products(int $warehouseId): array
    {
        $st=$this->db->prepare("SELECT p.idProduct,p.code,p.name,p.salePrice,u.symbol,COALESCE(s.quantity,0) quantity FROM inventory_product p INNER JOIN inventory_unit u ON u.idUnit=p.idUnit LEFT JOIN inventory_stock s ON s.idProduct=p.idProduct AND s.idWarehouse=? WHERE p.active=1 ORDER BY p.name");
        $st->execute([$warehouseId]); return $st->fetchAll();
    }

    /** @return array<string,mixed>|null */
    public function client(int $id): ?array { $s=$this->db->prepare('SELECT * FROM cliente WHERE idCliente=? LIMIT 1');$s->execute([$id]);$r=$s->fetch();return $r?:null; }
    /** @return array<string,mixed>|null */
    public function product(int $id,int $warehouseId): ?array { $s=$this->db->prepare('SELECT p.*,u.symbol,u.allowsDecimals,COALESCE(st.quantity,0) quantity FROM inventory_product p INNER JOIN inventory_unit u ON u.idUnit=p.idUnit LEFT JOIN inventory_stock st ON st.idProduct=p.idProduct AND st.idWarehouse=? WHERE p.idProduct=? AND p.active=1 LIMIT 1');$s->execute([$warehouseId,$id]);$r=$s->fetch();return $r?:null; }

    /** @return list<array<string,mixed>> */
    public function orders(string $search='',string $status='all'): array
    {
        $w=[];$p=[];
        if($search!==''){ $like='%'.$search.'%';$w[]='(CAST(pe.idPedido AS CHAR) LIKE ? OR c.nombre LIKE ? OR c.apellido LIKE ? OR c.cuitDNI LIKE ?)';array_push($p,$like,$like,$like,$like); }
        if($status!=='all'){ $w[]='pe.estado=?';$p[]=$status; }
        $sql="SELECT pe.*,c.nombre,c.apellido,c.cuitDNI,c.tipoCliente,w.name warehouseName,(SELECT COUNT(*) FROM detallepedido d WHERE d.idPedido=pe.idPedido) itemCount FROM pedido pe INNER JOIN cliente c ON c.idCliente=pe.idCliente LEFT JOIN inventory_warehouse w ON w.idWarehouse=pe.idWarehouse".($w?' WHERE '.implode(' AND ',$w):'')." ORDER BY pe.fecha DESC,pe.idPedido DESC LIMIT 250";
        $s=$this->db->prepare($sql);$s->execute($p);return $s->fetchAll();
    }

    /** @return array<string,mixed>|null */
    public function order(int $id): ?array
    {
        $s=$this->db->prepare("SELECT pe.*,c.nombre,c.apellido,c.cuitDNI,c.tipoCliente,c.direccion clientAddress,c.localidad,w.name warehouseName,u.username seller FROM pedido pe INNER JOIN cliente c ON c.idCliente=pe.idCliente LEFT JOIN inventory_warehouse w ON w.idWarehouse=pe.idWarehouse LEFT JOIN usuario u ON u.idUsuario=pe.idUsuario WHERE pe.idPedido=? LIMIT 1");$s->execute([$id]);$r=$s->fetch();return $r?:null;
    }
    /** @return list<array<string,mixed>> */
    public function items(int $orderId): array { $s=$this->db->prepare("SELECT d.*,p.code,p.name,u.symbol FROM detallepedido d LEFT JOIN inventory_product p ON p.idProduct=d.idInventoryProduct LEFT JOIN inventory_unit u ON u.idUnit=p.idUnit WHERE d.idPedido=? ORDER BY d.idDetalle");$s->execute([$orderId]);return $s->fetchAll(); }
    /** @return list<array<string,mixed>> */
    public function adjustments(int $orderId): array { $s=$this->db->prepare('SELECT * FROM ajuste WHERE idPedido=? ORDER BY idAjuste');$s->execute([$orderId]);return $s->fetchAll(); }
    /** @return list<array<string,mixed>> */
    public function vehicles(): array { return $this->db->query("SELECT idVehiculo,matricula,modelo,capacidadCarga,estado FROM vehiculo WHERE activo=1 AND estado='DISPONIBLE' ORDER BY matricula")->fetchAll(); }
    /** @return array<string,mixed>|null */
    public function remittanceByOrder(int $orderId): ?array { $s=$this->db->prepare("SELECT r.*,(SELECT COUNT(*) FROM itemdespacho ix WHERE ix.idDespacho=d.idDespacho) itemCount,d.fechaHoraProgramada,d.destino,d.modalidad,d.estado despachoEstado,d.transportista,v.matricula,v.modelo FROM remitodespacho r INNER JOIN despacho d ON d.idDespacho=r.idDespacho LEFT JOIN vehiculo v ON v.idVehiculo=d.idVehiculo WHERE d.idPedido=? ORDER BY (d.estado IN ('CANCELADO','DEVUELTO')),r.idRemito DESC LIMIT 1");$s->execute([$orderId]);$r=$s->fetch();return $r?:null; }
    /** @return array<string,mixed>|null */
    public function invoiceByOrder(int $orderId): ?array { $s=$this->db->prepare('SELECT * FROM factura WHERE idPedido=? LIMIT 1');$s->execute([$orderId]);$r=$s->fetch();return $r?:null; }
    public function remittances(int $orderId): array
    {
        $s=$this->db->prepare('SELECT r.*,d.estado despachoEstado FROM remitodespacho r JOIN despacho d ON d.idDespacho=r.idDespacho WHERE d.idPedido=? ORDER BY r.idRemito DESC');
        $s->execute([$orderId]); return $s->fetchAll();
    }
    public function selectedRemittance(int $orderId, int $remitId=0, ?string $number=null): ?array
    {
        if ($remitId<=0 && $number===null) return $this->remittanceByOrder($orderId);
        $s=$this->db->prepare('SELECT r.*,d.destino,d.fechaHoraProgramada,d.transportista,d.estado despachoEstado,v.matricula,v.modelo FROM remitodespacho r JOIN despacho d ON d.idDespacho=r.idDespacho LEFT JOIN vehiculo v ON v.idVehiculo=d.idVehiculo WHERE d.idPedido=? AND '.($number!==null?'r.numeroRemito=?':'r.idRemito=?'));
        $s->execute([$orderId,$number??$remitId]); return $s->fetch()?:null;
    }
    public function remittanceItems(int $dispatchId): array
    {
        $s=$this->db->prepare('SELECT d.descripcion,p.name,u.symbol,i.cantidadADespachar cantidadSolicitada FROM itemdespacho i JOIN detallepedido d ON d.idDetalle=i.idDetallePedido LEFT JOIN inventory_product p ON p.idProduct=d.idInventoryProduct LEFT JOIN inventory_unit u ON u.idUnit=p.idUnit WHERE i.idDespacho=? ORDER BY i.idItemDespacho');
        $s->execute([$dispatchId]);return $s->fetchAll();
    }
    public function approvedPayments(int $orderId): float { $s=$this->db->prepare("SELECT COALESCE(SUM(importe),0) FROM pago WHERE idPedido=? AND estadoPago='APROBADO'");$s->execute([$orderId]);return (float)$s->fetchColumn(); }

    /** Effective approved payments only; account debits are not received funds. */
    public function settledPayments(int $orderId): float
    {
        $s = $this->db->prepare("SELECT COALESCE(SUM(importe),0) FROM pago WHERE idPedido=? AND estadoPago='APROBADO' AND COALESCE(tipoOperacion,'PAGO')='PAGO'");
        $s->execute([$orderId]);
        return (float) $s->fetchColumn();
    }

    public function correctionBlockedReason(int $id): ?string
    {
        $order = $this->order($id);
        if (!$order) return 'El pedido no existe.';
        if (!in_array((string) $order['estado'], ['CONFIRMADO', 'PENDIENTE'], true)) return 'El estado actual del pedido no permite modificarlo o eliminarlo.';
        foreach (['pago', 'despacho', 'factura', 'current_account_movement'] as $table) {
            $s = $this->db->prepare('SELECT COUNT(*) FROM ' . $table . ' WHERE idPedido=?');
            $s->execute([$id]);
            if ((int) $s->fetchColumn() > 0) return 'El pedido ya tiene cobros, cuenta corriente, despachos o comprobantes asociados.';
        }
        return null;
    }

    public function correctionVersion(int $id): string
    {
        return hash('sha256', json_encode([$this->order($id), $this->items($id), $this->adjustments($id)], JSON_THROW_ON_ERROR));
    }

    public function lockOrder(int $id): void
    {
        $s = $this->db->prepare('SELECT idPedido FROM pedido WHERE idPedido=? FOR UPDATE');
        $s->execute([$id]);
        if (!$s->fetchColumn()) throw new SalesException('El pedido no existe.');
    }

    public function audit(int $orderId,int $userId,string $event,string $detail=''): void { $s=$this->db->prepare('INSERT INTO sales_order_audit(idPedido,idUsuario,eventType,detail) VALUES(?,?,?,?)');$s->execute([$orderId,$userId,$event,$detail?:null]); }
}
