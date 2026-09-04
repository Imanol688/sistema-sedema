<?php
declare(strict_types=1);
require dirname(__DIR__, 2) . '/src/bootstrap.php';

use Sedema\Authorization;
use Sedema\Csrf;
use Sedema\Inventory\InventoryContext;
use Sedema\Inventory\InventoryPage;

$context = InventoryContext::boot();
$user = $context['user'];
$repository = $context['repository'];
$service = $context['service'];
$warehouses = $repository->warehouses();
$categories = $repository->categories();
$requestedWarehouse = (int) ($_GET['warehouse'] ?? $_SESSION['inventory_warehouse'] ?? 0);
$warehouseIds = array_map(static fn (array $row): int => (int) $row['idWarehouse'], $warehouses);
$warehouseId = in_array($requestedWarehouse, $warehouseIds, true) ? $requestedWarehouse : ($warehouseIds[0] ?? 0);
$_SESSION['inventory_warehouse'] = $warehouseId;
$search = mb_substr(trim((string) ($_GET['search'] ?? '')), 0, 100);
$categoryId = max(0, (int) ($_GET['category'] ?? 0));
$onlyLow = (string) ($_GET['low'] ?? '') === '1';
$summary = $repository->summary($warehouseId);
$products = $repository->products($warehouseId, $search, $categoryId, $onlyLow);
$selectedWarehouse = null;
foreach ($warehouses as $warehouse) {
    if ((int) $warehouse['idWarehouse'] === $warehouseId) {
        $selectedWarehouse = $warehouse;
        break;
    }
}

InventoryPage::begin('Resumen y productos', 'overview', $user);
?>
<section class="inventory-heading">
    <div>
        <p class="section-kicker">Control de existencias</p>
        <h2>Inventario actual</h2>
        <p>Existencias y niveles mínimos por depósito.</p>
    </div>
    <div class="inventory-actions">
        <?php if (Authorization::can($user, 'inventory.adjust')): ?>
            <a class="button button-secondary" href="movement.php"><?= InventoryPage::icon('movement') ?> Registrar movimiento</a>
        <?php endif; ?>
        <?php if (Authorization::can($user, 'inventory.manage')): ?>
            <a class="button button-primary" href="product.php"><?= InventoryPage::icon('plus') ?> Nuevo producto</a>
        <?php endif; ?>
    </div>
</section>

<?php if (!$warehouses): ?>
    <section class="empty-modules"><h3>No hay depósitos activos</h3><p>Creá un depósito desde Configuración antes de registrar productos.</p></section>
<?php else: ?>
<form class="inventory-toolbar" method="get">
    <div class="field compact-field">
        <label for="warehouse">Depósito</label>
        <select id="warehouse" name="warehouse" data-auto-submit>
            <?php foreach ($warehouses as $warehouse): ?>
                <option value="<?= (int) $warehouse['idWarehouse'] ?>" <?= (int) $warehouse['idWarehouse'] === $warehouseId ? 'selected' : '' ?>><?= e((string) $warehouse['name']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="field compact-field toolbar-search">
        <label for="search">Buscar producto</label>
        <input id="search" name="search" type="search" value="<?= e($search) ?>" placeholder="Código o nombre">
    </div>
    <div class="field compact-field">
        <label for="category">Categoría</label>
        <select id="category" name="category">
            <option value="0">Todas</option>
            <?php foreach ($categories as $category): ?>
                <option value="<?= (int) $category['idCategory'] ?>" <?= (int) $category['idCategory'] === $categoryId ? 'selected' : '' ?>><?= e((string) $category['name']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <label class="check-field"><input type="checkbox" name="low" value="1" <?= $onlyLow ? 'checked' : '' ?>><span>Solo stock bajo</span></label>
    <button class="button button-filter" type="submit">Aplicar</button>
</form>

<section class="inventory-stats" aria-label="Resumen del depósito">
    <article><span class="stat-icon"><?= InventoryPage::icon('box') ?></span><div><strong><?= $summary['products'] ?></strong><span>Productos activos</span></div></article>
    <article><span class="stat-icon"><?= InventoryPage::icon('warehouse') ?></span><div><strong><?= $summary['stockPositions'] ?></strong><span>Productos con saldo</span></div></article>
    <article class="<?= $summary['lowStock'] > 0 ? 'is-warning' : '' ?>"><span class="stat-icon"><?= InventoryPage::icon('alert') ?></span><div><strong><?= $summary['lowStock'] ?></strong><span>Con stock mínimo</span></div></article>
    <article class="<?= $summary['outOfStock'] > 0 ? 'is-danger' : '' ?>"><span class="stat-icon"><?= InventoryPage::icon('alert') ?></span><div><strong><?= $summary['outOfStock'] ?></strong><span>Sin existencias</span></div></article>
</section>

<section class="inventory-panel">
    <div class="panel-title-row">
        <div><h3>Productos</h3><p><?= e((string) ($selectedWarehouse['name'] ?? 'Depósito')) ?> · <?= count($products) ?> resultados</p></div>
    </div>
    <?php if (!$products): ?>
        <div class="table-empty"><h4>No se encontraron productos</h4><p>Revisá los filtros o registrá el primer producto del inventario.</p></div>
    <?php else: ?>
        <div class="inventory-table-wrap">
            <table class="inventory-table">
                <thead><tr><th>Producto</th><th>Categoría</th><th>Existencia</th><th>Mínimo</th><th>Estado</th><th><span class="sr-only">Acciones</span></th></tr></thead>
                <tbody>
                <?php foreach ($products as $product):
                    $quantity = (float) $product['quantity'];
                    $minimum = (float) $product['minimumStock'];
                    $isLow = $minimum > 0 && $quantity <= $minimum;
                    $isEmpty = $quantity <= 0;
                ?>
                    <tr>
                        <td><strong><?= e((string) $product['name']) ?></strong><span class="cell-meta"><?= e((string) $product['code']) ?></span></td>
                        <td><?= e((string) $product['categoryName']) ?></td>
                        <td class="quantity-cell"><strong><?= e($service->formatQuantity($quantity)) ?></strong> <?= e((string) $product['symbol']) ?></td>
                        <td><?= e($service->formatQuantity($minimum)) ?> <?= e((string) $product['symbol']) ?></td>
                        <td><span class="stock-badge <?= $isEmpty ? 'stock-empty' : ($isLow ? 'stock-low' : 'stock-ok') ?>"><?= $isEmpty ? 'Sin stock' : ($isLow ? 'Stock bajo' : 'Disponible') ?></span></td>
                        <td class="table-actions">
                            <a href="movements.php?product=<?= (int) $product['idProduct'] ?>&warehouse=<?= $warehouseId ?>">Historial</a>
                            <?php if (Authorization::can($user, 'inventory.manage')): ?><a href="product.php?id=<?= (int) $product['idProduct'] ?>">Editar</a><?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>
<?php endif; ?>
<?php InventoryPage::end(); ?>
