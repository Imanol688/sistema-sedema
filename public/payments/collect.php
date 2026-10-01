<?php
declare(strict_types=1);
require dirname(__DIR__,2).'/src/bootstrap.php';
use Sedema\Authorization;use Sedema\Csrf;use Sedema\Payments\PaymentContext;use Sedema\Payments\PaymentPage;use Sedema\Payments\PaymentService;
$c=PaymentContext::boot();$user=$c['user'];$repo=$c['repository'];$gateway=$c['gateway'];Authorization::require($user,'payments.manage');
$id=max(0,(int)($_GET['id']??0));$order=$repo->order($id);if(!$order){http_response_code(404);exit('El pedido no existe.');}
$payments=$repo->payments($id);$balance=max(0,round((float)$order['total']-(float)$order['pagado'],2));$accountBalance=$repo->currentAccountBalance((int)$order['idCliente']);
PaymentPage::begin('Cobrar pedido #'.$id,'payments',$user);
?>
<section class="inventory-heading compact-heading"><div><p class="section-kicker">Pedido #<?=$id?></p><h2><?=e($order['apellido'].', '.$order['nombre'])?></h2><p><?=e((string)$order['cuitDNI'])?> · <?=e((string)$order['tipoCliente'])?></p></div><a class="back-button" href="index.php">← Volver</a></section>
<section class="inventory-stats"><article><div><strong>$<?=number_format((float)$order['total'],2,',','.')?></strong><span>Total actual</span></div></article><article><div><strong>$<?=number_format((float)$order['pagado'],2,',','.')?></strong><span>Pagado</span></div></article><article><div><strong>$<?=number_format($balance,2,',','.')?></strong><span>Saldo pendiente</span></div></article><article><div><strong>$<?=number_format($accountBalance,2,',','.')?></strong><span>Cuenta corriente</span></div></article></section>

<?php if($balance>0.004):?>
<form class="inventory-form" method="post" action="action.php" id="payment-form">
<input type="hidden" name="csrf_token" value="<?=e(Csrf::token())?>"><input type="hidden" name="action" value="register"><input type="hidden" name="idPedido" value="<?=$id?>">
<section class="form-section"><div class="form-section-heading"><span>01</span><div><h3>Registrar cobro</h3><p>Elegí si vas a registrar un pago o imputar el saldo a la cuenta corriente del cliente.</p></div></div>
<div class="payment-choice-grid">
<label class="payment-choice"><input type="radio" name="tipoOperacion" value="PAGO" checked><span><strong>Pago</strong><small>Efectivo, tarjeta, cheque o transferencia.</small></span></label>
<label class="payment-choice"><input type="radio" name="tipoOperacion" value="CUENTA_CORRIENTE"><span><strong>Cuenta corriente</strong><small>Imputa el importe como deuda del cliente.</small></span></label>
</div>
<div class="form-grid" style="margin-top:16px"><div class="field"><label>Importe base a cobrar *</label><input type="number" min="0.01" max="<?=e(number_format($balance,2,'.',''))?>" step="0.01" name="importeCobro" id="importeCobro" value="<?=e(number_format($balance,2,'.',''))?>" required></div><div class="field"><label>Saldo pendiente</label><input value="$<?=number_format($balance,2,',','.')?>" disabled></div></div>
</section>

<section class="form-section" data-payment-method-section><div class="form-section-heading"><span>02</span><div><h3>Medio de pago</h3><p>El diagrama contempla efectivo, tarjeta, cheque y transferencia.</p></div></div>
<div class="form-grid"><div class="field"><label>Medio *</label><select name="medioPago" id="medioPago"><option value="">Seleccionar</option><?php foreach(PaymentService::METHODS as $m):?><option value="<?=e($m)?>"><?=e(PaymentService::methodLabel($m))?></option><?php endforeach;?></select></div></div>

<div class="payment-method-panel" data-method-panel="EFECTIVO" hidden><div class="form-grid"><div class="field"><label>Importe recibido *</label><input type="number" min="0" step="0.01" name="importeRecibido" id="importeRecibido"></div><div class="field"><label>Vuelto estimado</label><input id="vueltoEstimado" value="$0,00" disabled></div></div></div>

<div class="payment-method-panel" data-method-panel="CHEQUE" hidden><div class="form-grid"><div class="field"><label>Banco *</label><input name="chequeBanco" maxlength="120"></div><div class="field"><label>Número de cheque *</label><input name="chequeNumero" maxlength="80"></div><div class="field"><label>Titular *</label><input name="chequeTitular" maxlength="150"></div><div class="field"><label>Fecha de emisión *</label><input type="date" name="chequeFechaEmision"></div><div class="field"><label>Fecha de vencimiento *</label><input type="date" name="chequeFechaVencimiento"></div><div class="field field-wide"><label>Observaciones</label><input name="chequeObservaciones" maxlength="255" data-optional="1"></div></div><aside class="access-note"><strong>Validación de cheque</strong><p>El cheque queda pendiente hasta que Caja/Finanzas lo apruebe o rechace desde el comprobante.</p></aside></div>

