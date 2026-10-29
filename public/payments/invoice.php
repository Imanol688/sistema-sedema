<?php
declare(strict_types=1);
require dirname(__DIR__, 2) . '/src/bootstrap.php';
use Sedema\Payments\PaymentContext;
use Sedema\Sales\SalesRepository;
use Sedema\Database;
$context = PaymentContext::boot();
$repository = new SalesRepository(Database::connection());
$id = (int) ($_GET['id'] ?? 0);
$order = $repository->order($id);
$invoice = $repository->invoiceByOrder($id);
$items = $repository->items($id);
$linkedRemit = $invoice ? $repository->selectedRemittance($id, 0, (string)$invoice['nroEnvio']) : null;
if (!$order || !$invoice) { http_response_code(404); exit('Factura no encontrada.'); }
?>
<!doctype html><html lang="es"><head><meta charset="utf-8"><title>Factura <?= e((string) $invoice['tipoFactura']) ?></title><link rel="stylesheet" href="../assets/css/styles.css"></head><body class="dashboard-page"><main class="print-document"><p>SEDEMA S.R.L.</p><h1>Factura <?= e((string) $invoice['tipoFactura']) ?></h1><p>PV <?= str_pad((string) $invoice['puntoVenta'], 4, '0', STR_PAD_LEFT) ?> · Nº <?= str_pad((string) $invoice['nroComprobante'], 8, '0', STR_PAD_LEFT) ?> · <?= e((string) $invoice['fechaEmision']) ?></p><p><strong>Cliente:</strong> <?= e($order['apellido'] . ', ' . $order['nombre']) ?> · <?= e((string) $order['cuitDNI']) ?></p><table class="inventory-table"><thead><tr><th>Artículo</th><th>Cantidad</th><th>Unitario</th><th>Subtotal</th></tr></thead><tbody><?php foreach ($items as $item): ?><tr><td><?= e((string) ($item['name'] ?? $item['descripcion'])) ?></td><td><?= number_format((float) $item['cantidadSolicitada'], 3, ',', '.') ?></td><td>$<?= number_format((float) $item['precioUnitario'], 2, ',', '.') ?></td><td>$<?= number_format((float) $item['subtotal'], 2, ',', '.') ?></td></tr><?php endforeach; ?></tbody></table><p>Descuentos: $<?= number_format((float) $invoice['descuentoAplicado'], 2, ',', '.') ?> · Recargos: $<?= number_format((float) $invoice['recargoAplicado'], 2, ',', '.') ?></p><h2>Total: $<?= number_format((float) $invoice['totalFacturado'], 2, ',', '.') ?></h2><?php if ($linkedRemit): ?><p>Remito asociado: <a href="remit.php?id=<?= $id ?>&amp;remit=<?= (int)$linkedRemit['idRemito'] ?>"><?= e((string)$linkedRemit['numeroRemito']) ?></a></p><?php endif; ?><p>Firma / aclaración: <?= e((string) ($invoice['firmayAclaracion'] ?? '________________________')) ?></p></main></body></html>
