<?php
declare(strict_types=1);
require dirname(__DIR__, 2) . '/src/bootstrap.php';

use Sedema\Authorization;
use Sedema\Inventory\InventoryContext;
use Sedema\Inventory\InventoryPage;

$context = InventoryContext::boot();
$user = $context['user'];
$repository = $context['repository'];
$service = $context['service'];
$warehouses = $repository->warehouses();
$warehouseId = max(0, (int) ($_GET['warehouse'] ?? 0));
$productId = max(0, (int) ($_GET['product'] ?? 0));
$referenceWarehouse = $warehouseId ?: (int) ($_SESSION['inventory_warehouse'] ?? ($warehouses[0]['idWarehouse'] ?? 0));
$products = $referenceWarehouse > 0 ? $repository->products($referenceWarehouse) : [];
$movements = $repository->movements($warehouseId, $productId);
$typeLabels = [
    'INGRESO' => 'Ingreso', 'EGRESO' => 'Egreso',
    'AJUSTE_POSITIVO' => 'Ajuste positivo', 'AJUSTE_NEGATIVO' => 'Ajuste negativo',
    'TRANSFERENCIA_ENTRADA' => 'Transferencia recibida', 'TRANSFERENCIA_SALIDA' => 'Transferencia enviada',
];
$positiveTypes = ['INGRESO', 'AJUSTE_POSITIVO', 'TRANSFERENCIA_ENTRADA'];

InventoryPage::begin('Historial de movimientos', 'history', $user);
?>
<section class="inventory-heading">
    <div><p class="section-kicker">Trazabilidad</p><h2>Historial de movimientos</h2><p>Registro inalterable de ingresos, egresos, ajustes y transferencias.</p></div>
    <?php if (Authorization::can($user, 'inventory.adjust')): ?><a class="button button-secondary" href="movement.php"><?= InventoryPage::icon('movement') ?> Registrar movimiento</a><?php endif; ?>
</section>

<form class="inventory-toolbar history-toolbar" method="get">
    <div class="field compact-field"><label for="warehouse">Depósito</label><select id="warehouse" name="warehouse"><option value="0">Todos</option><?php foreach ($warehouses as $warehouse): ?><option value="<?= (int) $warehouse['idWarehouse'] ?>" <?= (int) $warehouse['idWarehouse'] === $warehouseId ? 'selected' : '' ?>><?= e((string) $warehouse['name']) ?></option><?php endforeach; ?></select></div>
    <div class="field compact-field toolbar-search"><label for="product">Producto</label><select id="product" name="product"><option value="0">Todos</option><?php foreach ($products as $product): ?><option value="<?= (int) $product['idProduct'] ?>" <?= (int) $product['idProduct'] === $productId ? 'selected' : '' ?>><?= e((string) $product['code']) ?> · <?= e((string) $product['name']) ?></option><?php endforeach; ?></select></div>
    <button class="button button-filter" type="submit">Aplicar filtros</button>
    <?php if ($warehouseId || $productId): ?><a class="clear-filter" href="movements.php">Limpiar</a><?php endif; ?>
</form>

<section class="inventory-panel">
    <div class="panel-title-row"><div><h3>Operaciones registradas</h3><p><?= count($movements) ?> movimientos recientes</p></div></div>
    <?php if (!$movements): ?>
        <div class="table-empty"><h4>No hay movimientos registrados</h4><p>Los cambios de existencias aparecerán en esta sección.</p></div>
    <?php else: ?>
        <div class="inventory-table-wrap">
            <table class="inventory-table movement-table">
                <thead><tr><th>Fecha</th><th>Producto</th><th>Depósito</th><th>Movimiento</th><th>Cantidad</th><th>Resultado</th><th>Referencia</th></tr></thead>
                <tbody>
                <?php foreach ($movements as $movement):
                    $type = (string) $movement['movementType'];
                    $positive = in_array($type, $positiveTypes, true);
                ?>
                    <tr>
                        <td><strong><?= e(date('d/m/Y', strtotime((string) $movement['createdAt']))) ?></strong><span class="cell-meta"><?= e(date('H:i', strtotime((string) $movement['createdAt']))) ?></span></td>
                        <td><strong><?= e((string) $movement['productName']) ?></strong><span class="cell-meta"><?= e((string) $movement['code']) ?></span></td>
                        <td><?= e((string) $movement['warehouseName']) ?></td>
                        <td><span class="movement-type <?= $positive ? 'movement-positive' : 'movement-negative' ?>"><?= e($typeLabels[$type] ?? $type) ?></span><span class="cell-meta"><?= e((string) $movement['observations']) ?></span></td>
                        <td class="movement-quantity <?= $positive ? 'positive' : 'negative' ?>"><?= $positive ? '+' : '−' ?><?= e($service->formatQuantity((float) $movement['quantity'])) ?> <?= e((string) $movement['symbol']) ?></td>
                        <td><?= e($service->formatQuantity((float) $movement['resultingQuantity'])) ?> <?= e((string) $movement['symbol']) ?></td>
                        <td><strong><?= e((string) $movement['sourceModule']) ?></strong><span class="cell-meta"><?= e((string) ($movement['sourceReference'] ?: 'Usuario #' . ($movement['actorUserId'] ?? '—'))) ?></span></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>
<?php InventoryPage::end(); ?>
