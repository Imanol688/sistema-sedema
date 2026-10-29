<?php
declare(strict_types=1);
require dirname(__DIR__, 2) . '/src/bootstrap.php';

use Sedema\Authorization;
use Sedema\Csrf;
use Sedema\Sales\SalesContext;
use Sedema\Sales\SalesPage;
use Sedema\Sales\SalesService;

$context = SalesContext::boot();
$user = $context['user'];
$repository = $context['repository'];
$id = max(0, (int) ($_GET['id'] ?? 0));
$order = $id > 0 ? $repository->order($id) : null;
if ($id > 0 && !$order) {
    http_response_code(404);
    exit('Pedido no encontrado.');
}
$warehouses = $repository->warehouses();
$warehouseId = $order ? (int) $order['idWarehouse'] : max(0, (int) ($_GET['warehouse'] ?? ($warehouses[0]['idWarehouse'] ?? 0)));
$products = $warehouseId > 0 ? $repository->products($warehouseId) : [];
$clients = $repository->clients();
$selectedClientId = max(0, (int) ($_GET['client'] ?? 0));
$items = $order ? $repository->items($id) : [];
$adjustments = $order ? $repository->adjustments($id) : [];
$paid = $order ? $repository->approvedPayments($id) : 0.0;

SalesPage::begin($order ? 'Pedido #' . $id : 'Nuevo pedido', 'orders', $user);
?>
<section class="inventory-heading">
    <div><p class="section-kicker">Orden de venta</p><h2><?= $order ? 'Pedido #' . $id : 'Nuevo pedido' ?></h2><p><?= $order ? 'Consulta de la operación y estado.' : 'Seleccioná cliente, artículos y modalidad operativa.' ?></p></div>
    <a class="button button-secondary" href="index.php">Volver</a>
</section>

<?php if (!$order): ?>
<form class="inventory-panel inventory-form" method="post" action="action.php">
    <input type="hidden" name="csrf_token" value="<?= e(Csrf::token()) ?>">
    <input type="hidden" name="action" value="create-order">
    <input type="hidden" name="idWarehouse" value="<?= $warehouseId ?>">
    <div class="panel-title-row"><div><h3>Datos del pedido</h3><p>El precio se toma del catálogo de Inventario y el stock se valida al confirmar.</p></div></div>
    <div class="form-grid">
        <div class="field"><label for="client">Cliente *</label><select id="client" name="idCliente" required><option value="">Seleccionar</option><?php foreach ($clients as $client): ?><option value="<?= (int) $client['idCliente'] ?>" <?= $selectedClientId === (int) $client['idCliente'] ? 'selected' : '' ?>><?= e($client['apellido'] . ', ' . $client['nombre'] . ' · ' . $client['cuitDNI'] . ' · ' . $client['tipoCliente']) ?></option><?php endforeach; ?></select><?php if (Authorization::can($user, 'clientes.manage')): ?><a class="field-help-link" href="../clients/client.php?return=sales">+ Registrar nuevo cliente</a><?php endif; ?></div>
        <div class="field"><label for="warehouseSelector">Depósito de origen *</label><select id="warehouseSelector" data-sales-warehouse><?php foreach ($warehouses as $warehouse): ?><option value="<?= (int) $warehouse['idWarehouse'] ?>" <?= $warehouseId === (int) $warehouse['idWarehouse'] ? 'selected' : '' ?>><?= e((string) $warehouse['name']) ?></option><?php endforeach; ?></select></div>
        <div class="field"><label for="modality">Modalidad *</label><select id="modality" name="modalidadOperativa" required><?php foreach (SalesService::MODALITIES as $modality): ?><option value="<?= e($modality) ?>"><?= e(SalesService::modalityLabel($modality)) ?></option><?php endforeach; ?></select></div>
        <div class="field"><label for="address">Dirección de entrega</label><input id="address" name="direccionEntrega" maxlength="255" placeholder="Para entrega a domicilio"></div>
        <div class="field"><label for="date">Fecha/hora prevista</label><input id="date" name="fechaEntrega" type="datetime-local"></div>
        <div class="field"><label for="obs">Observaciones</label><input id="obs" name="observaciones" maxlength="500"></div>
    </div>

    <div class="panel-title-row"><div><h3>Artículos y stock</h3><p>Ingresá cantidad solamente en los productos que integran el pedido.</p></div></div>
    <div class="inventory-table-wrap"><table class="inventory-table"><thead><tr><th>Producto</th><th>Precio</th><th>Disponible</th><th>Cantidad</th></tr></thead><tbody>
        <?php foreach ($products as $product): ?>
        <tr><td><strong><?= e((string) $product['name']) ?></strong><span class="cell-meta"><?= e((string) $product['code']) ?></span></td><td>$<?= number_format((float) $product['salePrice'], 2, ',', '.') ?></td><td><?= number_format((float) $product['quantity'], 3, ',', '.') ?> <?= e((string) $product['symbol']) ?></td><td><input class="sales-qty" type="number" name="quantity[<?= (int) $product['idProduct'] ?>]" min="0" max="<?= e((string) $product['quantity']) ?>" step="0.001" value="0"></td></tr>
        <?php endforeach; ?>
    </tbody></table></div>

    <div class="panel-title-row"><div><h3>Ajustes / financiación</h3><p>Bonificaciones, descuentos por volumen y recargos de tarjeta.</p></div></div>
    <div class="form-grid">
        <div class="field"><label>Descuento comercial %</label><input type="number" min="0" max="100" step="0.01" name="discountPercent" value="0"></div>
        <div class="field"><label>Descuento por volumen %</label><input type="number" min="0" max="100" step="0.01" name="volumeDiscountPercent" value="0"></div>
        <div class="field"><label>Recargo tarjeta %</label><input type="number" min="0" max="100" step="0.01" name="cardSurchargePercent" value="0"></div>
        <div class="field"><label>Tipo de tarjeta</label><input name="tipoTarjeta" maxlength="50" placeholder="Visa, Mastercard, etc."></div>
    </div>
    <div class="form-actions"><button class="button button-primary" type="submit">Confirmar pedido</button></div>
