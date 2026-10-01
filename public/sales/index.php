<?php
declare(strict_types=1);
require dirname(__DIR__, 2) . '/src/bootstrap.php';

use Sedema\Authorization;
use Sedema\Sales\SalesContext;
use Sedema\Sales\SalesPage;
use Sedema\Sales\SalesService;

$context = SalesContext::boot();
$user = $context['user'];
$repository = $context['repository'];
$search = mb_substr(trim((string) ($_GET['search'] ?? '')), 0, 100);
$status = (string) ($_GET['status'] ?? 'all');
if ($status !== 'all' && !in_array($status, SalesService::STATUSES, true)) {
    $status = 'all';
}
$orders = $repository->orders($search, $status);

SalesPage::begin('Pedidos de venta', 'orders', $user);
?>
<section class="inventory-heading">
    <div>
        <h2>Pedidos y comprobantes</h2>
        <p>Carga de órdenes, consulta de stock, ajustes, remitos y facturación.</p>
    </div>
    <?php if (Authorization::can($user, 'sales.manage')): ?>
        <a class="button button-primary" href="order.php"><?= SalesPage::icon('plus') ?> Nuevo pedido</a>
    <?php endif; ?>
</section>

<form class="inventory-toolbar personnel-toolbar" method="get">
    <div class="field compact-field toolbar-search">
        <label for="search">Buscar</label>
        <input id="search" name="search" type="search" value="<?= e($search) ?>" placeholder="Pedido, cliente o DNI/CUIT">
    </div>
    <div class="field compact-field">
        <label for="status">Estado</label>
        <select id="status" name="status">
            <option value="all">Todos</option>
            <?php foreach (SalesService::STATUSES as $state): ?>
                <option value="<?= e($state) ?>" <?= $status === $state ? 'selected' : '' ?>><?= e(str_replace('_', ' ', ucfirst(strtolower($state)))) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <button class="button button-filter" type="submit">Aplicar</button>
</form>

<section class="inventory-panel">
    <div class="panel-title-row"><div><h3>Pedidos registrados</h3><p><?= count($orders) ?> resultados</p></div></div>
    <?php if (!$orders): ?>
        <div class="table-empty"><h4>No hay pedidos</h4><p>Creá una orden de venta para comenzar.</p></div>
    <?php else: ?>
        <div class="inventory-table-wrap">
            <table class="inventory-table">
                <thead><tr><th>Pedido</th><th>Cliente</th><th>Modalidad</th><th>Artículos</th><th>Total</th><th>Estado</th><th></th></tr></thead>
                <tbody>
                <?php foreach ($orders as $order): ?>
                    <tr>
                        <td><strong>#<?= (int) $order['idPedido'] ?></strong><span class="cell-meta"><?= e((string) $order['fecha']) ?></span></td>
                        <td><strong><?= e($order['apellido'] . ', ' . $order['nombre']) ?></strong><span class="cell-meta"><?= e((string) $order['cuitDNI']) ?></span></td>
                        <td><?= e(SalesService::modalityLabel((string) $order['modalidadOperativa'])) ?></td>
                        <td><?= (int) $order['itemCount'] ?></td>
                        <td><strong>$<?= number_format((float) $order['total'], 2, ',', '.') ?></strong></td>
                        <td><span class="stock-badge stock-ok"><?= e((string) $order['estado']) ?></span></td>
                        <td class="table-actions"><a href="order.php?id=<?= (int) $order['idPedido'] ?>">Ver</a></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>
<?php SalesPage::end(); ?>
