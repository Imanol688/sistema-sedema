<?php
declare(strict_types=1);
require dirname(__DIR__, 2) . '/src/bootstrap.php';

use Sedema\Suppliers\SupplierContext;

$context = SupplierContext::boot();
$id = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
if ($id === false || $id === null) {
    http_response_code(404);
    exit('Remito inexistente.');
}
$repository = $context['repository'];
$order = $repository->order($id);
if (!$order) {
    http_response_code(404);
    exit('Remito inexistente.');
}
$supplier = $repository->supplier((int) $order['idProveedor']);
$lines = $repository->lines($id);
$statuses = [
    'BORRADOR' => 'Borrador', 'PENDIENTE' => 'Pendiente', 'EMITIDA' => 'Emitido',
    'RECEPCION_PARCIAL' => 'Recepción parcial', 'RECEPCION_TOTAL' => 'Recepción total',
    'CANCELADA' => 'Cancelado',
];
$total = 0.0;
$quantity = static fn (string $value): string => rtrim(rtrim(number_format((float) $value, 3, ',', '.'), '0'), ',');
$money = static fn (float $value): string => '$ ' . number_format($value, 2, ',', '.');
header('Cache-Control: private, no-store');
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Remito de proveedor <?= e((string) $order['numeroRemito']) ?> | SEDEMA</title>
    <link rel="stylesheet" href="../assets/css/styles.css">
    <link rel="stylesheet" href="remit-print.css">
    <script src="remit-print.js" defer></script>
</head>
<body class="supplier-remit-page">
<nav class="supplier-remit-actions" aria-label="Acciones del remito">
    <a class="button button-secondary" href="detail.php?id=<?= (int) $id ?>">Volver al detalle</a>
    <button class="button button-primary" type="button" data-print-remit>Imprimir / guardar PDF</button>
</nav>
<main class="supplier-remit">
    <header class="supplier-remit-heading">
        <div><p class="supplier-remit-brand">SEDEMA S.R.L.</p><h1>Remito de proveedor</h1><p>Registro de mercadería del proveedor</p></div>
        <div class="supplier-remit-number"><span>Número de remito</span><strong><?= e((string) $order['numeroRemito']) ?></strong></div>
    </header>
    <section class="supplier-remit-meta" aria-label="Datos del documento">
        <div><span>Fecha de emisión</span><strong><?= e(date('d/m/Y', strtotime((string) $order['fechaEmision']))) ?></strong></div>
        <div><span>Registro SEDEMA</span><strong>#<?= (int) $id ?></strong></div>
        <div><span>Estado de recepción</span><strong><?= e($statuses[(string) $order['estado']] ?? (string) $order['estado']) ?></strong></div>
    </section>
    <?php if ($order['estado'] === 'CANCELADA'): ?>
        <p class="supplier-remit-status">REMITO CANCELADO</p>
    <?php elseif ($order['estado'] === 'BORRADOR'): ?>
        <p class="supplier-remit-status">BORRADOR</p>
    <?php endif; ?>
    <section class="supplier-remit-parties">
        <div><h2>Proveedor</h2><p><strong><?= e((string) $order['razonSocial']) ?></strong></p><p>CUIT: <?= e((string) ($supplier['cuit'] ?? '')) ?></p><p>Contacto: <?= e((string) (($supplier['contacto'] ?? '') ?: 'Sin contacto registrado')) ?></p></div>
        <div><h2>Destino</h2><p><strong>SEDEMA S.R.L.</strong></p><p>Corralón de materiales de construcción.</p></div>
    </section>
    <table class="supplier-remit-items">
        <thead><tr><th scope="col">Código</th><th scope="col">Descripción</th><th scope="col" class="numeric">Cantidad</th><th scope="col">Unidad</th><th scope="col" class="numeric">Precio unitario</th><th scope="col" class="numeric">Subtotal</th></tr></thead>
        <tbody>
        <?php foreach ($lines as $line):
            $subtotal = round((float) $line['cantidadSolicitada'] * (float) $line['precioUnitario'], 2);
            $total += $subtotal;
        ?>
            <tr><td><?= e((string) $line['code']) ?></td><td><?= e((string) $line['name']) ?></td><td class="numeric"><?= e($quantity((string) $line['cantidadSolicitada'])) ?></td><td><?= e((string) $line['symbol']) ?></td><td class="numeric"><?= e($money((float) $line['precioUnitario'])) ?></td><td class="numeric"><?= e($money($subtotal)) ?></td></tr>
        <?php endforeach; ?>
        <?php if (!$lines): ?><tr><td colspan="6">El remito no contiene productos registrados.</td></tr><?php endif; ?>
        </tbody>
    </table>
    <div class="supplier-remit-total"><span>Total del remito</span><strong><?= e($money($total)) ?></strong></div>
    <p class="supplier-remit-note">Las cantidades corresponden al remito registrado. Las recepciones parciales o totales se registran por separado.</p>
    <footer class="supplier-remit-signatures"><div>Entregó / aclaración</div><div>Recibió / aclaración</div><div>Fecha de recepción</div></footer>
</main>
</body>
</html>
