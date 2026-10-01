<?php
declare(strict_types=1);require dirname(__DIR__,2).'/src/bootstrap.php';
use Sedema\Authorization;use Sedema\Csrf;use Sedema\Payments\PaymentContext;use Sedema\Payments\PaymentException;use Sedema\Payments\PaymentPage;
$c=PaymentContext::boot();$user=$c['user'];$service=$c['service'];
if($_SERVER['REQUEST_METHOD']!=='POST'||!Csrf::validate($_POST['csrf_token']??null)){PaymentPage::flash('error','La sesión del formulario venció.');redirect('index.php');}
$action=(string)($_POST['action']??'');$target='index.php';
try{
    switch($action){
        case'register':Authorization::require($user,'payments.manage');$id=$service->register($_POST,(int)$user['id']);PaymentPage::flash('success','Operación registrada.');$target='payment.php?id='.$id;break;
        case'verify':Authorization::require($user,'payments.manage');$id=(int)($_POST['idPago']??0);$status=$service->verify($id,(int)$user['id']);PaymentPage::flash('success','Estado actualizado: '.$status.'.');$target='payment.php?id='.$id;break;
        case'mock-approve':Authorization::require($user,'payments.manage');$id=(int)($_POST['idPago']??0);$service->mockApprove($id,(int)$user['id']);PaymentPage::flash('success','Pago aprobado en simulación.');$target='payment.php?id='.$id;break;
        case'approve-check':Authorization::require($user,'payments.manage');$id=(int)($_POST['idPago']??0);$service->approveCheck($id,(int)$user['id']);PaymentPage::flash('success','Cheque aprobado.');$target='payment.php?id='.$id;break;
        case'reject-check':Authorization::require($user,'payments.manage');$id=(int)($_POST['idPago']??0);$service->rejectCheck($id,(int)$user['id']);PaymentPage::flash('success','Cheque rechazado.');$target='payment.php?id='.$id;break;
        default:throw new PaymentException('Operación inválida.');
    }
    Csrf::rotate();
}catch(PaymentException $e){
    PaymentPage::flash('error',$e->getMessage());$id=(int)($_POST['idPago']??0);$order=(int)($_POST['idPedido']??0);$target=$id>0?'payment.php?id='.$id:($order>0?'collect.php?id='.$order:'index.php');
}
redirect($target);
