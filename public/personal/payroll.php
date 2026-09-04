<?php
declare(strict_types=1);
require dirname(__DIR__, 2) . '/src/bootstrap.php';

use Sedema\Authorization;
use Sedema\Csrf;
use Sedema\Personnel\PersonnelContext;
use Sedema\Personnel\PersonnelPage;

$context = PersonnelContext::boot();
$user = $context['user'];
Authorization::require($user, 'personal.payroll');
$repository = $context['repository'];
$service = $context['service'];
$employees = $repository->activeEmployees();
$currentPeriod = date('Y-m');

PersonnelPage::begin('Nueva liquidación', 'payroll', $user);
?>
<section class="inventory-heading compact-heading"><div><p class="section-kicker">Haberes</p><h2>Nueva liquidación</h2><p>El sueldo base se toma del legajo y queda guardado como valor histórico del recibo.</p></div><a class="back-button" href="payrolls.php">← Volver a haberes</a></section>
<?php if (!$employees): ?><section class="empty-modules"><h3>No hay empleados activos</h3><p>Registrá o reactivá un legajo antes de generar liquidaciones.</p></section><?php else: ?>
<form class="inventory-form" method="post" action="action.php">
    <input type="hidden" name="csrf_token" value="<?= e(Csrf::token()) ?>"><input type="hidden" name="action" value="create-payroll">
    <section class="form-section"><div class="form-section-heading"><span>01</span><div><h3>Período y empleado</h3><p>Seleccioná el trabajador que se liquidará.</p></div></div><div class="form-grid">
        <div class="field field-wide"><label for="idEmpleado">Empleado *</label><select id="idEmpleado" name="idEmpleado" required><option value="">Seleccionar</option><?php foreach ($employees as $employee): ?><option value="<?= (int) $employee['idEmpleado'] ?>"><?= e((string) $employee['apellido']) ?>, <?= e((string) $employee['nombre']) ?> · DNI <?= e((string) $employee['dni']) ?> · <?= e($service->formatMoney((float) $employee['sueldoBase'])) ?></option><?php endforeach; ?></select></div>
        <div class="field"><label for="periodo">Período *</label><input id="periodo" name="periodo" type="month" required value="<?= e($currentPeriod) ?>"></div>
    </div></section>
    <section class="form-section"><div class="form-section-heading"><span>02</span><div><h3>Conceptos</h3><p>El sueldo base se agrega automáticamente. Los demás conceptos son opcionales.</p></div></div><div class="inventory-form-columns">
        <div class="field"><label for="haberes">Haberes adicionales</label><textarea id="haberes" name="haberes" rows="7" placeholder="Presentismo=50000&#10;Horas extra=35000"></textarea><span class="password-hint">Un concepto por línea: Concepto=Importe.</span></div>
        <div class="field"><label for="descuentos">Descuentos</label><textarea id="descuentos" name="descuentos" rows="7" placeholder="Adelanto=25000"></textarea><span class="password-hint">Un concepto por línea: Concepto=Importe.</span></div>
    </div></section>
    <div class="form-footer"><a class="button button-secondary" href="payrolls.php">Cancelar</a><button class="button button-primary" type="submit">Procesar liquidación</button></div>
</form>
<?php endif; ?>
<?php PersonnelPage::end(); ?>
