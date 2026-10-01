<?php
declare(strict_types=1);
require dirname(__DIR__, 2) . '/src/bootstrap.php';

use Sedema\Authorization;
use Sedema\Csrf;
use Sedema\Inventory\InventoryContext;
use Sedema\Inventory\InventoryPage;

$context = InventoryContext::boot();
$user = $context['user'];
Authorization::require($user, 'inventory.adjust');
$repository = $context['repository'];
$warehouses = $repository->warehouses();
$warehouseId = (int) ($_GET['warehouse'] ?? $_SESSION['inventory_warehouse'] ?? ($warehouses[0]['idWarehouse'] ?? 0));
$products = $warehouseId > 0 ? $repository->products($warehouseId) : [];

InventoryPage::begin('Registrar movimiento', 'movement', $user);
?>
<section class="inventory-heading compact-heading">
    <div><p class="section-kicker">Existencias</p><h2>Registrar movimiento</h2><p>Todo cambio queda asentado con usuario, fecha, cantidades y observación.</p></div>
    <a class="back-button" href="index.php">← Volver al inventario</a>
</section>

<div class="inventory-form-columns">
    <form class="inventory-form" method="post" action="action.php">
        <input type="hidden" name="csrf_token" value="<?= e(Csrf::token()) ?>"><input type="hidden" name="action" value="movement">
        <section class="form-section">
            <div class="form-section-heading"><span>01</span><div><h3>Ingreso, egreso o ajuste</h3><p>Para operaciones realizadas dentro de Inventario.</p></div></div>
            <div class="form-grid single-column">
                <div class="field"><label for="idWarehouse">Depósito *</label><select id="idWarehouse" name="idWarehouse" required><?php foreach ($warehouses as $warehouse): ?><option value="<?= (int) $warehouse['idWarehouse'] ?>" <?= (int) $warehouse['idWarehouse'] === $warehouseId ? 'selected' : '' ?>><?= e((string) $warehouse['name']) ?></option><?php endforeach; ?></select></div>
                <div class="field"><label for="idProduct">Producto *</label><select id="idProduct" name="idProduct" required><option value="">Seleccionar</option><?php foreach ($products as $product): ?><option value="<?= (int) $product['idProduct'] ?>"><?= e((string) $product['code']) ?> · <?= e((string) $product['name']) ?> (<?= e((string) $product['quantity']) ?> <?= e((string) $product['symbol']) ?>)</option><?php endforeach; ?></select></div>
                <div class="field"><label for="movementType">Tipo *</label><select id="movementType" name="movementType" required><option value="INGRESO">Ingreso</option><option value="EGRESO">Egreso</option><option value="AJUSTE_POSITIVO">Ajuste positivo</option><option value="AJUSTE_NEGATIVO">Ajuste negativo</option></select></div>
                <div class="field"><label for="quantity">Cantidad *</label><input id="quantity" name="quantity" type="number" min="0.001" step="0.001" required></div>
                <div class="field"><label for="observations">Observación *</label><textarea id="observations" name="observations" rows="4" maxlength="500" required placeholder="Motivo y detalle del movimiento"></textarea></div>
            </div>
            <button class="button button-primary button-full" type="submit">Confirmar movimiento</button>
        </section>
    </form>

    <form class="inventory-form" method="post" action="action.php">
        <input type="hidden" name="csrf_token" value="<?= e(Csrf::token()) ?>"><input type="hidden" name="action" value="transfer">
        <section class="form-section">
            <div class="form-section-heading"><span>02</span><div><h3>Transferencia</h3><p>Mueve existencias entre dos depósitos en una sola operación.</p></div></div>
            <div class="form-grid single-column">
                <div class="field"><label for="transferProduct">Producto *</label><select id="transferProduct" name="idProduct" required><option value="">Seleccionar</option><?php foreach ($products as $product): ?><option value="<?= (int) $product['idProduct'] ?>"><?= e((string) $product['code']) ?> · <?= e((string) $product['name']) ?></option><?php endforeach; ?></select></div>
                <div class="field"><label for="sourceWarehouse">Depósito de origen *</label><select id="sourceWarehouse" name="idWarehouse" required><?php foreach ($warehouses as $warehouse): ?><option value="<?= (int) $warehouse['idWarehouse'] ?>" <?= (int) $warehouse['idWarehouse'] === $warehouseId ? 'selected' : '' ?>><?= e((string) $warehouse['name']) ?></option><?php endforeach; ?></select></div>
                <div class="field"><label for="targetWarehouse">Depósito de destino *</label><select id="targetWarehouse" name="targetWarehouse" required><option value="">Seleccionar</option><?php foreach ($warehouses as $warehouse): ?><option value="<?= (int) $warehouse['idWarehouse'] ?>"><?= e((string) $warehouse['name']) ?></option><?php endforeach; ?></select></div>
                <div class="field"><label for="transferQuantity">Cantidad *</label><input id="transferQuantity" name="quantity" type="number" min="0.001" step="0.001" required></div>
                <div class="field"><label for="transferObservations">Observación *</label><textarea id="transferObservations" name="observations" rows="4" maxlength="500" required placeholder="Motivo o referencia de la transferencia"></textarea></div>
            </div>
            <button class="button button-secondary button-full" type="submit">Registrar transferencia</button>
        </section>
    </form>
</div>
<?php InventoryPage::end(); ?>
