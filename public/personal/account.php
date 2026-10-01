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
$userId = max(0, (int) ($_GET['id'] ?? 0));
$account = $userId > 0 ? $repository->account($userId) : null;
if ($userId > 0 && !$account) {
    http_response_code(404);
    exit('La cuenta indicada no existe.');
}
$assigned = $account ? (json_decode((string) ($account['permisos'] ?: '[]'), true) ?: []) : [];
$hasModule = static function (string $module) use ($assigned): bool {
    return in_array($module . '.*', $assigned, true) || ($module === 'inventario' && in_array('inventory.*', $assigned, true));
};
$roleLabels = [
    'ADMINISTRADOR' => 'Administrador',
    'CAJA' => 'Caja',
    'ALMACEN' => 'Almacén',
    'FINANZAS' => 'Finanzas',
    'SISTEMAS' => 'Sistemas',
    'VENDEDOR' => 'Vendedor',
    'PROVEEDOR' => 'Proveedor',
    'DEPOSITO' => 'Depósito',
    'LOGISTICA' => 'Logística',
];

PersonnelPage::begin($account ? 'Administrar acceso' : 'Crear cuenta', 'access', $user);
?>
<section class="inventory-heading compact-heading">
    <div><p class="section-kicker">Gestión de usuarios</p><h2><?= $account ? 'Administrar cuenta' : 'Crear cuenta de empleado' ?></h2><p><?= $account ? 'Modificá el rol, permisos o estado de la cuenta.' : 'El sistema generará una contraseña temporal de 8 caracteres y la enviará al correo indicado.' ?></p></div>
    <a class="back-button" href="access.php">← Volver a usuarios</a>
</section>

<?php if (!$account): ?>
<form class="inventory-form" method="post" action="action.php">
    <input type="hidden" name="csrf_token" value="<?= e(Csrf::token()) ?>">
    <input type="hidden" name="action" value="create-account">
    <section class="form-section">
        <div class="form-section-heading"><span>01</span><div><h3>Empleado</h3><p>Si el DNI ya tiene un legajo sin usuario, la cuenta se vinculará a ese legajo.</p></div></div>
        <div class="form-grid">
            <div class="field"><label for="nombre">Nombre *</label><input id="nombre" name="nombre" maxlength="100" required></div>
            <div class="field"><label for="apellido">Apellido *</label><input id="apellido" name="apellido" maxlength="100" required></div>
            <div class="field"><label for="dni">DNI *</label><input id="dni" name="dni" maxlength="20" required autocomplete="off"></div>
            <div class="field"><label for="email">Correo electrónico *</label><input id="email" name="email" type="email" maxlength="150" required autocomplete="off"></div>
        </div>
    </section>
    <section class="form-section">
        <div class="form-section-heading"><span>02</span><div><h3>Rol de acceso</h3><p>Seleccioná el perfil operativo del empleado.</p></div></div>
        <div class="form-grid">
            <div class="field"><label for="role">Rol de acceso *</label><select id="role" name="role" required><option value="">Seleccionar</option><?php foreach (array_slice($roleLabels, 1, null, true) as $value => $label): ?><option value="<?= e($value) ?>"><?= e($label) ?></option><?php endforeach; ?></select></div>
        </div>
    </section>
    <section class="form-section">
        <div class="form-section-heading"><span>03</span><div><h3>Permisos por módulo</h3><p>Marcá únicamente los módulos que esta persona necesita utilizar.</p></div></div>
        <div class="permission-grid">
            <?php foreach (['ventas'=>'Ventas y pedidos','clientes'=>'Clientes','inventario'=>'Inventario','proveedores'=>'Proveedores y compras','pagos'=>'Pagos y cobranzas','logistica'=>'Despacho y logística'] as $key => $label): ?>
                <label class="permission-option"><input type="checkbox" name="permissions[]" value="<?= e($key) ?>"><span><strong><?= e($label) ?></strong><small>Permitir acceso al módulo.</small></span></label>
            <?php endforeach; ?>
        </div>
    </section>
    <aside class="access-note"><strong>Credencial temporal</strong><p>La contraseña temporal no se guarda en texto plano. Para que llegue al Gmail del empleado, el archivo <code>.env</code> debe usar <code>MAIL_TRANSPORT=smtp</code> con una contraseña de aplicación de Google.</p></aside>
    <div class="form-footer"><a class="button button-secondary" href="access.php">Cancelar</a><button class="button button-primary" type="submit">Crear cuenta y enviar acceso</button></div>
