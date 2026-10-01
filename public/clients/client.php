<?php
declare(strict_types=1);
require dirname(__DIR__, 2) . '/src/bootstrap.php';

use Sedema\Authorization;
use Sedema\Clients\ClientContext;
use Sedema\Clients\ClientPage;
use Sedema\Clients\ClientService;
use Sedema\Csrf;

$context = ClientContext::boot();
$user = $context['user'];
$repository = $context['repository'];
$clientId = max(0, (int) ($_GET['id'] ?? 0));
$returnTo = (string) ($_GET['return'] ?? '');
$backUrl = $returnTo === 'sales' ? '../sales/order.php' : 'index.php';
$client = $clientId > 0 ? $repository->client($clientId) : null;
if ($clientId > 0 && !$client) {
    http_response_code(404);
    exit('El cliente no existe.');
}
$canManage = Authorization::can($user, 'clientes.manage');
if (!$client && !$canManage) {
    Authorization::require($user, 'clientes.manage');
}

ClientPage::begin($client ? 'Ficha del cliente' : 'Nuevo cliente', 'clients', $user);
?>
<section class="inventory-heading compact-heading">
    <div><p class="section-kicker">Padrón comercial</p><h2><?= $client ? ($canManage ? 'Editar cliente' : 'Consultar cliente') : 'Nuevo cliente' ?></h2><p>Datos de identificación, clasificación y contacto utilizados por Ventas y Pedidos.</p></div>
    <a class="back-button" href="<?= e($backUrl) ?>">← <?= $returnTo === 'sales' ? 'Volver al pedido' : 'Volver a clientes' ?></a>
</section>

<form class="inventory-form" method="post" action="action.php">
    <input type="hidden" name="csrf_token" value="<?= e(Csrf::token()) ?>">
    <input type="hidden" name="action" value="save-client">
    <input type="hidden" name="idCliente" value="<?= $clientId ?>">
    <input type="hidden" name="return" value="<?= e($returnTo) ?>">
    <fieldset <?= $canManage ? '' : 'disabled' ?> style="border:0;padding:0;margin:0;min-width:0">
        <section class="form-section">
            <div class="form-section-heading"><span>01</span><div><h3>Identificación</h3><p>Información principal y clasificación comercial.</p></div></div>
            <div class="form-grid">
                <div class="field"><label for="nombre">Nombre *</label><input id="nombre" name="nombre" maxlength="100" required value="<?= e((string) ($client['nombre'] ?? '')) ?>"></div>
                <div class="field"><label for="apellido">Apellido *</label><input id="apellido" name="apellido" maxlength="100" required value="<?= e((string) ($client['apellido'] ?? '')) ?>"></div>
                <div class="field"><label for="cuitDNI">DNI / CUIT *</label><input id="cuitDNI" name="cuitDNI" maxlength="20" required value="<?= e((string) ($client['cuitDNI'] ?? '')) ?>"></div>
                <div class="field"><label for="tipoCliente">Tipo de cliente *</label><select id="tipoCliente" name="tipoCliente" required><option value="">Seleccionar</option><?php foreach (ClientService::TYPES as $type): ?><option value="<?= e($type) ?>" <?= (string) ($client['tipoCliente'] ?? '') === $type ? 'selected' : '' ?>><?= e(ClientService::typeLabel($type)) ?></option><?php endforeach; ?></select></div>
                <div class="field form-field-wide"><label for="razonSocial">Razón social</label><input id="razonSocial" name="razonSocial" maxlength="150" value="<?= e((string) ($client['razonSocial'] ?? '')) ?>" placeholder="Opcional; útil para empresas"></div>
            </div>
        </section>
        <section class="form-section">
            <div class="form-section-heading"><span>02</span><div><h3>Contacto y ubicación</h3><p>Información operativa para atención y futuras entregas.</p></div></div>
            <div class="form-grid">
                <div class="field"><label for="telefono">Teléfono</label><input id="telefono" name="telefono" maxlength="50" value="<?= e((string) ($client['telefono'] ?? '')) ?>"></div>
                <div class="field"><label for="localidad">Localidad *</label><input id="localidad" name="localidad" maxlength="100" required value="<?= e((string) ($client['localidad'] ?? '')) ?>"></div>
                <div class="field form-field-wide"><label for="direccion">Dirección *</label><input id="direccion" name="direccion" maxlength="255" required value="<?= e((string) ($client['direccion'] ?? '')) ?>"></div>
            </div>
        </section>
    </fieldset>
    <div class="form-footer"><a class="button button-secondary" href="<?= e($backUrl) ?>">Volver</a><?php if ($canManage): ?><button class="button button-primary" type="submit">Guardar cliente</button><?php endif; ?></div>
</form>

<?php if ($client && $canManage): ?>
<section class="danger-zone">
    <div><h3><?= (int) $client['activo'] === 1 ? 'Dar de baja al cliente' : 'Reactivar al cliente' ?></h3><p>La baja es lógica: conserva la ficha para mantener el historial y futuras referencias de pedidos o comprobantes.</p></div>
    <form method="post" action="action.php" data-confirm="<?= (int) $client['activo'] === 1 ? '¿Dar de baja este cliente?' : '¿Reactivar este cliente?' ?>">
        <input type="hidden" name="csrf_token" value="<?= e(Csrf::token()) ?>"><input type="hidden" name="action" value="toggle-client"><input type="hidden" name="idCliente" value="<?= $clientId ?>">
    <input type="hidden" name="return" value="<?= e($returnTo) ?>"><input type="hidden" name="active" value="<?= (int) $client['activo'] === 1 ? '0' : '1' ?>">
        <button class="button <?= (int) $client['activo'] === 1 ? 'button-danger' : 'button-primary' ?>" type="submit"><?= (int) $client['activo'] === 1 ? 'Dar de baja' : 'Reactivar' ?></button>
    </form>
</section>
<?php endif; ?>
<?php ClientPage::end(); ?>