</form>
<?php else: ?>
<section class="inventory-stats">
    <article><div><strong>$<?= number_format((float) $order['total'], 2, ',', '.') ?></strong><span>Total</span></div></article>
    <article><div><strong>$<?= number_format($paid, 2, ',', '.') ?></strong><span>Pagos aprobados</span></div></article>
    <article><div><strong><?= count($items) ?></strong><span>Ítems</span></div></article>
    <article><div><strong><?= e((string) $order['estado']) ?></strong><span>Estado</span></div></article>
</section>

<section class="inventory-panel">
    <div class="panel-title-row"><div><h3><?= e($order['apellido'] . ', ' . $order['nombre']) ?></h3><p><?= e((string) $order['cuitDNI']) ?> · <?= e(SalesService::modalityLabel((string) $order['modalidadOperativa'])) ?> · <?= e((string) ($order['warehouseName'] ?? '')) ?></p></div></div>
    <div class="inventory-table-wrap"><table class="inventory-table"><thead><tr><th>Producto</th><th>Cantidad</th><th>Precio</th><th>Subtotal</th></tr></thead><tbody><?php foreach ($items as $item): ?><tr><td><strong><?= e((string) ($item['name'] ?? $item['descripcion'])) ?></strong><span class="cell-meta"><?= e((string) ($item['code'] ?? '')) ?></span></td><td><?= number_format((float) $item['cantidadSolicitada'], 3, ',', '.') ?> <?= e((string) ($item['symbol'] ?? '')) ?></td><td>$<?= number_format((float) $item['precioUnitario'], 2, ',', '.') ?></td><td>$<?= number_format((float) $item['subtotal'], 2, ',', '.') ?></td></tr><?php endforeach; ?></tbody></table></div>
    <?php if ($adjustments): ?><div class="panel-title-row"><div><h3>Ajustes aplicados</h3></div></div><?php foreach ($adjustments as $adjustment): ?><p><strong><?= e((string) $adjustment['tipoAjuste']) ?>:</strong> <?= number_format((float) $adjustment['porcentaje'], 2, ',', '.') ?>% · $<?= number_format((float) $adjustment['montoCalculado'], 2, ',', '.') ?></p><?php endforeach; ?><?php endif; ?>
</section>

<?php if (Authorization::can($user, 'payments.view') && (string) $order['estado'] !== 'CANCELADO'): ?>
<div class="form-actions"><a class="button button-primary" href="../payments/collect.php?id=<?= $id ?>">Continuar a Pagos y cobranzas</a></div>
<?php endif; ?>

<?php if (Authorization::can($user, 'sales.manage') && !in_array((string) $order['estado'], ['FACTURADO', 'CANCELADO'], true)): ?>
<form method="post" action="action.php"><input type="hidden" name="csrf_token" value="<?= e(Csrf::token()) ?>"><input type="hidden" name="action" value="cancel-order"><input type="hidden" name="idPedido" value="<?= $id ?>"><button class="button button-secondary" type="submit">Cancelar pedido</button></form>
<?php endif; ?>
<?php endif; ?>
<?php SalesPage::end(); ?>
