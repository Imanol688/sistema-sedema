<?php
declare(strict_types=1);require dirname(__DIR__,2).'/src/bootstrap.php';
use Sedema\Sales\SalesRepository;use Sedema\Sales\SalesService;use Sedema\Sales\SalesException;use Sedema\Database;
use Sedema\Authorization;use Sedema\Csrf;use Sedema\Payments\PaymentContext;use Sedema\Payments\PaymentException;use Sedema\Payments\PaymentPage;
$c=PaymentContext::boot();$user=$c['user'];$service=$c['service'];
if($_SERVER['REQUEST_METHOD']!=='POST'||!Csrf::validate($_POST['csrf_token']??null)){PaymentPage::flash('error','La sesión del formulario venció.');redirect('index.php');}
$action=(string)($_POST['action']??'');$target='index.php';
try{
    switch($action){
        case'register':Authorization::require($user,'payments.manage');$id=$service->register($_POST,(int)$user['id']);PaymentPage::flash('success','Operación registrada.');$target='payment.php?id='.$id;break;
        case'verify':Authorization::require($user,'payments.manage');$id=(int)($_POST['idPago']??0);$status=$service->verify($id,(int)$user['id']);PaymentPage::flash('success','Estado actualizado: '.$status.'.');$target='payment.php?id='.$id;break;
        case'mp-cancel':Authorization::require($user,'payments.manage');$id=(int)($_POST['idPago']??0);$status=(new \Sedema\Payments\MercadoPagoQrService($c['repository']))->cancel($id,(int)$user['id']);PaymentPage::flash('success','Estado del QR: '.$status.'.');$target='payment.php?id='.$id;break;
        case'mock-approve':Authorization::require($user,'payments.manage');$id=(int)($_POST['idPago']??0);$service->mockApprove($id,(int)$user['id']);PaymentPage::flash('success','Pago aprobado en simulación.');$target='payment.php?id='.$id;break;
        case'approve-check':Authorization::require($user,'payments.manage');$id=(int)($_POST['idPago']??0);$service->approveCheck($id,(int)$user['id']);PaymentPage::flash('success','Cheque aprobado.');$target='payment.php?id='.$id;break;
        case'reject-check':Authorization::require($user,'payments.manage');$id=(int)($_POST['idPago']??0);$service->rejectCheck($id,(int)$user['id']);PaymentPage::flash('success','Cheque rechazado.');$target='payment.php?id='.$id;break;
        case 'update-order':
            Authorization::require($user, 'payments.manage');
            $orderId = max(0, (int) ($_POST['idPedido'] ?? 0));
            if ($orderId <= 0) throw new PaymentException('Pedido inválido.');
            $orders = new SalesService(new SalesRepository(Database::connection()));
            $orders->createOrder($_POST, (int) $user['id'], $orderId);
            PaymentPage::flash('success', 'Pedido corregido correctamente.');
            $target = 'collect.php?id=' . $orderId;
            break;
        case 'delete-order':
            Authorization::require($user, 'payments.manage');
            $orderId = max(0, (int) ($_POST['idPedido'] ?? 0));
            $orders = new SalesService(new SalesRepository(Database::connection()));
            $orders->deleteOrder($orderId, (string) ($_POST['orderVersion'] ?? ''), (int) $user['id']);
            PaymentPage::flash('success', 'Pedido eliminado correctamente.');
            $target = 'index.php';
            break;
        case 'issue-remit':
        case 'issue-invoice':
            Authorization::require($user, 'payments.manage');
            $orderId = max(0, (int) ($_POST['idPedido'] ?? 0));
            $documents = new SalesService(new SalesRepository(Database::connection()));
            if ($action === 'issue-remit') {
                $documents->issueRemittance($orderId, $_POST, (int) $user['id']);
                PaymentPage::flash('success', 'Remito emitido correctamente.');
            } else {
                $documents->issueInvoice($orderId, $_POST, (int) $user['id']);
                PaymentPage::flash('success', 'Factura emitida correctamente.');
            }
            $target = 'documents.php?id=' . $orderId;
            break;
        default:throw new PaymentException('Operación inválida.');
    }
    // On a fully approved payment, continue with the documents in this module.
    if (in_array($action, ['register', 'verify', 'mock-approve', 'approve-check'], true) && isset($id)) {
        $payment = $c['repository']->payment($id);
        if ($payment && (string) $payment['estadoPago'] === 'APROBADO' && (string) ($payment['tipoOperacion'] ?? 'PAGO') === 'PAGO') {
            $salesRepository = new SalesRepository(Database::connection());
            $orderId = (int) $payment['idPedido'];
            $order = $salesRepository->order($orderId);
            $paid = $salesRepository->settledPayments($orderId);
            if ($order && (string) $order['estado'] !== 'CANCELADO' && $paid > 0 && $paid + 0.005 >= (float) $order['total']) {
                $target = 'documents.php?id=' . $orderId;
            }
        }
    }
    Csrf::rotate();
}catch(PaymentException|SalesException $e){
    PaymentPage::flash('error',$e->getMessage());$id=(int)($_POST['idPago']??0);$order=(int)($_POST['idPedido']??0);$target=$action==='update-order'?'edit-order.php?id='.$order:(in_array($action,['issue-remit','issue-invoice'],true)?'documents.php?id='.$order:($id>0?'payment.php?id='.$id:($order>0?'collect.php?id='.$order:'index.php')));
}
redirect($target);
