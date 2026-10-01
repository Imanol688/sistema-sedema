<?php
declare(strict_types=1);
require dirname(__DIR__,2).'/src/bootstrap.php';
use Sedema\{Authorization,Csrf};
use Sedema\Suppliers\{SupplierContext,SupplierException,SupplierPage};
$context=SupplierContext::boot(); $user=$context['user']; $service=$context['service'];
$action=(string)($_POST['action']??'');
if ($_SERVER['REQUEST_METHOD']!=='POST' || !Csrf::validate($_POST['csrf_token']??null)) {
    SupplierPage::flash('error','La sesión del formulario venció.'); redirect('index.php');
}
$target='index.php';
try {
    switch ($action) {
        case 'supplier':
            Authorization::require($user,'suppliers.manage');
            $id=$service->saveSupplier($_POST); $target='supplier.php?id='.$id;
            SupplierPage::flash('success','Proveedor guardado.'); break;
        case 'order':
            Authorization::require($user,'suppliers.manage');
            $id=$service->createOrder($_POST); $target='detail.php?id='.$id;
            SupplierPage::flash('success','Remito registrado.'); break;
        case 'receive':
            Authorization::require($user,'suppliers.receive');
            $id=(int)($_POST['idRemitoProveedor']??0);
            $receipt=$service->receive($_POST,(int)$user['id']); $target='detail.php?id='.$id;
            SupplierPage::flash('success','Recepción #'.$receipt.' registrada y stock actualizado.'); break;
        case 'cancel':
            Authorization::require($user,'suppliers.manage');
            $id=(int)($_POST['idRemitoProveedor']??0); $service->cancel($id); $target='detail.php?id='.$id;
            SupplierPage::flash('success','Remito cancelado.'); break;
        default: throw new SupplierException('Acción inválida.');
    }
    Csrf::rotate();
} catch (SupplierException $e) {
    SupplierPage::flash('error',$e->getMessage());
    if ($action==='supplier') $target='supplier.php'.((int)($_POST['idProveedor']??0)>0?'?id='.(int)$_POST['idProveedor']:'');
    elseif ($action==='order') $target='order.php';
    elseif (in_array($action,['receive','cancel'],true)) $target='detail.php?id='.(int)($_POST['idRemitoProveedor']??0);
}
redirect($target);
