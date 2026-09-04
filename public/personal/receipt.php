<?php
declare(strict_types=1);
require dirname(__DIR__, 2) . '/src/bootstrap.php';

use Sedema\Authorization;
use Sedema\Personnel\PersonnelContext;
use Sedema\Personnel\PersonnelPage;

$context = PersonnelContext::boot();
$user = $context['user'];
Authorization::require($user, 'personal.payroll');
$repository = $context['repository'];
$service = $context['service'];
$id = max(0, (int) ($_GET['id'] ?? 0));
$payroll = $repository->payroll($id);
if (!$payroll) { http_response_code(404); exit('La liquidación no existe.'); }
$items = $repository->payrollItems($id);
PersonnelPage::begin('Recibo de haberes', 'payroll', $user);
?>
<section class="inventory-heading compact-heading no-print"><div><p class="section-kicker">Comprobante interno</p><h2>Recibo de haberes</h2><p><?= e((string) $payroll['numeroRecibo']) ?></p></div><div class="inventory-actions"><a class="button button-secondary" href="payrolls.php">← Volver</a><button class="button button-primary" type="button" data-print>Imprimir</button></div></section>
<section class="payroll-receipt">
    <header><div><span class="status-mark">SEDEMA S.R.L.</span><h2>Recibo de haberes</h2></div><div class="receipt-meta"><span>Número</span><strong><?= e((string) $payroll['numeroRecibo']) ?></strong><span>Período</span><strong><?= e((string) $payroll['periodo']) ?></strong></div></header>
    <div class="receipt-employee"><div><span>Empleado</span><strong><?= e((string) $payroll['apellido']) ?>, <?= e((string) $payroll['nombre']) ?></strong></div><div><span>DNI</span><strong><?= e((string) $payroll['dni']) ?></strong></div><div><span>Fecha</span><strong><?= e(date('d/m/Y H:i', strtotime((string) $payroll['fechaLiquidacion']))) ?></strong></div></div>
    <table><thead><tr><th>Concepto</th><th>Tipo</th><th>Importe</th></tr></thead><tbody><?php foreach ($items as $item): ?><tr><td><?= e((string) $item['concepto']) ?></td><td><?= $item['tipo'] === 'HABER' ? 'Haber' : 'Descuento' ?></td><td><?= e($service->formatMoney((float) $item['importe'])) ?></td></tr><?php endforeach; ?></tbody></table>
    <div class="receipt-totals"><div><span>Total haberes</span><strong><?= e($service->formatMoney((float) $payroll['totalHaberes'])) ?></strong></div><div><span>Total descuentos</span><strong><?= e($service->formatMoney((float) $payroll['totalDescuentos'])) ?></strong></div><div class="net"><span>Neto</span><strong><?= e($service->formatMoney((float) $payroll['montoNeto'])) ?></strong></div></div>
    <footer><span>Liquidado por</span><strong><?= e((string) ($payroll['liquidadorNombre'] ?: 'Administrador')) ?></strong><div class="signature-line">Firma / aclaración</div></footer>
</section>
<?php PersonnelPage::end(); ?>