<div class="payment-method-panel" data-method-panel="TARJETA" hidden><aside class="access-note"><strong>Solicitud electrónica</strong><p>Al continuar se generará una solicitud mediante servicio de billetera/pasarela. El pago solo se confirma cuando la API informa aprobación.</p></aside></div>
<div class="payment-method-panel" data-method-panel="TRANSFERENCIA" hidden><aside class="access-note"><strong>QR / transferencia</strong><p>Al continuar se generará una solicitud/QR y luego deberá verificarse el estado en la pasarela antes de confirmar el pago.</p></aside></div>
</section>

<section class="form-section"><div class="form-section-heading"><span>03</span><div><h3>Ajuste directo en cobranza</h3><p>Aplicá un descuento o recargo comercial de último momento antes de confirmar el cobro.</p></div></div><div class="form-grid"><div class="field"><label>Tipo</label><select name="adjustmentType" id="adjustmentType"><option value="NINGUNO">Sin ajuste</option><option value="DESCUENTO">Descuento</option><option value="RECARGO">Recargo</option></select></div><div class="field"><label>Porcentaje %</label><input type="number" min="0" max="100" step="0.01" name="adjustmentPercent" id="adjustmentPercent" value="0"></div><div class="field"><label>Importe final estimado</label><input id="importeFinalEstimado" disabled></div></div></section>
<section class="form-section"><div class="form-section-heading"><span>04</span><div><h3>Confirmación</h3><p>Podés agregar una referencia interna antes de registrar la operación.</p></div></div><div class="field"><label>Observaciones</label><textarea name="observaciones" maxlength="255" rows="3"></textarea></div></section>
<div class="form-footer"><button class="button button-primary">Registrar / iniciar cobro</button></div>
</form>
<?php endif;?>

<section class="inventory-panel" style="margin-top:20px"><div class="panel-title-row"><div><h3>Historial de cobros</h3><p><?=count($payments)?> operaciones</p></div></div><div class="inventory-table-wrap"><table class="inventory-table"><thead><tr><th>Fecha</th><th>Operación</th><th>Importe</th><th>Ajuste</th><th>Estado</th><th>Referencia</th><th></th></tr></thead><tbody><?php if(!$payments):?><tr><td colspan="7">Todavía no hay cobros registrados.</td></tr><?php endif;?><?php foreach($payments as $p):?><tr><td><?=e((string)$p['fechaPago'])?></td><td><?=e(PaymentService::paymentLabel($p))?></td><td>$<?=number_format((float)$p['importe'],2,',','.')?></td><td><?=!empty($p['adjustmentType'])?e((string)$p['adjustmentType']).' '.number_format((float)$p['adjustmentPercent'],2,',','.').'%':'—'?></td><td><span class="status-pill"><?=e((string)$p['estadoPago'])?></span></td><td><?=e((string)($p['transaccionExternaID']??$p['checkNumber']??'—'))?></td><td><a class="table-link" href="payment.php?id=<?=(int)$p['idPago']?>">Ver</a></td></tr><?php endforeach;?></tbody></table></div></section>
<?php if($gateway->mode()==='mock'):?><aside class="access-note" style="margin-top:20px"><strong>Pasarela en modo simulación</strong><p>Tarjeta y transferencia generan solicitudes pendientes. Para validar el circuito local podés usar “Simular aprobación”. En producción configurá PAYMENT_GATEWAY_MODE=api y las credenciales del proveedor.</p></aside><?php endif;?>
<script>
(()=>{
 const form=document.getElementById('payment-form'); if(!form)return;
 const ops=[...form.querySelectorAll('input[name="tipoOperacion"]')], methodSection=form.querySelector('[data-payment-method-section]'), method=document.getElementById('medioPago');
 const panels=[...form.querySelectorAll('[data-method-panel]')], base=document.getElementById('importeCobro'), adjType=document.getElementById('adjustmentType'), adjPct=document.getElementById('adjustmentPercent'), final=document.getElementById('importeFinalEstimado'), received=document.getElementById('importeRecibido'), change=document.getElementById('vueltoEstimado');
 const money=n=>new Intl.NumberFormat('es-AR',{style:'currency',currency:'ARS'}).format(Number.isFinite(n)?n:0);
 const finalAmount=()=>{const b=parseFloat(base.value||'0'),p=parseFloat(adjPct.value||'0');return adjType.value==='DESCUENTO'?Math.max(0,b-b*p/100):adjType.value==='RECARGO'?b+b*p/100:b};
 function recalc(){const f=finalAmount();final.value=money(f);if(received)change.value=money(Math.max(0,parseFloat(received.value||'0')-f));}
 function setRequired(panel,on){panel.querySelectorAll('input,select,textarea').forEach(el=>{if(on && el.dataset.optional!=='1')el.setAttribute('required','required');else el.removeAttribute('required');});}
 function refresh(){const op=ops.find(x=>x.checked)?.value||'PAGO';methodSection.hidden=op!=='PAGO';method.required=op==='PAGO';panels.forEach(p=>{const active=op==='PAGO'&&p.dataset.methodPanel===method.value;p.hidden=!active;setRequired(p,active && p.dataset.methodPanel!=='TARJETA' && p.dataset.methodPanel!=='TRANSFERENCIA');});recalc();}
 ops.forEach(x=>x.addEventListener('change',refresh));method.addEventListener('change',refresh);[base,adjType,adjPct,received].forEach(x=>x&&x.addEventListener('input',recalc));adjType.addEventListener('change',recalc);refresh();
})();
</script>
<?php PaymentPage::end(); ?>