</form>
<?php else: ?>
<form class="inventory-form" method="post" action="action.php">
    <input type="hidden" name="csrf_token" value="<?= e(Csrf::token()) ?>"><input type="hidden" name="action" value="update-account"><input type="hidden" name="idUsuario" value="<?= $userId ?>">
    <section class="form-section">
        <div class="form-section-heading"><span>01</span><div><h3>Cuenta vinculada</h3><p>Identidad del empleado y estado del primer ingreso.</p></div></div>
        <div class="personnel-link-card"><div><span>Empleado</span><strong><?= e((string) $account['apellido']) ?>, <?= e((string) $account['nombre']) ?></strong></div><div><span>Correo actual</span><strong><?= e((string) $account['email']) ?></strong></div><div><span>Primer acceso</span><strong><?= (int) $account['mustChangePassword'] === 1 ? 'Pendiente' : 'Completado' ?></strong></div></div>
    </section>
    <section class="form-section">
        <div class="form-section-heading"><span>02</span><div><h3>Rol de acceso</h3><p>Los cambios invalidan sesiones abiertas de esta cuenta.</p></div></div>
        <div class="form-grid">
            <div class="field"><label for="email">Correo electrónico *</label><input id="email" name="email" type="email" maxlength="150" required value="<?= e((string) $account['email']) ?>" autocomplete="off"><small>Podés cambiarlo si el empleado perdió acceso al correo anterior.</small></div>
            <div class="field"><label for="role">Rol *</label><select id="role" name="role" required><?php foreach ($roleLabels as $role => $label): ?><option value="<?= e($role) ?>" <?= (string) $account['roles'] === $role ? 'selected' : '' ?>><?= e($label) ?></option><?php endforeach; ?></select></div>
        </div>
    </section>
    <section class="form-section">
        <div class="form-section-heading"><span>03</span><div><h3>Permisos</h3><p>Los permisos se aplican además del perfil asociado al rol.</p></div></div>
        <div class="permission-grid"><?php foreach (['ventas'=>'Ventas y pedidos','clientes'=>'Clientes','inventario'=>'Inventario','proveedores'=>'Proveedores y compras','pagos'=>'Pagos y cobranzas','logistica'=>'Despacho y logística'] as $key => $label): ?><label class="permission-option"><input type="checkbox" name="permissions[]" value="<?= e($key) ?>" <?= $hasModule($key) ? 'checked' : '' ?>><span><strong><?= e($label) ?></strong><small>Permitir acceso al módulo.</small></span></label><?php endforeach; ?></div>
        <label class="account-toggle"><input type="checkbox" name="enabled" value="1" <?= (int) $account['habilitado'] === 1 ? 'checked' : '' ?>><span><strong>Cuenta habilitada</strong><small>Si se desmarca, la persona no podrá iniciar sesión.</small></span></label>
    </section>
    <div class="form-footer"><a class="button button-secondary" href="access.php">Cancelar</a><button class="button button-primary" type="submit">Guardar acceso</button></div>
</form>

<section class="inventory-panel" style="margin-top:20px">
    <div class="panel-title-row"><div><h3>Contraseña temporal</h3><p>Usá esta opción si la cuenta fue creada mientras el correo estaba en modo de prueba o si el empleado no recibió el mensaje.</p></div></div>
    <form method="post" action="action.php" onsubmit="return confirm('Se generará una nueva contraseña temporal y la anterior dejará de funcionar. ¿Continuar?');">
        <input type="hidden" name="csrf_token" value="<?= e(Csrf::token()) ?>">
        <input type="hidden" name="action" value="resend-temporary-credential">
        <input type="hidden" name="idUsuario" value="<?= $userId ?>">
        <button class="button button-secondary" type="submit">Generar y reenviar contraseña temporal</button>
    </form>
</section>

<section class="inventory-panel" style="margin-top:20px">
    <div class="panel-title-row"><div><h3>Eliminar cuenta</h3><p>Elimina el acceso del usuario, pero conserva el legajo y las referencias históricas del empleado.</p></div></div>
    <?php if ($userId !== (int) $user['id']): ?>
    <form method="post" action="action.php" onsubmit="return confirm('Esta cuenta dejará de existir en Usuarios y accesos y no podrá iniciar sesión. El legajo se conservará. ¿Eliminar definitivamente el acceso?');">
        <input type="hidden" name="csrf_token" value="<?= e(Csrf::token()) ?>">
        <input type="hidden" name="action" value="delete-account">
        <input type="hidden" name="idUsuario" value="<?= $userId ?>">
        <button class="button button-danger" type="submit">Eliminar cuenta</button>
    </form>
    <?php else: ?>
        <p class="cell-meta">No podés eliminar tu propia cuenta mientras estás usando el sistema.</p>
    <?php endif; ?>
</section>

<?php endif; ?>
<?php PersonnelPage::end(); ?>
