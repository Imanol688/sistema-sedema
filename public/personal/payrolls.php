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
$period = trim((string) ($_GET['period'] ?? ''));
if ($period !== '' && !preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $period)) {
    $period = '';
}
$payrolls = $repository->payrolls($period);

PersonnelPage::begin('Haberes', 'payroll', $user);
?>
<section class="inventory-heading">
    <div><p class="section-kicker">Liquidación interna</p><h2>Haberes</h2><p>Liquidaciones históricas y recibos emitidos.</p></div>
    <a class="button button-primary" href="payroll.php"><?= PersonnelPage::icon('plus') ?> Nueva liquidación</a>
</section>
<form class="inventory-toolbar payroll-toolbar" method="get"><div class="field compact-field"><label for="period">Período</label><input id="period" name="period" type="month" value="<?= e($period) ?>"></div><button class="button button-filter" type="submit">Aplicar</button><?php if ($period !== ''): ?><a class="clear-filter" href="payrolls.php">Limpiar</a><?php endif; ?></form>
<section class="inventory-panel">
    <div class="panel-title-row"><div><h3>Liquidaciones</h3><p><?= count($payrolls) ?> registros</p></div></div>
    <?php if (!$payrolls): ?><div class="table-empty"><h4>Sin liquidaciones</h4><p>No hay recibos registrados para el filtro actual.</p></div><?php else: ?>
    <div class="inventory-table-wrap"><table class="inventory-table payroll-table"><thead><tr><th>Período</th><th>Empleado</th><th>Haberes</th><th>Descuentos</th><th>Neto</th><th>Recibo</th><th></th></tr></thead><tbody>
    <?php foreach ($payrolls as $payroll): ?><tr>
        <td><strong><?= e((string) $payroll['periodo']) ?></strong></td>
        <td><strong><?= e((string) $payroll['apellido']) ?>, <?= e((string) $payroll['nombre']) ?></strong><span class="cell-meta">DNI <?= e((string) $payroll['dni']) ?></span></td>
        <td><?= e($service->formatMoney((float) $payroll['totalHaberes'])) ?></td>
        <td><?= e($service->formatMoney((float) $payroll['totalDescuentos'])) ?></td>
        <td class="quantity-cell"><strong><?= e($service->formatMoney((float) $payroll['montoNeto'])) ?></strong></td>
        <td><?= e((string) $payroll['numeroRecibo']) ?></td>
        <td class="table-actions"><a href="receipt.php?id=<?= (int) $payroll['idLiquidacion'] ?>">Ver recibo</a></td>
    </tr><?php endforeach; ?>
    </tbody></table></div><?php endif; ?>
</section>
<?php PersonnelPage::end(); ?>
