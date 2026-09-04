<?php
declare(strict_types=1);
require dirname(__DIR__, 2) . '/src/bootstrap.php';

use Sedema\Authorization;
use Sedema\Csrf;
use Sedema\Inventory\InventoryContext;
use Sedema\Inventory\InventoryPage;

$context = InventoryContext::boot();
$user = $context['user'];
Authorization::require($user, 'inventory.manage');
$repository = $context['repository'];
$service = $context['service'];
$productId = max(0, (int) ($_GET['id'] ?? 0));
$product = $productId > 0 ? $repository->product($productId) : null;
if ($productId > 0 && !$product) {
    http_response_code(404);
    exit('El producto no existe.');
}
$categories = $repository->categories();
$units = $repository->units();
$warehouses = $repository->warehouses();
$minimums = $product ? $repository->productMinimums($productId) : [];

InventoryPage::begin($product ? 'Editar producto' : 'Nuevo producto', 'overview', $user);
?>
<section class="inventory-heading compact-heading">
    <div><p class="section-kicker">Catálogo</p><h2><?= $product ? 'Editar producto' : 'Nuevo producto' ?></h2><p>Definí los datos comerciales y el mínimo para cada depósito.</p></div>
    <a class="back-button" href="index.php">← Volver a productos</a>
</section>

<?php if (!$categories || !$units || !$warehouses): ?>
    <section class="empty-modules"><h3>Falta configuración inicial</h3><p>Necesitás al menos una categoría, una unidad y un depósito activos.</p><a class="button button-primary" href="catalogs.php">Abrir configuración</a></section>
<?php else: ?>
<form class="inventory-form" method="post" action="action.php">
    <input type="hidden" name="csrf_token" value="<?= e(Csrf::token()) ?>">
    <input type="hidden" name="action" value="save-product">
    <input type="hidden" name="idProduct" value="<?= $productId ?>">

    <section class="form-section">
        <div class="form-section-heading"><span>01</span><div><h3>Identificación</h3><p>Datos principales del artículo.</p></div></div>
        <div class="form-grid">
            <div class="field"><label for="code">Código *</label><input id="code" name="code" maxlength="50" required value="<?= e((string) ($product['code'] ?? '')) ?>" placeholder="Ej. CEM-001"></div>
            <div class="field field-wide"><label for="name">Nombre *</label><input id="name" name="name" maxlength="150" required value="<?= e((string) ($product['name'] ?? '')) ?>" placeholder="Nombre del producto"></div>
            <div class="field"><label for="idCategory">Categoría *</label><select id="idCategory" name="idCategory" required><option value="">Seleccionar</option><?php foreach ($categories as $category): ?><option value="<?= (int) $category['idCategory'] ?>" <?= (int) ($product['idCategory'] ?? 0) === (int) $category['idCategory'] ? 'selected' : '' ?>><?= e((string) $category['name']) ?></option><?php endforeach; ?></select></div>
            <div class="field"><label for="idUnit">Unidad de medida *</label><select id="idUnit" name="idUnit" required><option value="">Seleccionar</option><?php foreach ($units as $unit): ?><option value="<?= (int) $unit['idUnit'] ?>" <?= (int) ($product['idUnit'] ?? 0) === (int) $unit['idUnit'] ? 'selected' : '' ?>><?= e((string) $unit['name']) ?> (<?= e((string) $unit['symbol']) ?>)</option><?php endforeach; ?></select></div>
            <div class="field"><label for="salePrice">Precio de venta</label><input id="salePrice" name="salePrice" type="number" min="0" step="0.01" value="<?= e(number_format((float) ($product['salePrice'] ?? 0), 2, '.', '')) ?>"></div>
            <div class="field field-full"><label for="description">Descripción</label><textarea id="description" name="description" rows="3" maxlength="2000" placeholder="Descripción opcional del producto"><?= e((string) ($product['description'] ?? '')) ?></textarea></div>
        </div>
    </section>

    <section class="form-section">
        <div class="form-section-heading"><span>02</span><div><h3>Stock mínimo</h3><p>Se controla de forma independiente en cada depósito.</p></div></div>
        <div class="minimum-grid">
            <?php foreach ($warehouses as $warehouse): $warehouseId = (int) $warehouse['idWarehouse']; ?>
                <label class="minimum-card"><span><?= e((string) $warehouse['name']) ?></span><input name="minimumStock[<?= $warehouseId ?>]" type="number" min="0" step="0.001" value="<?= e(number_format((float) ($minimums[$warehouseId] ?? 0), 3, '.', '')) ?>"><small>Alerta cuando la existencia sea igual o menor.</small></label>
            <?php endforeach; ?>
        </div>
    </section>

    <section class="form-section">
        <div class="form-section-heading"><span>03</span><div><h3>Características adicionales</h3><p>Campos flexibles para cada tipo de material.</p></div></div>
        <div class="field"><label for="customAttributes">Atributos personalizados</label><textarea id="customAttributes" name="customAttributes" rows="5" placeholder="Marca=Ejemplo&#10;Presentación=Bolsa 50 kg"><?= e($service->attributesForForm(isset($product['customAttributes']) ? (string) $product['customAttributes'] : null)) ?></textarea><span class="password-hint">Escribí un atributo por línea con el formato Nombre=Valor.</span></div>
    </section>

    <div class="form-footer"><a class="button button-secondary" href="index.php">Cancelar</a><button class="button button-primary" type="submit">Guardar producto</button></div>
</form>

<?php if ($product): ?>
<section class="danger-zone">
    <div><h3>Dar de baja el producto</h3><p>Conserva su historial, pero deja de mostrarlo en las operaciones habituales.</p></div>
    <form method="post" action="action.php" data-confirm="¿Dar de baja este producto?"><input type="hidden" name="csrf_token" value="<?= e(Csrf::token()) ?>"><input type="hidden" name="action" value="toggle-product"><input type="hidden" name="idProduct" value="<?= $productId ?>"><input type="hidden" name="active" value="0"><button class="button button-danger" type="submit">Dar de baja</button></form>
</section>
<?php endif; ?>
<?php endif; ?>
<?php InventoryPage::end(); ?>
