<?php
// Included from detail.php, after authentication and permission checks.
if (!isset($d,$user,$repo,$id)) { http_response_code(404); return; }
if (!\Sedema\Authorization::can($user,'logistics.manage') || !in_array($d['estado'],['EN_ESPERA','EN_PREPARACION'],true)) return;
$availableVehicles=$repo->vehicles();
?>
<section class="inventory-panel">
    <div class="panel-title-row"><div><h3>Reprogramar despacho</h3><p>Podés cambiar la fecha o el vehículo antes de la salida. Se conservan los productos y cantidades asignados.</p></div></div>
    <form class="inventory-form" method="post" action="action.php">
        <input type="hidden" name="csrf_token" value="<?=e(\Sedema\Csrf::token())?>">
        <input type="hidden" name="operation_key" value="<?=\Sedema\Inventory\OperationGuard::newKey()?>">
        <input type="hidden" name="operationVersion" value="<?=(int)$d['operationVersion']?>">
        <input type="hidden" name="action" value="reschedule">
        <input type="hidden" name="idDespacho" value="<?=$id?>">
        <div class="form-grid">
            <div class="field"><label for="reschedule-date">Nueva fecha y hora *</label><input id="reschedule-date" name="fechaHoraProgramada" type="datetime-local" value="<?=e(date('Y-m-d\TH:i',strtotime((string)$d['fechaHoraProgramada'])))?>" required></div>
            <?php if ($d['modalidad']==='ENTREGA_DOMICILIO'): ?>
            <div class="field"><label for="reschedule-vehicle">Vehículo *</label><select id="reschedule-vehicle" name="idVehiculo" required><option value="">Seleccionar</option><?php foreach($availableVehicles as $v): ?><option value="<?=(int)$v['idVehiculo']?>" <?=(int)$v['idVehiculo']===(int)$d['idVehiculo']?'selected':''?> <?=$v['estado']!=='DISPONIBLE'?'disabled':''?>><?=e((string)$v['matricula'].' · '.$v['modelo'].' · '.$v['estado'])?></option><?php endforeach; ?></select></div>
            <?php endif; ?>
            <div class="field field-span"><label for="reschedule-reason">Motivo *</label><textarea id="reschedule-reason" name="motivo" maxlength="250" required rows="2"></textarea></div>
        </div>
        <button class="button button-primary" type="submit">Confirmar reprogramación</button>
    </form>
</section>
<section class="inventory-panel">
    <div class="panel-title-row"><div><h3>Cancelar despacho</h3><p>Libera las cantidades reservadas para otra entrega. No descuenta ni repone existencias.</p></div></div>
    <form class="inventory-form" method="post" action="action.php">
        <input type="hidden" name="csrf_token" value="<?=e(\Sedema\Csrf::token())?>">
        <input type="hidden" name="operation_key" value="<?=\Sedema\Inventory\OperationGuard::newKey()?>">
        <input type="hidden" name="operationVersion" value="<?=(int)$d['operationVersion']?>">
        <input type="hidden" name="action" value="cancel-dispatch">
        <input type="hidden" name="idDespacho" value="<?=$id?>">
        <div class="field"><label for="cancel-reason">Motivo de cancelación *</label><textarea id="cancel-reason" name="motivo" maxlength="250" required rows="2"></textarea></div>
        <button class="button button-danger" type="submit" data-confirm-state="CANCELADO">Cancelar despacho</button>
    </form>
</section>
