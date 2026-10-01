<?php
declare(strict_types=1);
namespace Sedema\Suppliers;
use PDO;
use Throwable;

final class SupplierRepository
{
    public function __construct(private readonly PDO $db) {}
    public function transaction(callable $fn): mixed
    {
        $this->db->beginTransaction();
        try { $result = $fn(); $this->db->commit(); return $result; }
        catch (Throwable $e) { if ($this->db->inTransaction()) $this->db->rollBack(); throw $e; }
    }
    private function rows(string $sql, array $params = []): array
    {
        $stmt = $this->db->prepare($sql); $stmt->execute($params); return $stmt->fetchAll();
    }
    private function execute(string $sql, array $params = []): void
    {
        $stmt = $this->db->prepare($sql); $stmt->execute($params);
    }
    public function suppliers(string $search = ''): array
    {
        return $this->rows('SELECT * FROM proveedor WHERE razonSocial LIKE ? OR cuit LIKE ? ORDER BY razonSocial LIMIT 250', ['%' . $search . '%', '%' . $search . '%']);
    }
    public function supplier(int $id): ?array
    {
        return $this->rows('SELECT * FROM proveedor WHERE idProveedor = ?', [$id])[0] ?? null;
    }
    public function saveSupplier(int $id, string $name, string $cuit, ?string $description, ?string $contact): int
    {
        if ($id) {
            $this->execute('UPDATE proveedor SET razonSocial=?, cuit=?, descripcionProveedor=?, contacto=? WHERE idProveedor=?', [$name,$cuit,$description,$contact,$id]);
            return $id;
        }
        $this->execute('INSERT INTO proveedor (razonSocial,cuit,descripcionProveedor,contacto) VALUES (?,?,?,?)', [$name,$cuit,$description,$contact]);
        return (int) $this->db->lastInsertId();
    }
    public function orders(): array
    {
        return $this->rows('SELECT r.*, p.razonSocial,
            (SELECT COALESCE(SUM(d.cantidadSolicitada*d.precioUnitario),0) FROM detalleremito d WHERE d.idRemitoProveedor=r.idRemitoProveedor) AS total,
            (SELECT COUNT(*) FROM recepcioncompra x WHERE x.idRemitoProveedor=r.idRemitoProveedor) AS receptions
            FROM remitoproveedor r JOIN proveedor p ON p.idProveedor=r.idProveedor
            ORDER BY r.idRemitoProveedor DESC LIMIT 250');
    }
    public function order(int $id, bool $lock = false): ?array
    {
        return $this->rows('SELECT r.*,p.razonSocial FROM remitoproveedor r JOIN proveedor p ON p.idProveedor=r.idProveedor WHERE r.idRemitoProveedor=?' . ($lock ? ' FOR UPDATE' : ''), [$id])[0] ?? null;
    }
    public function lines(int $id): array
    {
        return $this->rows('SELECT d.*,p.code,p.name,u.symbol,u.allowsDecimals,
            COALESCE((SELECT SUM(dr.cantidadRecibida) FROM detallerecepcion dr JOIN recepcioncompra rc ON rc.idRecepcion=dr.idRecepcion WHERE rc.idRemitoProveedor=d.idRemitoProveedor AND dr.idProducto=d.idProducto),0) AS received
            FROM detalleremito d JOIN inventory_product p ON p.idProduct=d.idProducto JOIN inventory_unit u ON u.idUnit=p.idUnit
            WHERE d.idRemitoProveedor=? ORDER BY d.idDetalleRemito', [$id]);
    }
    public function createOrder(int $supplier, string $number, string $date, array $lines): int
    {
        $this->execute('INSERT INTO remitoproveedor (idProveedor,numeroRemito,fechaEmision,estado) VALUES (?,?,?,\'PENDIENTE\')', [$supplier,$number,$date]);
        $id = (int)$this->db->lastInsertId();
        foreach ($lines as $line) $this->execute('INSERT INTO detalleremito (idRemitoProveedor,idProducto,cantidadSolicitada,precioUnitario) VALUES (?,?,?,?)', [$id,$line['product'],$line['quantity'],$line['price']]);
        return $id;
    }
    public function products(): array
    {
        return $this->rows('SELECT p.idProduct,p.code,p.name,u.symbol,u.allowsDecimals FROM inventory_product p JOIN inventory_unit u ON u.idUnit=p.idUnit WHERE p.active=1 ORDER BY p.name');
    }
    public function warehouses(): array
    {
        return $this->rows('SELECT idWarehouse,name FROM inventory_warehouse WHERE active=1 ORDER BY name');
    }
    public function product(int $id): ?array
    {
        return $this->rows('SELECT p.idProduct,u.allowsDecimals FROM inventory_product p JOIN inventory_unit u ON u.idUnit=p.idUnit WHERE p.idProduct=? AND p.active=1', [$id])[0] ?? null;
    }
    public function warehouse(int $id): bool
    {
        return (bool)$this->rows('SELECT idWarehouse FROM inventory_warehouse WHERE idWarehouse=? AND active=1', [$id]);
    }
    public function reception(int $order, int $warehouse, string $note): int
    {
        $this->execute('INSERT INTO recepcioncompra (idRemitoProveedor,idWarehouse,observaciones) VALUES (?,?,?)', [$order,$warehouse,$note]);
        return (int)$this->db->lastInsertId();
    }
    public function receiptLine(int $receipt, int $product, string $quantity): void
    {
        $this->execute('INSERT INTO detallerecepcion (idRecepcion,idProducto,cantidadRecibida) VALUES (?,?,?)', [$receipt,$product,$quantity]);
    }
    public function stock(int $product, int $warehouse): ?array
    {
        // Lock the stock position in the same transaction as receipt and movement.
        return $this->rows('SELECT s.quantity FROM inventory_stock s JOIN inventory_product p ON p.idProduct=s.idProduct JOIN inventory_warehouse w ON w.idWarehouse=s.idWarehouse WHERE s.idProduct=? AND s.idWarehouse=? AND p.active=1 AND w.active=1 FOR UPDATE', [$product,$warehouse])[0] ?? null;
    }
    public function updateStock(int $product, int $warehouse, string $quantity): void
    {
        $this->execute('UPDATE inventory_stock SET quantity=? WHERE idProduct=? AND idWarehouse=?', [$quantity,$product,$warehouse]);
    }
    public function movement(int $product, int $warehouse, string $quantity, string $before, string $after, string $note, int $actor, int $receipt): void
    {
        $this->execute('INSERT INTO inventory_movement (idProduct,idWarehouse,movementType,quantity,previousQuantity,resultingQuantity,observations,actorUserId,sourceModule,sourceReference) VALUES (?,?,\'INGRESO\',?,?,?,?,?,\'PROVEEDORES\',?)', [$product,$warehouse,$quantity,$before,$after,$note,$actor,'RECEPCION-' . $receipt]);
    }
    public function status(int $order, string $status): void
    {
        $this->execute('UPDATE remitoproveedor SET estado=? WHERE idRemitoProveedor=?', [$status,$order]);
    }
    public function receptions(int $order): array
    {
        return $this->rows('SELECT r.*,w.name AS warehouseName,d.idProducto,d.cantidadRecibida,p.name AS productName,u.symbol,m.actorUserId
            FROM recepcioncompra r JOIN inventory_warehouse w ON w.idWarehouse=r.idWarehouse
            JOIN detallerecepcion d ON d.idRecepcion=r.idRecepcion JOIN inventory_product p ON p.idProduct=d.idProducto
            JOIN inventory_unit u ON u.idUnit=p.idUnit LEFT JOIN inventory_movement m ON m.sourceModule=\'PROVEEDORES\' AND m.sourceReference=CONCAT(\'RECEPCION-\',r.idRecepcion) AND m.idProduct=d.idProducto AND m.idWarehouse=r.idWarehouse
            WHERE r.idRemitoProveedor=? ORDER BY r.idRecepcion DESC,d.idProducto', [$order]);
    }
}
