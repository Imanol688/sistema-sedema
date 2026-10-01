<?php
declare(strict_types=1);
require dirname(__DIR__, 2) . '/src/bootstrap.php';
use Sedema\Sales\SalesContext;
$context = SalesContext::boot();
$repository = $context['repository'];
$id = (int) ($_GET['id'] ?? 0);
$order = $repository->order($id);
$remittance = $repository->remittanceByOrder($id);
$items = $repository->items($id);
if (!$order || !$remittance) { http_response_code(404); exit('Remito no encontrado.'); }
?>
<!doctype html><html lang="es"><head><meta charset="utf-8"><title>Remito <?= e((string) $remittance['numeroRemito']) ?></title><link rel="stylesheet" href="../assets/css/styles.css"></head><body class="dashboard-page"><main class="print-document"><p>SEDEMA S.R.L.</p><h1>Remito de salida <?= e((string) $remittance['numeroRemito']) ?></h1><p><strong>Cliente:</strong> <?= e($order['apellido'] . ', ' . $order['nombre']) ?> · <?= e((string) $order['cuitDNI']) ?></p><p><strong>Destino:</strong> <?= e((string) $remittance['destino']) ?> · <strong>Fecha:</strong> <?= e((string) $remittance['fechaHoraProgramada']) ?></p><p><strong>Transportista:</strong> <?= e((string) ($remittance['transportista'] ?? 'Sin asignar')) ?> · <strong>Vehículo:</strong> <?= e(trim(($remittance['matricula'] ?? '') . ' ' . ($remittance['modelo'] ?? '')) ?: 'Sin asignar') ?></p><table class="inventory-table"><thead><tr><th>Artículo</th><th>Cantidad</th></tr></thead><tbody><?php foreach ($items as $item): ?><tr><td><?= e((string) ($item['name'] ?? $item['descripcion'])) ?></td><td><?= number_format((float) $item['cantidadSolicitada'], 3, ',', '.') ?> <?= e((string) ($item['symbol'] ?? '')) ?></td></tr><?php endforeach; ?></tbody></table><p class="signature-line">Firma y aclaración: ______________________________</p></main></body></html>
