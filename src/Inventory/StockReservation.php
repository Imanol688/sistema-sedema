<?php
declare(strict_types=1);

namespace Sedema\Inventory;

use PDO;

final class StockReservation
{
    private const LINES = "FROM detallepedido d JOIN pedido pe ON pe.idPedido=d.idPedido
        WHERE pe.estado IN ('PENDIENTE','CONFIRMADO','DESPACHO_PROGRAMADO','FACTURADO')
          AND d.cantidadPendiente > 0";

    public static function catalogExpression(): string
    {
        return 'GREATEST(0, COALESCE(s.quantity,0) - (SELECT COALESCE(SUM(d.cantidadPendiente),0) '
            . self::LINES . ' AND pe.idWarehouse=s.idWarehouse AND d.idInventoryProduct=p.idProduct AND pe.idPedido<>?))';
    }

    // Call after locking the corresponding inventory_stock row.
    public static function lockedQuantity(PDO $db, int $product, int $warehouse, int $exceptOrder = 0): float
    {
        $query = $db->prepare('SELECT d.cantidadPendiente ' . self::LINES
            . ' AND pe.idWarehouse=? AND d.idInventoryProduct=? AND pe.idPedido<>? FOR UPDATE');
        $query->execute([$warehouse, $product, $exceptOrder]);
        return round(array_sum($query->fetchAll(PDO::FETCH_COLUMN)), 3);
    }
}
