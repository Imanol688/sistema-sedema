<?php
declare(strict_types=1);
require dirname(__DIR__,2).'/src/bootstrap.php';
use Sedema\{Authorization,Csrf};
use Sedema\Suppliers\{SupplierContext,SupplierPage};
$c=SupplierContext::boot(); $u=$c['user']; Authorization::require($u,'suppliers.manage');
$providers=$c['repository']->suppliers(); $products=$c['repository']->products();
SupplierPage::begin('Nuevo remito','movement',$u);
?>
<section class="inventory-heading compact-heading"><div><p class="section-kicker">Compras</p><h2>Registrar remito del proveedor</h2><p>Las cantidades registradas quedan pendientes hasta confirmar una recepción.</p></div><a class="back-button" href="index.php">← Volver</a></section>
<?php if(!$providers || !$products): ?><div class="alert alert-error">Se requiere al menos un proveedor y un producto activo. <a href="supplier.php">Crear proveedor</a></div><?php else: ?>
<form class="inventory-form" action="action.php" method="post"><input type="hidden" name="csrf_token" value="<?= e(Csrf::token()) ?>"><input type="hidden" name="action" value="order">
<section class="form-section"><div class="form-section-heading"><span>01</span><div><h3>Datos del remito</h3></div></div><div class="form-grid"><div class="field"><label for="provider">Proveedor *</label><select id="provider" name="idProveedor" required><option value="">Seleccionar</option><?php foreach($providers as $p): ?><option value="<?= (int)$p['idProveedor'] ?>"><?= e((string)$p['razonSocial']) ?> · <?= e((string)$p['cuit']) ?></option><?php endforeach; ?></select></div><div class="field"><label for="number">Número de remito *</label><input id="number" name="numeroRemito" maxlength="50" required></div><div class="field"><label for="date">Fecha de emisión *</label><input id="date" name="fechaEmision" type="date" value="<?= date('Y-m-d') ?>" required></div></div></section>
<section class="form-section"><div class="form-section-heading"><span>02</span><div><h3>Detalle de materiales</h3><p>Una fila por producto. Podés dejar filas libres.</p></div></div><div class="inventory-table-wrap"><table class="inventory-table"><thead><tr><th>Producto</th><th>Cantidad</th><th>Precio unitario</th></tr></thead><tbody><?php for($i=0;$i<10;$i++): ?><tr><td><select name="product[]"><option value="">Seleccionar</option><?php foreach($products as $p): ?><option value="<?= (int)$p['idProduct'] ?>"><?= e((string)$p['code'].' · '.$p['name'].' ('.$p['symbol'].')') ?></option><?php endforeach; ?></select></td><td><input type="number" name="quantity[]" min="0.001" step="0.001" placeholder="0.000"></td><td><input type="number" name="price[]" min="0.01" step="0.01" placeholder="0.00"></td></tr><?php endfor; ?></tbody></table></div></section>
<div class="form-footer"><a class="button button-secondary" href="index.php">Cancelar</a><button class="button button-primary">Registrar remito</button></div></form><?php endif; ?>
<?php SupplierPage::end(); ?>
