<?php
declare(strict_types=1);
require dirname(__DIR__, 2) . '/src/bootstrap.php';

use Sedema\Authorization;
use Sedema\Csrf;
use Sedema\Inventory\InventoryContext;
use Sedema\Inventory\InventoryPage;

$context = InventoryContext::boot();
$user = $context['user'];
Authorization::require($user, 'inventory.catalogs');
$repository = $context['repository'];
$categories = $repository->categories(false);
$units = $repository->units(false);
$warehouses = $repository->warehouses(false);

InventoryPage::begin('Configuración', 'catalogs', $user);
?>
<section class="inventory-heading compact-heading">
    <div><p class="section-kicker">Catálogos</p><h2>Configuración de inventario</h2><p>Categorías, unidades de medida y depósitos compartidos por todo el módulo.</p></div>
    <a class="back-button" href="index.php">← Volver al inventario</a>
</section>

<div class="catalog-grid">
    <section class="catalog-card">
        <div class="catalog-card-header"><span><?= InventoryPage::icon('box') ?></span><div><h3>Categorías</h3><p><?= count($categories) ?> registradas</p></div></div>
        <form method="post" action="action.php" class="catalog-form"><input type="hidden" name="csrf_token" value="<?= e(Csrf::token()) ?>"><input type="hidden" name="action" value="add-category"><div class="field"><label for="categoryName">Nombre *</label><input id="categoryName" name="name" maxlength="100" required></div><div class="field"><label for="categoryDescription">Descripción</label><input id="categoryDescription" name="description" maxlength="255"></div><button class="button button-primary button-full" type="submit">Agregar categoría</button></form>
        <div class="catalog-list"><?php foreach ($categories as $category): ?><div><strong><?= e((string) $category['name']) ?></strong><span><?= (int) $category['active'] === 1 ? 'Activa' : 'Inactiva' ?></span></div><?php endforeach; ?></div>
    </section>

    <section class="catalog-card">
        <div class="catalog-card-header"><span><?= InventoryPage::icon('overview') ?></span><div><h3>Unidades</h3><p><?= count($units) ?> registradas</p></div></div>
        <form method="post" action="action.php" class="catalog-form"><input type="hidden" name="csrf_token" value="<?= e(Csrf::token()) ?>"><input type="hidden" name="action" value="add-unit"><div class="form-grid"><div class="field"><label for="unitCode">Código *</label><input id="unitCode" name="code" maxlength="20" required placeholder="KG"></div><div class="field"><label for="unitSymbol">Símbolo *</label><input id="unitSymbol" name="symbol" maxlength="15" required placeholder="kg"></div><div class="field field-full"><label for="unitName">Nombre *</label><input id="unitName" name="name" maxlength="80" required placeholder="Kilogramo"></div></div><label class="check-field"><input type="checkbox" name="allowsDecimals" value="1" checked><span>Permite decimales</span></label><button class="button button-primary button-full" type="submit">Agregar unidad</button></form>
        <div class="catalog-list"><?php foreach ($units as $unit): ?><div><strong><?= e((string) $unit['name']) ?> <small>(<?= e((string) $unit['symbol']) ?>)</small></strong><span><?= (int) $unit['allowsDecimals'] === 1 ? 'Decimal' : 'Entera' ?></span></div><?php endforeach; ?></div>
    </section>

    <section class="catalog-card">
        <div class="catalog-card-header"><span><?= InventoryPage::icon('warehouse') ?></span><div><h3>Depósitos</h3><p><?= count($warehouses) ?> registrados</p></div></div>
        <form method="post" action="action.php" class="catalog-form"><input type="hidden" name="csrf_token" value="<?= e(Csrf::token()) ?>"><input type="hidden" name="action" value="add-warehouse"><div class="field"><label for="warehouseName">Nombre *</label><input id="warehouseName" name="name" maxlength="100" required></div><div class="field"><label for="warehouseAddress">Dirección</label><input id="warehouseAddress" name="address" maxlength="255"></div><div class="field"><label for="warehouseDescription">Descripción</label><input id="warehouseDescription" name="description" maxlength="255"></div><button class="button button-primary button-full" type="submit">Agregar depósito</button></form>
        <div class="catalog-list"><?php foreach ($warehouses as $warehouse): ?><div><strong><?= e((string) $warehouse['name']) ?></strong><span><?= (int) $warehouse['active'] === 1 ? 'Activo' : 'Inactivo' ?></span></div><?php endforeach; ?></div>
    </section>
</div>
<?php InventoryPage::end(); ?>
