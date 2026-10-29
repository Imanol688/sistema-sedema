<?php
declare(strict_types=1);
require dirname(__DIR__, 2) . '/src/bootstrap.php';
use Sedema\Authorization;
use Sedema\Csrf;
use Sedema\Database;
use Sedema\Payments\PaymentContext;
use Sedema\Payments\PaymentPage;
use Sedema\Sales\SalesRepository;

$context = PaymentContext::boot();
$user = $context['user'];
$repository = new SalesRepository(Database::connection());
$id = max(0, (int) ($_GET['id'] ?? 0));
$order = $repository->order($id);
if (!$order) { http_response_code(404); exit('Pedido no encontrado.'); }
$paid = $repository->settledPayments($id);
$settled = $paid > 0 && $paid + 0.005 >= (float) $order['total'];
$remittance = $repository->remittanceByOrder($id);
$invoice = $repository->invoiceByOrder($id);
$canIssueRemit = !$remittance || in_array((string)$remittance['despachoEstado'], ['CANCELADO','DEVUELTO'], true);
$remitHistory = $repository->remittances($id);
PaymentPage::begin('Remito y factura del pedido #' . $id, 'payments', $user);
?>
<section class="inventory-heading"><div><h2>Comprobantes del pedido #<?= $id ?></h2><p><?= e($order['apellido'] . ', ' . $order['nombre']) ?> · <?= e((string) $order['cuitDNI']) ?></p><p>Pagos efectivos aprobados: $<?= number_format($paid, 2, ',', '.') ?> / $<?= number_format((float) $order['total'], 2, ',', '.') ?></p></div><a class="button button-secondary" href="collect.php?id=<?= $id ?>">Volver al pedido y cobros</a></section>
<?php if (!$settled && (string) $order['estado'] !== 'CANCELADO'): ?>
<div class="alert alert-error"><p>Primero completá el pago del pedido. Los cobros pendientes, rechazados y las imputaciones a cuenta corriente no habilitan nuevos comprobantes.</p></div>
<?php endif; ?>
<?php if ((string) $order['estado'] !== 'CANCELADO'): ?>
<?php if ($remittance && (int)$remittance['itemCount']===0): ?><div class="alert alert-error"><p>El remito histórico no tiene artículos vinculados al despacho. Revisalo en Logística; si nunca hubo salida, cancelá ese despacho y programá uno nuevo con su detalle. Se conserva el comprobante histórico.</p></div><?php endif; ?>
<section class="inventory-form-columns">
    <article class="inventory-panel"><div class="panel-title-row"><div><h3>Remito de salida</h3></div></div>
        <?php if ($remittance && !$canIssueRemit): ?><p><strong><?= e((string) $remittance['numeroRemito']) ?></strong> · <?= e((string) $remittance['fechaHoraProgramada']) ?></p><a class="button button-secondary" href="remit.php?id=<?= $id ?>" target="_blank">Ver / imprimir remito</a>
        <?php elseif ($settled && Authorization::can($user, 'payments.manage')): ?><form method="post" action="action.php"><input type="hidden" name="csrf_token" value="<?= e(Csrf::token()) ?>"><input type="hidden" name="action" value="issue-remit"><input type="hidden" name="operation_key" value="<?= e(\Sedema\Inventory\OperationGuard::newKey()) ?>"><input type="hidden" name="idPedido" value="<?= $id ?>"><div class="field"><label>Fecha y hora *</label><input type="datetime-local" name="fechaHoraProgramada" required></div><div class="field"><label>Destino *</label><input name="destino" value="<?= e((string) ($order['direccionEntrega'] ?? $order['clientAddress'])) ?>" required></div><?php if ($order['modalidadOperativa'] === 'ACOPIO'): ?><div class="field"><label>Modalidad de salida</label><select name="modalidad"><option value="EN_CORRALON">Retiro en corralón</option><option value="ENTREGA_DOMICILIO">Entrega a domicilio</option></select></div><?php endif; ?><div class="field"><label>Transportista</label><input name="transportista"></div><div class="field"><label>Vehículo</label><select name="idVehiculo"><option value="0">Sin asignar</option><?php foreach ($repository->vehicles() as $vehicle): ?><option value="<?= (int) $vehicle['idVehiculo'] ?>"><?= e($vehicle['matricula'] . ' · ' . $vehicle['modelo'] . ' · ' . $vehicle['estado']) ?></option><?php endforeach; ?></select></div><div class="field"><label>Sucursal de origen</label><input name="sucursalOrigen" value="SEDEMA S.R.L."></div><button class="button button-primary" type="submit">Emitir remito</button></form>
        <?php else: ?><p>La emisión requiere pago completo aprobado y permiso de gestión de pagos.</p><?php endif; ?>
    </article>

    <article class="inventory-panel"><div class="panel-title-row"><div><h3>Factura</h3><p>Solo disponible para pedidos pagados.</p></div></div>
        <?php if ($invoice): ?><p><strong>Factura <?= e((string) $invoice['tipoFactura']) ?></strong> · PV <?= (int) $invoice['puntoVenta'] ?> · Nº <?= (int) $invoice['nroComprobante'] ?></p><a class="button button-secondary" href="invoice.php?id=<?= $id ?>" target="_blank">Ver / imprimir factura</a>
        <?php elseif ($settled && $remittance && (int)$remittance['itemCount']>0 && !$canIssueRemit && Authorization::can($user, 'payments.manage')): ?><p>Pagado: $<?= number_format($paid, 2, ',', '.') ?> / $<?= number_format((float) $order['total'], 2, ',', '.') ?></p><form method="post" action="action.php"><input type="hidden" name="csrf_token" value="<?= e(Csrf::token()) ?>"><input type="hidden" name="action" value="issue-invoice"><input type="hidden" name="idPedido" value="<?= $id ?>"><div class="form-grid"><div class="field"><label>Tipo</label><select name="tipoFactura"><option>A</option><option selected>B</option><option>C</option></select></div><div class="field"><label>Punto de venta</label><input type="number" name="puntoVenta" min="1" value="1" required></div><div class="field"><label>Nº comprobante</label><input value="Automático, aleatorio y único" readonly aria-label="Número generado al emitir"></div></div><div class="field"><label>Firma / aclaración</label><input name="firmayAclaracion" maxlength="150"></div><button class="button button-primary" type="submit" <?= !$settled ? 'disabled' : '' ?>>Emitir factura</button></form>
        <?php else: ?><p>Primero completá el pago y emití el remito. La factura requiere permiso de gestión de pagos.</p><?php endif; ?>
    </article>
</section>
<?php endif; ?>

<?php if ((string) $order['estado'] === 'CANCELADO'): ?><p>El pedido está cancelado. No se pueden emitir nuevos comprobantes.</p><?php endif; ?>
<?php if ($remitHistory): ?><section class="inventory-panel"><h3>Remitos e historial de despachos</h3><?php foreach ($remitHistory as $rem): ?><p><a href="remit.php?id=<?= $id ?>&amp;remit=<?= (int)$rem['idRemito'] ?>" target="_blank"><?= e($rem['numeroRemito']) ?></a> · <?= e($rem['despachoEstado']) ?></p><?php endforeach; ?></section><?php endif; ?>
<?php PaymentPage::end(); ?>
