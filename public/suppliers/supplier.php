<?php
declare(strict_types=1);
require dirname(__DIR__,2).'/src/bootstrap.php';
use Sedema\{Authorization,Csrf};
use Sedema\Suppliers\{SupplierContext,SupplierPage};
$c=SupplierContext::boot(); $u=$c['user']; $id=max(0,(int)($_GET['id']??0)); $p=$id?$c['repository']->supplier($id):null;
if($id && !$p){http_response_code(404);exit('Proveedor inexistente.');}
$manage=Authorization::can($u,'suppliers.manage'); if(!$p && !$manage) Authorization::require($u,'suppliers.manage');
SupplierPage::begin($p?'Ficha del proveedor':'Nuevo proveedor','catalogs',$u);
?>
<section class="inventory-heading compact-heading"><div><p class="section-kicker">Abastecimiento</p><h2><?= $p?'Ficha del proveedor':'Nuevo proveedor' ?></h2></div><a class="back-button" href="index.php">← Volver</a></section>
<form class="inventory-form" method="post" action="action.php"><input type="hidden" name="csrf_token" value="<?= e(Csrf::token()) ?>"><input type="hidden" name="action" value="supplier"><input type="hidden" name="idProveedor" value="<?= $id ?>">
<fieldset <?= $manage?'':'disabled' ?> style="border:0;padding:0;margin:0;min-width:0"><section class="form-section"><div class="form-section-heading"><span>01</span><div><h3>Identificación y contacto</h3></div></div><div class="form-grid">
<div class="field"><label for="name">Razón social *</label><input id="name" name="razonSocial" maxlength="150" required value="<?= e((string)($p['razonSocial']??'')) ?>"></div>
<div class="field"><label for="cuit">CUIT (11 dígitos) *</label><input id="cuit" name="cuit" maxlength="20" required value="<?= e((string)($p['cuit']??'')) ?>"></div>
<div class="field"><label for="contact">Contacto</label><input id="contact" name="contacto" maxlength="100" value="<?= e((string)($p['contacto']??'')) ?>"></div>
<div class="field form-field-wide"><label for="description">Descripción</label><textarea id="description" name="descripcionProveedor" maxlength="5000" rows="4"><?= e((string)($p['descripcionProveedor']??'')) ?></textarea></div>
</div></section></fieldset><div class="form-footer"><a class="button button-secondary" href="index.php">Volver</a><?php if($manage): ?><button class="button button-primary">Guardar proveedor</button><?php endif; ?></div></form>
<?php SupplierPage::end(); ?>
