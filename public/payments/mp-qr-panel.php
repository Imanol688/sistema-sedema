<?php
declare(strict_types=1);
if(!defined('BASE_PATH')){http_response_code(404);exit;}
$mp=(new \Sedema\Payments\MercadoPagoQrService($repo))->order($id);
?>
<?php if($mp): ?>
<section class="inventory-panel">
<h3>Mercado Pago · QR de mostrador</h3>
<p>Entorno: <?=e((string)$mp['environment'])?> · Caja: <?=e((string)$mp['posExternalId'])?></p>
<p>Orden: <?=e((string)($mp['externalOrderId']?:'Envío pendiente de confirmación'))?></p>
<p>Estado en Mercado Pago: <strong><?=e((string)$mp['status'])?></strong> · <?=e((string)($mp['statusDetail']??''))?></p>
<p>Última consulta: <?=e((string)($mp['checkedAt']??'Sin consultar'))?></p>
<?php if(!empty($mp['lastError'])): ?><div class="alert alert-error"><p><?=e((string)$mp['lastError'])?></p></div><?php endif; ?>
<?php if((string)$p['estadoPago']==='PENDIENTE'): ?>
<p>El cliente debe escanear el QR impreso de la caja indicada. El importe a pagar es $<?=number_format((float)$mp['amount'],2,',','.')?>. No confirmes la entrega con una captura del cliente: esperá el estado APROBADO en SEDEMA.</p>
<?php endif; ?>
<?php if(in_array((string)$mp['status'],['refunded'],true)||$mp['statusDetail']==='partially_refunded'): ?><p>Se detectó una devolución. El importe de este cobro refleja el neto conservado. Revisá también los comprobantes y la entrega con el responsable; no se anulan automáticamente.</p><?php endif; ?>
<?php if(\Sedema\Authorization::can($user,'payments.manage')): ?>
<div class="form-actions">
<form method="post" action="action.php"><input type="hidden" name="csrf_token" value="<?=e(\Sedema\Csrf::token())?>"><input type="hidden" name="action" value="verify"><input type="hidden" name="idPago" value="<?=$id?>"><button class="button button-primary">Verificar / recuperar solicitud</button></form>
<?php if((string)$p['estadoPago']==='PENDIENTE'): ?>
<form method="post" action="action.php"><input type="hidden" name="csrf_token" value="<?=e(\Sedema\Csrf::token())?>"><input type="hidden" name="action" value="mp-cancel"><input type="hidden" name="idPago" value="<?=$id?>"><button class="button button-danger">Cancelar solicitud QR</button></form>
</div><p id="mp-qr-status" data-payment-id="<?=$id?>" data-csrf="<?=e(\Sedema\Csrf::token())?>">Consultando automáticamente cada 10 segundos mientras esta pantalla esté abierta.</p>
<script src="../assets/js/mp-qr-status.js" defer></script>
<?php else: ?></div><?php endif; ?>
<?php endif; ?>
</section>
<?php endif; ?>
