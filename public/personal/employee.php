<?php
declare(strict_types=1);
require dirname(__DIR__, 2) . '/src/bootstrap.php';

use Sedema\Authorization;
use Sedema\Csrf;
use Sedema\Personnel\PersonnelContext;
use Sedema\Personnel\PersonnelPage;

$context = PersonnelContext::boot();
$user = $context['user'];
Authorization::require($user, 'personal.manage');
$repository = $context['repository'];
$employeeId = max(0, (int) ($_GET['id'] ?? 0));
$employee = $employeeId > 0 ? $repository->employee($employeeId) : null;
if ($employeeId > 0 && !$employee) {
    http_response_code(404);
    exit('El empleado no existe.');
}

PersonnelPage::begin($employee ? 'Editar legajo' : 'Nuevo empleado', 'employees', $user);
?>
<section class="inventory-heading compact-heading">
    <div><p class="section-kicker">Legajo de personal</p><h2><?= $employee ? 'Editar empleado' : 'Nuevo empleado' ?></h2><p>Datos requeridos para la administración interna y la liquidación de haberes.</p></div>
    <a class="back-button" href="index.php">← Volver a legajos</a>
</section>

<form class="inventory-form" method="post" action="action.php">
    <input type="hidden" name="csrf_token" value="<?= e(Csrf::token()) ?>">
    <input type="hidden" name="action" value="save-employee">
    <input type="hidden" name="idEmpleado" value="<?= $employeeId ?>">
    <section class="form-section">
        <div class="form-section-heading"><span>01</span><div><h3>Identificación</h3><p>Información principal del trabajador.</p></div></div>
        <div class="form-grid">
            <div class="field"><label for="nombre">Nombre *</label><input id="nombre" name="nombre" maxlength="100" required value="<?= e((string) ($employee['nombre'] ?? '')) ?>"></div>
            <div class="field"><label for="apellido">Apellido *</label><input id="apellido" name="apellido" maxlength="100" required value="<?= e((string) ($employee['apellido'] ?? '')) ?>"></div>
            <div class="field"><label for="dni">DNI *</label><input id="dni" name="dni" maxlength="20" required value="<?= e((string) ($employee['dni'] ?? '')) ?>"></div>
            <div class="field"><label for="telefono">Teléfono</label><input id="telefono" name="telefono" maxlength="50" value="<?= e((string) ($employee['telefono'] ?? '')) ?>"></div>
            <div class="field"><label for="sueldoBase">Sueldo base *</label><input id="sueldoBase" name="sueldoBase" type="number" min="0" step="0.01" required value="<?= e(number_format((float) ($employee['sueldoBase'] ?? 0), 2, '.', '')) ?>"></div>
        </div>
    </section>
    <?php if ($employee && $employee['idUsuario']): ?>
    <section class="form-section">
        <div class="form-section-heading"><span>02</span><div><h3>Acceso vinculado</h3><p>El usuario se administra de forma independiente para conservar responsabilidades desacopladas.</p></div></div>
        <div class="personnel-link-card"><div><span>Usuario</span><strong><?= e((string) $employee['username']) ?></strong></div><div><span>Rol</span><strong><?= e((string) $employee['roles']) ?></strong></div><div><span>Estado</span><strong><?= (int) $employee['habilitado'] === 1 ? 'Habilitado' : 'Deshabilitado' ?></strong></div></div>
    </section>
    <?php endif; ?>
    <div class="form-footer"><a class="button button-secondary" href="index.php">Cancelar</a><button class="button button-primary" type="submit">Guardar legajo</button></div>
</form>

<?php if ($employee): ?>
<section class="danger-zone">
    <div><h3><?= (int) $employee['activo'] === 1 ? 'Dar de baja el legajo' : 'Reactivar el legajo' ?></h3><p>La baja conserva historial y datos relacionados. Un usuario vinculado no podrá iniciar sesión mientras el legajo permanezca inactivo.</p></div>
    <form method="post" action="action.php" data-confirm="<?= (int) $employee['activo'] === 1 ? '¿Dar de baja este empleado?' : '¿Reactivar este empleado?' ?>">
        <input type="hidden" name="csrf_token" value="<?= e(Csrf::token()) ?>"><input type="hidden" name="action" value="toggle-employee"><input type="hidden" name="idEmpleado" value="<?= $employeeId ?>"><input type="hidden" name="active" value="<?= (int) $employee['activo'] === 1 ? '0' : '1' ?>">
        <button class="button <?= (int) $employee['activo'] === 1 ? 'button-danger' : 'button-primary' ?>" type="submit"><?= (int) $employee['activo'] === 1 ? 'Dar de baja' : 'Reactivar' ?></button>
    </form>
</section>
<?php endif; ?>
<?php PersonnelPage::end(); ?>
