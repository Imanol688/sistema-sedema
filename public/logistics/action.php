<?php
declare(strict_types=1);require dirname(__DIR__,2).'/src/bootstrap.php';
use Sedema\Authorization;use Sedema\Csrf;use Sedema\Logistics\LogisticsContext;use Sedema\Logistics\LogisticsException;use Sedema\Logistics\LogisticsPage;
$c=LogisticsContext::boot();$user=$c['user'];$service=$c['service'];$action=(string)($_POST['action']??'');
if($_SERVER['REQUEST_METHOD']!=='POST'||!Csrf::validate($_POST['csrf_token']??null)){LogisticsPage::flash('error','La sesión del formulario venció. Intentá nuevamente.');redirect('index.php');}
$target='index.php';
try{
    switch($action){
        case 'schedule':Authorization::require($user,'logistics.manage');$id=$service->schedule($_POST,is_array($_POST['quantity']??null)?$_POST['quantity']:[],(int)$user['id']);LogisticsPage::flash('success','Despacho #'.$id.' programado y cantidades reservadas.');$target='detail.php?id='.$id;break;
        case 'transition':Authorization::require($user,'logistics.dispatch');$id=(int)($_POST['idDespacho']??0);$service->transition($id,(string)($_POST['estado']??''),(string)($_POST['observaciones']??''),(int)$user['id']);LogisticsPage::flash('success','Estado actualizado correctamente.');$target='detail.php?id='.$id;break;
        case 'save-vehicle':Authorization::require($user,'logistics.manage');$id=$service->saveVehicle($_POST);LogisticsPage::flash('success','Vehículo #'.$id.' guardado.');$target='vehicles.php';break;
        case 'toggle-vehicle':Authorization::require($user,'logistics.manage');$service->setVehicleActive((int)($_POST['idVehiculo']??0),(string)($_POST['active']??'0')==='1');LogisticsPage::flash('success','Disponibilidad administrativa del vehículo actualizada.');$target='vehicles.php';break;
        default:throw new LogisticsException('La operación solicitada no es válida.');
    }Csrf::rotate();
}catch(LogisticsException $e){LogisticsPage::flash('error',$e->getMessage());if($action==='schedule')$target='dispatch.php?order='.(int)($_POST['idPedido']??0);elseif($action==='transition')$target='detail.php?id='.(int)($_POST['idDespacho']??0);elseif(str_contains($action,'vehicle'))$target='vehicles.php';}
redirect($target);
