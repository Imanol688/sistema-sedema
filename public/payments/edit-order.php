<?php
declare(strict_types=1);
require dirname(__DIR__, 2) . '/src/bootstrap.php';
use Sedema\Authorization;
use Sedema\Csrf;
use Sedema\Database;
use Sedema\Payments\PaymentContext;
use Sedema\Payments\PaymentPage;
use Sedema\Sales\SalesRepository;
use Sedema\Sales\SalesService;
$c = PaymentContext::boot(); $user = $c['user'];
Authorization::require($user, 'payments.manage');
$repository = new SalesRepository(Database::connection());
$id = max(0, (int) ($_GET['id'] ?? 0));
$order = $repository->order($id);
if (!$order) { http_response_code(404); exit('Pedido no encontrado.'); }
$reason = $repository->correctionBlockedReason($id);
if ($reason) { PaymentPage::flash('error', $reason); redirect('collect.php?id=' . $id); }
$version = $repository->correctionVersion($id);
$warehouses = $repository->warehouses();
$warehouseId = max(0, (int) ($_GET['warehouse'] ?? $order['idWarehouse']));
$products = $repository->products($warehouseId);
$clients = $repository->clients();
$selectedClientId = (int) $order['idCliente'];
$quantities = [];
$originalItems = $repository->items($id);
foreach ($originalItems as $item) { $quantities[(int) $item['idInventoryProduct']] = (float) $item['cantidadSolicitada']; }
$discountPercent = 0; $volumeDiscountPercent = 0; $cardSurchargePercent = 0; $cardType = '';
foreach ($repository->adjustments($id) as $adjustment) {
    switch ((string) $adjustment['tipoAjuste']) {
        case 'DESCUENTO': $discountPercent += (float) $adjustment['porcentaje']; break;
        case 'DESCUENTO_VOLUMEN': $volumeDiscountPercent += (float) $adjustment['porcentaje']; break;
        case 'RECARGO_TARJETA': $cardSurchargePercent += (float) $adjustment['porcentaje']; $cardType = (string) ($adjustment['tipoTarjeta'] ?? ''); break;
    }
}
PaymentPage::begin('Modificar pedido #' . $id, 'payments', $user);
?>
<section class="inventory-heading"><div><h2>Modificar pedido #<?= $id ?></h2><p>Corregí cliente, productos, cantidades, modalidad, entrega y ajustes antes del cobro.</p></div><a class="button button-secondary" href="collect.php?id=<?= $id ?>">Volver</a></section>
<form class="inventory-panel inventory-form" method="post" action="action.php">
    <input type="hidden" name="csrf_token" value="<?= e(Csrf::token()) ?>">
    <input type="hidden" name="action" value="update-order">
    <input type="hidden" name="idPedido" value="<?= $id ?>"><input type="hidden" name="orderVersion" value="<?= e($version) ?>">
    <input type="hidden" name="idWarehouse" value="<?= $warehouseId ?>">
    <div class="panel-title-row"><div><h3>Datos del pedido</h3><p>Al guardar se recalculan precios, stock y total con el catálogo vigente.</p></div></div>
    <div class="form-grid">
        <div class="field"><label for="client">Cliente *</label><select id="client" name="idCliente" required><option value="">Seleccionar</option><?php foreach ($clients as $client): ?><option value="<?= (int) $client['idCliente'] ?>" <?= $selectedClientId === (int) $client['idCliente'] ? 'selected' : '' ?>><?= e($client['apellido'] . ', ' . $client['nombre'] . ' · ' . $client['cuitDNI'] . ' · ' . $client['tipoCliente']) ?></option><?php endforeach; ?></select></div>
        <div class="field"><label for="warehouseSelector">Depósito de origen *</label><select id="warehouseSelector" data-order-warehouse><?php foreach ($warehouses as $warehouse): ?><option value="<?= (int) $warehouse['idWarehouse'] ?>" <?= $warehouseId === (int) $warehouse['idWarehouse'] ? 'selected' : '' ?>><?= e((string) $warehouse['name']) ?></option><?php endforeach; ?></select></div>
        <div class="field"><label for="modality">Modalidad *</label><select id="modality" name="modalidadOperativa" required><?php foreach (SalesService::MODALITIES as $modality): ?><option value="<?= e($modality) ?>" <?= (string) $order['modalidadOperativa'] === $modality ? 'selected' : '' ?>><?= e(SalesService::modalityLabel($modality)) ?></option><?php endforeach; ?></select></div>
        <div class="field"><label for="address">Dirección de entrega</label><input id="address" name="direccionEntrega" value="<?= e((string) ($order['direccionEntrega'] ?? '')) ?>" maxlength="255" placeholder="Para entrega a domicilio"></div>
        <div class="field"><label for="date">Fecha/hora prevista</label><input id="date" name="fechaEntrega" value="<?= e(!empty($order['fechaEntrega']) ? date('Y-m-d\TH:i', strtotime((string) $order['fechaEntrega'])) : '') ?>" type="datetime-local"></div>
        <div class="field"><label for="obs">Observaciones</label><input id="obs" name="observaciones" value="<?= e((string) ($order['observaciones'] ?? '')) ?>" maxlength="500"></div>
    </div>

    <div class="panel-title-row"><div><h3>Artículos y stock</h3><p>Ingresá cantidad solamente en los productos que integran el pedido.</p></div></div>
    <div class="inventory-table-wrap"><table class="inventory-table"><thead><tr><th>Producto</th><th>Precio</th><th>Disponible</th><th>Cantidad</th></tr></thead><tbody>
        <?php foreach ($products as $product): ?>
        <tr><td><strong><?= e((string) $product['name']) ?></strong><span class="cell-meta"><?= e((string) $product['code']) ?></span></td><td>$<?= number_format((float) $product['salePrice'], 2, ',', '.') ?></td><td><?= number_format((float) $product['quantity'], 3, ',', '.') ?> <?= e((string) $product['symbol']) ?></td><td><input class="sales-qty" type="number" name="quantity[<?= (int) $product['idProduct'] ?>]" min="0" max="<?= e((string) $product['quantity']) ?>" step="0.001" value="<?= e((string) ($quantities[(int) $product['idProduct']] ?? 0)) ?>"></td></tr>
        <?php endforeach; ?>
    </tbody></table></div>

    <div class="panel-title-row"><div><h3>Ajustes / financiación</h3><p>Bonificaciones, descuentos por volumen y recargos de tarjeta.</p></div></div>
    <div class="form-grid">
        <div class="field"><label>Descuento comercial %</label><input type="number" min="0" max="100" step="0.01" name="discountPercent" value="<?= e((string) $discountPercent) ?>"></div>
        <div class="field"><label>Descuento por volumen %</label><input type="number" min="0" max="100" step="0.01" name="volumeDiscountPercent" value="<?= e((string) $volumeDiscountPercent) ?>"></div>
        <div class="field"><label>Recargo tarjeta %</label><input type="number" min="0" max="100" step="0.01" name="cardSurchargePercent" value="<?= e((string) $cardSurchargePercent) ?>"></div>
        <div class="field"><label>Tipo de tarjeta</label><input name="tipoTarjeta" value="<?= e($cardType) ?>" maxlength="50" placeholder="Visa, Mastercard, etc."></div>
    </div>
    <section class="form-section"><h3>Revisión de artículos originales</h3><p>Si algún producto original ya no está activo o pertenece al catálogo histórico, no aparecerá en la selección y se quitará al guardar. Revisá este listado antes de confirmar.</p><ul><?php foreach ($originalItems as $original): ?><li><?= e((string) ($original['name'] ?? $original['descripcion'])) ?>: <?= number_format((float) $original['cantidadSolicitada'], 3, ',', '.') ?></li><?php endforeach; ?></ul><label><input type="checkbox" name="confirmRemovedItems" value="1"> Confirmo quitar los artículos originales que quedaron sin cantidad en la selección.</label></section>
    <div class="form-actions"><button class="button button-primary" type="submit">Guardar cambios del pedido</button></div>
</form>

<script src="../assets/js/payment-orders.js" defer></script>
<?php PaymentPage::end(); ?>
