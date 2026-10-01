<?php
declare(strict_types=1);
require dirname(__DIR__, 2) . '/src/bootstrap.php';

use Sedema\Authorization;
use Sedema\Clients\ClientContext;
use Sedema\Clients\ClientPage;
use Sedema\Clients\ClientService;

$context = ClientContext::boot();
$user = $context['user'];
$repository = $context['repository'];
$search = mb_substr(trim((string) ($_GET['search'] ?? '')), 0, 100);
$type = mb_strtoupper(trim((string) ($_GET['type'] ?? 'all')));
if ($type !== 'ALL' && !in_array($type, ClientService::TYPES, true)) {
    $type = 'ALL';
}
$status = (string) ($_GET['status'] ?? 'active');
if (!in_array($status, ['active', 'inactive', 'all'], true)) {
    $status = 'active';
}
$summary = $repository->summary();
$clients = $repository->clients($search, strtolower($type) === 'all' ? 'all' : $type, $status);

ClientPage::begin('Clientes', 'clients', $user);
?>
<section class="inventory-heading">
    <div><p class="section-kicker">Padrón comercial</p><h2>Clientes</h2><p>Registro, clasificación y consulta de clientes para operaciones de venta.</p></div>
    <?php if (Authorization::can($user, 'clientes.manage')): ?><a class="button button-primary" href="client.php"><?= ClientPage::icon('plus') ?> Nuevo cliente</a><?php endif; ?>
</section>

<form class="inventory-toolbar personnel-toolbar" method="get">
    <div class="field compact-field toolbar-search"><label for="search">Buscar cliente</label><input id="search" name="search" type="search" value="<?= e($search) ?>" placeholder="Nombre, DNI/CUIT, razón social o localidad"></div>
    <div class="field compact-field"><label for="type">Tipo</label><select id="type" name="type"><option value="all">Todos</option><?php foreach (ClientService::TYPES as $clientType): ?><option value="<?= e($clientType) ?>" <?= $type === $clientType ? 'selected' : '' ?>><?= e(ClientService::typeLabel($clientType)) ?></option><?php endforeach; ?></select></div>
    <div class="field compact-field"><label for="status">Estado</label><select id="status" name="status"><option value="active" <?= $status === 'active' ? 'selected' : '' ?>>Activos</option><option value="inactive" <?= $status === 'inactive' ? 'selected' : '' ?>>Dados de baja</option><option value="all" <?= $status === 'all' ? 'selected' : '' ?>>Todos</option></select></div>
    <button class="button button-filter" type="submit">Aplicar</button>
</form>

<section class="inventory-stats" aria-label="Resumen de clientes">
    <article><span class="stat-icon"><?= ClientPage::icon('clients') ?></span><div><strong><?= $summary['total'] ?></strong><span>Clientes registrados</span></div></article>
    <article><span class="stat-icon"><?= ClientPage::icon('active') ?></span><div><strong><?= $summary['active'] ?></strong><span>Clientes activos</span></div></article>
    <article><span class="stat-icon"><?= ClientPage::icon('company') ?></span><div><strong><?= $summary['companies'] ?></strong><span>Empresas activas</span></div></article>
    <article><span class="stat-icon"><?= ClientPage::icon('badge') ?></span><div><strong><?= $summary['professionals'] ?></strong><span>Albañiles / contratistas</span></div></article>
</section>

<section class="inventory-panel">
    <div class="panel-title-row"><div><h3>Padrón de clientes</h3><p><?= count($clients) ?> resultados</p></div></div>
    <?php if (!$clients): ?>
        <div class="table-empty"><h4>No se encontraron clientes</h4><p>Revisá los filtros o registrá un nuevo cliente.</p></div>
    <?php else: ?>
        <div class="inventory-table-wrap"><table class="inventory-table personnel-table">
            <thead><tr><th>Cliente</th><th>DNI / CUIT</th><th>Tipo</th><th>Contacto</th><th>Ubicación</th><th>Estado</th><th><span class="sr-only">Acciones</span></th></tr></thead>
            <tbody>
            <?php foreach ($clients as $client): ?>
                <tr>
                    <td><strong><?= e((string) $client['apellido']) ?>, <?= e((string) $client['nombre']) ?></strong><?php if (!empty($client['razonSocial'])): ?><span class="cell-meta"><?= e((string) $client['razonSocial']) ?></span><?php else: ?><span class="cell-meta">Cliente #<?= (int) $client['idCliente'] ?></span><?php endif; ?></td>
                    <td><?= e((string) $client['cuitDNI']) ?></td>
                    <td><span class="stock-badge stock-ok"><?= e(ClientService::typeLabel((string) $client['tipoCliente'])) ?></span></td>
                    <td><?= e((string) ($client['telefono'] ?: 'Sin teléfono')) ?></td>
                    <td><strong><?= e((string) $client['localidad']) ?></strong><span class="cell-meta"><?= e((string) $client['direccion']) ?></span></td>
                    <td><span class="stock-badge <?= (int) $client['activo'] === 1 ? 'stock-ok' : 'stock-empty' ?>"><?= (int) $client['activo'] === 1 ? 'Activo' : 'Baja' ?></span></td>
                    <td class="table-actions"><a href="client.php?id=<?= (int) $client['idCliente'] ?>"><?= Authorization::can($user, 'clientes.manage') ? 'Editar' : 'Consultar' ?></a></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table></div>
    <?php endif; ?>
</section>
<?php ClientPage::end(); ?>
