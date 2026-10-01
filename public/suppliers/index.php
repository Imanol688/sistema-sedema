<?php
declare(strict_types=1);
require dirname(__DIR__,2).'/src/bootstrap.php';
use Sedema\Authorization;
use Sedema\Suppliers\{SupplierContext,SupplierPage};
$c=SupplierContext::boot(); $u=$c['user']; $r=$c['repository'];
$search=mb_substr(trim((string)($_GET['q']??'')),0,100);
$providers=$r->suppliers($search); $orders=$r->orders();
SupplierPage::begin('Proveedores y compras','overview',$u);
?>
<section class="inventory-heading"><div><p class="section-kicker">Abastecimiento</p><h2>Proveedores y remitos</h2><p>Seguimiento de compras y recepción de materiales.</p></div>
<?php if(Authorization::can($u,'suppliers.manage')): ?><div><a class="button button-secondary" href="supplier.php">Nuevo proveedor</a> <a class="button button-primary" href="order.php">Nuevo remito</a></div><?php endif; ?></section>
<form class="inventory-toolbar" method="get"><div class="field compact-field toolbar-search"><label for="q">Buscar proveedor</label><input id="q" type="search" name="q" value="<?= e($search) ?>" placeholder="Razón social o CUIT"></div><button class="button button-filter">Buscar</button></form>
<section class="inventory-panel"><div class="panel-title-row"><div><h3>Proveedores</h3><p><?= count($providers) ?> resultados</p></div></div>
<?php if(!$providers): ?><div class="table-empty"><p>No hay proveedores registrados con ese filtro.</p></div><?php else: ?><div class="inventory-table-wrap"><table class="inventory-table"><thead><tr><th>Razón social</th><th>CUIT</th><th>Contacto</th><th>Ficha</th></tr></thead><tbody><?php foreach($providers as $p): ?><tr><td><strong><?= e((string)$p['razonSocial']) ?></strong></td><td><?= e((string)$p['cuit']) ?></td><td><?= e((string)($p['contacto']??'')) ?></td><td><a href="supplier.php?id=<?= (int)$p['idProveedor'] ?>">Ver ficha</a></td></tr><?php endforeach; ?></tbody></table></div><?php endif; ?></section>
<section class="inventory-panel"><div class="panel-title-row"><div><h3>Remitos de compra</h3><p>Estado de las recepciones y monto de referencia</p></div></div>
<?php if(!$orders): ?><div class="table-empty"><p>Todavía no hay remitos de proveedores.</p></div><?php else: ?><div class="inventory-table-wrap"><table class="inventory-table"><thead><tr><th>Remito</th><th>Proveedor</th><th>Fecha</th><th>Estado</th><th>Recepciones</th><th>Importe</th><th></th></tr></thead><tbody><?php foreach($orders as $o): ?><tr><td><?= e((string)$o['numeroRemito']) ?></td><td><?= e((string)$o['razonSocial']) ?></td><td><?= e((string)$o['fechaEmision']) ?></td><td><?= e((string)$o['estado']) ?></td><td><?= (int)$o['receptions'] ?></td><td>$ <?= number_format((float)$o['total'],2,',','.') ?></td><td><a href="detail.php?id=<?= (int)$o['idRemitoProveedor'] ?>">Ver detalle</a></td></tr><?php endforeach; ?></tbody></table></div><?php endif; ?></section>
<?php SupplierPage::end(); ?>
