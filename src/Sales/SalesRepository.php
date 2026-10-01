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
    public function product(int $id,int $warehouseId): ?array { $s=$this->db->prepare('SELECT p.*,u.symbol,COALESCE(st.quantity,0) quantity FROM inventory_product p INNER JOIN inventory_unit u ON u.idUnit=p.idUnit LEFT JOIN inventory_stock st ON st.idProduct=p.idProduct AND st.idWarehouse=? WHERE p.idProduct=? AND p.active=1 LIMIT 1');$s->execute([$warehouseId,$id]);$r=$s->fetch();return $r?:null; }

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
    public function vehicles(): array { return $this->db->query("SELECT idVehiculo,matricula,modelo,capacidadCarga,estado FROM vehiculo WHERE estado<>'EN_MANTENIMIENTO' ORDER BY matricula")->fetchAll(); }
    /** @return array<string,mixed>|null */
    public function remittanceByOrder(int $orderId): ?array { $s=$this->db->prepare("SELECT r.*,d.fechaHoraProgramada,d.destino,d.modalidad,d.estado despachoEstado,d.transportista,v.matricula,v.modelo FROM remitodespacho r INNER JOIN despacho d ON d.idDespacho=r.idDespacho LEFT JOIN vehiculo v ON v.idVehiculo=d.idVehiculo WHERE d.idPedido=? ORDER BY r.idRemito DESC LIMIT 1");$s->execute([$orderId]);$r=$s->fetch();return $r?:null; }
    /** @return array<string,mixed>|null */
    public function invoiceByOrder(int $orderId): ?array { $s=$this->db->prepare('SELECT * FROM factura WHERE idPedido=? LIMIT 1');$s->execute([$orderId]);$r=$s->fetch();return $r?:null; }
    public function approvedPayments(int $orderId): float { $s=$this->db->prepare("SELECT COALESCE(SUM(importe),0) FROM pago WHERE idPedido=? AND estadoPago='APROBADO'");$s->execute([$orderId]);return (float)$s->fetchColumn(); }

    public function audit(int $orderId,int $userId,string $event,string $detail=''): void { $s=$this->db->prepare('INSERT INTO sales_order_audit(idPedido,idUsuario,eventType,detail) VALUES(?,?,?,?)');$s->execute([$orderId,$userId,$event,$detail?:null]); }
}
