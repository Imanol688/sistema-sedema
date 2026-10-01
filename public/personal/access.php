<?php
declare(strict_types=1);
require dirname(__DIR__, 2) . '/src/bootstrap.php';

use Sedema\Access\UserAccessRepository;
use Sedema\Authorization;
use Sedema\Csrf;
use Sedema\Database;
use Sedema\Personnel\PersonnelContext;
use Sedema\Personnel\PersonnelPage;

$context = PersonnelContext::boot();
$user = $context['user'];
Authorization::require($user, 'personal.access');
$repository = new UserAccessRepository(Database::connection());
$search = mb_substr(trim((string) ($_GET['search'] ?? '')), 0, 100);
$accounts = $repository->accounts($search);

PersonnelPage::begin('Usuarios y accesos', 'access', $user);
?>
<section class="inventory-heading">
    <div><p class="section-kicker">Seguridad interna</p><h2>Usuarios y accesos</h2><p>Creación de cuentas, roles, permisos, correo de acceso y estado del primer ingreso.</p></div>
    <a class="button button-primary" href="account.php"><?= PersonnelPage::icon('plus') ?> Crear cuenta</a>
</section>

<form class="inventory-toolbar access-toolbar" method="get">
    <div class="field compact-field toolbar-search"><label for="search">Buscar usuario</label><input id="search" name="search" type="search" value="<?= e($search) ?>" placeholder="Nombre, DNI, correo, usuario o rol"></div>
    <button class="button button-filter" type="submit">Buscar</button>
</form>

<section class="inventory-panel">
    <div class="panel-title-row"><div><h3>Cuentas internas</h3><p><?= count($accounts) ?> resultados</p></div></div>
    <?php if (!$accounts): ?>
        <div class="table-empty"><h4>No se encontraron cuentas</h4><p>Creá la primera cuenta o modificá el criterio de búsqueda.</p></div>
    <?php else: ?>
        <form method="post" action="action.php" onsubmit="return confirm('Las cuentas seleccionadas dejarán de poder ingresar al sistema. Sus legajos se conservarán. ¿Continuar?');">
            <input type="hidden" name="csrf_token" value="<?= e(Csrf::token()) ?>">
            <input type="hidden" name="action" value="delete-accounts">
            <div class="bulk-actions" style="display:flex;justify-content:flex-end;margin-bottom:12px">
                <button class="button button-danger" type="submit">Eliminar seleccionados</button>
            </div>
            <div class="inventory-table-wrap"><table class="inventory-table personnel-table access-table">
                <thead><tr><th style="width:44px"><input type="checkbox" id="select-all-users" aria-label="Seleccionar todos"></th><th>Empleado</th><th>Correo / usuario</th><th>Rol de acceso</th><th>Acceso</th><th>Estado</th><th><span class="sr-only">Acciones</span></th></tr></thead>
                <tbody>
                <?php foreach ($accounts as $account): ?>
                    <tr>
                        <td><?php if ((int) $account['idUsuario'] !== (int) $user['id']): ?><input class="user-select" type="checkbox" name="user_ids[]" value="<?= (int) $account['idUsuario'] ?>" aria-label="Seleccionar cuenta de <?= e((string) $account['nombre'] . ' ' . (string) $account['apellido']) ?>"><?php endif; ?></td>
                        <td><strong><?= e((string) $account['apellido']) ?>, <?= e((string) $account['nombre']) ?></strong><span class="cell-meta">DNI <?= e((string) $account['dni']) ?></span></td>
                        <td><strong><?= e((string) $account['email']) ?></strong><span class="cell-meta"><?= e((string) $account['username']) ?></span></td>
                        <td><strong><?= e((string) $account['roles']) ?></strong></td>
                        <td>
                            <?php if ((int) $account['mustChangePassword'] === 1 || (int) $account['initialSetupCompleted'] === 0): ?>
                                <span class="stock-badge stock-warning">Primer ingreso pendiente</span>
                            <?php else: ?>
                                <span class="stock-badge stock-ok">Configurado</span>
                            <?php endif; ?>
                            <span class="cell-meta"><?= $account['ultimoAcceso'] ? 'Último acceso ' . e(date('d/m/Y H:i', strtotime((string) $account['ultimoAcceso']))) : 'Sin ingresos registrados' ?></span>
                        </td>
                        <td><span class="stock-badge <?= (int) $account['habilitado'] === 1 && (int) $account['activo'] === 1 ? 'stock-ok' : 'stock-empty' ?>"><?= (int) $account['habilitado'] === 1 && (int) $account['activo'] === 1 ? 'Habilitada' : 'Bloqueada' ?></span></td>
                        <td class="table-actions"><a href="account.php?id=<?= (int) $account['idUsuario'] ?>">Administrar</a></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table></div>
        </form>
        <script>
        document.getElementById('select-all-users')?.addEventListener('change', function () {
            document.querySelectorAll('.user-select').forEach(function (box) { box.checked = this.checked; }, this);
        });
        </script>
    <?php endif; ?>
</section>
<?php PersonnelPage::end(); ?>
