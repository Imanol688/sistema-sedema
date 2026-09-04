<?php
declare(strict_types=1);
require dirname(__DIR__, 2) . '/src/bootstrap.php';

use Sedema\Authorization;
use Sedema\Personnel\PersonnelContext;
use Sedema\Personnel\PersonnelPage;

$context = PersonnelContext::boot();
$user = $context['user'];
$repository = $context['repository'];
$service = $context['service'];
$search = mb_substr(trim((string) ($_GET['search'] ?? '')), 0, 100);
$status = (string) ($_GET['status'] ?? 'active');
if (!in_array($status, ['active', 'inactive', 'all'], true)) {
    $status = 'active';
}
$summary = $repository->summary();
$employees = $repository->employees($search, $status);

PersonnelPage::begin('Legajos', 'employees', $user);
?>
<section class="inventory-heading">
    <div><p class="section-kicker">Administración de personal</p><h2>Legajos</h2><p>Altas, modificaciones y bajas lógicas de trabajadores.</p></div>
    <?php if (Authorization::can($user, 'personal.manage')): ?><a class="button button-primary" href="employee.php"><?= PersonnelPage::icon('plus') ?> Nuevo empleado</a><?php endif; ?>
</section>

<form class="inventory-toolbar personnel-toolbar" method="get">
    <div class="field compact-field toolbar-search"><label for="search">Buscar empleado</label><input id="search" name="search" type="search" value="<?= e($search) ?>" placeholder="Nombre, apellido, DNI o usuario"></div>
    <div class="field compact-field"><label for="status">Estado</label><select id="status" name="status"><option value="active" <?= $status === 'active' ? 'selected' : '' ?>>Activos</option><option value="inactive" <?= $status === 'inactive' ? 'selected' : '' ?>>Dados de baja</option><option value="all" <?= $status === 'all' ? 'selected' : '' ?>>Todos</option></select></div>
    <button class="button button-filter" type="submit">Aplicar</button>
</form>

<section class="inventory-stats" aria-label="Resumen de personal">
    <article><span class="stat-icon"><?= PersonnelPage::icon('badge') ?></span><div><strong><?= $summary['total'] ?></strong><span>Legajos registrados</span></div></article>
    <article><span class="stat-icon"><?= PersonnelPage::icon('active') ?></span><div><strong><?= $summary['active'] ?></strong><span>Personal activo</span></div></article>
    <article class="<?= $summary['inactive'] > 0 ? 'is-warning' : '' ?>"><span class="stat-icon"><?= PersonnelPage::icon('inactive') ?></span><div><strong><?= $summary['inactive'] ?></strong><span>Dados de baja</span></div></article>
    <article><span class="stat-icon"><?= PersonnelPage::icon('employees') ?></span><div><strong><?= $summary['withUser'] ?></strong><span>Con usuario vinculado</span></div></article>
</section>

<section class="inventory-panel">
    <div class="panel-title-row"><div><h3>Personal</h3><p><?= count($employees) ?> resultados</p></div></div>
    <?php if (!$employees): ?>
        <div class="table-empty"><h4>No se encontraron empleados</h4><p>Revisá los filtros o registrá el primer legajo.</p></div>
    <?php else: ?>
        <div class="inventory-table-wrap"><table class="inventory-table personnel-table">
            <thead><tr><th>Empleado</th><th>DNI</th><th>Contacto</th><th>Sueldo base</th><th>Acceso</th><th>Estado</th><th><span class="sr-only">Acciones</span></th></tr></thead>
            <tbody>
            <?php foreach ($employees as $employee): ?>
                <tr>
                    <td><strong><?= e((string) $employee['apellido']) ?>, <?= e((string) $employee['nombre']) ?></strong><span class="cell-meta">Legajo #<?= (int) $employee['idEmpleado'] ?></span></td>
                    <td><?= e((string) $employee['dni']) ?></td>
                    <td><?= e((string) ($employee['telefono'] ?: 'Sin teléfono')) ?></td>
                    <td class="quantity-cell"><strong><?= e($service->formatMoney((float) $employee['sueldoBase'])) ?></strong></td>
                    <td><?php if ($employee['idUsuario']): ?><strong><?= e((string) $employee['username']) ?></strong><span class="cell-meta"><?= e((string) $employee['roles']) ?></span><?php else: ?><span class="cell-meta">Sin usuario</span><?php endif; ?></td>
                    <td><span class="stock-badge <?= (int) $employee['activo'] === 1 ? 'stock-ok' : 'stock-empty' ?>"><?= (int) $employee['activo'] === 1 ? 'Activo' : 'Baja' ?></span></td>
                    <td class="table-actions"><?php if (Authorization::can($user, 'personal.manage')): ?><a href="employee.php?id=<?= (int) $employee['idEmpleado'] ?>">Editar</a><?php endif; ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table></div>
    <?php endif; ?>
</section>
<?php PersonnelPage::end(); ?>
